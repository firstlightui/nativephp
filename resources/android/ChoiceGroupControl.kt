package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.selection.selectable
import androidx.compose.foundation.selection.selectableGroup
import androidx.compose.foundation.selection.toggleable
import androidx.compose.material3.Checkbox
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.RadioButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.error as semanticsError
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.semantics.stateDescription
import androidx.compose.ui.unit.dp

@Composable
fun FirstlightChoiceGroupControl(
    labels: List<String>,
    values: List<String>,
    enabled: List<Boolean>,
    selectedValues: List<String>,
    multiple: Boolean,
    groupEnabled: Boolean,
    awaitingPublication: Boolean,
    label: String,
    helper: String,
    error: String?,
    onSelection: (Int) -> Unit,
    modifier: Modifier = Modifier,
    labelColor: Color = MaterialTheme.colorScheme.onSurface,
    helperColor: Color = MaterialTheme.colorScheme.onSurfaceVariant,
    errorColor: Color = MaterialTheme.colorScheme.error,
    required: Boolean = false,
    accessibilityLabel: String = label,
    accessibilityHint: String = "",
) {
    val fieldSemantics = firstlightFieldSemantics(
        accessibilityLabel = accessibilityLabel,
        accessibilityHint = accessibilityHint,
        required = required,
        error = error,
    )

    Column(
        modifier = modifier
            .fillMaxWidth()
            .semantics {
                if (fieldSemantics.contentDescription.isNotEmpty()) {
                    contentDescription = fieldSemantics.contentDescription
                }
                fieldSemantics.stateDescription?.let { stateDescription = it }
                fieldSemantics.error?.let { semanticsError(it) }
            },
        verticalArrangement = Arrangement.spacedBy(6.dp),
    ) {
        if (label.isNotEmpty()) {
            Text(
                text = if (required) "$label *" else label,
                color = labelColor,
                style = MaterialTheme.typography.labelLarge,
            )
        }

        Column(modifier = if (multiple) Modifier else Modifier.selectableGroup()) {
            labels.forEachIndexed { index, optionLabel ->
                val selected = values.getOrNull(index) in selectedValues
                val optionEnabled = groupEnabled && enabled.getOrElse(index) { false }
                val rowModifier = Modifier
                    .fillMaxWidth()
                    .defaultMinSize(minHeight = 48.dp)
                    .then(
                        if (multiple) {
                            Modifier.toggleable(
                                value = selected,
                                enabled = optionEnabled,
                                role = Role.Checkbox,
                                onValueChange = {
                                    if (!awaitingPublication) onSelection(index)
                                },
                            )
                        } else {
                            Modifier.selectable(
                                selected = selected,
                                enabled = optionEnabled,
                                role = Role.RadioButton,
                                onClick = {
                                    if (!awaitingPublication) onSelection(index)
                                },
                            )
                        },
                    )
                    .padding(horizontal = 4.dp, vertical = 2.dp)

                Row(
                    modifier = rowModifier,
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    if (multiple) {
                        Checkbox(
                            checked = selected,
                            onCheckedChange = null,
                            enabled = optionEnabled,
                        )
                    } else {
                        RadioButton(
                            selected = selected,
                            onClick = null,
                            enabled = optionEnabled,
                        )
                    }
                    Spacer(Modifier.width(12.dp))
                    Text(
                        text = optionLabel,
                        color = labelColor,
                        style = MaterialTheme.typography.bodyLarge,
                    )
                }
            }
        }

        val supportingText = error ?: helper
        if (supportingText.isNotEmpty()) {
            Text(
                text = supportingText,
                color = if (error != null) errorColor else helperColor,
                style = MaterialTheme.typography.bodySmall,
            )
        }
    }
}
