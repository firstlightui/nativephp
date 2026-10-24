package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.widthIn
import androidx.compose.material3.IconButtonDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedIconButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.disabled
import androidx.compose.ui.semantics.error
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.semantics.stateDescription
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.Density
import androidx.compose.ui.unit.dp
import com.nativephp.mobile.ui.MaterialIcon
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
    val fieldColor = if (configuration.disabled) tokens.onSurface.copy(alpha = 0.38f) else tokens.onSurface

    Column(modifier = modifier.fillMaxWidth(), verticalArrangement = Arrangement.spacedBy(4.dp)) {
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
            horizontalArrangement = Arrangement.spacedBy(8.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            if (configuration.label.isNotEmpty()) {
                Text(
                    text = configuration.label,
                    color = fieldColor,
                    style = MaterialTheme.typography.bodyLarge,
                    modifier = Modifier
                        .weight(1f)
                        .clearAndSetSemantics {},
                )
            } else {
                Spacer(Modifier.weight(1f))
            }

            StepperButton(
                icon = "remove",
                actionLabel = "Decrease ${configuration.accessibilityLabel}",
                enabled = configuration.canPressDecrement && !state.isAwaitingPublication,
                tokens = tokens,
                onClick = onDecrement,
            )
            Text(
                text = configuration.displayValue,
                color = fieldColor,
                style = MaterialTheme.typography.titleMedium,
                textAlign = TextAlign.Center,
                modifier = Modifier.widthIn(min = 32.dp),
            )
            StepperButton(
                icon = "add",
                actionLabel = "Increase ${configuration.accessibilityLabel}",
                enabled = configuration.canPressIncrement && !state.isAwaitingPublication,
                tokens = tokens,
                onClick = onIncrement,
            )
        }

        if (supporting.isNotEmpty()) {
            Text(
                text = supporting,
                color = when {
                    configuration.error.isNotEmpty() -> tokens.destructive
                    configuration.disabled -> fieldColor
                    else -> tokens.onSurfaceVariant
                },
                style = MaterialTheme.typography.bodySmall,
                modifier = Modifier.clearAndSetSemantics {},
            )
        }
    }
}

@Composable
private fun StepperButton(
    icon: String,
    actionLabel: String,
    enabled: Boolean,
    tokens: NativeUITokens,
    onClick: () -> Unit,
) {
    OutlinedIconButton(
        onClick = onClick,
        enabled = enabled,
        modifier = Modifier.semantics { contentDescription = actionLabel },
        colors = IconButtonDefaults.outlinedIconButtonColors(
            contentColor = tokens.onSurface,
            disabledContentColor = tokens.onSurface.copy(alpha = 0.38f),
        ),
        border = BorderStroke(1.dp, if (enabled) tokens.outline else tokens.onSurface.copy(alpha = 0.12f)),
    ) {
        // Material icons keep their 24 dp size at every font scale; only text grows.
        val density = LocalDensity.current
        CompositionLocalProvider(LocalDensity provides Density(density.density, 1f)) {
            MaterialIcon(name = icon, contentDescription = null)
        }
    }
}
