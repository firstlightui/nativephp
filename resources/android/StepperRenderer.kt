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

data class StepperRendererEvent(val callbackId: Int, val nodeId: Int) {
    val wireName = "PRESS"
}

class StepperRendererEvents(private val send: (StepperRendererEvent) -> Unit) {
    fun dispatch(event: StepperRendererEvent) = send(event)

    companion object {
        val native = StepperRendererEvents { event ->
            NativeUIBridge.sendPressEvent(event.callbackId, event.nodeId)
        }
    }
}

data class StepperRendererConfiguration(
    val nodeId: Int,
    val displayValue: String,
    val label: String,
    val helper: String,
    val error: String,
    val disabled: Boolean,
    val canDecrement: Boolean,
    val canIncrement: Boolean,
    val decrementCallback: Int,
    val incrementCallback: Int,
    val accessibilityLabel: String,
    val accessibilityHint: String,
) {
    constructor(node: NativeUINode) : this(
        nodeId = node.id,
        displayValue = node.props.getString("display_value"),
        label = node.props.getString("label"),
        helper = node.props.getString("helper"),
        error = node.props.getString("error"),
        disabled = node.props.getBool("disabled"),
        canDecrement = node.props.getBool("can_decrement"),
        canIncrement = node.props.getBool("can_increment"),
        decrementCallback = node.props.getCallbackId("on_decrement"),
        incrementCallback = node.props.getCallbackId("on_increment"),
        accessibilityLabel = node.props.getString("a11y_label").ifEmpty { node.props.getString("label") },
        accessibilityHint = node.props.getString("a11y_hint"),
    )

    val canPressDecrement: Boolean get() = !disabled && canDecrement && decrementCallback != 0
    val canPressIncrement: Boolean get() = !disabled && canIncrement && incrementCallback != 0
}

class StepperRendererState(configuration: StepperRendererConfiguration) {
    var configuration by mutableStateOf(configuration); private set
    var isAwaitingPublication by mutableStateOf(false); private set

    constructor(node: NativeUINode) : this(StepperRendererConfiguration(node))

    fun decrement(): StepperRendererEvent? = propose(
        callbackId = configuration.decrementCallback,
        enabled = configuration.canPressDecrement,
    )

    fun increment(): StepperRendererEvent? = propose(
        callbackId = configuration.incrementCallback,
        enabled = configuration.canPressIncrement,
    )

    fun serverPublished(tree: NativeUITree): Boolean {
        val node = tree.root.findStepperNode(configuration.nodeId) ?: return false
        val previousDisplayValue = configuration.displayValue
        configuration = StepperRendererConfiguration(node)
        isAwaitingPublication = false
        return previousDisplayValue != configuration.displayValue
    }

    fun serverPublished(published: StepperRendererConfiguration) {
        configuration = published
        isAwaitingPublication = false
    }

    private fun propose(callbackId: Int, enabled: Boolean): StepperRendererEvent? {
        if (isAwaitingPublication || !enabled) return null
        isAwaitingPublication = true
        return StepperRendererEvent(callbackId = callbackId, nodeId = configuration.nodeId)
    }
}

object StepperRenderer {
    @Composable
    fun Render(
        node: NativeUINode,
        modifier: Modifier,
        events: StepperRendererEvents = StepperRendererEvents.native,
    ) {
        val state = remember(node.id) { StepperRendererState(node) }

        LaunchedEffect(node.props, node.onPress) {
            state.serverPublished(StepperRendererConfiguration(node))
        }
        NativeUIBridge.currentTree.value?.let { tree ->
            LaunchedEffect(tree) { state.serverPublished(tree) }
        }

        FirstlightStepperControl(
            state = state,
            tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light,
            onDecrement = { state.decrement()?.let(events::dispatch) },
            onIncrement = { state.increment()?.let(events::dispatch) },
            modifier = modifier,
        )
    }
}

private fun NativeUINode.findStepperNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findStepperNode(id)?.let { return it } }
    return null
}
