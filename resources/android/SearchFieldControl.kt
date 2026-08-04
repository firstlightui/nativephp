package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.sizeIn
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material3.IconButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.text.input.TextFieldValue
import androidx.compose.ui.unit.dp
import com.nativephp.mobile.ui.MaterialIcon

@Composable
internal fun FirstlightSearchFieldControl(
    configuration: SearchFieldRendererConfiguration,
    draft: TextFieldValue,
    onValueChange: (TextFieldValue) -> Unit,
    onClear: () -> Unit,
    onSubmit: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val description = listOf(configuration.accessibilityLabel, configuration.accessibilityHint)
        .filter(String::isNotEmpty)
        .joinToString(". ")

    OutlinedTextField(
        value = draft,
        onValueChange = onValueChange,
        modifier = modifier
            .fillMaxWidth()
            .defaultMinSize(minHeight = 48.dp)
            .semantics { contentDescription = description },
        enabled = !configuration.disabled,
        singleLine = true,
        shape = CircleShape,
        placeholder = configuration.placeholder.takeIf(String::isNotEmpty)?.let { text ->
            { androidx.compose.material3.Text(text) }
        },
        leadingIcon = {
            MaterialIcon("search", null)
        },
        trailingIcon = draft.text.takeIf(String::isNotEmpty)?.let {
            {
                IconButton(
                    onClick = onClear,
                    enabled = !configuration.disabled,
                    modifier = Modifier
                        .sizeIn(minWidth = 48.dp, minHeight = 48.dp)
                        .semantics { contentDescription = "Clear search" },
                ) {
                    MaterialIcon("close", null)
                }
            }
        },
        keyboardOptions = searchFieldKeyboardOptions(configuration),
        keyboardActions = searchFieldKeyboardActions(onSubmit),
    )
}
