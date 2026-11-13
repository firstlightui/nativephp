package dev.firstlightui.plugins.firstlight_ui.ui

import android.view.HapticFeedbackConstants
import androidx.compose.material3.MaterialTheme
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.SemanticsActions
import androidx.compose.ui.test.ExperimentalTestApi
import androidx.compose.ui.test.onNodeWithContentDescription
import androidx.compose.ui.test.performClick
import androidx.compose.ui.test.performSemanticsAction
import androidx.compose.ui.test.performTouchInput
import androidx.compose.ui.test.runComposeUiTest
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeElementBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import org.robolectric.RuntimeEnvironment

class HapticMappingTest {
    @Test fun `each meaning plays its own constant on Android 14`() {
        val expected = mapOf(
            FirstlightHaptic.Success to HapticFeedbackConstants.CONFIRM,
            FirstlightHaptic.Warning to HapticFeedbackConstants.REJECT,
            FirstlightHaptic.Error to HapticFeedbackConstants.REJECT,
            FirstlightHaptic.Selection to HapticFeedbackConstants.SEGMENT_TICK,
            FirstlightHaptic.Tick to HapticFeedbackConstants.SEGMENT_FREQUENT_TICK,
            FirstlightHaptic.ToggleOn to HapticFeedbackConstants.TOGGLE_ON,
            FirstlightHaptic.ToggleOff to HapticFeedbackConstants.TOGGLE_OFF,
            FirstlightHaptic.Press to HapticFeedbackConstants.VIRTUAL_KEY,
            FirstlightHaptic.Keypress to HapticFeedbackConstants.KEYBOARD_TAP,
            FirstlightHaptic.ThresholdActivate to HapticFeedbackConstants.GESTURE_THRESHOLD_ACTIVATE,
            FirstlightHaptic.ThresholdDeactivate to HapticFeedbackConstants.GESTURE_THRESHOLD_DEACTIVATE,
            FirstlightHaptic.DragStart to HapticFeedbackConstants.DRAG_START,
            FirstlightHaptic.Start to HapticFeedbackConstants.GESTURE_START,
            FirstlightHaptic.Stop to HapticFeedbackConstants.GESTURE_END,
        )

        expected.forEach { (haptic, constant) ->
            assertEquals(haptic.name, constant, FirstlightHaptics.feedbackConstant(haptic, sdk = 34))
        }
        assertNull(FirstlightHaptics.feedbackConstant(FirstlightHaptic.None, sdk = 34))
    }

    @Test fun `Android 10 falls back as androidx does instead of going silent`() {
        assertEquals(HapticFeedbackConstants.VIRTUAL_KEY, FirstlightHaptics.feedbackConstant(FirstlightHaptic.Success, sdk = 29))
        assertEquals(HapticFeedbackConstants.LONG_PRESS, FirstlightHaptics.feedbackConstant(FirstlightHaptic.Error, sdk = 29))
        assertEquals(HapticFeedbackConstants.CONTEXT_CLICK, FirstlightHaptics.feedbackConstant(FirstlightHaptic.ToggleOn, sdk = 33))
        assertEquals(HapticFeedbackConstants.CLOCK_TICK, FirstlightHaptics.feedbackConstant(FirstlightHaptic.ToggleOff, sdk = 33))
        assertEquals(HapticFeedbackConstants.CLOCK_TICK, FirstlightHaptics.feedbackConstant(FirstlightHaptic.Tick, sdk = 33))
        assertEquals(HapticFeedbackConstants.CONFIRM, FirstlightHaptics.feedbackConstant(FirstlightHaptic.Success, sdk = 30))
    }

    @Test fun `wire names match the PHP Haptic enum`() {
        assertEquals(FirstlightHaptic.ThresholdActivate, FirstlightHaptic.fromWire("threshold-activate"))
        assertEquals(FirstlightHaptic.ToggleOff, FirstlightHaptic.fromWire("toggle-off"))
        assertNull(FirstlightHaptic.fromWire("buzz"))
    }
}

