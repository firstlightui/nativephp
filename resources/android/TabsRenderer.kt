package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.compositeOver
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import com.nativephp.plugins.native_ui.NativeUITheme
import com.nativephp.plugins.native_ui.NativeUITokens

data class TabsRendererEvent(
    val callbackId: Int,
    val nodeId: Int,
    val value: String,
) {
    val wireName = "SELECT_CHANGE"
}

class TabsRendererEvents(
    private val send: (TabsRendererEvent) -> Unit,
) {
    fun dispatch(event: TabsRendererEvent) = send(event)

    companion object {
        val native = TabsRendererEvents { event ->
            NativeUIBridge.sendSelectChangeEvent(event.callbackId, event.nodeId, event.value)
        }
    }
}

data class TabsRendererConfiguration(
    val nodeId: Int,
    val values: List<String>,
    val labels: List<String>,
    val badges: List<String>,
    val accessibilityLabels: List<String>,
    val enabled: List<Boolean>,
    val hasSelection: Boolean,
    val selectedValue: String,
    val onChangeCallback: Int,
    val disabled: Boolean,
    val groupAccessibilityLabel: String,
    val groupAccessibilityHint: String,
) {
    constructor(node: NativeUINode) : this(
        nodeId = node.id,
        values = node.props.getStringList("tab_values"),
        labels = node.props.getStringList("tab_labels"),
        badges = node.props.getStringList("tab_badges"),
        accessibilityLabels = node.props.getStringList("tab_a11y_labels"),
        enabled = node.props.getStringList("tab_enabled").map { it == "1" },
        hasSelection = node.props.getBool("has_selection"),
        selectedValue = node.props.getString("selected_value"),
        onChangeCallback = node.props.getCallbackId("on_change"),
        disabled = node.props.getBool("disabled") || node.props.getStringList("tab_values").isEmpty(),
        groupAccessibilityLabel = node.props.getString("a11y_label"),
        groupAccessibilityHint = node.props.getString("a11y_hint"),
    )

    /** Index of PHP's accepted value, or null when nothing (or an unknown value) is selected. */
    val selectedIndex: Int?
        get() = if (hasSelection) values.indexOf(selectedValue).takeIf { it >= 0 } else null

    val groupDescription: String
        get() = listOf(groupAccessibilityLabel, groupAccessibilityHint)
            .filter(String::isNotEmpty)
            .joinToString(". ")
}

class TabsRendererState(node: NativeUINode) {
    var configuration by mutableStateOf(TabsRendererConfiguration(node))
        private set

    /**
     * A tap proposes a value without moving the selection; the next
     * publication from PHP decides which tab is selected.
     */
    fun userSelected(index: Int): TabsRendererEvent? {
        val current = configuration
        if (current.disabled ||
            !current.values.indices.contains(index) ||
            !current.enabled.getOrElse(index) { false } ||
            current.selectedIndex == index ||
            current.onChangeCallback == 0
        ) {
            return null
        }

        return TabsRendererEvent(current.onChangeCallback, current.nodeId, current.values[index])
    }

    fun serverPublished(tree: NativeUITree): Boolean {
        val node = tree.root.findTabsNode(configuration.nodeId) ?: return false
        return serverPublished(node)
    }

    fun serverPublished(node: NativeUINode): Boolean {
        if (node.id != configuration.nodeId) return false
        val previous = configuration.selectedIndex
        configuration = TabsRendererConfiguration(node)
        return previous != configuration.selectedIndex
    }
}

object TabsRenderer {
    @Composable
    fun Render(
        node: NativeUINode,
        modifier: Modifier,
        events: TabsRendererEvents = TabsRendererEvents.native,
    ) {
        val state = remember(node.id) { TabsRendererState(node) }
        val publishedTree = NativeUIBridge.currentTree.value

        LaunchedEffect(node.props) { state.serverPublished(node) }
        LaunchedEffect(publishedTree) { publishedTree?.let(state::serverPublished) }

        val tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light
        val configuration = state.configuration

        FirstlightTabsControl(
            labels = configuration.labels,
            badges = configuration.badges,
            accessibilityLabels = configuration.accessibilityLabels,
            enabled = configuration.enabled,
            selectedIndex = configuration.selectedIndex,
            groupEnabled = !configuration.disabled,
            onSelection = { index -> state.userSelected(index)?.let(events::dispatch) },
            colors = resolveTabsTokenColors(tokens),
            modifier = modifier,
            groupDescription = configuration.groupDescription,
        )
    }
}

private fun NativeUINode.findTabsNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findTabsNode(id)?.let { return it } }
    return null
}

internal fun resolveTabsTokenColors(tokens: NativeUITokens): TabsTokenColors {
    val container = tokens.surface.compositeOver(tokens.background).copy(alpha = 1f)
    val primary = tokens.primary.compositeOver(container).copy(alpha = 1f)
    // The indicator also marks selection, so text falls back to onSurface
    // rather than shipping a low-contrast selected label.
    val selectedContent = primary.takeIf { contrastRatio(it, container) >= 4.5f } ?: tokens.onSurface
    val badgeContainer = tokens.destructive.compositeOver(container).copy(alpha = 1f)
    val preferredBadgeContent = tokens.onDestructive.compositeOver(badgeContainer).copy(alpha = 1f)
    val badgeContent = preferredBadgeContent.takeIf { contrastRatio(it, badgeContainer) >= 4.5f }
        ?: listOf(Color.Black, Color.White)
            .maxBy { contrastRatio(it, badgeContainer) }

    return TabsTokenColors(
        container = container,
        selectedContent = selectedContent,
        unselectedContent = tokens.onSurfaceVariant,
        indicator = primary,
        divider = tokens.outline,
        badgeContainer = badgeContainer,
        badgeContent = badgeContent,
    )
}
