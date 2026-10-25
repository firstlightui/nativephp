package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.text.input.OffsetMapping
import androidx.compose.ui.text.input.TransformedText
import androidx.compose.ui.text.input.VisualTransformation

/**
 * Formats one line of text against a Text Field `mask` pattern.
 *
 * `#` accepts a digit, `A` a letter and `*` either; every other pattern character is a literal.
 * The raw value holds only slot characters. Literals are displayed once the slot after them is
 * filled, and trailing literals once every slot is filled, so an empty field shows its placeholder.
 */
class FirstlightTextMask private constructor(private val pattern: String) {
    /** Pattern index of every slot, in order. */
    private val slotPositions: List<Int> = pattern.indices.filter { pattern[it].isSlot() }

    val slotCount: Int get() = slotPositions.size

    /** The displayed text for an already conformed raw value. */
    fun format(raw: String): String = buildString {
        var slot = 0
        for (token in pattern) {
            if (token.isSlot()) {
                if (slot >= raw.length) return@buildString
                append(raw[slot++])
            } else {
                if (slot >= raw.length && raw.length != slotCount) return@buildString
                append(token)
            }
        }
    }

    /**
     * Normalises an authored, server or pasted value to raw slot characters, dropping characters
     * that fit no slot and anything beyond the last slot.
     */
    fun conform(value: String): String = fill(value, fromSlot = 0, capacity = slotCount)

    /** Display offset for a caret placed after [rawOffset] raw characters. */
    fun displayOffset(rawOffset: Int, rawCount: Int): Int {
        if (rawOffset >= rawCount && rawCount >= slotCount) return pattern.length
        val offset = minOf(rawOffset, rawCount)
        return if (offset <= 0) 0 else slotPositions[offset - 1] + 1
    }

    /** Number of raw characters displayed before [displayOffset]. */
    fun rawOffset(displayOffset: Int, rawCount: Int): Int =
        minOf(rawCount, slotPositions.count { it < displayOffset })

    /**
     * Replaces a raw range with typed or pasted text. Characters that fit no slot are dropped and
     * insertion stops when every slot is full. Returns the new raw value and raw caret.
     */
    fun replace(raw: String, start: Int, end: Int, inserted: String): Pair<String, Int> {
        val lower = start.coerceIn(0, raw.length)
        val upper = end.coerceIn(lower, raw.length)
        val prefix = raw.substring(0, lower)
        val suffix = raw.substring(upper)
        val accepted = fill(inserted, fromSlot = lower, capacity = slotCount - prefix.length - suffix.length)
        val result = StringBuilder(prefix).append(accepted)
        for (character in suffix) {
            if (result.length >= slotCount) break
            accept(character, result.length)?.let(result::append)
        }
        return result.toString() to lower + accepted.length
    }

    /**
     * Applies a native edit of the raw buffer, such as Compose reports through a visual
     * transformation. [caretAfterEdit] resolves edits inside runs of repeated characters.
     */
    fun edit(raw: String, newRaw: String, caretAfterEdit: Int?): Pair<String, Int> {
        val change = TextEditDifference(raw, newRaw, caretAfterEdit)
        return replace(raw, change.start, change.start + change.removed, change.inserted)
    }

    /** Displays the raw buffer formatted while Compose edits and reports raw text and offsets. */
    val visualTransformation = VisualTransformation { text ->
        val raw = text.text
        TransformedText(
            AnnotatedString(format(raw)),
            object : OffsetMapping {
                override fun originalToTransformed(offset: Int) = displayOffset(offset, raw.length)
                override fun transformedToOriginal(offset: Int) = rawOffset(offset, raw.length)
            },
        )
    }

    private fun fill(text: String, fromSlot: Int, capacity: Int): String {
        if (capacity <= 0 || fromSlot >= slotCount) return ""
        // Text that already fits the slots is taken verbatim, so raw values and typed characters
        // that equal a literal still fill slots.
        val verbatim = StringBuilder()
        for (character in text.take(capacity)) {
            val normalized = accept(character, fromSlot + verbatim.length) ?: break
            verbatim.append(normalized)
        }
        if (verbatim.length == minOf(text.length, capacity)) return verbatim.toString()

        // Otherwise treat the text as formatted: matching literals are consumed, then each
        // remaining character fills the next slot that accepts it.
        val output = StringBuilder()
        var position = if (fromSlot == 0) 0 else slotPositions[fromSlot - 1] + 1
        for (character in text) {
            if (output.length >= capacity || position >= pattern.length) break
            var next = position
            var consumedLiteral = false
            while (next < pattern.length && !pattern[next].isSlot()) {
                if (pattern[next] == character) {
                    position = next + 1
                    consumedLiteral = true
                    break
                }
                next++
            }
            if (consumedLiteral || next >= pattern.length) continue
            accept(character, fromSlot + output.length)?.let {
                output.append(it)
                position = next + 1
            }
        }
        return output.toString()
    }

    private fun accept(character: Char, slot: Int): Char? {
        if (slot >= slotCount) return null
        val digit = if (Character.isDigit(character)) Character.forDigit(Character.digit(character, 10), 10) else null
        val letter = character.takeIf { it in 'a'..'z' || it in 'A'..'Z' }
        return when (pattern[slotPositions[slot]]) {
            '#' -> digit
            'A' -> letter
            else -> digit ?: letter
        }
    }

    override fun equals(other: Any?) = other is FirstlightTextMask && other.pattern == pattern
    override fun hashCode() = pattern.hashCode()

    companion object {
        /** Returns null for a pattern without slots, which means the field is not masked. */
        fun of(pattern: String): FirstlightTextMask? =
            pattern.takeIf { value -> value.any { it.isSlot() } }?.let(::FirstlightTextMask)

        private fun Char.isSlot() = this == '#' || this == 'A' || this == '*'
    }
}

/** The single contiguous replacement that turns [old] into [new]. */
internal class TextEditDifference(old: String, new: String, caretAfterEdit: Int?) {
    val start: Int
    val removed: Int
    val inserted: String

    init {
        var prefix = 0
        while (prefix < old.length && prefix < new.length && old[prefix] == new[prefix]) prefix++
        var suffix = 0
        while (suffix < old.length - prefix && suffix < new.length - prefix &&
            old[old.length - 1 - suffix] == new[new.length - 1 - suffix]
        ) suffix++
        var first = prefix
        removed = old.length - prefix - suffix
        val insertedCount = new.length - prefix - suffix
        // Within a run of repeated characters the shortest difference is ambiguous; prefer the
        // alignment that ends at the platform caret.
        if (caretAfterEdit != null) {
            val candidate = caretAfterEdit - insertedCount
            if (candidate in 0 until first && candidate + removed <= old.length && caretAfterEdit <= new.length &&
                old.substring(0, candidate) + new.substring(candidate, caretAfterEdit) + old.substring(candidate + removed) == new
            ) {
                first = candidate
            }
        }
        start = first
        inserted = new.substring(start, start + insertedCount)
    }
}
