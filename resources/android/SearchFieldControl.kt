package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.sizeIn
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material3.IconButton
import androidx.compose.material3.TextField
import androidx.compose.material3.TextFieldDefaults
import androidx.compose.ui.graphics.Color
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.text.input.TextFieldValue
import androidx.compose.ui.unit.dp
import com.nativephp.mobile.ui.MaterialIcon
import com.nativephp.plugins.native_ui.NativeUITokens

@Composable
internal fun FirstlightSearchFieldControl(
    configuration: SearchFieldRendererConfiguration,
    draft: TextFieldValue,
    onValueChange: (TextFieldValue) -> Unit,
    onClear: () -> Unit,
    onSubmit: () -> Unit,
    tokens: NativeUITokens,
    modifier: Modifier = Modifier,
) {
    val description = listOf(configuration.accessibilityLabel, configuration.accessibilityHint)
        .filter(String::isNotEmpty)
        .joinToString(". ")

    // Material 3 search bar: a filled full-radius container with no outline
    // or focus indicator, at the 56 dp search bar height.
    TextField(
        value = draft,
        onValueChange = onValueChange,
        modifier = modifier
            .fillMaxWidth()
            .defaultMinSize(minHeight = 56.dp)
            .semantics { contentDescription = description },
        enabled = !configuration.disabled,
        singleLine = true,
        shape = CircleShape,
        colors = TextFieldDefaults.colors(
            focusedContainerColor = tokens.surfaceVariant,
            unfocusedContainerColor = tokens.surfaceVariant,
            disabledContainerColor = tokens.surfaceVariant.copy(alpha = 0.38f),
            focusedTextColor = tokens.onSurface,
            unfocusedTextColor = tokens.onSurface,
            focusedPlaceholderColor = tokens.onSurfaceVariant,
            unfocusedPlaceholderColor = tokens.onSurfaceVariant,
            focusedLeadingIconColor = tokens.onSurface,
            unfocusedLeadingIconColor = tokens.onSurface,
            focusedTrailingIconColor = tokens.onSurfaceVariant,
            unfocusedTrailingIconColor = tokens.onSurfaceVariant,
            cursorColor = tokens.primary,
            focusedIndicatorColor = Color.Transparent,
            unfocusedIndicatorColor = Color.Transparent,
            disabledIndicatorColor = Color.Transparent,
        ),
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
                        .semantics { contentDescription = configuration.clearA11yLabel },
                ) {
                    MaterialIcon("close", null)
                }
            }
        },
        keyboardOptions = searchFieldKeyboardOptions(configuration),
        keyboardActions = searchFieldKeyboardActions(onSubmit),
    )
}
