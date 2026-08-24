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

sealed interface AlertDialogRendererEvent {
    val wireName: String

    data class Dismiss(val callbackId: Int, val nodeId: Int) : AlertDialogRendererEvent {
        override val wireName = "PRESS"
    }
}

class AlertDialogRendererEvents(private val send: (AlertDialogRendererEvent) -> Unit) {
    fun dispatch(event: AlertDialogRendererEvent) = send(event)

    companion object {
        val native = AlertDialogRendererEvents { event ->
            when (event) {
                is AlertDialogRendererEvent.Dismiss -> NativeUIBridge.sendPressEvent(
                    event.callbackId,
                    event.nodeId,
                )
            }
        }
    }
}

data class AlertDialogRendererConfiguration(
    val nodeId: Int,
    val visible: Boolean,
    val title: String,
    val message: String,
    val actionLabel: String,
    val dismissCallback: Int,
) {
    constructor(node: NativeUINode) : this(
        nodeId = node.id,
        visible = node.props.getBool("visible"),
        title = node.props.getString("title"),
        message = node.props.getString("message"),
        actionLabel = node.props.getString("action_label", "OK"),
        dismissCallback = node.props.getCallbackId("on_dismiss"),
    )

    val canPresent: Boolean
        get() = visible && dismissCallback != 0
}

class AlertDialogRendererState(node: NativeUINode) {
    var configuration by mutableStateOf(AlertDialogRendererConfiguration(node))
        private set
    var isPresented by mutableStateOf(configuration.canPresent)
        private set

    fun dismiss(): AlertDialogRendererEvent.Dismiss? {
        if (!isPresented || configuration.dismissCallback == 0) return null

        isPresented = false

        return AlertDialogRendererEvent.Dismiss(
            callbackId = configuration.dismissCallback,
            nodeId = configuration.nodeId,
        )
    }

    fun serverPublished(tree: NativeUITree): Boolean {
        val node = tree.root.findAlertDialogNode(configuration.nodeId) ?: return false
        return publish(AlertDialogRendererConfiguration(node))
    }

    fun serverPublished(node: NativeUINode): Boolean {
        if (node.id != configuration.nodeId) return false
        return publish(AlertDialogRendererConfiguration(node))
    }

    private fun publish(published: AlertDialogRendererConfiguration): Boolean {
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

object AlertDialogRenderer {
    @Composable
    fun Render(
        node: NativeUINode,
        modifier: Modifier,
        events: AlertDialogRendererEvents = AlertDialogRendererEvents.native,
    ) {
        val state = remember(node.id) { AlertDialogRendererState(node) }

        LaunchedEffect(node.props) { state.serverPublished(node) }
        NativeUIBridge.currentTree.value?.let { tree ->
            LaunchedEffect(tree) { state.serverPublished(tree) }
        }

        AlertDialogControl(
            state = state,
            tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light,
            onDismiss = { state.dismiss()?.let(events::dispatch) },
            modifier = modifier,
        )
    }
}

private fun NativeUINode.findAlertDialogNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findAlertDialogNode(id)?.let { return it } }
    return null
}
