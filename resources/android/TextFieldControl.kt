package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.sizeIn
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.autofill.ContentType
import androidx.compose.ui.autofill.contentType
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.error
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.TextFieldValue
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.unit.dp
import com.nativephp.mobile.ui.MaterialIcon
import com.nativephp.plugins.native_ui.NativeUITokens

@Composable
internal fun FirstlightTextFieldControl(
    configuration: TextFieldRendererConfiguration,
    draft: TextFieldValue,
    revealed: Boolean,
    tokens: NativeUITokens,
    onValueChange: (TextFieldValue) -> Unit,
    onClear: () -> Unit,
    onReveal: () -> Unit,
    onTrailingPress: () -> Unit,
    onSubmit: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val supporting = configuration.error.ifEmpty { configuration.helper }
    val description = listOf(configuration.accessibilityLabel, configuration.accessibilityHint)
        .filter(String::isNotEmpty)
        .joinToString(". ")

    Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
        OutlinedTextField(
            value = draft,
            onValueChange = onValueChange,
            modifier = modifier
                .fillMaxWidth()
                .defaultMinSize(minHeight = 48.dp)
                .firstlightContentType(configuration.contentType)
                .semantics {
                    if (description.isNotEmpty()) contentDescription = description
                    if (configuration.error.isNotEmpty()) error(configuration.error)
                },
            enabled = !configuration.disabled,
            readOnly = configuration.readOnly,
            singleLine = true,
            isError = configuration.error.isNotEmpty(),
            label = configuration.label.takeIf(String::isNotEmpty)?.let { label ->
                { Text(if (configuration.required) "$label *" else label) }
            },
            placeholder = configuration.placeholder.takeIf(String::isNotEmpty)?.let { text ->
                { Text(text) }
            },
            supportingText = supporting.takeIf(String::isNotEmpty)?.let { text ->
                { Text(text, color = if (configuration.error.isEmpty()) tokens.onSurfaceVariant else tokens.destructive) }
            },
            leadingIcon = configuration.leadingIcon.takeIf(String::isNotEmpty)?.let { name ->
                { MaterialIcon(name, null) }
            },
            trailingIcon = trailingSlot(
                configuration = configuration,
                hasValue = draft.text.isNotEmpty(),
                revealed = revealed,
                onClear = onClear,
                onReveal = onReveal,
                onTrailingPress = onTrailingPress,
            ),
            visualTransformation = if (configuration.secure && !revealed) {
                PasswordVisualTransformation()
            } else {
                VisualTransformation.None
            },
            keyboardOptions = textFieldKeyboardOptions(configuration),
            keyboardActions = textFieldKeyboardActions(configuration, onSubmit),
        )
    }
}

private fun Modifier.firstlightContentType(value: String): Modifier {
    val type = when (value) {
        "name" -> ContentType.PersonFullName
        "username" -> ContentType.Username
        "email" -> ContentType.EmailAddress
        "password" -> ContentType.Password
        "new-password" -> ContentType.NewPassword
        "one-time-code" -> ContentType.SmsOtpCode
        else -> null
    }

    return type?.let(::contentType) ?: this
}

private fun trailingSlot(
    configuration: TextFieldRendererConfiguration,
    hasValue: Boolean,
    revealed: Boolean,
    onClear: () -> Unit,
    onReveal: () -> Unit,
    onTrailingPress: () -> Unit,
): (@Composable () -> Unit)? = when {
    configuration.clearable && hasValue && !configuration.readOnly -> ({
        textFieldIconButton("close", configuration.clearA11yLabel, configuration.disabled, onClear)
    })
    configuration.revealable -> ({
        textFieldIconButton(
            if (revealed) "visibility_off" else "visibility",
            if (revealed) configuration.hidePasswordA11yLabel else configuration.showPasswordA11yLabel,
            configuration.disabled,
            onReveal,
        )
    })
    configuration.trailingIcon.isNotEmpty() && configuration.onPressCallback != 0 -> ({
        textFieldIconButton(
            configuration.trailingIcon,
            configuration.trailingAccessibilityLabel,
            configuration.disabled,
            onTrailingPress,
        )
    })
    configuration.trailingIcon.isNotEmpty() -> ({ MaterialIcon(configuration.trailingIcon, null) })
    else -> null
}

@Composable
private fun textFieldIconButton(name: String, label: String, disabled: Boolean, action: () -> Unit) {
    IconButton(
        onClick = action,
        enabled = !disabled,
        modifier = Modifier
            .sizeIn(minWidth = 48.dp, minHeight = 48.dp)
            .semantics { contentDescription = label },
    ) {
        MaterialIcon(name, null)
    }
}
