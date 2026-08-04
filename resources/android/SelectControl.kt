package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.selection.selectable
import androidx.compose.foundation.selection.selectableGroup
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExposedDropdownMenuAnchorType
import androidx.compose.material3.ExposedDropdownMenuBox
import androidx.compose.material3.ExposedDropdownMenuDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.RadioButton
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.error as semanticsError
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.semantics.stateDescription
import androidx.compose.ui.unit.dp
import androidx.compose.ui.window.Dialog
import com.nativephp.mobile.ui.MaterialIcon
import com.nativephp.plugins.native_ui.NativeUITokens

internal fun filterSelectOptionIndices(labels: List<String>, query: String): List<Int> =
    labels.indices.filter { index -> query.isEmpty() || labels[index].contains(query, ignoreCase = true) }

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FirstlightSelectControl(
    configuration: SelectRendererConfiguration,
    awaitingPublication: Boolean,
    onSelection: (Int) -> Unit,
    modifier: Modifier = Modifier,
    tokens: NativeUITokens,
) {
    var expanded by remember { mutableStateOf(false) }
    var dialogVisible by remember { mutableStateOf(false) }
    var query by remember { mutableStateOf("") }
    val selectedValue = configuration.selectedValues.firstOrNull()
    val selectedIndex = selectedValue?.let(configuration.optionValues::indexOf) ?: -1
    val selectedLabel = configuration.optionLabels.getOrNull(selectedIndex)
    val fieldSemantics = firstlightFieldSemantics(
        accessibilityLabel = configuration.accessibilityLabel,
        accessibilityHint = configuration.accessibilityHint,
        required = configuration.required,
        error = configuration.error.takeIf(String::isNotEmpty),
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
        if (configuration.label.isNotEmpty()) {
            Text(
                text = if (configuration.required) "${configuration.label} *" else configuration.label,
                color = tokens.onSurface,
                style = MaterialTheme.typography.labelLarge,
            )
        }

        ExposedDropdownMenuBox(
            expanded = expanded,
            onExpandedChange = {
                if (!configuration.disabled) {
                    if (configuration.searchEnabled) {
                        dialogVisible = true
                    } else {
                        expanded = it
                    }
                }
            },
        ) {
            OutlinedTextField(
                value = selectedLabel.orEmpty(),
                onValueChange = {},
                readOnly = true,
                enabled = !configuration.disabled,
                placeholder = if (configuration.placeholder.isNotEmpty()) {
                    { Text(configuration.placeholder) }
                } else {
                    null
                },
                trailingIcon = {
                    if (configuration.searchEnabled) {
                        MaterialIcon("search", null)
                    } else {
                        ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded)
                    }
                },
                modifier = Modifier
                    .fillMaxWidth()
                    .menuAnchor(ExposedDropdownMenuAnchorType.PrimaryNotEditable)
                    .defaultMinSize(minHeight = 48.dp),
                colors = OutlinedTextFieldDefaults.colors(
                    focusedTextColor = tokens.onSurface,
                    unfocusedTextColor = tokens.onSurface,
                    focusedBorderColor = if (configuration.error.isEmpty()) tokens.primary else tokens.destructive,
                    unfocusedBorderColor = if (configuration.error.isEmpty()) tokens.outline else tokens.destructive,
                    focusedPlaceholderColor = tokens.onSurfaceVariant,
                    unfocusedPlaceholderColor = tokens.onSurfaceVariant,
                    disabledBorderColor = tokens.outline.copy(alpha = 0.5f),
                ),
            )

            if (!configuration.searchEnabled) {
                ExposedDropdownMenu(
                    expanded = expanded,
                    onDismissRequest = { expanded = false },
                ) {
                    configuration.optionLabels.forEachIndexed { index, optionLabel ->
                        DropdownMenuItem(
                            text = { Text(optionLabel) },
                            onClick = {
                                expanded = false
                                if (!awaitingPublication) onSelection(index)
                            },
                            enabled = optionIsEnabled(configuration, index),
                            trailingIcon = if (index == selectedIndex) {
                                { MaterialIcon("check", null) }
                            } else {
                                null
                            },
                        )
                    }
                }
            }
        }

        val supportingText = configuration.error.ifEmpty { configuration.helper }
        if (supportingText.isNotEmpty()) {
            Text(
                text = supportingText,
                color = if (configuration.error.isEmpty()) tokens.onSurfaceVariant else tokens.destructive,
                style = MaterialTheme.typography.bodySmall,
            )
        }
    }

    if (dialogVisible) {
        Dialog(
            onDismissRequest = {
                dialogVisible = false
                query = ""
            },
        ) {
            Surface(
                shape = MaterialTheme.shapes.extraLarge,
                color = tokens.surface,
                tonalElevation = 6.dp,
                modifier = Modifier.fillMaxWidth().heightIn(max = 640.dp),
            ) {
                Column(
                    modifier = Modifier.padding(16.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp),
                ) {
                    Text(
                        text = configuration.label.ifEmpty { configuration.accessibilityLabel },
                        color = tokens.onSurface,
                        style = MaterialTheme.typography.titleLarge,
                    )
                    OutlinedTextField(
                        value = query,
                        onValueChange = { query = it },
                        singleLine = true,
                        placeholder = {
                            Text(configuration.placeholder.ifEmpty { "Search options" })
                        },
                        leadingIcon = { MaterialIcon("search", null) },
                        modifier = Modifier.fillMaxWidth(),
                    )
                    LazyColumn(modifier = Modifier.selectableGroup()) {
                        items(
                            filterSelectOptionIndices(configuration.optionLabels, query),
                            key = { it },
                        ) { index ->
                            val selected = index == selectedIndex
                            val enabled = optionIsEnabled(configuration, index)

                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .defaultMinSize(minHeight = 48.dp)
                                    .selectable(
                                        selected = selected,
                                        enabled = enabled,
                                        role = Role.RadioButton,
                                        onClick = {
                                            dialogVisible = false
                                            query = ""
                                            if (!awaitingPublication) onSelection(index)
                                        },
                                    )
                                    .padding(horizontal = 4.dp, vertical = 2.dp),
                                verticalAlignment = Alignment.CenterVertically,
                            ) {
                                RadioButton(
                                    selected = selected,
                                    onClick = null,
                                    enabled = enabled,
                                )
                                Spacer(Modifier.width(12.dp))
                                Text(
                                    text = configuration.optionLabels[index],
                                    color = tokens.onSurface,
                                    style = MaterialTheme.typography.bodyLarge,
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}

private fun optionIsEnabled(configuration: SelectRendererConfiguration, index: Int): Boolean =
    !configuration.disabled && configuration.optionEnabled.getOrElse(index) { false }
