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

sealed interface CalloutRendererEvent {
    data class Press(val callbackId: Int, val nodeId: Int) : CalloutRendererEvent
}

data class CalloutRendererConfiguration(
    val nodeId: Int,
    val message: String,
    val tone: CalloutTone,
    val actionLabel: String,
    val accessibilityLabel: String,
    val accessibilityHint: String,
    val callbackId: Int,
) {
    constructor(node: NativeUINode) : this(
        nodeId = node.id,
        message = node.props.getString("message"),
        tone = CalloutTone.fromWire(node.props.getString("tone", "info")),
        actionLabel = node.props.getString("action_label"),
        accessibilityLabel = node.props.getString("a11y_label"),
        accessibilityHint = node.props.getString("a11y_hint"),
        callbackId = node.onPress,
    )

    val hasAction: Boolean
        get() = actionLabel.isNotEmpty() && callbackId != 0

    val resolvedAccessibilityDescription: String
        get() = listOf(
            firstlightCalloutAccessibilityLabel(message, tone, accessibilityLabel),
            accessibilityHint,
        ).filter(String::isNotEmpty).joinToString(". ")

    fun pressEvent(): CalloutRendererEvent.Press? = if (hasAction) {
        CalloutRendererEvent.Press(callbackId = callbackId, nodeId = nodeId)
    } else {
        null
    }
}

class CalloutRendererState(node: NativeUINode) {
    var configuration by mutableStateOf(CalloutRendererConfiguration(node))
        private set

    fun serverPublished(tree: NativeUITree): Boolean {
        val node = tree.root.findCalloutNode(configuration.nodeId) ?: return false
        val published = CalloutRendererConfiguration(node)
        if (published == configuration) return false

        configuration = published
        return true
    }
}

object CalloutRenderer {
    @Composable
    fun Render(node: NativeUINode, modifier: Modifier) {
        val state = remember(node.id) { CalloutRendererState(node) }
        val publishedTree = NativeUIBridge.currentTree.value
        LaunchedEffect(publishedTree) {
            publishedTree?.let(state::serverPublished)
        }

        val configuration = state.configuration
        FirstlightCalloutControl(
            configuration = configuration,
            tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light,
            onPress = {
                configuration.pressEvent()?.let { event ->
                    NativeUIBridge.sendPressEvent(event.callbackId, event.nodeId)
                }
            },
            modifier = modifier,
        )
    }
}

private fun NativeUINode.findCalloutNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findCalloutNode(id)?.let { return it } }
    return null
}
