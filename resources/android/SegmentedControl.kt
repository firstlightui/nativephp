package com.clinically.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.IntrinsicSize
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.SegmentedButton
import androidx.compose.material3.SegmentedButtonColors
import androidx.compose.material3.SegmentedButtonDefaults
import androidx.compose.material3.SingleChoiceSegmentedButtonRow
import androidx.compose.material3.Text
import androidx.compose.material3.minimumInteractiveComponentSize
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.error as semanticsError
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.semantics.stateDescription
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp

class SegmentedSelectionState(
    selectedWireValue: String?,
) {
    var selectedWireValue: String? by mutableStateOf(selectedWireValue)
        private set

    fun select(candidateWireValue: String, enabled: Boolean): Boolean {
        if (!enabled || candidateWireValue == selectedWireValue) {
            return false
        }

        return true
    }

    fun reconcile(serverSelectedWireValue: String?) {
        selectedWireValue = serverSelectedWireValue
    }
}

fun selectedIndex(
    hasSelection: Boolean,
    selectedValue: String,
    optionValues: List<String>,
): Int? = if (hasSelection) {
    optionValues.indexOf(selectedValue).takeIf { it >= 0 }
} else {
    null
}

internal data class FirstlightFieldSemantics(
    val contentDescription: String,
    val stateDescription: String?,
    val error: String?,
)

internal fun firstlightFieldSemantics(
    accessibilityLabel: String,
    accessibilityHint: String,
    required: Boolean,
    error: String?,
) = FirstlightFieldSemantics(
    contentDescription = listOf(accessibilityLabel, accessibilityHint)
        .filter(String::isNotEmpty)
        .joinToString(". "),
    stateDescription = "Required".takeIf { required },
    error = error,
)

@Composable
fun FirstlightSegmentedControl(
    labels: List<String>,
    enabled: List<Boolean>,
    selectedIndex: Int?,
    groupEnabled: Boolean,
    label: String,
    helper: String,
    error: String?,
    onSelection: (Int) -> Unit,
    modifier: Modifier = Modifier,
    colors: SegmentedButtonColors = SegmentedButtonDefaults.colors(),
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
    val fieldModifier = modifier
        .fillMaxWidth()
        .semantics {
            if (fieldSemantics.contentDescription.isNotEmpty()) {
                contentDescription = fieldSemantics.contentDescription
            }
            fieldSemantics.stateDescription?.let {
                stateDescription = it
            }
            fieldSemantics.error?.let {
                semanticsError(it)
            }
        }

    Column(
        modifier = fieldModifier,
        verticalArrangement = Arrangement.spacedBy(8.dp),
    ) {
        if (label.isNotEmpty()) {
            Text(
                text = if (required) "$label *" else label,
                color = labelColor,
                style = MaterialTheme.typography.labelLarge,
            )
        }

        SingleChoiceSegmentedButtonRow(
            modifier = Modifier
                .fillMaxWidth()
                .height(IntrinsicSize.Min),
        ) {
            labels.forEachIndexed { index, optionLabel ->
                val optionEnabled = groupEnabled && enabled.getOrElse(index) { false }

                SegmentedButton(
                    // M3 supplies selectable, selected, and disabled semantics for each
                    // option. Keep those roles native and cover them in the runtime
                    // TalkBack gate; Paparazzi has no stable JVM semantics-node API.
                    selected = selectedIndex == index,
                    onClick = { onSelection(index) },
                    shape = SegmentedButtonDefaults.itemShape(index = index, count = labels.size),
                    enabled = optionEnabled,
                    colors = colors,
                    modifier = Modifier
                        .weight(1f)
                        .fillMaxHeight()
                        .minimumInteractiveComponentSize()
                        .defaultMinSize(minHeight = 48.dp)
                        .semantics { contentDescription = optionLabel },
                ) {
                    Text(
                        text = optionLabel,
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis,
                        style = MaterialTheme.typography.labelLarge,
                    )
                }
            }
        }

        val supportingText = error ?: helper
        if (supportingText.isNotEmpty()) {
            Text(
                text = supportingText,
                color = if (error != null) {
                    errorColor
                } else {
                    helperColor
                },
                style = MaterialTheme.typography.bodySmall,
            )
        }
    }
}
