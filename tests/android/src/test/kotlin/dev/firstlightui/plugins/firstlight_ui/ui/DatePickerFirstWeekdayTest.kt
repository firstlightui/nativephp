package dev.firstlightui.plugins.firstlight_ui.ui

import android.content.res.Configuration
import androidx.compose.material3.MaterialTheme
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.test.ExperimentalTestApi
import androidx.compose.ui.test.getUnclippedBoundsInRoot
import androidx.compose.ui.test.onAllNodesWithContentDescription
import androidx.compose.ui.test.runComposeUiTest
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.plugins.native_ui.NativeUITheme
import java.util.Locale
import org.junit.Assert.assertEquals
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import org.robolectric.annotation.Config
import org.robolectric.annotation.GraphicsMode

/** Which weekday the open calendar's first column shows, for a device locale and picker props. */
@OptIn(ExperimentalTestApi::class)
@RunWith(RobolectricTestRunner::class)
@GraphicsMode(GraphicsMode.Mode.NATIVE)
@Config(qualifiers = "w411dp-h891dp")
class DatePickerFirstWeekdayTest {
    @Test
    fun `a bare language locale follows the device region`() {
        // Laravel's default `en` names no region; on its own it would start weeks on Sunday.
        assertEquals("Monday", firstColumn(device = "en-AU", props = mapOf("locale" to "en")))
        assertEquals("Sunday", firstColumn(device = "en-US", props = mapOf("locale" to "en")))
    }

    @Test
    fun `the device's own first-day-of-week setting wins over its region`() {
        // Android 13+ carries Settings > Regional preferences > First day of week as `fw`.
        assertEquals("Monday", firstColumn(device = "en-US-u-fw-mon", props = mapOf("locale" to "en-US")))
    }

    @Test
    fun `an authored first day of week overrides the device`() {
        assertEquals(
            "Saturday",
            firstColumn(device = "en-AU", props = mapOf("locale" to "en-AU", "first_day_of_week" to "saturday")),
        )
        assertEquals("Sunday", firstColumn(device = "en-AU", props = mapOf("first_day_of_week" to "sunday")))
    }

    @Test
    @Config(qualifiers = "w915dp-h387dp")
    fun `the landscape layout beside the header follows the same first weekday`() {
        assertEquals("Monday", firstColumn(device = "en-AU", props = mapOf("locale" to "en")))
        assertEquals("Saturday", firstColumn(device = "en-AU", props = mapOf("first_day_of_week" to "saturday")))
    }

    private fun firstColumn(device: String, props: Map<String, Any>): String {
        var first = ""
        runComposeUiTest {
            val state = DatePickerRendererState(node(props))
            state.open()
            setContent {
                val platform = LocalConfiguration.current
                val deviceConfiguration = Configuration(platform).apply { setLocale(Locale.forLanguageTag(device)) }
                CompositionLocalProvider(LocalConfiguration provides deviceConfiguration) {
                    MaterialTheme {
                        FirstlightDatePickerControl(state, NativeUITheme.light, onOpen = { true }, onCancel = {}, onSelect = {}, onConfirm = {})
                    }
                }
            }
            waitForIdle()
            // Material labels each weekday column header with the day's full English name here.
            first = WEEKDAYS.minBy { day ->
                onAllNodesWithContentDescription(day, useUnmergedTree = true)[0].getUnclippedBoundsInRoot().left.value
            }
        }
        return first
    }

    private fun node(values: Map<String, Any>) = NativeUINode(
        id = 7,
        type = DatePickerRendererState.NODE_TYPE,
        props = GenericProps(
            mapOf(
                "has_value" to true,
                "value" to "2026-11-18",
                "label" to "Appointment",
                "timezone" to "Australia/Sydney",
                "on_change" to 41,
            ) + values,
        ),
    )

    private companion object {
        val WEEKDAYS = listOf("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday")
    }
}
