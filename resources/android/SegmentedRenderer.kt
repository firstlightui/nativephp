package com.clinically.plugins.firstlight_ui.ui

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.SegmentedButtonDefaults
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.compositeOver
import androidx.compose.ui.graphics.luminance
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import com.nativephp.plugins.native_ui.NativeUITheme
import com.nativephp.plugins.native_ui.NativeUITokens

sealed interface SegmentedRendererEvent {
    val wireName: String

    data class SelectChange(
        val callbackId: Int,
        val nodeId: Int,
        val value: String,
    ) : SegmentedRendererEvent {
        override val wireName = "SELECT_CHANGE"
    }

    data class Press(
        val callbackId: Int,
        val nodeId: Int,
    ) : SegmentedRendererEvent {
        override val wireName = "PRESS"
    }
}

class SegmentedRendererEvents(
    private val send: (SegmentedRendererEvent) -> Unit,
) {
    fun dispatch(event: SegmentedRendererEvent) = send(event)

    companion object {
        val native = SegmentedRendererEvents { event ->
            when (event) {
                is SegmentedRendererEvent.SelectChange -> NativeUIBridge.sendSelectChangeEvent(
                    event.callbackId,
                    event.nodeId,
                    event.value,
                )

                is SegmentedRendererEvent.Press -> NativeUIBridge.sendPressEvent(
                    event.callbackId,
                    event.nodeId,
                )
            }
        }
    }
}

data class SegmentedRendererConfiguration(
    val nodeId: Int,
    val optionValues: List<String>,
    val optionLabels: List<String>,
    val optionEnabled: List<Boolean>,
    val optionCallbacks: List<Int>,
    val valueType: String,
    val hasSelection: Boolean,
    val selectedValue: String,
    val onChangeCallback: Int,
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
        valueType = node.props.getString("value_type"),
        hasSelection = node.props.getBool("has_selection"),
        selectedValue = node.props.getString("selected_value"),
        onChangeCallback = node.props.getCallbackId("on_change"),
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

    val serverSelectedIndex: Int?
        get() = selectedIndex(hasSelection, selectedValue, optionValues)

    val serverSelectedWireValue: String?
        get() = serverSelectedIndex?.let(optionValues::get)
}

class SegmentedRendererState(node: NativeUINode) {
    var configuration by mutableStateOf(SegmentedRendererConfiguration(node))
        private set

    private val selectionState = SegmentedSelectionState(configuration.serverSelectedWireValue)

    val selectedIndex: Int?
        get() = selectionState.selectedWireValue?.let(configuration.optionValues::indexOf)?.takeIf { it >= 0 }

    fun userSelected(index: Int): SegmentedRendererEvent? {
        if (configuration.disabled ||
            !configuration.optionValues.indices.contains(index) ||
            !configuration.optionEnabled.getOrElse(index) { false }
        ) {
            return null
        }

        val value = configuration.optionValues[index]
        if (!selectionState.select(value, enabled = true)) {
            return null
        }

        return when (configuration.valueType) {
            "string" -> configuration.onChangeCallback.takeIf { it != 0 }?.let { callbackId ->
                SegmentedRendererEvent.SelectChange(callbackId, configuration.nodeId, value)
            }

            "integer" -> configuration.optionCallbacks.getOrNull(index)?.takeIf { it != 0 }?.let { callbackId ->
                SegmentedRendererEvent.Press(callbackId, configuration.nodeId)
            }

            else -> null
        }
    }

    fun serverPublished(publicationId: Long, tree: NativeUITree): Boolean {
        val node = tree.root.findNode(configuration.nodeId) ?: return false
        val previousSelection = selectionState.selectedWireValue
        val published = SegmentedRendererConfiguration(node)
        configuration = published
        selectionState.reconcile(publicationId, published.serverSelectedWireValue)
        return previousSelection != selectionState.selectedWireValue
    }
}

