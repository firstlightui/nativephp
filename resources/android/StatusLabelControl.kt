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

data class FirstlightWarningColors(
    val light: String = "#B45309",
    val onLight: String = "#FFFFFF",
    val dark: String = "#FBBF24",
    val onDark: String = "#1C1917",
) {
    constructor(node: com.nativephp.mobile.ui.nativerender.NativeUINode) : this(
        node.props.getString("warning_color", "#B45309"),
        node.props.getString("on_warning_color", "#FFFFFF"),
        node.props.getString("dark_warning_color", "#FBBF24"),
        node.props.getString("dark_on_warning_color", "#1C1917"),
    )

    fun resolve(isDark: Boolean): Pair<Color, Color> {
        fun color(value: String, fallback: Long): Color {
            val digits = value.removePrefix("#")
            val parsed = if (value.startsWith("#") && digits.length in listOf(6, 8)) digits.toLongOrNull(16) else null
            return Color(if (parsed == null) fallback else if (digits.length == 6) parsed or 0xFF000000 else parsed)
        }
        return if (isDark) color(dark, 0xFFFBBF24) to color(onDark, 0xFF1C1917)
        else color(light, 0xFFB45309) to color(onLight, 0xFFFFFFFF)
    }
}

internal fun resolveStatusLabelTokenColors(
    tokens: NativeUITokens,
    tone: StatusLabelTone,
    warningColors: FirstlightWarningColors = FirstlightWarningColors(),
    isDark: Boolean = false,
): StatusLabelTokenColors {
    val (candidateBackground, candidateForeground) = when (tone) {
        StatusLabelTone.Neutral -> tokens.surfaceVariant to tokens.onSurfaceVariant
        StatusLabelTone.Info -> tokens.primary to tokens.onPrimary
        StatusLabelTone.Success -> tokens.success to tokens.onSuccess
        StatusLabelTone.Warning -> warningColors.resolve(isDark)
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
