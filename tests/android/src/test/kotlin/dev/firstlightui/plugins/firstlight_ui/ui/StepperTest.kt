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
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Assume.assumeTrue
import org.junit.Rule
import org.junit.Test

class StepperRendererContractTest {
    @Test fun `configuration decodes accepted display and metadata`() {
        val configuration = StepperRendererConfiguration(stepperNode())

        assertEquals(7, configuration.nodeId)
        assertEquals("5", configuration.displayValue)
        assertEquals("Quantity", configuration.label)
        assertEquals("Adjust one at a time", configuration.helper)
        assertEquals("Medication quantity", configuration.accessibilityLabel)
        assertEquals("Use increase or decrease", configuration.accessibilityHint)
        assertTrue(configuration.canPressDecrement)
        assertTrue(configuration.canPressIncrement)
    }

    @Test fun `proposal emits PRESS without optimistically changing accepted display`() {
        val state = StepperRendererState(stepperNode())

        val event = state.increment()

        assertEquals(StepperRendererEvent(42, 7), event)
        assertEquals("PRESS", event?.wireName)
        assertEquals("5", state.configuration.displayValue)
        assertTrue(state.isAwaitingPublication)
        assertNull(state.increment())
        assertNull(state.decrement())
    }

    @Test fun `identical publication releases stale tap suppression`() {
        val state = StepperRendererState(stepperNode())
        state.increment()

        assertFalse(state.serverPublished(stepperTree(stepperNode())))
        assertFalse(state.isAwaitingPublication)
        assertEquals(StepperRendererEvent(42, 7), state.increment())
    }

    @Test fun `nested changed publication reconciles accepted display`() {
        val state = StepperRendererState(stepperNode())
        state.decrement()

        assertTrue(state.serverPublished(stepperTree(stepperNode(displayValue = "4"))))
        assertEquals("4", state.configuration.displayValue)
        assertFalse(state.isAwaitingPublication)
    }

    @Test fun `missing stable node keeps proposal pending`() {
        val state = StepperRendererState(stepperNode())
        state.increment()

        assertFalse(state.serverPublished(NativeUITree(NativeUINode(id = 99, props = GenericProps()))))
        assertTrue(state.isAwaitingPublication)
    }

    @Test fun `disabled bounds and missing callbacks are inert`() {
        val disabled = StepperRendererState(stepperNode(disabled = true))
        val minimum = StepperRendererState(stepperNode(canDecrement = false))
        val maximum = StepperRendererState(stepperNode(canIncrement = false))
        val callbackless = StepperRendererState(stepperNode(decrementCallback = 0, incrementCallback = 0))

        assertNull(disabled.decrement())
        assertNull(disabled.increment())
        assertNull(minimum.decrement())
        assertNotNull(minimum.increment())
        assertNotNull(maximum.decrement())
        assertNull(maximum.increment())
        assertNull(callbackless.decrement())
        assertNull(callbackless.increment())
    }
}

class StepperControlScreenshotTest {
    @get:Rule val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test fun `light dark error and disabled evidence is controller gated`() {
        assumeTrue(System.getenv("FIRSTLIGHT_VERIFY_STEPPER_SNAPSHOTS") == "1")
        snapshot(dark = false)
        snapshot(dark = true)
        snapshot(dark = false, error = "Quantity is unavailable")
        snapshot(dark = false, disabled = true)
    }

    private fun snapshot(dark: Boolean, error: String = "", disabled: Boolean = false) {
        paparazzi.snapshot { stepperScreenshotContent(dark, error, disabled) }
    }
}

class StepperControlLargeTextScreenshotTest {
    @get:Rule val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2f))

    @Test fun `large text evidence is controller gated`() {
        assumeTrue(System.getenv("FIRSTLIGHT_VERIFY_STEPPER_SNAPSHOTS") == "1")
        paparazzi.snapshot { stepperScreenshotContent(dark = false) }
    }
}

@androidx.compose.runtime.Composable
private fun stepperScreenshotContent(dark: Boolean, error: String = "", disabled: Boolean = false) {
    MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
        Surface(modifier = Modifier.fillMaxSize()) {
            FirstlightStepperControl(
                state = StepperRendererState(stepperNode(disabled = disabled, error = error)),
                tokens = if (dark) NativeUITheme.dark else NativeUITheme.light,
                onDecrement = {},
                onIncrement = {},
                modifier = Modifier.padding(16.dp),
            )
        }
    }
}

private fun stepperNode(
    displayValue: String = "5",
    disabled: Boolean = false,
    canDecrement: Boolean = true,
    canIncrement: Boolean = true,
    decrementCallback: Int = 41,
    incrementCallback: Int = 42,
    error: String = "",
) = NativeUINode(
    id = 7,
    props = GenericProps(
        mapOf(
            "display_value" to displayValue,
            "label" to "Quantity",
            "helper" to "Adjust one at a time",
            "error" to error,
            "disabled" to disabled,
            "can_decrement" to canDecrement,
            "can_increment" to canIncrement,
            "on_decrement" to decrementCallback,
            "on_increment" to incrementCallback,
            "a11y_label" to "Medication quantity",
            "a11y_hint" to "Use increase or decrease",
        ),
    ),
)

private fun stepperTree(target: NativeUINode) = NativeUITree(
    root = NativeUINode(id = 99, props = GenericProps(), children = listOf(target)),
)
