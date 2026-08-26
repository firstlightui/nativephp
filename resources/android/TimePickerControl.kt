package dev.firstlightui.plugins.firstlight_ui.ui

import android.content.res.Configuration
import android.text.format.DateFormat
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.DatePickerDialog
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TimePicker
import androidx.compose.material3.rememberTimePickerState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.runtime.key
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.disabled
import androidx.compose.ui.semantics.error
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.semantics.stateDescription
import androidx.compose.ui.unit.dp
import com.nativephp.mobile.ui.MaterialIcon
import com.nativephp.plugins.native_ui.NativeUITokens
import java.time.LocalTime
import java.time.format.DateTimeFormatter
import java.time.format.FormatStyle
import java.util.Locale

@OptIn(ExperimentalMaterial3Api::class)
@Composable
internal fun FirstlightTimePickerControl(
    state: TimePickerRendererState,
    tokens: NativeUITokens,
    onOpen: () -> Boolean,
    onCancel: () -> Unit,
    onSelect: (String) -> Unit,
    onConfirm: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val configuration = state.configuration
    val supporting = configuration.error.ifEmpty { configuration.helper }
    val description = listOf(
        configuration.accessibilityLabel,
        "Required".takeIf { configuration.required }.orEmpty(),
        configuration.accessibilityHint,
        supporting,
    )
        .filter(String::isNotEmpty)
        .joinToString(". ")
    val enabled = !configuration.disabled && configuration.onChangeCallback != 0
    val shown = configuration.acceptedValue?.let { formatTimeForDisplay(it, configuration.locale) }.orEmpty()

    Box(modifier = modifier.fillMaxWidth()) {
        OutlinedTextField(
            value = shown,
            onValueChange = {},
            modifier = Modifier
                .fillMaxWidth()
                .defaultMinSize(minHeight = 48.dp)
                .clearAndSetSemantics {},
            enabled = enabled,
            readOnly = true,
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
            trailingIcon = { MaterialIcon("schedule", null) },
        )

        Box(
            Modifier
                .matchParentSize()
                .clickable(enabled = enabled, role = Role.Button) { onOpen() }
                .semantics {
                    if (description.isNotEmpty()) contentDescription = description
                    stateDescription = shown.ifEmpty { configuration.placeholder }
                    if (!enabled) disabled()
                    if (configuration.error.isNotEmpty()) error(configuration.error)
                },
        )
    }

    if (state.isPresented) {
        key(state.presentationVersion) {
            val draft = LocalTime.parse(requireNotNull(state.draft))
            val context = LocalContext.current
            val locale = remember(configuration.locale) {
                configuration.locale.takeIf(String::isNotEmpty)?.let(Locale::forLanguageTag) ?: Locale.getDefault()
            }
            val pickerState = rememberTimePickerState(
                initialHour = draft.hour,
                initialMinute = draft.minute,
                is24Hour = uses24HourClock(
                    locale = locale,
                    explicitLocale = configuration.locale.isNotEmpty(),
                    systemUses24HourClock = DateFormat.is24HourFormat(context),
                ),
            )
            val platformConfiguration = LocalConfiguration.current
            val localizedConfiguration = remember(platformConfiguration, locale) {
                Configuration(platformConfiguration).apply { setLocale(locale) }
            }

            CompositionLocalProvider(LocalConfiguration provides localizedConfiguration) {
                DatePickerDialog(
                    onDismissRequest = onCancel,
                    confirmButton = {
                        TextButton(onClick = {
                            onSelect(LocalTime.of(pickerState.hour, pickerState.minute).toCanonicalTime())
                            onConfirm()
                        }) { Text(configuration.confirmLabel) }
                    },
                    dismissButton = {
                        TextButton(onClick = onCancel) { Text(configuration.cancelLabel) }
                    },
                ) {
                    Column(Modifier.padding(24.dp)) {
                        TimePicker(state = pickerState)
                    }
                }
            }
        }
    }
}

internal fun formatTimeForDisplay(value: String, locale: String): String =
    LocalTime.parse(value).format(
        DateTimeFormatter.ofLocalizedTime(FormatStyle.SHORT).withLocale(
            locale.takeIf(String::isNotEmpty)?.let(Locale::forLanguageTag) ?: Locale.getDefault(),
        ),
    )

internal fun uses24HourClock(
    locale: Locale,
    explicitLocale: Boolean,
    systemUses24HourClock: Boolean,
): Boolean {
    if (!explicitLocale) return systemUses24HourClock

    val pattern = DateFormat.getBestDateTimePattern(locale, "jm")
    return pattern.none { it == 'a' || it == 'b' || it == 'B' }
}