@OptIn(ExperimentalTestApi::class)
@RunWith(RobolectricTestRunner::class)
class HapticRendererTest {
    @After fun reset() {
        NativeElementBridge.onPressEvent = null
        FirstlightHaptics.setEnabled(RuntimeEnvironment.getApplication(), true)
    }

    @Test fun `a switch plays toggle on or off as the user flips it`() = runComposeUiTest {
        val played = mutableListOf<FirstlightHaptic>()
        setContent {
            CompositionLocalProvider(LocalFirstlightHaptics provides FirstlightHapticPlayer { played += it }) {
                MaterialTheme { SwitchRenderer.Render(switchNode(value = false), Modifier) }
            }
        }

        onNodeWithContentDescription("Receive notifications").performClick()
        assertEquals(listOf(FirstlightHaptic.ToggleOn), played)
    }

    @Test fun `haptic none keeps a control silent`() = runComposeUiTest {
        val played = mutableListOf<FirstlightHaptic>()
        setContent {
            CompositionLocalProvider(LocalFirstlightHaptics provides FirstlightHapticPlayer { played += it }) {
                MaterialTheme { SwitchRenderer.Render(switchNode(value = true, haptic = "none"), Modifier) }
            }
        }

        onNodeWithContentDescription("Receive notifications").performClick()
        assertEquals(emptyList<FirstlightHaptic>(), played)
    }

    @Test fun `a button plays its haptic on touch down and still sends one press`() = runComposeUiTest {
        val played = mutableListOf<FirstlightHaptic>()
        val presses = mutableListOf<Int>()
        NativeElementBridge.onPressEvent = { callbackId, _ -> presses += callbackId }
        setContent {
            CompositionLocalProvider(LocalFirstlightHaptics provides FirstlightHapticPlayer { played += it }) {
                MaterialTheme { FirstlightButtonRenderer.Render(buttonNode(haptic = "keypress"), Modifier) }
            }
        }

        val key = onNodeWithContentDescription("Digit 1")
        key.performTouchInput { down(center) }
        // The haptic lands with the finger, before the button is released.
        assertEquals(listOf(FirstlightHaptic.Keypress), played)
        assertEquals(emptyList<Int>(), presses)

        key.performTouchInput { up() }
        assertEquals(listOf(FirstlightHaptic.Keypress), played)

        // TalkBack's double tap sends the press without a touch, so no haptic.
        key.performSemanticsAction(SemanticsActions.OnClick)
        assertEquals(listOf(52), presses)
        assertEquals(listOf(FirstlightHaptic.Keypress), played)
    }

    @Test fun `a button without a haptic stays silent`() = runComposeUiTest {
        val played = mutableListOf<FirstlightHaptic>()
        setContent {
            CompositionLocalProvider(LocalFirstlightHaptics provides FirstlightHapticPlayer { played += it }) {
                MaterialTheme { FirstlightButtonRenderer.Render(buttonNode(haptic = ""), Modifier) }
            }
        }

        onNodeWithContentDescription("Digit 1").performTouchInput { down(center); up() }
        assertEquals(emptyList<FirstlightHaptic>(), played)
    }

    @Test fun `the app switch turns the view haptics off`() {
        val context = RuntimeEnvironment.getApplication()
        val view = android.view.View(context)

        FirstlightHaptics.setEnabled(context, false)
        assertEquals(false, FirstlightHaptics.perform(view, FirstlightHaptic.Success))
        assertEquals(false, FirstlightHaptics.impact(context, "medium", 1f))
    }
}

private fun switchNode(value: Boolean, haptic: String = "") = NativeUINode(
    id = 7,
    props = GenericProps(
        mapOf(
            "value" to value,
            "label" to "Notifications",
            "a11y_label" to "Receive notifications",
            "on_change" to 41,
            "haptic" to haptic,
        ),
    ),
)

private fun buttonNode(haptic: String) = NativeUINode(
    id = 12,
    type = "firstlight.button",
    props = GenericProps(mapOf("label" to "1", "variant" to "secondary", "a11y_label" to "Digit 1", "haptic" to haptic)),
    onPress = 52,
)
