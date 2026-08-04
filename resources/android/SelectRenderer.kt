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

sealed interface SelectRendererEvent {
    val wireName: String

    data class Press(
        val callbackId: Int,
        val nodeId: Int,
    ) : SelectRendererEvent {
        override val wireName = "PRESS"
    }
}

class SelectRendererEvents(
    private val send: (SelectRendererEvent) -> Unit,
) {
    fun dispatch(event: SelectRendererEvent) = send(event)

    companion object {
        val native = SelectRendererEvents { event ->
            when (event) {
                is SelectRendererEvent.Press -> NativeUIBridge.sendPressEvent(
                    event.callbackId,
                    event.nodeId,
                )
            }
        }
    }
}

data class SelectRendererConfiguration(
    val nodeId: Int,
    val optionValues: List<String>,
    val optionLabels: List<String>,
    val optionEnabled: List<Boolean>,
    val optionCallbacks: List<Int>,
    val selectedValues: List<String>,
    val valueType: String,
    val searchEnabled: Boolean,
    val disabled: Boolean,
    val label: String,
    val placeholder: String,
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
        searchEnabled = node.props.getBool("search_enabled"),
        disabled = node.props.getBool("disabled") || node.props.getStringList("option_values").isEmpty(),
        label = node.props.getString("label"),
        placeholder = node.props.getString("placeholder"),
        helper = node.props.getString("helper"),
        error = node.props.getString("error"),
        required = node.props.getBool("required"),
        accessibilityLabel = node.props.getString("a11y_label").ifEmpty {
            node.props.getString("label")
        },
        accessibilityHint = node.props.getString("a11y_hint"),
    )
}

class SelectRendererState(node: NativeUINode) {
    var configuration by mutableStateOf(SelectRendererConfiguration(node))
        private set

    var isAwaitingPublication by mutableStateOf(false)
        private set

    fun userSelected(index: Int): SelectRendererEvent? {
        val selectedValue = configuration.selectedValues.firstOrNull()

        if (isAwaitingPublication ||
            configuration.disabled ||
            !configuration.optionValues.indices.contains(index) ||
            !configuration.optionEnabled.getOrElse(index) { false } ||
            configuration.optionValues[index] == selectedValue
        ) {
            return null
        }

        val callback = configuration.optionCallbacks.getOrNull(index)?.takeIf { it != 0 }
            ?: return null

        isAwaitingPublication = true
        return SelectRendererEvent.Press(callback, configuration.nodeId)
    }

    fun serverPublished(tree: NativeUITree): Boolean {
        val node = tree.root.findSelectNode(configuration.nodeId) ?: return false
        val previousSelection = configuration.selectedValues
        configuration = SelectRendererConfiguration(node)
        isAwaitingPublication = false
        return previousSelection != configuration.selectedValues
    }
}

object FirstlightSelectRenderer {
    @Composable
    fun Render(
        node: NativeUINode,
        modifier: Modifier,
        events: SelectRendererEvents = SelectRendererEvents.native,
    ) {
        val rendererState = remember(node.id) { SelectRendererState(node) }
        val publishedTree = NativeUIBridge.currentTree.value

        LaunchedEffect(publishedTree) {
            publishedTree?.let(rendererState::serverPublished)
        }

        val tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light

        FirstlightSelectControl(
            configuration = rendererState.configuration,
            awaitingPublication = rendererState.isAwaitingPublication,
            onSelection = { index ->
                rendererState.userSelected(index)?.let(events::dispatch)
            },
            modifier = modifier,
            tokens = tokens,
        )
    }
}

private fun NativeUINode.findSelectNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findSelectNode(id)?.let { return it } }
    return null
}
