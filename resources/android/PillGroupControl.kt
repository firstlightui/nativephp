package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.expandHorizontally
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.shrinkHorizontally
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ExperimentalLayoutApi
import androidx.compose.foundation.layout.FlowRow
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material3.FilterChip
import androidx.compose.material3.FilterChipDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.SelectableChipColors
import androidx.compose.material3.Text
import androidx.compose.material3.minimumInteractiveComponentSize
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.error as semanticsError
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.semantics.stateDescription
import androidx.compose.ui.unit.dp
import com.nativephp.mobile.ui.MaterialIcon

@OptIn(ExperimentalLayoutApi::class)
@Composable
fun FirstlightPillGroupControl(
    labels: List<String>,
    values: List<String>,
    enabled: List<Boolean>,
    selectedValues: List<String>,
    groupEnabled: Boolean,
    awaitingPublication: Boolean,
    label: String,
    helper: String,
    error: String?,
    onSelection: (Int) -> Unit,
    modifier: Modifier = Modifier,
    colors: SelectableChipColors = FilterChipDefaults.filterChipColors(),
    labelColor: Color = MaterialTheme.colorScheme.onSurface,
    helperColor: Color = MaterialTheme.colorScheme.onSurfaceVariant,
    errorColor: Color = MaterialTheme.colorScheme.error,
    required: Boolean = false,
    accessibilityLabel: String = label,
    accessibilityHint: String = "",
    icons: List<String> = emptyList(),
    scrollable: Boolean = false,
    collapseLabels: Boolean = false,
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
        verticalArrangement = Arrangement.spacedBy(8.dp),
    ) {
        if (label.isNotEmpty()) {
            Text(
                text = if (required) "$label *" else label,
                color = labelColor,
                style = MaterialTheme.typography.labelLarge,
            )
        }

        val pills: @Composable () -> Unit = {
            labels.forEachIndexed { index, optionLabel ->
                val selected = values.getOrNull(index) in selectedValues
                val optionEnabled = groupEnabled && enabled.getOrElse(index) { false }
                val icon = icons.getOrElse(index) { "" }

                FilterChip(
                    selected = selected,
                    onClick = {
                        if (!awaitingPublication) onSelection(index)
                    },
                    enabled = optionEnabled,
                    label = {
                        if (collapseLabels) {
                            // The icon lives in the label slot so an unselected
                            // pill keeps symmetric icon-only padding; the label
                            // expands beside it once PHP publishes the selection.
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                PillIcon(icon)
                                AnimatedVisibility(
                                    visible = selected,
                                    enter = fadeIn() + expandHorizontally(),
                                    exit = fadeOut() + shrinkHorizontally(),
                                ) {
                                    Text(
                                        text = optionLabel,
                                        modifier = Modifier.padding(start = 8.dp),
                                        style = MaterialTheme.typography.labelLarge,
                                    )
                                }
                            }
                        } else {
                            Text(
                                text = optionLabel,
                                style = MaterialTheme.typography.labelLarge,
                            )
                        }
                    },
                    leadingIcon = when {
                        collapseLabels -> null
                        // Material filter chips replace the leading icon with a
                        // checkmark when selected.
                        selected -> {
                            {
                                Text(
                                    text = "✓",
                                    modifier = Modifier.clearAndSetSemantics { },
                                    style = MaterialTheme.typography.labelLarge,
                                )
                            }
                        }
                        icon.isNotEmpty() -> { { PillIcon(icon) } }
                        else -> null
                    },
                    colors = colors,
                    modifier = Modifier
                        .minimumInteractiveComponentSize()
                        .defaultMinSize(minHeight = 48.dp)
                        .semantics { contentDescription = optionLabel },
                )
            }
        }

        if (scrollable) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .horizontalScroll(rememberScrollState()),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) { pills() }
        } else {
            FlowRow(
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalArrangement = Arrangement.spacedBy(8.dp),
            ) { pills() }
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

@Composable
private fun PillIcon(name: String) {
    if (name.isEmpty()) return
    MaterialIcon(
        name = name,
        contentDescription = null,
        modifier = Modifier.clearAndSetSemantics { },
        size = FilterChipDefaults.IconSize,
    )
}
