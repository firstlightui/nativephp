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

enum class PillGroupTone(val wireName: String) {
    Primary("primary"),
    Accent("accent"),
    Success("success"),
    Warning("warning"),
    Danger("danger");

    companion object {
        fun fromWire(value: String): PillGroupTone = entries.firstOrNull {
            it.wireName == value
        } ?: Primary
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
    val optionIcons: List<String> = emptyList(),
    val optionIconVariants: List<String> = emptyList(),
    val scrollable: Boolean = false,
    val collapseLabels: Boolean = false,
    val tone: PillGroupTone = PillGroupTone.Primary,
    val warningColors: FirstlightWarningColors = FirstlightWarningColors(),
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
        optionIcons = node.props.getStringList("option_icons"),
        optionIconVariants = node.props.getStringList("option_icon_variants"),
        scrollable = node.props.getBool("scrollable"),
        collapseLabels = node.props.getBool("collapse_labels"),
        tone = PillGroupTone.fromWire(node.props.getString("tone", "primary")),
        warningColors = FirstlightWarningColors(node),
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
        firstlightValidationAlert(node.id)
        val rendererState = remember(node.id) { PillGroupRendererState(node) }
        val publishedTree = NativeUIBridge.currentTree.value

        LaunchedEffect(publishedTree) {
            publishedTree?.let(rendererState::serverPublished)
        }

        val isDark = isSystemInDarkTheme()
        val tokens = if (isDark) NativeUITheme.dark else NativeUITheme.light
        val configuration = rendererState.configuration

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
            colors = pillGroupColors(tokens, configuration.tone, configuration.warningColors, isDark),
            labelColor = tokens.onSurface,
            helperColor = tokens.onSurfaceVariant,
            errorColor = tokens.destructive,
            required = rendererState.configuration.required,
            accessibilityLabel = rendererState.configuration.accessibilityLabel,
            accessibilityHint = rendererState.configuration.accessibilityHint,
            icons = configuration.optionIcons,
            scrollable = configuration.scrollable,
            collapseLabels = configuration.collapseLabels,
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
    val selectedLeadingIcon: Color,
    val unselectedContainer: Color,
    val unselectedContent: Color,
    val outline: Color,
)

internal fun resolvePillGroupTokenColors(
    tokens: NativeUITokens,
    tone: PillGroupTone = PillGroupTone.Primary,
    warningColors: FirstlightWarningColors = FirstlightWarningColors(),
    isDark: Boolean = false,
): PillGroupTokenColors {
    val surface = tokens.surface.compositeOver(tokens.background).copy(alpha = 1f)
    // Only the selected pill takes the tone; unselected pills stay uniform.
    val (toneContainer, toneContent) = when (tone) {
        PillGroupTone.Primary -> tokens.primary to tokens.onPrimary
        PillGroupTone.Accent -> tokens.accent to tokens.onAccent
        PillGroupTone.Success -> tokens.success to tokens.onSuccess
        PillGroupTone.Warning -> warningColors.resolve(isDark)
        PillGroupTone.Danger -> tokens.destructive to tokens.onDestructive
    }
    val selectedContainer = toneContainer.compositeOver(surface).copy(alpha = 1f)
    val preferredSelectedContent = toneContent.compositeOver(selectedContainer).copy(alpha = 1f)
    val selectedContent = preferredSelectedContent.takeIf {
        contrastRatio(it, selectedContainer) >= 4.5f
    } ?: listOf(Color.Black, Color.White).maxBy { contrastRatio(it, selectedContainer) }

    return PillGroupTokenColors(
        selectedContainer = selectedContainer,
        selectedContent = selectedContent,
        selectedLeadingIcon = selectedContent,
        unselectedContainer = tokens.surfaceVariant.compositeOver(surface).copy(alpha = 1f),
        unselectedContent = tokens.onSurface,
        outline = tokens.outline,
    )
}

@Composable
internal fun pillGroupColors(
    tokens: NativeUITokens,
    tone: PillGroupTone = PillGroupTone.Primary,
    warningColors: FirstlightWarningColors = FirstlightWarningColors(),
    isDark: Boolean = false,
): SelectableChipColors {
    val resolved = resolvePillGroupTokenColors(tokens, tone, warningColors, isDark)

    return FilterChipDefaults.filterChipColors(
        containerColor = resolved.unselectedContainer,
        labelColor = resolved.unselectedContent,
        iconColor = resolved.unselectedContent,
        selectedContainerColor = resolved.selectedContainer,
        selectedLabelColor = resolved.selectedContent,
        selectedLeadingIconColor = resolved.selectedLeadingIcon,
        disabledContainerColor = resolved.unselectedContainer.copy(alpha = 0.38f),
        disabledLabelColor = tokens.onSurfaceVariant.copy(alpha = 0.38f),
        disabledLeadingIconColor = tokens.onSurfaceVariant.copy(alpha = 0.38f),
    )
}
