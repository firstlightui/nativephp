package dev.firstlightui.plugins.firstlight_ui.ui

internal enum class CodeEditorLanguage(val wireName: String) {
    Plain("plain"),
    Json("json"),
    Yaml("yaml"),
    Env("env");

    val hasLineComments: Boolean get() = this == Yaml || this == Env

    companion object {
        fun from(raw: String?): CodeEditorLanguage = entries.firstOrNull { it.wireName == raw } ?: Plain
    }
}

/** UTF-16 selection; [start] is never greater than [end]. */
internal data class CodeEditorSelection(val start: Int, val end: Int) {
    val collapsed: Boolean get() = start == end
}

internal data class CodeEditorEdit(val text: String, val selection: CodeEditorSelection)

internal sealed interface CodeEditorOperation {
    data object Return : CodeEditorOperation
    data object Indent : CodeEditorOperation
    data object Outdent : CodeEditorOperation
    data object ToggleComment : CodeEditorOperation
    data class Insert(val symbol: String) : CodeEditorOperation
}

/**
 * Pure editing rules shared with iOS through tests/fixtures/code-editor/editing.json.
 * Do not add behaviour here that the fixtures do not cover.
 */
internal object CodeEditorEditing {
    fun apply(
        text: String,
        selection: CodeEditorSelection,
        operation: CodeEditorOperation,
        language: CodeEditorLanguage,
        indent: Int,
    ): CodeEditorEdit {
        val unit = " ".repeat(indent)
        return when (operation) {
            CodeEditorOperation.Return -> newline(text, selection, language, unit)
            CodeEditorOperation.Indent -> applyEdits(
                text,
                selection,
                touchedLineStarts(text, selection)
                    .filter { lineEnd(text, it) > it }
                    .map { Splice(it, 0, unit) },
            )
            CodeEditorOperation.Outdent -> applyEdits(
                text,
                selection,
                touchedLineStarts(text, selection).mapNotNull { start ->
                    val removable = removableIndent(text, start, indent)
                    if (removable == 0) null else Splice(start, removable, "")
                },
            )
            CodeEditorOperation.ToggleComment -> toggleComment(text, selection, language)
            is CodeEditorOperation.Insert -> insert(text, selection, operation.symbol, language, indent)
        }
    }

    private data class Splice(val position: Int, val deleteLength: Int, val insertion: String)

    private fun newline(
        text: String,
        selection: CodeEditorSelection,
        language: CodeEditorLanguage,
        unit: String,
    ): CodeEditorEdit {
        val prefix = text.substring(lineStart(text, selection.start), selection.start)
        val carried = prefix.takeWhile { it == ' ' || it == '\t' }
        val opener = when (language) {
            CodeEditorLanguage.Json -> jsonOpenerAtEnd(prefix)
            else -> null
        }
        val increases = opener != null ||
            (language == CodeEditorLanguage.Yaml && yamlWithoutComment(prefix).trimEnd().endsWith(':'))
        val inner = "\n" + carried + (if (increases) unit else "")
        val closer = when (opener) {
            '{' -> '}'
            '[' -> ']'
            else -> null
        }
        val splits = closer != null && selection.end < text.length && text[selection.end] == closer
        val insertion = if (splits) inner + "\n" + carried else inner
        val cursor = selection.start + inner.length
        return CodeEditorEdit(
            text.substring(0, selection.start) + insertion + text.substring(selection.end),
            CodeEditorSelection(cursor, cursor),
        )
    }

    private fun insert(
        text: String,
        selection: CodeEditorSelection,
        symbol: String,
        language: CodeEditorLanguage,
        indent: Int,
    ): CodeEditorEdit {
        val start = lineStart(text, selection.start)
        val prefix = text.substring(start, selection.start)
        val removed = if (
            language == CodeEditorLanguage.Json && (symbol == "}" || symbol == "]") &&
            prefix.isNotEmpty() && prefix.all { it == ' ' || it == '\t' }
        ) {
            removableIndent(text, start, indent)
        } else {
            0
        }
        val result = text.substring(0, start) + prefix.substring(removed) + symbol + text.substring(selection.end)
        val cursor = selection.start - removed + symbol.length
        return CodeEditorEdit(result, CodeEditorSelection(cursor, cursor))
    }

    private fun toggleComment(
        text: String,
        selection: CodeEditorSelection,
        language: CodeEditorLanguage,
    ): CodeEditorEdit {
        if (!language.hasLineComments) return CodeEditorEdit(text, selection)
        val lines = touchedLineStarts(text, selection).filter { start ->
            text.substring(start, lineEnd(text, start)).isNotBlank()
        }
        if (lines.isEmpty()) return CodeEditorEdit(text, selection)
        val leading = lines.map { start -> leadingWhitespaceLength(text, start) }
        val allCommented = lines.indices.all { text[lines[it] + leading[it]] == '#' }
        val splices = if (allCommented) {
            lines.indices.map { index ->
                val hash = lines[index] + leading[index]
                val space = if (hash + 1 < text.length && text[hash + 1] == ' ') 1 else 0
                Splice(hash, 1 + space, "")
            }
        } else {
            val column = leading.min()
            lines.map { Splice(it + column, 0, "# ") }
        }
        return applyEdits(text, selection, splices)
    }

