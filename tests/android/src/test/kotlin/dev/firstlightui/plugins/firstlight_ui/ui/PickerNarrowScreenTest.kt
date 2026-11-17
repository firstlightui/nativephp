package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.material3.MaterialTheme
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.test.ComposeUiTest
import androidx.compose.ui.test.ExperimentalTestApi
import androidx.compose.ui.test.assertIsDisplayed
import androidx.compose.ui.test.getUnclippedBoundsInRoot
import androidx.compose.ui.test.onNodeWithText
import androidx.compose.ui.test.runComposeUiTest
import androidx.compose.ui.unit.Density
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.width
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.plugins.native_ui.NativeUITheme
import org.junit.Assert.assertTrue
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import org.robolectric.annotation.Config
import org.robolectric.annotation.GraphicsMode

/** A 320 dp phone, such as a Pixel 3 at its largest display size, with large text. */
@OptIn(ExperimentalTestApi::class)
@RunWith(RobolectricTestRunner::class)
@GraphicsMode(GraphicsMode.Mode.NATIVE)
@Config(qualifiers = "w320dp-h640dp")
class PickerNarrowScreenTest {
    @Test
    fun `the date picker dialog keeps Confirm on a 320 dp screen`() = runComposeUiTest {
        val state = DatePickerRendererState(node(mapOf("value" to "2026-08-04", "min" to "0001-01-01", "max" to "9999-12-31")))
        state.open()
        render {
            FirstlightDatePickerControl(state, NativeUITheme.light, onOpen = { true }, onCancel = {}, onSelect = {}, onConfirm = {})
        }

        assertConfirmOnScreen()
    }

    @Test
    fun `the time picker dialog keeps Confirm and AM PM on a 320 dp screen`() = runComposeUiTest {
        // Without a locale the picker follows the system clock setting; this one is 12-hour.
        val context = androidx.test.core.app.ApplicationProvider.getApplicationContext<android.content.Context>()
        android.provider.Settings.System.putString(context.contentResolver, android.provider.Settings.System.TIME_12_24, "12")
        val state = TimePickerRendererState(node(mapOf("value" to "14:30", "locale" to "")))
        state.open()
        render {
            FirstlightTimePickerControl(state, NativeUITheme.light, onOpen = { true }, onCancel = {}, onSelect = {}, onConfirm = {})
        }

        assertConfirmOnScreen()
        // The 12-hour AM/PM toggle sits at the entry's trailing edge.
        val pm = onNodeWithText("PM").assertIsDisplayed().getUnclippedBoundsInRoot()
        assertTrue("PM ends at ${pm.right}, past the 320 dp screen", pm.right <= 320.dp)
        // Material's toggle is 52 dp wide; a row too narrow for the entry squeezes it first.
        assertTrue("the AM/PM toggle is squeezed to ${pm.width}", pm.width >= 52.dp)
    }

    private fun ComposeUiTest.render(content: @androidx.compose.runtime.Composable () -> Unit) {
        setContent {
            CompositionLocalProvider(LocalDensity provides Density(LocalDensity.current.density, 1.3f)) {
                MaterialTheme(content = content)
            }
        }
        waitForIdle()
    }

    private fun ComposeUiTest.assertConfirmOnScreen() {
        val confirm = onNodeWithText("Confirm").assertIsDisplayed()
        val bounds = confirm.getUnclippedBoundsInRoot()
        assertTrue("Confirm ends at ${bounds.right}, past the 320 dp screen", bounds.right <= 320.dp)
    }

    private fun node(values: Map<String, Any>) = NativeUINode(
        id = 7,
        props = GenericProps(
            mapOf(
                "has_value" to true,
                "label" to "Appointment",
                "locale" to "en-AU",
                "timezone" to "Australia/Sydney",
                "on_change" to 41,
            ) + values,
        ),
    )
}
