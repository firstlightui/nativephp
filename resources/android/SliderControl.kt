package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.material3.Slider
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.disabled
import androidx.compose.ui.semantics.error
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.semantics.stateDescription
import androidx.compose.ui.text.font.FontWeight
import com.nativephp.plugins.native_ui.NativeUITokens

@Composable
internal fun FirstlightSliderControl(
    state: SliderRendererState,
    tokens: NativeUITokens,
    onValueChange: (Float) -> Unit,
    onValueChangeFinished: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val configuration = state.configuration
    val supporting = configuration.error.ifEmpty { configuration.helper }
    val description = listOf(
        configuration.accessibilityLabel,
        configuration.accessibilityHint,
        supporting.takeIf { configuration.error.isEmpty() }.orEmpty(),
        "Error: ${configuration.error}".takeIf { configuration.error.isNotEmpty() }.orEmpty(),
    ).filter(String::isNotEmpty).joinToString(". ")

    Column(modifier = modifier.fillMaxWidth()) {
        if (configuration.label.isNotEmpty()) {
            Text(
                text = configuration.label,
                modifier = Modifier.clearAndSetSemantics {},
                color = tokens.onSurface,
                fontWeight = FontWeight.Medium,
            )
        }

        Slider(
            value = state.draft,
            onValueChange = onValueChange,
            onValueChangeFinished = onValueChangeFinished,
            modifier = Modifier
                .fillMaxWidth()
                .semantics {
                    if (description.isNotEmpty()) contentDescription = description
                    if (configuration.accessibilityValue.isNotEmpty() && !state.isEditing) {
                        stateDescription = configuration.accessibilityValue
                    }
                    if (!configuration.isInteractive) disabled()
                    if (configuration.error.isNotEmpty()) error(configuration.error)
                },
            enabled = configuration.isInteractive,
            valueRange = configuration.min..configuration.max,
            steps = (configuration.intervalCount - 1).coerceAtLeast(0),
        )

        if (supporting.isNotEmpty()) {
            Text(
                text = supporting,
                modifier = Modifier.clearAndSetSemantics {},
                color = if (configuration.error.isEmpty()) tokens.onSurfaceVariant else tokens.destructive,
            )
        }
    }
}
