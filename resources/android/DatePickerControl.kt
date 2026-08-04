package dev.firstlightui.plugins.firstlight_ui.ui

import android.content.res.Configuration
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.material3.DatePicker
import androidx.compose.material3.DatePickerDialog
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.SelectableDates
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.rememberDatePickerState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.runtime.key
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalConfiguration
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
import java.time.Instant
import java.time.LocalDate
import java.time.ZoneOffset
import java.time.format.DateTimeFormatter
import java.time.format.FormatStyle
import java.util.Locale

@OptIn(ExperimentalMaterial3Api::class)
@Composable
internal fun FirstlightDatePickerControl(
    state: DatePickerRendererState,
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

    val shown = configuration.acceptedValue?.let { formatDateForDisplay(it, configuration.locale) }.orEmpty()

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
            trailingIcon = { MaterialIcon("calendar_month", null) },
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
            val draft = requireNotNull(state.draft)
            val minimum = configuration.min.takeIf(String::isNotEmpty)
            val maximum = configuration.max.takeIf(String::isNotEmpty)
            val pickerState = rememberDatePickerState(
                initialSelectedDateMillis = canonicalDateToUtcMillis(draft),
                yearRange = (minimum?.take(4)?.toInt() ?: 1)..(maximum?.take(4)?.toInt() ?: 9999),
                selectableDates = remember(minimum, maximum) { DatePickerBounds(minimum, maximum) },
            )
            val platformConfiguration = LocalConfiguration.current
            val locale = remember(configuration.locale) {
                configuration.locale.takeIf(String::isNotEmpty)?.let(Locale::forLanguageTag) ?: Locale.getDefault()
            }
            val localizedConfiguration = remember(platformConfiguration, locale) {
                Configuration(platformConfiguration).apply { setLocale(locale) }
            }

            CompositionLocalProvider(LocalConfiguration provides localizedConfiguration) {
                DatePickerDialog(
                    onDismissRequest = onCancel,
                    confirmButton = {
                        TextButton(onClick = {
                            pickerState.selectedDateMillis?.let(::utcMillisToCanonicalDate)?.let(onSelect)
                            onConfirm()
                        }) { Text("Confirm") }
                    },
                    dismissButton = {
                        TextButton(onClick = onCancel) { Text("Cancel") }
                    },
                ) {
                    DatePicker(state = pickerState)
                }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
private class DatePickerBounds(private val minimum: String?, private val maximum: String?) : SelectableDates {
    override fun isSelectableDate(utcTimeMillis: Long): Boolean {
        val date = utcMillisToCanonicalDate(utcTimeMillis)
        return (minimum == null || date >= minimum) && (maximum == null || date <= maximum)
    }

    override fun isSelectableYear(year: Int): Boolean =
        (minimum == null || year >= minimum.take(4).toInt()) &&
            (maximum == null || year <= maximum.take(4).toInt())
}

internal fun canonicalDateToUtcMillis(value: String): Long =
    LocalDate.parse(value).atStartOfDay(ZoneOffset.UTC).toInstant().toEpochMilli()

internal fun utcMillisToCanonicalDate(value: Long): String =
    Instant.ofEpochMilli(value).atZone(ZoneOffset.UTC).toLocalDate().toString()

internal fun formatDateForDisplay(value: String, locale: String): String =
    LocalDate.parse(value).format(
        DateTimeFormatter.ofLocalizedDate(FormatStyle.MEDIUM).withLocale(
            locale.takeIf(String::isNotEmpty)?.let(Locale::forLanguageTag) ?: Locale.getDefault(),
        ),
    )