object SegmentedRenderer {
    /**
     * Development dependency: NativePHP local fork commit ca1bf3e exposes
     * NativeUIBridge.treePublicationId. This package remains blocked from public release
     * until an official NativePHP version ships the equivalent publication-revision API.
     */
    @Composable
    fun Render(
        node: NativeUINode,
        modifier: Modifier,
        events: SegmentedRendererEvents = SegmentedRendererEvents.native,
    ) {
        val rendererState = remember(node.id) { SegmentedRendererState(node) }

        // Reading longValue subscribes this renderer to every mounted tree publication,
        // including an equal tree whose node reference was reused by NativePHP diffing.
        val publicationId = NativeUIBridge.treePublicationId.longValue
        LaunchedEffect(publicationId) {
            NativeUIBridge.currentTree.value?.let { tree ->
                rendererState.serverPublished(publicationId, tree)
            }
        }

        val tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light
        val selectedIndex = rendererState.selectedIndex

        FirstlightSegmentedControl(
            labels = rendererState.configuration.optionLabels,
            enabled = rendererState.configuration.optionEnabled,
            selectedIndex = selectedIndex,
            groupEnabled = !rendererState.configuration.disabled,
            label = rendererState.configuration.label,
            helper = rendererState.configuration.helper,
            error = rendererState.configuration.error.takeIf(String::isNotEmpty),
            onSelection = { index ->
                rendererState.userSelected(index)?.let(events::dispatch)
            },
            modifier = modifier,
            colors = segmentedButtonColors(tokens),
            labelColor = tokens.onSurface,
            helperColor = tokens.onSurfaceVariant,
            errorColor = tokens.destructive,
            required = rendererState.configuration.required,
            accessibilityLabel = rendererState.configuration.accessibilityLabel,
            accessibilityHint = rendererState.configuration.accessibilityHint,
        )
    }
}

private fun NativeUINode.findNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findNode(id)?.let { return it } }
    return null
}

@Composable
internal fun segmentedButtonColors(tokens: NativeUITokens) = run {
    val resolved = resolveSegmentedTokenColors(tokens)

    SegmentedButtonDefaults.colors(
        activeContainerColor = resolved.activeContainer,
        activeContentColor = resolved.activeContent,
        activeBorderColor = resolved.activeContainer,
        inactiveContainerColor = resolved.inactiveContainer,
        inactiveContentColor = resolved.inactiveContent,
        inactiveBorderColor = resolved.inactiveBorder,
        disabledActiveContainerColor = resolved.activeContainer.copy(alpha = 0.38f),
        disabledActiveContentColor = resolved.activeContent.copy(alpha = 0.38f),
        disabledInactiveContainerColor = resolved.inactiveContainer,
        disabledInactiveContentColor = tokens.onSurfaceVariant.copy(alpha = 0.38f),
    )
}

internal data class SegmentedTokenColors(
    val activeContainer: Color,
    val activeContent: Color,
    val inactiveContainer: Color,
    val inactiveContent: Color,
    val inactiveBorder: Color,
)

internal fun resolveSegmentedTokenColors(tokens: NativeUITokens): SegmentedTokenColors {
    val surface = tokens.surface.compositeOver(tokens.background).copy(alpha = 1f)
    val activeContainer = tokens.primary.compositeOver(surface).copy(alpha = 1f)
    val preferredActiveContent = tokens.onPrimary.compositeOver(activeContainer).copy(alpha = 1f)
    val activeContent = preferredActiveContent.takeIf {
        contrastRatio(it, activeContainer) >= 4.5f
    } ?: listOf(Color.Black, Color.White).maxBy { contrastRatio(it, activeContainer) }

    return SegmentedTokenColors(
        activeContainer = activeContainer,
        activeContent = activeContent,
        inactiveContainer = surface,
        inactiveContent = tokens.onSurface,
        inactiveBorder = tokens.outline,
    )
}

internal fun contrastRatio(foreground: Color, background: Color): Float {
    val lighter = maxOf(foreground.luminance(), background.luminance())
    val darker = minOf(foreground.luminance(), background.luminance())
    return (lighter + 0.05f) / (darker + 0.05f)
}
