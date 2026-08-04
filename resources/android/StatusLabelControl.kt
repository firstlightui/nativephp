package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.compositeOver
import androidx.compose.ui.graphics.luminance
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.nativephp.plugins.native_ui.NativeUITokens

enum class StatusLabelTone(val wireName: String) {
    Neutral("neutral"),
    Info("info"),
    Success("success"),
    Warning("warning"),
    Danger("danger");

    companion object {
        fun fromWire(value: String): StatusLabelTone = entries.firstOrNull {
            it.wireName == value
        } ?: Neutral
    }
}

data class StatusLabelTokenColors(
    val background: Color,
    val foreground: Color,
)

internal fun resolveStatusLabelTokenColors(
    tokens: NativeUITokens,
    tone: StatusLabelTone,
): StatusLabelTokenColors {
    val (candidateBackground, candidateForeground) = when (tone) {
        StatusLabelTone.Neutral -> tokens.surfaceVariant to tokens.onSurfaceVariant
        StatusLabelTone.Info -> tokens.primary to tokens.onPrimary
        StatusLabelTone.Success -> tokens.success to tokens.onSuccess
        StatusLabelTone.Warning -> tokens.accent to tokens.onAccent
        StatusLabelTone.Danger -> tokens.destructive to tokens.onDestructive
    }
    val background = candidateBackground.compositeOver(tokens.surface).copy(alpha = 1f)
    val preferredForeground = candidateForeground.compositeOver(background).copy(alpha = 1f)
    val foreground = preferredForeground.takeIf {
        statusLabelContrastRatio(it, background) >= 4.5f
    } ?: listOf(Color.Black, Color.White).maxBy {
        statusLabelContrastRatio(it, background)
    }

    return StatusLabelTokenColors(background, foreground)
}

internal fun firstlightStatusLabelDescription(
    label: String,
    accessibilityLabel: String,
    accessibilityHint: String,
): String = listOf(accessibilityLabel.ifEmpty { label }, accessibilityHint)
    .filter(String::isNotEmpty)
    .joinToString(". ")

@Composable
fun FirstlightStatusLabelControl(
    label: String,
    colors: StatusLabelTokenColors,
    accessibilityDescription: String,
    modifier: Modifier = Modifier,
) {
    Surface(
        modifier = modifier.semantics(mergeDescendants = true) {
            contentDescription = accessibilityDescription
        },
        shape = RoundedCornerShape(percent = 50),
        color = colors.background,
        contentColor = colors.foreground,
    ) {
        Text(
            text = label,
            modifier = Modifier.padding(horizontal = 10.dp, vertical = 5.dp),
            color = colors.foreground,
            style = MaterialTheme.typography.labelMedium,
            fontWeight = FontWeight.SemiBold,
        )
    }
}

internal fun statusLabelContrastRatio(foreground: Color, background: Color): Float {
    val lighter = maxOf(foreground.luminance(), background.luminance())
    val darker = minOf(foreground.luminance(), background.luminance())
    return (lighter + 0.05f) / (darker + 0.05f)
}
