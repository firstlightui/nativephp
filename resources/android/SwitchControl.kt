package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.selection.toggleable
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Switch
import androidx.compose.material3.SwitchDefaults
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.error as semanticsError
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.unit.dp
import com.nativephp.plugins.native_ui.NativeUITokens

@Composable
fun FirstlightSwitchControl(
    value: Boolean,
    label: String,
    helper: String,
    error: String,
    disabled: Boolean,
    accessibilityLabel: String,
    accessibilityHint: String,
    tokens: NativeUITokens,
    onProposal: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val description = listOf(accessibilityLabel, accessibilityHint)
        .filter(String::isNotEmpty)
        .joinToString(". ")
    val supportingText = error.ifEmpty { helper }

    Row(
        modifier = modifier
            .fillMaxWidth()
            .defaultMinSize(minHeight = 48.dp)
            .toggleable(
                value = value,
                enabled = !disabled,
                role = Role.Switch,
                onValueChange = { onProposal() },
            )
            .semantics(mergeDescendants = true) {
                if (description.isNotEmpty()) {
                    contentDescription = description
                }
                if (error.isNotEmpty()) {
                    semanticsError(error)
                }
            },
    ) {
        Column(
            modifier = Modifier
                .weight(1f)
                .padding(end = 16.dp),
        ) {
            if (label.isNotEmpty()) {
                Text(
                    text = label,
                    color = tokens.onSurface,
                    style = MaterialTheme.typography.bodyLarge,
                )
            }
            if (supportingText.isNotEmpty()) {
                Text(
                    text = supportingText,
                    color = if (error.isNotEmpty()) tokens.destructive else tokens.onSurfaceVariant,
                    style = MaterialTheme.typography.bodySmall,
                )
            }
        }
        Switch(
            checked = value,
            onCheckedChange = null,
            enabled = !disabled,
            colors = SwitchDefaults.colors(
                checkedThumbColor = tokens.onPrimary,
                checkedTrackColor = tokens.primary,
                uncheckedThumbColor = tokens.onSurfaceVariant,
                uncheckedTrackColor = tokens.surfaceVariant,
                uncheckedBorderColor = tokens.outline,
                disabledCheckedThumbColor = tokens.onPrimary.copy(alpha = 0.38f),
                disabledCheckedTrackColor = tokens.primary.copy(alpha = 0.38f),
                disabledUncheckedThumbColor = tokens.onSurfaceVariant.copy(alpha = 0.38f),
                disabledUncheckedTrackColor = tokens.surfaceVariant.copy(alpha = 0.38f),
                disabledUncheckedBorderColor = tokens.outline.copy(alpha = 0.38f),
            ),
            // The row is the one Switch semantic target. The visual Material 3
            // control stays silent so TalkBack never encounters a second toggle.
            modifier = Modifier.clearAndSetSemantics { },
        )
    }
}
