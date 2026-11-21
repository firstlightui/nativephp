package dev.firstlightui.plugins.firstlight_ui.ui

import android.content.res.Configuration
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.material3.DatePicker
import androidx.compose.material3.DatePickerDefaults
import androidx.compose.material3.LocalContentColor
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ProvideTextStyle
import androidx.compose.material3.AssistChip
import androidx.compose.material3.DisplayMode
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.SelectableDates
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.rememberDatePickerState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.runtime.LaunchedEffect
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
import java.time.DayOfWeek
import java.time.Instant
import java.time.LocalDate
import java.time.ZoneOffset
import java.time.format.DateTimeFormatter
import java.time.format.FormatStyle
import java.time.temporal.WeekFields
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

    if (configuration.variant == "chip") {
        androidx.compose.foundation.layout.Column(modifier) {
            AssistChip(
                onClick = { onOpen() }, enabled = enabled,
                label = { Text(listOf(
                    configuration.label + if (configuration.required) " *" else "",
                    if (configuration.acceptedValue != null) configuration.displayText.ifEmpty { shown } else configuration.placeholder,
                ).filter(String::isNotBlank).joinToString(" ")) },
                trailingIcon = { MaterialIcon("arrow_drop_down", null) },
                modifier = Modifier.defaultMinSize(minHeight = 48.dp).semantics {
                    contentDescription = description
                    stateDescription = shown.ifEmpty { configuration.placeholder }
                    if (configuration.error.isNotEmpty()) error(configuration.error)
                },
            )
            if (supporting.isNotEmpty()) Text(supporting, style = androidx.compose.material3.MaterialTheme.typography.bodySmall,
                color = if (configuration.error.isEmpty()) tokens.onSurfaceVariant else tokens.destructive)
        }
    } else {
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
    }

    if (state.isPresented) {
        key(state.presentationVersion) {
            val draft = requireNotNull(state.draft)
            val minimum = configuration.min.takeIf(String::isNotEmpty)
            val maximum = configuration.max.takeIf(String::isNotEmpty)
            val platformConfiguration = LocalConfiguration.current
            val localizedConfiguration = remember(platformConfiguration, configuration.locale, configuration.firstDayOfWeek) {
                Configuration(platformConfiguration).apply {
                    setLocale(
                        datePickerCalendarLocale(
                            configuration.locale,
                            configuration.firstDayOfWeek,
                            device = platformConfiguration.locales[0] ?: Locale.getDefault(),
                        ),
                    )
                }
            }

            // Material's state reads the calendar's locale, and with it the first weekday, when
            // it is created, so it is created inside the localized configuration.
            CompositionLocalProvider(LocalConfiguration provides localizedConfiguration) {
                val pickerState = rememberDatePickerState(
                    initialSelectedDateMillis = canonicalDateToUtcMillis(draft),
                    // The calendar needs 360 dp; a narrower screen gets Material's date entry instead.
                    initialDisplayMode = if (pickerFitsDialogWidth()) {
                        DisplayMode.Picker
                    } else {
                        DisplayMode.Input
                    },
                    yearRange = (minimum?.take(4)?.toInt() ?: 1)..(maximum?.take(4)?.toInt() ?: 9999),
                    selectableDates = remember(minimum, maximum) { DatePickerBounds(minimum, maximum) },
                )
                FirstlightPickerDialog(
                    onDismissRequest = onCancel,
                    confirmButton = {
                        TextButton(onClick = {
                            pickerState.selectedDateMillis?.let(::utcMillisToCanonicalDate)?.let(onSelect)
                            onConfirm()
                        }) { Text(configuration.confirmLabel) }
                    },
                    dismissButton = {
                        TextButton(onClick = onCancel) { Text(configuration.cancelLabel) }
                    },
                ) {
                    if (pickerUsesSideBySideLayout()) {
                        // A phone in landscape is too short for the header above the calendar.
                        // Beside it, the calendar shows about three weeks instead of one. There's
                        // no room for the keyboard-entry toggle, which the wide screen doesn't need.
                        // A picker switched to keyboard entry before rotating returns to the
                        // calendar here, keeping its date; otherwise it would have no way back.
                        LaunchedEffect(Unit) { pickerState.displayMode = DisplayMode.Picker }
                        val colors = DatePickerDefaults.colors()
                        Row {
                            Column(Modifier.width(200.dp).padding(start = 24.dp, end = 12.dp, top = 16.dp)) {
                                ProvideTextStyle(MaterialTheme.typography.labelLarge) {
                                    CompositionLocalProvider(LocalContentColor provides colors.titleContentColor) {
                                        DatePickerDefaults.DatePickerTitle(pickerState.displayMode)
                                    }
                                }
                                ProvideTextStyle(MaterialTheme.typography.headlineSmall) {
                                    CompositionLocalProvider(LocalContentColor provides colors.headlineContentColor) {
                                        DatePickerDefaults.DatePickerHeadline(
                                            selectedDateMillis = pickerState.selectedDateMillis,
                                            displayMode = pickerState.displayMode,
                                            dateFormatter = remember { DatePickerDefaults.dateFormatter() },
                                            modifier = Modifier.padding(top = 12.dp),
                                        )
                                    }
                                }
                            }
                            DatePicker(state = pickerState, title = null, headline = null, showModeToggle = false)
                        }
                    } else {
                        DatePicker(
                            state = pickerState,
                            showModeToggle = pickerFitsDialogWidth(),
                        )
                    }
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

/**
 * Material's calendar starts its weeks on the first weekday of its locale's region. The display
 * `locale` is often a bare language, such as Laravel's `en`, whose region would start every week
 * on Sunday, so the first weekday comes from `first_day_of_week` or else from the device's locale,
 * which also carries Android's own first-day-of-week setting as its `fw` keyword.
 */
internal fun datePickerCalendarLocale(displayLocale: String, firstDayOfWeek: String, device: Locale): Locale {
    val display = displayLocale.takeIf(String::isNotEmpty)?.let(Locale::forLanguageTag) ?: device
    val firstDay = DayOfWeek.entries.firstOrNull { it.name.equals(firstDayOfWeek, ignoreCase = true) }
        ?: WeekFields.of(device).firstDayOfWeek
    return Locale.Builder()
        .setLocale(display)
        .setUnicodeLocaleKeyword("fw", firstDay.name.take(3).lowercase(Locale.ROOT))
        .build()
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
