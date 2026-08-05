package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.ButtonDefaults
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import com.nativephp.mobile.ui.MaterialIcon
import com.nativephp.plugins.native_ui.NativeUITokens

enum class CalloutTone(
    val wireName: String,
    val accessibilityName: String,
    val iconName: String,
) {
    Neutral("neutral", "Notice", "notifications"),
    Info("info", "Information", "info"),
    Success("success", "Success", "check_circle"),
    Warning("warning", "Warning", "warning"),
    Danger("danger", "Error", "error");

    companion object {
        fun fromWire(value: String): CalloutTone = entries.firstOrNull {
            it.wireName == value
        } ?: Info
    }

    val statusLabelTone: StatusLabelTone
        get() = StatusLabelTone.fromWire(wireName)
}

val firstlightCalloutActionMinimumHeight: Dp = 48.dp

internal fun firstlightCalloutAccessibilityLabel(
    message: String,
    tone: CalloutTone,
    explicit: String,
): String = explicit.ifEmpty { "${tone.accessibilityName}: $message" }

@Composable
fun FirstlightCalloutControl(
    configuration: CalloutRendererConfiguration,
    tokens: NativeUITokens,
    onPress: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val colors = resolveStatusLabelTokenColors(tokens, configuration.tone.statusLabelTone)

    Surface(
        modifier = modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.medium,
        color = colors.background,
        contentColor = colors.foreground,
        border = BorderStroke(1.dp, colors.foreground.copy(alpha = 0.3f)),
    ) {
        Row(
            modifier = Modifier.padding(12.dp),
            verticalAlignment = Alignment.Top,
        ) {
            MaterialIcon(
                name = configuration.tone.iconName,
                contentDescription = null,
                modifier = Modifier.size(20.dp),
            )
            Spacer(modifier = Modifier.width(12.dp))
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = configuration.message,
                    modifier = Modifier.semantics {
                        contentDescription = configuration.resolvedAccessibilityDescription
                    },
                    color = colors.foreground,
                    style = MaterialTheme.typography.bodyMedium,
                )

                if (configuration.hasAction) {
                    TextButton(
                        onClick = onPress,
                        modifier = Modifier.heightIn(min = firstlightCalloutActionMinimumHeight),
                        colors = ButtonDefaults.textButtonColors(contentColor = colors.foreground),
                    ) {
                        Text(
                            text = configuration.actionLabel,
                            fontWeight = FontWeight.SemiBold,
                        )
                    }
                }
            }
        }
    }
}
