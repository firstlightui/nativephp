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
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.ParagraphIntrinsics
import androidx.compose.ui.platform.LocalFontFamilyResolver
import androidx.compose.foundation.layout.BoxWithConstraints
import androidx.compose.ui.draw.alpha
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
import kotlin.math.ceil
import kotlin.math.floor
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

    BoxWithConstraints(modifier = modifier.fillMaxWidth()) {
        // When the label's longest word doesn't fit beside the buttons and value, as at
        // large text or on a narrow screen, the label takes its own line instead of
        // breaking mid-word. Android 12's largest font scale is only 1.3, so this
        // follows the space left rather than the scale.
        val labelStyle = MaterialTheme.typography.bodyLarge
        val valueStyle = MaterialTheme.typography.titleMedium
        val density = LocalDensity.current
        val fontFamilyResolver = LocalFontFamilyResolver.current
        val stackLabel = configuration.label.isNotEmpty() && run {
            fun width(text: String, style: TextStyle, longestWord: Boolean): Float =
                ParagraphIntrinsics(text, style, density = density, fontFamilyResolver = fontFamilyResolver)
                    .let { if (longestWord) it.minIntrinsicWidth else it.maxIntrinsicWidth }
            with(density) {
                // Two 48 dp buttons, the value (at least 32 dp) and three 8 dp gaps.
                // Text lays out in whole pixels, so round against the label.
                val controls = 2 * 48.dp.toPx() + maxOf(32.dp.toPx(), ceil(width(configuration.displayValue, valueStyle, false))) + 3 * 8.dp.toPx()
                ceil(width(configuration.label, labelStyle, true)) > floor(maxWidth.toPx() - controls)
            }
        }

        Column(modifier = Modifier.fillMaxWidth(), verticalArrangement = Arrangement.spacedBy(4.dp)) {
            if (stackLabel) {
                Text(
                    text = configuration.label,
                    color = fieldColor,
                    style = MaterialTheme.typography.bodyLarge,
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
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                if (configuration.label.isNotEmpty() && !stackLabel) {
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
            // The glyph is text, which Android's high contrast text redraws in full black, so a
            // disabled button fades the glyph's layer instead of relying on its colour.
            MaterialIcon(
                name = icon,
                contentDescription = null,
                modifier = if (enabled) Modifier else Modifier.alpha(0.38f),
                tint = tokens.onSurface,
            )
        }
    }
}