    /** Applies ascending, non-overlapping splices and maps the selection through them. */
    private fun applyEdits(text: String, selection: CodeEditorSelection, splices: List<Splice>): CodeEditorEdit {
        if (splices.isEmpty()) return CodeEditorEdit(text, selection)
        val builder = StringBuilder()
        var cursor = 0
        for (splice in splices) {
            builder.append(text, cursor, splice.position).append(splice.insertion)
            cursor = splice.position + splice.deleteLength
        }
        builder.append(text, cursor, text.length)
        val collapsed = selection.collapsed
        val start = mapOffset(selection.start, splices, movesWithInsertionAtOffset = collapsed)
        val end = if (collapsed) start else mapOffset(selection.end, splices, movesWithInsertionAtOffset = true)
        return CodeEditorEdit(builder.toString(), CodeEditorSelection(start, end))
    }

    private fun mapOffset(offset: Int, splices: List<Splice>, movesWithInsertionAtOffset: Boolean): Int {
        var shift = 0
        for (splice in splices) {
            val removalEnd = splice.position + splice.deleteLength
            when {
                splice.deleteLength > 0 && offset >= removalEnd -> shift -= splice.deleteLength
                splice.deleteLength > 0 && offset > splice.position -> shift -= offset - splice.position
                splice.insertion.isNotEmpty() &&
                    (splice.position < offset || (splice.position == offset && movesWithInsertionAtOffset)) ->
                    shift += splice.insertion.length
            }
        }
        return offset + shift
    }

    private fun touchedLineStarts(text: String, selection: CodeEditorSelection): List<Int> {
        val last = if (!selection.collapsed && selection.end == lineStart(text, selection.end)) {
            selection.end - 1
        } else {
            selection.end
        }
        val starts = mutableListOf(lineStart(text, selection.start))
        while (true) {
            val next = text.indexOf('\n', starts.last())
            if (next < 0 || next >= last) break
            starts += next + 1
        }
        return starts
    }

    private fun removableIndent(text: String, lineStart: Int, indent: Int): Int {
        if (lineStart < text.length && text[lineStart] == '\t') return 1
        var count = 0
        while (count < indent && lineStart + count < text.length && text[lineStart + count] == ' ') count++
        return count
    }

    private fun leadingWhitespaceLength(text: String, lineStart: Int): Int {
        var index = lineStart
        while (index < text.length && (text[index] == ' ' || text[index] == '\t')) index++
        return index - lineStart
    }

    private fun lineStart(text: String, offset: Int): Int =
        if (offset == 0) 0 else text.lastIndexOf('\n', offset - 1) + 1

    private fun lineEnd(text: String, offset: Int): Int =
        text.indexOf('\n', offset).let { if (it < 0) text.length else it }

    /** Returns `{` or `[` when it is the last significant character outside a string. */
    private fun jsonOpenerAtEnd(prefix: String): Char? {
        var inString = false
        var escaped = false
        var last: Char? = null
        for (character in prefix) {
            when {
                inString && escaped -> escaped = false
                inString && character == '\\' -> escaped = true
                inString && character == '"' -> { inString = false; last = '"' }
                inString -> Unit
                character == '"' -> inString = true
                !character.isWhitespace() -> last = character
            }
        }
        return if (!inString && (last == '{' || last == '[')) last else null
    }

    private fun yamlWithoutComment(prefix: String): String {
        var quote: Char? = null
        var escaped = false
        for ((index, character) in prefix.withIndex()) {
            when {
                quote == '"' && escaped -> escaped = false
                quote == '"' && character == '\\' -> escaped = true
                quote != null && character == quote -> quote = null
                quote != null -> Unit
                character == '"' || character == '\'' -> quote = character
                character == '#' && (index == 0 || prefix[index - 1] == ' ' || prefix[index - 1] == '\t') ->
                    return prefix.substring(0, index)
            }
        }
        return prefix
    }
}

/**
 * Maps the first visible line across a programmatic replacement, shared with iOS through
 * tests/fixtures/code-editor/scroll-anchor.json. Null means keep the previous scroll offset, clamped.
 */
internal object CodeEditorScrollAnchor {
    private const val CONTEXT_LINES = 2

    fun anchorLine(before: String, after: String, anchorLine: Int): Int? {
        val oldLines = before.split('\n')
        if (anchorLine !in oldLines.indices) return null

        val anchorEnd = oldLines.take(anchorLine + 1).sumOf { it.length } + anchorLine
        if (after.startsWith(before.substring(0, anchorEnd))) return anchorLine

        val newLines = after.split('\n')
        val contextEnd = anchorLine + minOf(CONTEXT_LINES, maxOf(0, oldLines.lastIndex - 1 - anchorLine))
        for (trimmed in 1..anchorLine) {
            val length = contextEnd - trimmed + 1
            if (length > newLines.size) continue
            if ((0 until length).all { oldLines[trimmed + it] == newLines[it] }) return anchorLine - trimmed
        }
        return null
    }
}
