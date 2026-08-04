package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.width
import androidx.compose.material3.IconButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.disabled
import androidx.compose.ui.semantics.error
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.semantics.stateDescription
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.nativephp.plugins.native_ui.NativeUITokens

@Composable
internal fun FirstlightStepperControl(
    state: StepperRendererState,
    tokens: NativeUITokens,
    onDecrement: () -> Unit,
    onIncrement: () -> Unit,
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

    Column(modifier = modifier.fillMaxWidth(), verticalArrangement = Arrangement.spacedBy(6.dp)) {
        if (configuration.label.isNotEmpty()) {
            Text(
                text = configuration.label,
                color = tokens.onSurface,
                fontWeight = FontWeight.Medium,
                modifier = Modifier.clearAndSetSemantics {},
            )
        }

        Row(
            modifier = Modifier
                .fillMaxWidth()
                .defaultMinSize(minHeight = 48.dp)
                .semantics(mergeDescendants = true) {
                    if (description.isNotEmpty()) contentDescription = description
                    stateDescription = configuration.displayValue
                    if (configuration.disabled || state.isAwaitingPublication) disabled()
                    if (configuration.error.isNotEmpty()) error(configuration.error)
                },
            verticalAlignment = Alignment.CenterVertically,
        ) {
            IconButton(
                onClick = onDecrement,
                modifier = Modifier.semantics {
                    contentDescription = "Decrease ${configuration.accessibilityLabel}"
                },
                enabled = configuration.canPressDecrement && !state.isAwaitingPublication,
            ) {
                Text(text = "−", color = tokens.onSurface)
            }

            Spacer(Modifier.width(8.dp))
            Text(
                text = configuration.displayValue,
                color = tokens.onSurface,
                modifier = Modifier.defaultMinSize(minWidth = 48.dp),
            )
            Spacer(Modifier.width(8.dp))

            IconButton(
                onClick = onIncrement,
                modifier = Modifier.semantics {
                    contentDescription = "Increase ${configuration.accessibilityLabel}"
                },
                enabled = configuration.canPressIncrement && !state.isAwaitingPublication,
            ) {
                Text(text = "+", color = tokens.onSurface)
            }
        }

        if (supporting.isNotEmpty()) {
            Text(
                text = supporting,
                color = if (configuration.error.isEmpty()) tokens.onSurfaceVariant else tokens.destructive,
                modifier = Modifier.clearAndSetSemantics {},
            )
        }
    }
}
