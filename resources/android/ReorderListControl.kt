package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.background
import androidx.compose.foundation.gestures.detectDragGestures
import androidx.compose.foundation.gestures.detectDragGesturesAfterLongPress
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.key
import androidx.compose.runtime.mutableFloatStateOf
import androidx.compose.runtime.mutableStateMapOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberUpdatedState
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.layout.onSizeChanged
import androidx.compose.ui.platform.testTag
import androidx.compose.ui.platform.LocalView
import androidx.compose.ui.semantics.CustomAccessibilityAction
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.customActions
import androidx.compose.ui.semantics.heading
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.semantics.stateDescription
import androidx.compose.ui.unit.dp
import androidx.compose.ui.zIndex
import com.nativephp.mobile.ui.MaterialIcon

/**
 * A Material 3 list whose rows drag by their handle, or by a long press anywhere on the row.
 * Section headers are entries in the same column, so one drag carries a row between sections.
 * Every move is also a custom accessibility action, so dragging is never the only way.
 *
 * [onMove] takes the row, destination section and position among that section's other rows,
 * and returns whether the move was proposed.
 */
internal const val REORDER_LIST_HANDLE_TAG = "firstlight-reorder-list-handle-"

@Composable
fun FirstlightReorderListControl(
    configuration: ReorderListRendererConfiguration,
    sections: List<List<Int>>,
    onMove: (Int, Int, Int) -> Boolean,
    modifier: Modifier = Modifier,
    labelColor: Color = MaterialTheme.colorScheme.onSurface,
    secondaryColor: Color = MaterialTheme.colorScheme.onSurfaceVariant,
    errorColor: Color = MaterialTheme.colorScheme.error,
) {
    val view = LocalView.current
    val showsHeaders = configuration.showsHeaders
    val entries = ReorderListEntry.entries(sections, showsHeaders)
    // A row never lands above the first header.
    val firstRowIndex = if (showsHeaders) 1 else 0

    // While dragging, entries are drawn in their working order; the drop proposes one move.
    var working by remember { mutableStateOf<List<ReorderListEntry>?>(null) }
    var dragStart by remember { mutableStateOf(-1) }
    var dragOffset by remember { mutableFloatStateOf(0f) }
    val heights = remember { mutableStateMapOf<String, Int>() }
    val drawn = working ?: entries
    val currentEntries by rememberUpdatedState(entries)

    fun location(row: Int): Pair<Int, Int>? {
        sections.forEachIndexed { section, rows ->
            val position = rows.indexOf(row)
            if (position >= 0) return section to position
        }
        return null
    }

    fun move(row: Int, section: Int, position: Int): Boolean {
        if (!onMove(row, section, position)) return false
        val count = sections[section].size + if (row in sections[section]) 0 else 1
        val parts = mutableListOf(configuration.rowLabels.getOrElse(row) { "" })
        if (showsHeaders) configuration.sectionLabels.getOrNull(section)?.let(parts::add)
        parts += configuration.spokenPosition(minOf(position, count - 1) + 1, count)
        @Suppress("DEPRECATION")
        view.announceForAccessibility(parts.joinToString(", "))
        return true
    }

    fun startDrag(entry: ReorderListEntry) {
        dragStart = currentEntries.indexOf(entry)
        working = currentEntries
        dragOffset = 0f
        FirstlightHaptics.perform(view, FirstlightHaptic.DragStart)
    }

    fun drag(entry: ReorderListEntry, delta: Float) {
        val list = working ?: return
        dragOffset += delta
        var index = list.indexOf(entry)
        var next = list
        while (true) {
            val below = next.getOrNull(index + 1)
            val above = next.getOrNull(index - 1)
            if (below != null && dragOffset > (heights[below.key] ?: 0) / 2f) {
                dragOffset -= heights[below.key] ?: 0
                next = ReorderListEntry.moved(next, index, index + 1)
                index += 1
            } else if (above != null && index - 1 >= firstRowIndex && dragOffset < -(heights[above.key] ?: 0) / 2f) {
                dragOffset += heights[above.key] ?: 0
                next = ReorderListEntry.moved(next, index, index - 1)
                index -= 1
            } else {
                break
            }
            FirstlightHaptics.perform(view, FirstlightHaptic.Selection)
        }
        working = next
    }

    fun endDrag(entry: ReorderListEntry) {
        val list = working
        val from = dragStart
        working = null
        dragStart = -1
        dragOffset = 0f
        if (list == null || entry !is ReorderListEntry.Row || from < 0) return
        val to = list.indexOf(entry)
        if (to == from) return
        val (section, position) = ReorderListEntry.destination(currentEntries, from, to)
        if (move(entry.row, section, position)) {
            FirstlightHaptics.perform(view, FirstlightHaptic.Alignment)
        }
    }

    // Gesture detectors outlive recompositions, so they call the latest handlers.
    val onStart by rememberUpdatedState(::startDrag)
    val onDrag by rememberUpdatedState(::drag)
    val onEnd by rememberUpdatedState(::endDrag)

    Column(
        modifier = modifier.fillMaxWidth(),
        verticalArrangement = Arrangement.spacedBy(6.dp),
    ) {
        if (configuration.label.isNotEmpty()) {
            Text(configuration.label, color = labelColor, style = MaterialTheme.typography.labelLarge)
        }

        Column(
            Modifier
                .fillMaxWidth()
                .alpha(if (configuration.disabled) 0.38f else 1f)
                .semantics {
                    if (configuration.accessibilityLabel.isNotEmpty()) {
                        contentDescription = configuration.accessibilityLabel
                    }
                },
        ) {
            drawn.forEachIndexed { index, entry ->
                key(entry.key) {
                    val dragging = working != null && entry == currentEntries.getOrNull(dragStart)
                    val measure = Modifier.onSizeChanged { heights[entry.key] = it.height }
                    when (entry) {
                        is ReorderListEntry.Header -> Text(
                            text = configuration.sectionLabels.getOrElse(entry.section) { "" },
                            color = secondaryColor,
                            style = MaterialTheme.typography.titleSmall,
                            modifier = measure
                                .padding(start = 16.dp, end = 16.dp, top = if (index == 0) 4.dp else 20.dp, bottom = 8.dp)
                                .semantics { heading() },
                        )
                        is ReorderListEntry.Row -> {
                            val row = entry.row
                            val first = drawn.getOrNull(index - 1) !is ReorderListEntry.Row
                            val last = drawn.getOrNull(index + 1) !is ReorderListEntry.Row
                            val locked = configuration.rowLocked.getOrElse(row) { false }
                            val movable = !locked && !configuration.disabled
                            val place = location(row)
                            ReorderListRow(
                                configuration = configuration,
                                row = row,
                                locked = locked,
                                first = first,
                                last = last,
                                dragging = dragging,
                                labelColor = labelColor,
                                secondaryColor = secondaryColor,
                                stateDescription = buildList {
                                    if (place != null) {
                                        if (showsHeaders) configuration.sectionLabels.getOrNull(place.first)?.let(::add)
                                        add(configuration.spokenPosition(place.second + 1, sections[place.first].size))
                                    }
                                    if (locked) add(configuration.lockedLabel)
                                }.joinToString(", "),
                                actions = if (!movable || place == null) emptyList() else buildList {
                                    val (section, position) = place
                                    if (position > 0) add(
                                        CustomAccessibilityAction(configuration.moveUpLabel) { move(row, section, position - 1) },
                                    )
                                    if (position < sections[section].size - 1) add(
                                        CustomAccessibilityAction(configuration.moveDownLabel) { move(row, section, position + 1) },
                                    )
                                    sections.indices.filter { it != section }.forEach { target ->
                                        configuration.moveToLabels.getOrNull(target)?.let { label ->
                                            add(CustomAccessibilityAction(label) { move(row, target, sections[target].size) })
                                        }
                                    }
                                },
                                dragModifier = if (!movable) Modifier else Modifier.pointerInput(entry.key) {
                                    detectDragGestures(
                                        onDragStart = { onStart(entry) },
                                        onDragEnd = { onEnd(entry) },
                                        onDragCancel = { onEnd(entry) },
                                        onDrag = { change, amount -> change.consume(); onDrag(entry, amount.y) },
                                    )
                                },
                                longPressModifier = if (!movable) Modifier else Modifier.pointerInput(entry.key) {
                                    detectDragGesturesAfterLongPress(
                                        onDragStart = { onStart(entry) },
                                        onDragEnd = { onEnd(entry) },
                                        onDragCancel = { onEnd(entry) },
                                        onDrag = { change, amount -> change.consume(); onDrag(entry, amount.y) },
                                    )
                                },
                                modifier = measure
                                    .zIndex(if (dragging) 1f else 0f)
                                    .graphicsLayer { translationY = if (dragging) dragOffset else 0f },
                            )
                        }
                    }
                }
            }
        }

        val supporting = configuration.error.ifEmpty { configuration.helper }
        if (supporting.isNotEmpty()) {
            Text(
                text = supporting,
                color = if (configuration.error.isNotEmpty()) errorColor else secondaryColor,
                style = MaterialTheme.typography.bodySmall,
            )
        }
    }
}

