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

sealed interface ChoiceGroupRendererEvent {
    val wireName: String

    data class Press(
        val callbackId: Int,
        val nodeId: Int,
    ) : ChoiceGroupRendererEvent {
        override val wireName = "PRESS"
    }
}

class ChoiceGroupRendererEvents(
    private val send: (ChoiceGroupRendererEvent) -> Unit,
) {
    fun dispatch(event: ChoiceGroupRendererEvent) = send(event)

    companion object {
        val native = ChoiceGroupRendererEvents { event ->
            when (event) {
                is ChoiceGroupRendererEvent.Press -> NativeUIBridge.sendPressEvent(
                    event.callbackId,
                    event.nodeId,
                )
            }
        }
    }
}

data class ChoiceGroupRendererConfiguration(
    val nodeId: Int,
    val optionValues: List<String>,
    val optionLabels: List<String>,
    val optionEnabled: List<Boolean>,
    val optionCallbacks: List<Int>,
    val selectedValues: List<String>,
    val valueType: String,
    val multiple: Boolean,
    val disabled: Boolean,
    val label: String,
    val helper: String,
    val error: String,
    val required: Boolean,
    val accessibilityLabel: String,
    val accessibilityHint: String,
) {
    constructor(node: NativeUINode) : this(
        nodeId = node.id,
        optionValues = node.props.getStringList("option_values"),
        optionLabels = node.props.getStringList("option_labels"),
        optionEnabled = node.props.getStringList("option_enabled").map { it == "1" },
        optionCallbacks = node.props.getStringList("option_callbacks").map { it.toIntOrNull() ?: 0 },
        selectedValues = node.props.getStringList("selected_values"),
        valueType = node.props.getString("value_type"),
        multiple = node.props.getBool("multiple"),
        disabled = node.props.getBool("disabled") || node.props.getStringList("option_values").isEmpty(),
        label = node.props.getString("label"),
        helper = node.props.getString("helper"),
        error = node.props.getString("error"),
        required = node.props.getBool("required"),
        accessibilityLabel = node.props.getString("a11y_label").ifEmpty {
            node.props.getString("label")
        },
        accessibilityHint = node.props.getString("a11y_hint"),
    )
}

class ChoiceGroupRendererState(node: NativeUINode) {
    var configuration by mutableStateOf(ChoiceGroupRendererConfiguration(node))
        private set

    var isAwaitingPublication by mutableStateOf(false)
        private set

    fun userSelected(index: Int): ChoiceGroupRendererEvent? {
        if (isAwaitingPublication ||
            configuration.disabled ||
            !configuration.optionValues.indices.contains(index) ||
            !configuration.optionEnabled.getOrElse(index) { false }
        ) {
            return null
        }

        val callback = configuration.optionCallbacks.getOrNull(index)?.takeIf { it != 0 }
            ?: return null

        isAwaitingPublication = true
        return ChoiceGroupRendererEvent.Press(callback, configuration.nodeId)
    }

    fun serverPublished(tree: NativeUITree): Boolean {
        val node = tree.root.findChoiceGroupNode(configuration.nodeId) ?: return false
        val previousSelection = configuration.selectedValues
        configuration = ChoiceGroupRendererConfiguration(node)
        isAwaitingPublication = false
        return previousSelection != configuration.selectedValues
    }
}

object ChoiceGroupRenderer {
    @Composable
    fun Render(
        node: NativeUINode,
        modifier: Modifier,
        events: ChoiceGroupRendererEvents = ChoiceGroupRendererEvents.native,
    ) {
        val rendererState = remember(node.id) { ChoiceGroupRendererState(node) }
        val publishedTree = NativeUIBridge.currentTree.value

        LaunchedEffect(publishedTree) {
            publishedTree?.let(rendererState::serverPublished)
        }

        val tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light

        FirstlightChoiceGroupControl(
            labels = rendererState.configuration.optionLabels,
            values = rendererState.configuration.optionValues,
            enabled = rendererState.configuration.optionEnabled,
            selectedValues = rendererState.configuration.selectedValues,
            multiple = rendererState.configuration.multiple,
            groupEnabled = !rendererState.configuration.disabled,
            awaitingPublication = rendererState.isAwaitingPublication,
            label = rendererState.configuration.label,
            helper = rendererState.configuration.helper,
            error = rendererState.configuration.error.takeIf(String::isNotEmpty),
            onSelection = { index ->
                rendererState.userSelected(index)?.let(events::dispatch)
            },
            modifier = modifier,
            labelColor = tokens.onSurface,
            helperColor = tokens.onSurfaceVariant,
            errorColor = tokens.destructive,
            required = rendererState.configuration.required,
            accessibilityLabel = rendererState.configuration.accessibilityLabel,
            accessibilityHint = rendererState.configuration.accessibilityHint,
        )
    }
}

private fun NativeUINode.findChoiceGroupNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findChoiceGroupNode(id)?.let { return it } }
    return null
}
