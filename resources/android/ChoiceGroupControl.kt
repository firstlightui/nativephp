package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.layout.FlowRow
import androidx.compose.material3.OutlinedCard
import androidx.compose.material3.CardDefaults
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.foundation.selection.selectable
import androidx.compose.foundation.selection.selectableGroup
import androidx.compose.foundation.selection.toggleable
import androidx.compose.foundation.BorderStroke
import androidx.compose.material3.Checkbox
import androidx.compose.material3.CheckboxDefaults
import androidx.compose.material3.RadioButtonDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.RadioButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.compositeOver
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
    selectedColor: Color = MaterialTheme.colorScheme.primary,
    required: Boolean = false,
    accessibilityLabel: String = label,
    accessibilityHint: String = "",
    tiles: Boolean = false,
    prefixes: List<String> = emptyList(),
    supporting: List<String> = emptyList(),
) {
    // Selection follows the theme's primary token, as on iOS, rather than the
    // Material scheme's secondaryContainer, which apps never theme.
    val radioColors = RadioButtonDefaults.colors(selectedColor = selectedColor)
    val checkboxColors = CheckboxDefaults.colors(checkedColor = selectedColor)
    val surface = MaterialTheme.colorScheme.surface
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

        if (tiles) {
            FlowRow(
                modifier = Modifier.fillMaxWidth().then(if (multiple) Modifier else Modifier.selectableGroup()),
                maxItemsInEachRow = if (LocalDensity.current.fontScale >= 1.5f) 1 else 2,
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalArrangement = Arrangement.spacedBy(8.dp),
            ) {
                labels.forEachIndexed { index, optionLabel ->
                    val selected = values.getOrNull(index) in selectedValues
                    val optionEnabled = groupEnabled && enabled.getOrElse(index) { false }
                    val selection = if (multiple) Modifier.toggleable(
                        value = selected, enabled = optionEnabled, role = Role.Checkbox,
                        onValueChange = { if (!awaitingPublication) onSelection(index) },
                    ) else Modifier.selectable(
                        selected = selected, enabled = optionEnabled, role = Role.RadioButton,
                        onClick = { if (!awaitingPublication) onSelection(index) },
                    )
                    OutlinedCard(
                        modifier = Modifier.weight(1f).defaultMinSize(minHeight = 48.dp).then(selection),
                        colors = CardDefaults.outlinedCardColors(
                            containerColor = if (selected) selectedColor.copy(alpha = 0.12f).compositeOver(surface) else surface,
                        ),
                        border = if (selected) BorderStroke(1.dp, selectedColor) else CardDefaults.outlinedCardBorder(optionEnabled),
                    ) {
                        Column(Modifier.padding(12.dp).alpha(if (optionEnabled) 1f else 0.38f), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                            if (multiple) Checkbox(checked = selected, onCheckedChange = null, enabled = optionEnabled, colors = checkboxColors)
                            else RadioButton(selected = selected, onClick = null, enabled = optionEnabled, colors = radioColors)
                            prefixes.getOrNull(index)?.takeIf { it.isNotEmpty() }?.let {
                                Text(it, style = MaterialTheme.typography.labelLarge)
                            }
                            Text(optionLabel, style = MaterialTheme.typography.bodyLarge)
                            supporting.getOrNull(index)?.takeIf { it.isNotEmpty() }?.let {
                                Text(it, style = MaterialTheme.typography.bodyMedium)
                            }
                        }
                    }
                }
            }
        } else {
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
                                colors = checkboxColors,
                            )
                        } else {
                            RadioButton(
                                selected = selected,
                                onClick = null,
                                enabled = optionEnabled,
                                colors = radioColors,
                            )
                        }
                        Spacer(Modifier.width(12.dp))
                        Column {
                            prefixes.getOrNull(index)?.takeIf { it.isNotEmpty() }?.let {
                                Text(it, color = helperColor, style = MaterialTheme.typography.labelLarge)
                            }
                            Text(text = optionLabel, color = labelColor, style = MaterialTheme.typography.bodyLarge)
                            supporting.getOrNull(index)?.takeIf { it.isNotEmpty() }?.let {
                                Text(it, color = helperColor, style = MaterialTheme.typography.bodyMedium)
                            }
                        }
                    }
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
