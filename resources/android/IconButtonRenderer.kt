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

sealed interface IconButtonRendererEvent {
    data class Press(val callbackId: Int, val nodeId: Int) : IconButtonRendererEvent
}

data class IconButtonRendererConfiguration(
    val nodeId: Int,
    val icon: String,
    val iconVariant: String,
    val variant: FirstlightIconButtonVariant,
    val size: FirstlightIconButtonSize,
    val disabled: Boolean,
    val loading: Boolean,
    val accessibilityLabel: String,
    val accessibilityHint: String,
    val callbackId: Int,
) {
    constructor(node: NativeUINode) : this(
        nodeId = node.id,
        icon = node.props.getString("icon"),
        iconVariant = node.props.getString("icon_variant"),
        variant = FirstlightIconButtonVariant.fromWire(
            node.props.getString("variant", "primary"),
        ),
        size = FirstlightIconButtonSize.fromWire(node.props.getString("size", "md")),
        disabled = node.props.getBool("disabled"),
        loading = node.props.getBool("loading"),
        accessibilityLabel = node.props.getString("a11y_label"),
        accessibilityHint = node.props.getString("a11y_hint"),
        callbackId = node.onPress,
    )

    val enabled: Boolean
        get() = !disabled && !loading && callbackId != 0

    fun pressEvent(): IconButtonRendererEvent.Press? = if (enabled) {
        IconButtonRendererEvent.Press(callbackId = callbackId, nodeId = nodeId)
    } else {
        null
    }
}

class IconButtonRendererState(node: NativeUINode) {
    var configuration by mutableStateOf(IconButtonRendererConfiguration(node))
        private set

    fun serverPublished(tree: NativeUITree): Boolean {
        val node = tree.root.findIconButtonNode(configuration.nodeId) ?: return false
        val published = IconButtonRendererConfiguration(node)
        if (published == configuration) return false

        configuration = published
        return true
    }
}

object IconButtonRenderer {
    @Composable
    fun Render(node: NativeUINode, modifier: Modifier) {
        val state = remember(node.id) { IconButtonRendererState(node) }
        val publishedTree = NativeUIBridge.currentTree.value
        LaunchedEffect(publishedTree) {
            publishedTree?.let(state::serverPublished)
        }

        val tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light
        val configuration = state.configuration

        FirstlightIconButtonControl(
            configuration = configuration,
            tokens = tokens,
            onPress = {
                configuration.pressEvent()?.let { event ->
                    NativeUIBridge.sendPressEvent(event.callbackId, event.nodeId)
                }
            },
            modifier = modifier,
        )
    }
}

private fun NativeUINode.findIconButtonNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findIconButtonNode(id)?.let { return it } }
    return null
}
