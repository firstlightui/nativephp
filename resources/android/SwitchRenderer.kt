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

sealed interface SwitchRendererEvent {
    data class ToggleChange(
        val callbackId: Int,
        val nodeId: Int,
        val value: Boolean,
    ) : SwitchRendererEvent
}

data class SwitchRendererConfiguration(
    val nodeId: Int,
    val value: Boolean,
    val label: String,
    val helper: String,
    val error: String,
    val disabled: Boolean,
    val accessibilityLabel: String,
    val accessibilityHint: String,
    val onChangeCallback: Int,
) {
    constructor(node: NativeUINode) : this(
        nodeId = node.id,
        value = node.props.getBool("value"),
        label = node.props.getString("label"),
        helper = node.props.getString("helper"),
        error = node.props.getString("error"),
        disabled = node.props.getBool("disabled"),
        accessibilityLabel = node.props.getString("a11y_label").ifEmpty {
            node.props.getString("label")
        },
        accessibilityHint = node.props.getString("a11y_hint"),
        onChangeCallback = node.props.getCallbackId("on_change"),
    )

    val supportingText: String
        get() = error.ifEmpty { helper }
}

class SwitchRendererState(node: NativeUINode) {
    var configuration by mutableStateOf(SwitchRendererConfiguration(node))
        private set

    private var pendingProposal: Boolean? = null

    fun proposeChange(): SwitchRendererEvent.ToggleChange? {
        if (configuration.disabled || configuration.onChangeCallback == 0 || pendingProposal != null) {
            return null
        }

        val proposal = !configuration.value
        pendingProposal = proposal

        return SwitchRendererEvent.ToggleChange(
            callbackId = configuration.onChangeCallback,
            nodeId = configuration.nodeId,
            value = proposal,
        )
    }

    fun serverPublished(tree: NativeUITree): Boolean {
        val node = tree.root.findSwitchNode(configuration.nodeId) ?: return false
        val previousValue = configuration.value
        configuration = SwitchRendererConfiguration(node)
        pendingProposal = null
        return previousValue != configuration.value
    }
}

object SwitchRenderer {
    @Composable
    fun Render(node: NativeUINode, modifier: Modifier) {
        val state = remember(node.id) { SwitchRendererState(node) }
        val publishedTree = NativeUIBridge.currentTree.value
        LaunchedEffect(publishedTree) {
            publishedTree?.let(state::serverPublished)
        }

        val configuration = state.configuration
        val tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light

        FirstlightSwitchControl(
            value = configuration.value,
            label = configuration.label,
            helper = configuration.helper,
            error = configuration.error,
            disabled = configuration.disabled,
            accessibilityLabel = configuration.accessibilityLabel,
            accessibilityHint = configuration.accessibilityHint,
            tokens = tokens,
            onProposal = {
                state.proposeChange()?.let { event ->
                    NativeUIBridge.sendToggleChangeEvent(
                        event.callbackId,
                        event.nodeId,
                        event.value,
                    )
                }
            },
            modifier = modifier,
        )
    }
}

private fun NativeUINode.findSwitchNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findSwitchNode(id)?.let { return it } }
    return null
}
