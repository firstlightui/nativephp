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

sealed interface ConfirmationDialogRendererEvent {
    val wireName: String

    data class Press(val callbackId: Int, val nodeId: Int) : ConfirmationDialogRendererEvent {
        override val wireName = "PRESS"
    }

    data class Dismiss(val callbackId: Int, val nodeId: Int) : ConfirmationDialogRendererEvent {
        override val wireName = "PRESS"
    }
}

class ConfirmationDialogRendererEvents(private val send: (ConfirmationDialogRendererEvent) -> Unit) {
    fun dispatch(event: ConfirmationDialogRendererEvent) = send(event)

    companion object {
        val native = ConfirmationDialogRendererEvents { event ->
            when (event) {
                is ConfirmationDialogRendererEvent.Press -> NativeUIBridge.sendPressEvent(
                    event.callbackId,
                    event.nodeId,
                )

                is ConfirmationDialogRendererEvent.Dismiss -> NativeUIBridge.sendPressEvent(
                    event.callbackId,
                    event.nodeId,
                )
            }
        }
    }
}

data class ConfirmationDialogRendererConfiguration(
    val nodeId: Int,
    val visible: Boolean,
    val title: String,
    val message: String,
    val confirmLabel: String,
    val cancelLabel: String,
    val tone: FirstlightConfirmationDialogTone,
    val confirmCallback: Int,
    val dismissCallback: Int,
) {
    constructor(node: NativeUINode) : this(
        nodeId = node.id,
        visible = node.props.getBool("visible"),
        title = node.props.getString("title"),
        message = node.props.getString("message"),
        confirmLabel = node.props.getString("confirm_label", "Confirm"),
        cancelLabel = node.props.getString("cancel_label", "Cancel"),
        tone = FirstlightConfirmationDialogTone.fromWire(
            node.props.getString("tone", "default"),
        ),
        confirmCallback = node.onPress,
        dismissCallback = node.props.getCallbackId("on_dismiss"),
    )

    val canPresent: Boolean
        get() = visible && confirmCallback != 0 && dismissCallback != 0
}

class ConfirmationDialogRendererState(node: NativeUINode) {
    var configuration by mutableStateOf(ConfirmationDialogRendererConfiguration(node))
        private set
    var isPresented by mutableStateOf(configuration.canPresent)
        private set

    fun confirm(): ConfirmationDialogRendererEvent.Press? {
        if (!isPresented || configuration.confirmCallback == 0) return null

        isPresented = false

        return ConfirmationDialogRendererEvent.Press(
            callbackId = configuration.confirmCallback,
            nodeId = configuration.nodeId,
        )
    }

    fun dismiss(): ConfirmationDialogRendererEvent.Dismiss? {
        if (!isPresented || configuration.dismissCallback == 0) return null

        isPresented = false

        return ConfirmationDialogRendererEvent.Dismiss(
            callbackId = configuration.dismissCallback,
            nodeId = configuration.nodeId,
        )
    }

    fun serverPublished(tree: NativeUITree): Boolean {
        val node = tree.root.findConfirmationDialogNode(configuration.nodeId) ?: return false
        return publish(ConfirmationDialogRendererConfiguration(node))
    }

    fun serverPublished(node: NativeUINode): Boolean {
        if (node.id != configuration.nodeId) return false
        return publish(ConfirmationDialogRendererConfiguration(node))
    }

    private fun publish(published: ConfirmationDialogRendererConfiguration): Boolean {
        if (published == configuration) return false

        val visibilityChanged = published.visible != configuration.visible
        configuration = published

        if (!published.canPresent) {
            isPresented = false
        } else if (visibilityChanged) {
            isPresented = true
        }

        return true
    }
}

object ConfirmationDialogRenderer {
    @Composable
    fun Render(
        node: NativeUINode,
        modifier: Modifier,
        events: ConfirmationDialogRendererEvents = ConfirmationDialogRendererEvents.native,
    ) {
        val state = remember(node.id) { ConfirmationDialogRendererState(node) }

        LaunchedEffect(node.props, node.onPress) { state.serverPublished(node) }
        NativeUIBridge.currentTree.value?.let { tree ->
            LaunchedEffect(tree) { state.serverPublished(tree) }
        }

        ConfirmationDialogControl(
            state = state,
            tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light,
            onConfirm = { state.confirm()?.let(events::dispatch) },
            onDismiss = { state.dismiss()?.let(events::dispatch) },
            modifier = modifier,
        )
    }
}

private fun NativeUINode.findConfirmationDialogNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findConfirmationDialogNode(id)?.let { return it } }
    return null
}