@Composable
private fun ReorderListRow(
    configuration: ReorderListRendererConfiguration,
    row: Int,
    locked: Boolean,
    first: Boolean,
    last: Boolean,
    dragging: Boolean,
    labelColor: Color,
    secondaryColor: Color,
    stateDescription: String,
    actions: List<CustomAccessibilityAction>,
    dragModifier: Modifier,
    longPressModifier: Modifier,
    modifier: Modifier,
) {
    val corner = 16.dp
    val shape = RoundedCornerShape(
        topStart = if (first || dragging) corner else 0.dp,
        topEnd = if (first || dragging) corner else 0.dp,
        bottomStart = if (last || dragging) corner else 0.dp,
        bottomEnd = if (last || dragging) corner else 0.dp,
    )
    val icon = configuration.rowIcons.getOrElse(row) { "" }
    val supporting = configuration.rowSupporting.getOrElse(row) { "" }

    Column(
        modifier
            .fillMaxWidth()
            .shadow(if (dragging) 8.dp else 0.dp, shape)
            .background(MaterialTheme.colorScheme.surfaceContainerHigh, shape)
            .then(longPressModifier)
            .semantics(mergeDescendants = true) {
                this.stateDescription = stateDescription
                customActions = actions
            },
    ) {
        Row(
            Modifier
                .fillMaxWidth()
                .defaultMinSize(minHeight = 56.dp)
                .padding(start = 16.dp, end = 4.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(16.dp),
        ) {
            if (icon.isNotEmpty()) {
                Box(Modifier.clearAndSetSemantics { }) {
                    MaterialIcon(name = icon, contentDescription = null, size = 24.dp)
                }
            }
            Column(Modifier.weight(1f).padding(vertical = 8.dp)) {
                Text(configuration.rowLabels.getOrElse(row) { "" }, color = labelColor, style = MaterialTheme.typography.bodyLarge)
                if (supporting.isNotEmpty()) {
                    Text(supporting, color = secondaryColor, style = MaterialTheme.typography.bodyMedium)
                }
            }
            // The handle and lock are visual: TalkBack reads the row and offers its actions.
            Box(
                Modifier
                    .size(48.dp)
                    .testTag(REORDER_LIST_HANDLE_TAG + row)
                    .clearAndSetSemantics { }
                    .then(dragModifier),
                contentAlignment = Alignment.Center,
            ) {
                MaterialIcon(
                    name = if (locked) "lock" else "drag_indicator",
                    contentDescription = null,
                    size = 24.dp,
                )
            }
        }
        if (!last && !dragging) {
            HorizontalDivider(Modifier.padding(start = if (icon.isNotEmpty()) 56.dp else 16.dp))
        }
    }
}
