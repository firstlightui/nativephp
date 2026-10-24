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
import org.json.JSONArray

sealed interface ListItemRendererEvent {
    data class Press(val callbackId: Int, val nodeId: Int) : ListItemRendererEvent
}

data class ListItemSwipeAction(
    val callbackId: Int,
    val label: String,
    val icon: String,
    val iconVariant: String,
    val role: String,
)

private fun decodeListItemActions(json: String): List<ListItemSwipeAction> {
    if (json.isEmpty()) return emptyList()
    return runCatching {
        val array = JSONArray(json)
        (0 until array.length()).map { index ->
            val action = array.getJSONObject(index)
            ListItemSwipeAction(
                callbackId = action.getInt("cb"),
                label = action.getString("label"),
                icon = action.getString("icon"),
                iconVariant = action.getString("icon_variant"),
                role = action.getString("role"),
            )
        }.filter { it.callbackId != 0 && it.label.isNotBlank() }
    }.getOrDefault(emptyList())
}

data class ListItemMenuItem(
    val callbackId: Int,
    val label: String,
    val icon: String,
    val iconVariant: String,
    val role: String,
    val disabled: Boolean,
) {
    val enabled: Boolean
        get() = !disabled && callbackId != 0
}

private fun decodeListItemMenu(json: String): List<ListItemMenuItem> {
    if (json.isEmpty()) return emptyList()
    return runCatching {
        val array = JSONArray(json)
        (0 until array.length()).map { index ->
            val item = array.getJSONObject(index)
            ListItemMenuItem(
                callbackId = item.getInt("cb"),
                label = item.getString("label"),
                icon = item.getString("icon"),
                iconVariant = item.getString("icon_variant"),
                role = item.getString("role"),
                disabled = item.getBoolean("disabled"),
            )
        }.filter { (it.callbackId != 0 || it.disabled) && it.label.isNotBlank() }
    }.getOrDefault(emptyList())
}

data class ListItemRendererConfiguration(
    val nodeId: Int,
    val headline: String,
    val supporting: String,
    val leadingType: FirstlightListItemLeadingType,
    val leadingValue: String,
    val leadingIconVariant: String,
    val trailingType: FirstlightListItemTrailingType,
    val trailingValue: String,
    val trailingIconVariant: String,
    val disabled: Boolean,
    val accessibilityLabel: String,
    val accessibilityHint: String,
    val callbackId: Int,
    val leadingActions: List<ListItemSwipeAction>,
    val trailingActions: List<ListItemSwipeAction>,
    val contextMenu: List<ListItemMenuItem>,
    val leadingIconTone: String,
    val trailingIconTone: String,
    val leadingIconColor: String,
    val trailingIconColor: String,
    val warningColors: FirstlightWarningColors,
) {
    constructor(node: NativeUINode) : this(
        nodeId = node.id,
        headline = node.props.getString("headline"),
        supporting = node.props.getString("supporting"),
        leadingType = FirstlightListItemLeadingType.fromWire(
            node.props.getString("leading_type"),
        ),
        leadingValue = node.props.getString("leading_value"),
        leadingIconVariant = node.props.getString("leading_icon_variant"),
        trailingType = FirstlightListItemTrailingType.fromWire(
            node.props.getString("trailing_type"),
        ),
        trailingValue = node.props.getString("trailing_value"),
        trailingIconVariant = node.props.getString("trailing_icon_variant"),
        disabled = node.props.getBool("disabled"),
        accessibilityLabel = node.props.getString("a11y_label"),
        accessibilityHint = node.props.getString("a11y_hint"),
        callbackId = node.onPress,
        leadingActions = decodeListItemActions(node.props.getString("firstlight_leading_actions_json")),
        trailingActions = decodeListItemActions(node.props.getString("firstlight_trailing_actions_json")),
        contextMenu = decodeListItemMenu(node.props.getString("firstlight_context_menu_json")),
        leadingIconTone = node.props.getString("leading_icon_tone"),
        trailingIconTone = node.props.getString("trailing_icon_tone"),
        leadingIconColor = node.props.getString("leading_icon_color"),
        trailingIconColor = node.props.getString("trailing_icon_color"),
        warningColors = FirstlightWarningColors(node),
    )

    val enabled: Boolean
        get() = !disabled && callbackId != 0

    fun pressEvent(): ListItemRendererEvent.Press? = if (enabled) {
        ListItemRendererEvent.Press(callbackId = callbackId, nodeId = nodeId)
    } else {
        null
    }

    /** The row exposes its context menu only while the row itself can act. */
    val hasContextMenu: Boolean
        get() = enabled && contextMenu.isNotEmpty()

    fun actionEvent(callbackId: Int): ListItemRendererEvent.Press? =
        if (enabled && callbackId != 0 && (
                (leadingActions + trailingActions).any { it.callbackId == callbackId } ||
                    contextMenu.any { it.callbackId == callbackId && it.enabled }
                )
        ) {
            ListItemRendererEvent.Press(callbackId, nodeId)
        } else {
            null
        }
}

class ListItemRendererState(node: NativeUINode) {
    var configuration by mutableStateOf(ListItemRendererConfiguration(node))
        private set

    fun serverPublished(tree: NativeUITree): Boolean {
        val node = tree.root.findFirstlightListItemNode(configuration.nodeId) ?: return false
        val published = ListItemRendererConfiguration(node)
        if (published == configuration) return false

        configuration = published
        return true
    }
}

object FirstlightListItemRenderer {
    @Composable
    fun Render(node: NativeUINode, modifier: Modifier) {
        val state = remember(node.id) { ListItemRendererState(node) }
        val publishedTree = NativeUIBridge.currentTree.value
        LaunchedEffect(publishedTree) {
            publishedTree?.let(state::serverPublished)
        }

        val tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light
        val configuration = state.configuration

        FirstlightListItemControl(
            configuration = configuration,
            tokens = tokens,
            onPress = {
                configuration.pressEvent()?.let { event ->
                    NativeUIBridge.sendPressEvent(event.callbackId, event.nodeId)
                }
            },
            onAction = { callbackId ->
                state.configuration.actionEvent(callbackId)?.let { event ->
                    NativeUIBridge.sendPressEvent(event.callbackId, event.nodeId)
                }
            },
            modifier = modifier,
        )
    }
}

private fun NativeUINode.findFirstlightListItemNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findFirstlightListItemNode(id)?.let { return it } }
    return null
}
