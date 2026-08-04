package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.selection.toggleable
import androidx.compose.material3.Checkbox
import androidx.compose.material3.CheckboxDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.error as semanticsError
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.unit.dp
import com.nativephp.plugins.native_ui.NativeUITokens

@Composable
fun FirstlightCheckboxControl(
    value: Boolean,
    label: String,
    helper: String,
    error: String,
    required: Boolean,
    disabled: Boolean,
    accessibilityLabel: String,
    accessibilityHint: String,
    tokens: NativeUITokens,
    onProposal: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val fieldSemantics = checkboxFieldSemantics(
        accessibilityLabel = accessibilityLabel,
        accessibilityHint = accessibilityHint,
        required = required,
        error = error,
    )
    val supportingText = error.ifEmpty { helper }

    Column(
        modifier = modifier
            .fillMaxWidth()
            .defaultMinSize(minHeight = 48.dp)
            .toggleable(
                value = value,
                enabled = !disabled,
                role = Role.Checkbox,
                onValueChange = { onProposal() },
            )
            .semantics(mergeDescendants = true) {
                if (fieldSemantics.contentDescription.isNotEmpty()) {
                    contentDescription = fieldSemantics.contentDescription
                }
                fieldSemantics.error?.let {
                    semanticsError(it)
                }
            },
    ) {
        Row(
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(8.dp),
        ) {
            Checkbox(
                checked = value,
                onCheckedChange = null,
                enabled = !disabled,
                colors = CheckboxDefaults.colors(
                    checkedColor = tokens.primary,
                    uncheckedColor = tokens.outline,
                    checkmarkColor = tokens.onPrimary,
                    disabledCheckedColor = tokens.primary.copy(alpha = 0.38f),
                    disabledUncheckedColor = tokens.outline.copy(alpha = 0.38f),
                ),
                // The whole field is one TalkBack checkbox target; the visual
                // Material control must not introduce a nested second target.
                modifier = Modifier.clearAndSetSemantics { },
            )

            if (label.isNotEmpty()) {
                Text(
                    text = if (required) "$label *" else label,
                    color = tokens.onSurface,
                    style = MaterialTheme.typography.bodyLarge,
                )
            }
        }

        if (supportingText.isNotEmpty()) {
            Text(
                text = supportingText,
                color = if (error.isNotEmpty()) tokens.destructive else tokens.onSurfaceVariant,
                style = MaterialTheme.typography.bodySmall,
                modifier = Modifier.padding(start = 48.dp),
            )
        }
    }
}

internal data class CheckboxFieldSemantics(
    val contentDescription: String,
    val error: String?,
)

internal fun checkboxFieldSemantics(
    accessibilityLabel: String,
    accessibilityHint: String,
    required: Boolean,
    error: String,
) = CheckboxFieldSemantics(
    // Preserve the checked/unchecked state emitted by toggleable. Required
    // belongs in the accessible name context, not stateDescription, because a
    // custom state description would replace TalkBack's native checked state.
    contentDescription = buildList {
        if (accessibilityLabel.isNotEmpty()) add(accessibilityLabel)
        if (required) add("Required")
        if (accessibilityHint.isNotEmpty()) add(accessibilityHint)
    }.joinToString(". "),
    error = error.takeIf(String::isNotEmpty),
)
