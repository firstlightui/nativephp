package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.size
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.FilledIconButton
import androidx.compose.material3.FilledTonalIconButton
import androidx.compose.material3.IconButton
import androidx.compose.material3.IconButtonDefaults
import androidx.compose.material3.LocalContentColor
import androidx.compose.material3.minimumInteractiveComponentSize
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.role
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.semantics.stateDescription
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import com.nativephp.mobile.ui.MaterialIcon
import com.nativephp.plugins.native_ui.NativeUITokens

enum class FirstlightIconButtonVariant(val wireName: String) {
    Primary("primary"),
    Secondary("secondary"),
    Destructive("destructive"),
    Success("success"),
    Ghost("ghost");

    companion object {
        fun fromWire(value: String): FirstlightIconButtonVariant = entries.firstOrNull {
            it.wireName == value
        } ?: Primary
    }
}

enum class FirstlightIconButtonSize(
    val wireName: String,
    val metrics: FirstlightIconButtonMetrics,
) {
    Small("sm", FirstlightIconButtonMetrics(visualSize = 32.dp, iconSize = 16.dp)),
    Medium("md", FirstlightIconButtonMetrics(visualSize = 40.dp, iconSize = 20.dp)),
    Large("lg", FirstlightIconButtonMetrics(visualSize = 48.dp, iconSize = 24.dp));

    companion object {
        fun fromWire(value: String): FirstlightIconButtonSize = entries.firstOrNull {
            it.wireName == value
        } ?: Medium
    }
}

data class FirstlightIconButtonMetrics(
    val visualSize: Dp,
    val iconSize: Dp,
    val minimumTarget: Dp = 48.dp,
)

internal fun firstlightIconButtonDescription(label: String, hint: String): String =
    listOf(label, hint).filter(String::isNotEmpty).joinToString(". ")

@Composable
fun FirstlightIconButtonControl(
    configuration: IconButtonRendererConfiguration,
    tokens: NativeUITokens,
    onPress: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val metrics = configuration.size.metrics
    val enabled = configuration.enabled
    val semanticsModifier = modifier
        .minimumInteractiveComponentSize()
        .size(metrics.visualSize)
        .semantics(mergeDescendants = true) {
            contentDescription = firstlightIconButtonDescription(
                configuration.accessibilityLabel,
                configuration.accessibilityHint,
            )
            role = Role.Button
            if (configuration.loading) {
                stateDescription = "Loading"
            }
        }
    val content: @Composable () -> Unit = {
        if (configuration.loading) {
            CircularProgressIndicator(
                modifier = Modifier.size(metrics.iconSize),
                strokeWidth = 2.dp,
                color = LocalContentColor.current,
            )
        } else {
            MaterialIcon(
                name = configuration.icon,
                contentDescription = null,
                modifier = Modifier.size(metrics.iconSize),
            )
        }
    }

    when (configuration.variant) {
        FirstlightIconButtonVariant.Ghost -> IconButton(
            onClick = onPress,
            enabled = enabled,
            modifier = semanticsModifier,
            colors = IconButtonDefaults.iconButtonColors(
                contentColor = tokens.primary,
                disabledContentColor = tokens.onSurfaceVariant,
            ),
            content = content,
        )

        FirstlightIconButtonVariant.Secondary -> FilledTonalIconButton(
            onClick = onPress,
            enabled = enabled,
            modifier = semanticsModifier,
            colors = IconButtonDefaults.filledTonalIconButtonColors(
                containerColor = tokens.surfaceVariant,
                contentColor = tokens.onSurfaceVariant,
                disabledContainerColor = tokens.surfaceVariant,
                disabledContentColor = tokens.onSurfaceVariant,
            ),
            content = content,
        )

        else -> {
            val (container, contentColor) = when (configuration.variant) {
                FirstlightIconButtonVariant.Destructive -> tokens.destructive to tokens.onDestructive
                FirstlightIconButtonVariant.Success -> tokens.success to tokens.onSuccess
                else -> tokens.primary to tokens.onPrimary
            }

            FilledIconButton(
                onClick = onPress,
                enabled = enabled,
                modifier = semanticsModifier,
                colors = IconButtonDefaults.filledIconButtonColors(
                    containerColor = container,
                    contentColor = contentColor,
                    disabledContainerColor = tokens.surfaceVariant,
                    disabledContentColor = tokens.onSurfaceVariant,
                ),
                content = content,
            )
        }
    }
}
