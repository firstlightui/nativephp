package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import app.cash.paparazzi.DeviceConfig
import app.cash.paparazzi.Paparazzi
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import com.nativephp.plugins.native_ui.NativeUITheme
import java.time.LocalTime
import java.util.Locale
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Rule
import org.junit.Test

class TimePickerTest {
    @Test
    fun `configuration decodes explicit null and display context`() {
        val configuration = TimePickerRendererConfiguration(node(hasValue = false, value = ""))

        assertFalse(configuration.hasValue)
        assertNull(configuration.acceptedValue)
        assertEquals("en-AU", configuration.locale)
        assertEquals("Australia/Sydney", configuration.timezone)
        assertEquals("Confirm", configuration.confirmLabel)
        assertEquals("Cancel", configuration.cancelLabel)
        assertEquals("Appointment time", configuration.accessibilityLabel)
    }

    @Test
    fun `null seed is the supplied current minute`() {
        val state = TimePickerRendererState(node(hasValue = false, value = ""))

        assertTrue(state.open(LocalTime.of(7, 5, 59)))
        assertEquals("07:05", state.draft)
    }

    @Test
    fun `confirm emits canonical change without optimistically accepting it`() {
        val state = TimePickerRendererState(node(value = "14:30"))
        state.open(LocalTime.of(7, 5))
        state.userSelected("14:45")

        assertEquals(TimePickerRendererEvent.Change(41, 7, "14:45"), state.confirm())
        assertEquals("14:30", state.configuration.acceptedValue)
        assertFalse(state.isPresented)

        assertTrue(state.open(LocalTime.of(7, 6)))
        assertEquals("14:30", state.draft)
    }

    @Test
    fun `cancel and confirming the accepted value publish nothing`() {
        val state = TimePickerRendererState(node(value = "14:30"))
        state.open()
        state.cancel()
        assertNull(state.confirm())

        state.open()
        assertNull(state.confirm())
    }

    @Test
    fun `disabled or callbackless triggers are inert`() {
        assertFalse(TimePickerRendererState(node(disabled = true)).open())
        assertFalse(TimePickerRendererState(node(callback = 0)).open())
    }

    @Test
    fun `accepted presentation changes dismiss and discard the draft`() {
        val state = TimePickerRendererState(node(value = "14:30"))
        state.open()
        state.userSelected("14:45")

        state.serverPublished(tree(node(value = "15:00")))

        assertFalse(state.isPresented)
        assertNull(state.draft)
        assertEquals("15:00", state.configuration.acceptedValue)
    }

    @Test
    fun `canonical helper retains leading zeroes`() {
        assertEquals("00:00", LocalTime.MIDNIGHT.toCanonicalTime())
        assertEquals("07:05", LocalTime.of(7, 5).toCanonicalTime())
        assertEquals("23:59", LocalTime.of(23, 59).toCanonicalTime())
    }

    @Test
    fun `explicit locale controls hour cycle while omission follows the system`() {
        assertFalse(uses24HourClock(Locale.US, explicitLocale = true, systemUses24HourClock = true))
        assertTrue(uses24HourClock(Locale.UK, explicitLocale = true, systemUses24HourClock = false))
        assertFalse(uses24HourClock(Locale.UK, explicitLocale = false, systemUses24HourClock = false))
        assertTrue(uses24HourClock(Locale.US, explicitLocale = false, systemUses24HourClock = true))
    }

    private fun node(
        hasValue: Boolean = true,
        value: String = "14:30",
        disabled: Boolean = false,
        callback: Int = 41,
    ) = NativeUINode(
        id = 7,
        props = GenericProps(
            mapOf(
                "has_value" to hasValue,
                "value" to value,
                "label" to "Appointment time",
                "placeholder" to "Choose a time",
                "helper" to "Clinic local time",
                "required" to true,
                "locale" to "en-AU",
                "timezone" to "Australia/Sydney",
                "on_change" to callback,
                "disabled" to disabled,
            ),
        ),
    )

    private fun tree(target: NativeUINode) = NativeUITree(
        root = NativeUINode(id = 99, props = GenericProps(), children = listOf(target)),
    )
}

class TimePickerScreenshotTest {
    @get:Rule val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test fun lightAccepted() = snapshot(dark = false)
    @Test fun darkError() = snapshot(dark = true, error = "Choose an available time")
    @Test fun disabled() = snapshot(dark = false, disabled = true)

    private fun snapshot(dark: Boolean, error: String = "", disabled: Boolean = false) {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
                Surface(modifier = Modifier.fillMaxSize()) {
                    FirstlightTimePickerControl(
                        state = TimePickerRendererState(screenshotNode(error, disabled)),
                        tokens = if (dark) NativeUITheme.dark else NativeUITheme.light,
                        onOpen = { false },
                        onCancel = {},
                        onSelect = {},
                        onConfirm = {},
                        modifier = Modifier.padding(16.dp),
                    )
                }
            }
        }
    }
}

class TimePickerLargeTextScreenshotTest {
    @get:Rule val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2.0f))

    @Test fun fontScaleTwo() {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = lightColorScheme()) {
                Surface(modifier = Modifier.fillMaxSize()) {
                    FirstlightTimePickerControl(
                        state = TimePickerRendererState(screenshotNode()),
                        tokens = NativeUITheme.light,
                        onOpen = { false },
                        onCancel = {},
                        onSelect = {},
                        onConfirm = {},
                        modifier = Modifier.padding(16.dp),
                    )
                }
            }
        }
    }
}

private fun screenshotNode(error: String = "", disabled: Boolean = false) = NativeUINode(
    id = 7,
    props = GenericProps(
        mapOf(
            "has_value" to true,
            "value" to "14:30",
            "label" to "Appointment time",
            "placeholder" to "Choose a time",
            "helper" to "Clinic local time",
            "error" to error,
            "required" to true,
            "locale" to "en-AU",
            "timezone" to "Australia/Sydney",
            "on_change" to 41,
            "disabled" to disabled,
        ),
    ),
)
