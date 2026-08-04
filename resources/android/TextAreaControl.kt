package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.error
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.text.input.TextFieldValue
import androidx.compose.ui.unit.dp
import com.nativephp.plugins.native_ui.NativeUITokens

@Composable
internal fun FirstlightTextAreaControl(
    configuration: TextAreaRendererConfiguration,
    draft: TextFieldValue,
    tokens: NativeUITokens,
    onValueChange: (TextFieldValue) -> Unit,
    modifier: Modifier = Modifier,
) {
    val supporting = configuration.error.ifEmpty { configuration.helper }
    val description = listOf(configuration.accessibilityLabel, configuration.accessibilityHint)
        .filter(String::isNotEmpty)
        .joinToString(". ")

    OutlinedTextField(
        value = draft,
        onValueChange = onValueChange,
        modifier = modifier
            .fillMaxWidth()
            .defaultMinSize(minHeight = 48.dp)
            .semantics {
                if (description.isNotEmpty()) contentDescription = description
                if (configuration.error.isNotEmpty()) error(configuration.error)
            },
        enabled = !configuration.disabled,
        readOnly = configuration.readOnly,
        singleLine = false,
        minLines = configuration.minLines,
        maxLines = configuration.maxLines,
        isError = configuration.error.isNotEmpty(),
        label = configuration.label.takeIf(String::isNotEmpty)?.let { label ->
            { Text(if (configuration.required) "$label *" else label) }
        },
        placeholder = configuration.placeholder.takeIf(String::isNotEmpty)?.let { placeholder ->
            { Text(placeholder) }
        },
        supportingText = supporting.takeIf(String::isNotEmpty)?.let { text ->
            {
                Text(
                    text = text,
                    color = if (configuration.error.isEmpty()) tokens.onSurfaceVariant else tokens.destructive,
                )
            }
        },
        keyboardOptions = textAreaKeyboardOptions(configuration),
    )
}
