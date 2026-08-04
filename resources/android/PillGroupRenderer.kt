package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.FilterChipDefaults
import androidx.compose.material3.SelectableChipColors
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

sealed interface PillGroupRendererEvent {
    val wireName: String

    data class Press(
        val callbackId: Int,
        val nodeId: Int,
    ) : PillGroupRendererEvent {
        override val wireName = "PRESS"
    }
}

class PillGroupRendererEvents(
    private val send: (PillGroupRendererEvent) -> Unit,
) {
    fun dispatch(event: PillGroupRendererEvent) = send(event)

    companion object {
        val native = PillGroupRendererEvents { event ->
            when (event) {
                is PillGroupRendererEvent.Press -> NativeUIBridge.sendPressEvent(
                    event.callbackId,
                    event.nodeId,
                )
            }
        }
    }
}

data class PillGroupRendererConfiguration(
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

class PillGroupRendererState(node: NativeUINode) {
    var configuration by mutableStateOf(PillGroupRendererConfiguration(node))
        private set

    var isAwaitingPublication by mutableStateOf(false)
        private set

    fun userSelected(index: Int): PillGroupRendererEvent? {
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
        return PillGroupRendererEvent.Press(callback, configuration.nodeId)
    }

    fun serverPublished(tree: NativeUITree): Boolean {
        val node = tree.root.findPillGroupNode(configuration.nodeId) ?: return false
        val previousSelection = configuration.selectedValues
        configuration = PillGroupRendererConfiguration(node)
        isAwaitingPublication = false
        return previousSelection != configuration.selectedValues
    }
}

object PillGroupRenderer {
    @Composable
    fun Render(
        node: NativeUINode,
        modifier: Modifier,
        events: PillGroupRendererEvents = PillGroupRendererEvents.native,
    ) {
        val rendererState = remember(node.id) { PillGroupRendererState(node) }
        val publishedTree = NativeUIBridge.currentTree.value

        LaunchedEffect(publishedTree) {
            publishedTree?.let(rendererState::serverPublished)
        }

        val tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light

        FirstlightPillGroupControl(
            labels = rendererState.configuration.optionLabels,
            values = rendererState.configuration.optionValues,
            enabled = rendererState.configuration.optionEnabled,
            selectedValues = rendererState.configuration.selectedValues,
            groupEnabled = !rendererState.configuration.disabled,
            awaitingPublication = rendererState.isAwaitingPublication,
            label = rendererState.configuration.label,
            helper = rendererState.configuration.helper,
            error = rendererState.configuration.error.takeIf(String::isNotEmpty),
            onSelection = { index ->
                rendererState.userSelected(index)?.let(events::dispatch)
            },
            modifier = modifier,
            colors = pillGroupColors(tokens),
            labelColor = tokens.onSurface,
            helperColor = tokens.onSurfaceVariant,
            errorColor = tokens.destructive,
            required = rendererState.configuration.required,
            accessibilityLabel = rendererState.configuration.accessibilityLabel,
            accessibilityHint = rendererState.configuration.accessibilityHint,
        )
    }
}

private fun NativeUINode.findPillGroupNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findPillGroupNode(id)?.let { return it } }
    return null
}

internal data class PillGroupTokenColors(
    val selectedContainer: Color,
    val selectedContent: Color,
    val unselectedContainer: Color,
    val unselectedContent: Color,
    val outline: Color,
)

internal fun resolvePillGroupTokenColors(tokens: NativeUITokens): PillGroupTokenColors {
    val surface = tokens.surface.compositeOver(tokens.background).copy(alpha = 1f)
    val selectedContainer = tokens.primary.compositeOver(surface).copy(alpha = 1f)
    val preferredSelectedContent = tokens.onPrimary.compositeOver(selectedContainer).copy(alpha = 1f)
    val selectedContent = preferredSelectedContent.takeIf {
        contrastRatio(it, selectedContainer) >= 4.5f
    } ?: listOf(Color.Black, Color.White).maxBy { contrastRatio(it, selectedContainer) }

    return PillGroupTokenColors(
        selectedContainer = selectedContainer,
        selectedContent = selectedContent,
        unselectedContainer = tokens.surfaceVariant.compositeOver(surface).copy(alpha = 1f),
        unselectedContent = tokens.onSurface,
        outline = tokens.outline,
    )
}

@Composable
internal fun pillGroupColors(tokens: NativeUITokens): SelectableChipColors {
    val resolved = resolvePillGroupTokenColors(tokens)

    return FilterChipDefaults.filterChipColors(
        containerColor = resolved.unselectedContainer,
        labelColor = resolved.unselectedContent,
        selectedContainerColor = resolved.selectedContainer,
        selectedLabelColor = resolved.selectedContent,
        disabledContainerColor = resolved.unselectedContainer.copy(alpha = 0.38f),
        disabledLabelColor = tokens.onSurfaceVariant.copy(alpha = 0.38f),
    )
}
