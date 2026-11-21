package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import com.nativephp.plugins.native_ui.NativeUITheme

sealed interface ReorderListRendererEvent {
    data class Press(val callbackId: Int, val nodeId: Int) : ReorderListRendererEvent
}

class ReorderListRendererEvents(private val send: (ReorderListRendererEvent) -> Unit) {
    fun dispatch(event: ReorderListRendererEvent) = send(event)

    companion object {
        val native = ReorderListRendererEvents { event ->
            when (event) {
                is ReorderListRendererEvent.Press -> NativeUIBridge.sendPressEvent(event.callbackId, event.nodeId)
            }
        }
    }
}

/**
 * The published contract. Rows are indexed in published order; [moveCallbacks] holds
 * [slotCount] callback ids per row, one per destination slot, sections in order. In each
 * section a row has one slot per position it can take there.
 */
data class ReorderListRendererConfiguration(
    val nodeId: Int,
    val sectionKeys: List<String>,
    val sectionLabels: List<String>,
    val sectionLengths: List<Int>,
    val rowValues: List<String>,
    val rowLabels: List<String>,
    val rowSupporting: List<String>,
    val rowIcons: List<String>,
    val rowLocked: List<Boolean>,
    val slotCount: Int,
    val moveCallbacks: List<Int>,
    val label: String,
    val helper: String,
    val error: String,
    val disabled: Boolean,
    val accessibilityLabel: String,
    val accessibilityHint: String,
    val moveUpLabel: String,
    val moveDownLabel: String,
    val moveToLabels: List<String>,
    val lockedLabel: String,
    val positionLabel: String,
) {
    constructor(node: NativeUINode) : this(
        nodeId = node.id,
        sectionKeys = node.props.getStringList("section_keys"),
        sectionLabels = node.props.getStringList("section_labels"),
        sectionLengths = node.props.getStringList("section_lengths").map { it.toIntOrNull() ?: 0 },
        rowValues = node.props.getStringList("row_values"),
        rowLabels = node.props.getStringList("row_labels"),
        rowSupporting = node.props.getStringList("row_supporting"),
        rowIcons = node.props.getStringList("row_icons"),
        rowLocked = node.props.getStringList("row_locked").map { it == "1" },
        slotCount = node.props.getInt("move_slot_count"),
        moveCallbacks = node.props.getStringList("move_callbacks").map { it.toIntOrNull() ?: 0 },
        label = node.props.getString("label"),
        helper = node.props.getString("helper"),
        error = node.props.getString("error"),
        disabled = node.props.getBool("disabled") || node.props.getStringList("row_values").isEmpty(),
        accessibilityLabel = node.props.getString("a11y_label").ifEmpty { node.props.getString("label") },
        accessibilityHint = node.props.getString("a11y_hint"),
        moveUpLabel = node.props.getString("move_up_label", "Move up"),
        moveDownLabel = node.props.getString("move_down_label", "Move down"),
        moveToLabels = node.props.getStringList("move_to_labels"),
        lockedLabel = node.props.getString("locked_label", "Locked"),
        positionLabel = node.props.getString("position_label", ":position of :count"),
    )

    val showsHeaders: Boolean get() = sectionKeys.isNotEmpty()

    /** Row indices per section, in published order. */
    val publishedSections: List<List<Int>>
        get() {
            var start = 0
            return sectionLengths.map { length ->
                val end = minOf(start + length, rowValues.size)
                (start until end).toList().also { start += length }
            }
        }

    /** The callback for moving [row] to [position] among [section]'s other rows; 0 emits nothing. */
    fun callback(row: Int, section: Int, position: Int): Int {
        val sections = publishedSections
        if (section !in sections.indices || row !in rowValues.indices) return 0
        var slot = 0
        for (index in 0 until section) slot += sections[index].count { it != row } + 1
        val length = sections[section].count { it != row }
        if (position < 0 || position > length) return 0
        return moveCallbacks.getOrElse(row * slotCount + slot + position) { 0 }
    }

    fun spokenPosition(position: Int, count: Int): String =
        positionLabel.replace(":position", position.toString()).replace(":count", count.toString())
}

/** A drawn entry: section headers are entries too, so a drag can cross them. */
sealed interface ReorderListEntry {
    val key: String

    data class Header(val section: Int) : ReorderListEntry {
        override val key = "h$section"
    }

    data class Row(val row: Int) : ReorderListEntry {
        override val key = "r$row"
    }

    companion object {
        fun entries(sections: List<List<Int>>, showsHeaders: Boolean): List<ReorderListEntry> =
            sections.flatMapIndexed { index, rows ->
                (if (showsHeaders) listOf<ReorderListEntry>(Header(index)) else emptyList()) + rows.map(::Row)
            }

        /** [entries] with the entry at [from] moved to final index [to]. */
        fun moved(entries: List<ReorderListEntry>, from: Int, to: Int): List<ReorderListEntry> =
            entries.toMutableList().apply { add(to.coerceIn(0, size - 1), removeAt(from)) }

        /** The section and position, among that section's other rows, the entry at [to] lands in. */
        fun destination(entries: List<ReorderListEntry>, from: Int, to: Int): Pair<Int, Int> {
            val moved = moved(entries, from, to)
            val landed = moved.indexOf(entries[from])
            var section = 0
            var position = 0
            moved.take(landed).forEach { entry ->
                when (entry) {
                    is Header -> { section = entry.section; position = 0 }
                    is Row -> position += 1
                }
            }
            return section to position
        }
    }
}

class ReorderListRendererState(node: NativeUINode) {
    var configuration by mutableStateOf(ReorderListRendererConfiguration(node))
        private set

    /** Row indices per section as drawn: published, or the dropped draft awaiting PHP. */
    var sections by mutableStateOf(configuration.publishedSections)
        private set

    var isAwaitingPublication by mutableStateOf(false)
        private set

    fun userMoved(row: Int, section: Int, position: Int): ReorderListRendererEvent? {
        if (isAwaitingPublication || configuration.disabled) return null
        val callback = configuration.callback(row, section, position).takeIf { it != 0 } ?: return null

        val draft = sections.map { rows -> rows.filter { it != row }.toMutableList() }
        draft[section].add(position.coerceAtMost(draft[section].size), row)
        sections = draft
        isAwaitingPublication = true
        return ReorderListRendererEvent.Press(callback, configuration.nodeId)
    }

    fun serverPublished(tree: NativeUITree): Boolean {
        val node = tree.root.findReorderListNode(configuration.nodeId) ?: return false
        val previous = sections
        configuration = ReorderListRendererConfiguration(node)
        sections = configuration.publishedSections
        isAwaitingPublication = false
        return previous != sections
    }
}

object ReorderListRenderer {
    @Composable
    fun Render(
        node: NativeUINode,
        modifier: Modifier,
        events: ReorderListRendererEvents = ReorderListRendererEvents.native,
    ) {
        firstlightValidationAlert(node.id)
        val state = remember(node.id) { ReorderListRendererState(node) }
        val publishedTree = NativeUIBridge.currentTree.value

        LaunchedEffect(publishedTree) {
            publishedTree?.let(state::serverPublished)
        }

        val tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light

        FirstlightReorderListControl(
            configuration = state.configuration,
            sections = state.sections,
            onMove = { row, section, position ->
                state.userMoved(row, section, position)?.let { events.dispatch(it); true } ?: false
            },
            modifier = modifier,
            labelColor = tokens.onSurface,
            secondaryColor = tokens.onSurfaceVariant,
            errorColor = tokens.destructive,
        )
    }
}

private fun NativeUINode.findReorderListNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findReorderListNode(id)?.let { return it } }
    return null
}
