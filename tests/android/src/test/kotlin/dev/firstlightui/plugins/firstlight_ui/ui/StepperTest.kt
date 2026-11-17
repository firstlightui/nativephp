package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.ui.platform.LocalLayoutDirection
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.semantics.SemanticsProperties
import androidx.compose.ui.test.ExperimentalTestApi
import androidx.compose.ui.test.SemanticsMatcher
import androidx.compose.ui.test.assert
import androidx.compose.ui.test.assertIsEnabled
import androidx.compose.ui.test.assertIsNotEnabled
import androidx.compose.ui.test.getUnclippedBoundsInRoot
import androidx.compose.ui.test.onNodeWithContentDescription
import androidx.compose.ui.test.performClick
import androidx.compose.ui.test.runComposeUiTest
import androidx.compose.ui.unit.LayoutDirection
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
import org.junit.Rule
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import org.robolectric.annotation.GraphicsMode
import org.robolectric.annotation.Config
import androidx.compose.ui.unit.Density
import androidx.compose.ui.test.ComposeUiTest
import androidx.compose.ui.platform.LocalDensity

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

/** A Pixel 3 at its largest display and font sizes: 320 dp wide, font scale 1.3. */
@OptIn(ExperimentalTestApi::class)
@RunWith(RobolectricTestRunner::class)
@GraphicsMode(GraphicsMode.Mode.NATIVE)
@Config(qualifiers = "w320dp-h640dp")
class StepperNarrowLabelTest {
    private fun ComposeUiTest.controlsRowTop(label: String): androidx.compose.ui.unit.Dp {
        setContent {
            CompositionLocalProvider(LocalDensity provides Density(LocalDensity.current.density, 1.3f)) {
                MaterialTheme {
                    FirstlightStepperControl(
                        state = StepperRendererState(stepperNode(label = label, displayValue = "1,250")),
                        tokens = NativeUITheme.light,
                        onDecrement = {},
                        onIncrement = {},
                        modifier = Modifier.padding(16.dp),
                    )
                }
            }
        }

        // The controls row is the node that announces the value.
        return onNode(SemanticsMatcher.expectValue(SemanticsProperties.StateDescription, "1,250")).getUnclippedBoundsInRoot().top
    }

    // A stacked label sits above the controls row; beside the controls it shares the row's top.
    @Test fun `a label whose longest word doesn't fit beside the controls takes its own line`() = runComposeUiTest {
        val top = controlsRowTop("Reconciliation quantity")
        assertTrue("the controls row starts at $top", top > 32.dp)
    }

    @Test fun `a label that fits stays beside the controls`() = runComposeUiTest {
        val top = controlsRowTop("Dose")
        assertTrue("the controls row starts at $top", top < 20.dp)
    }
}

@OptIn(ExperimentalTestApi::class)
@RunWith(RobolectricTestRunner::class)
class StepperControlSemanticsTest {
    @Test fun `direction actions are named buttons that follow bounds and layout direction`() {
        for (direction in listOf(LayoutDirection.Ltr, LayoutDirection.Rtl)) {
            runComposeUiTest {
                var increments = 0
                setContent {
                    CompositionLocalProvider(LocalLayoutDirection provides direction) {
                        MaterialTheme {
                            FirstlightStepperControl(
                                state = StepperRendererState(stepperNode(canDecrement = false)),
                                tokens = NativeUITheme.light,
                                onDecrement = {},
                                onIncrement = { increments++ },
                            )
                        }
                    }
                }

                val decrease = onNodeWithContentDescription("Decrease Medication quantity")
                    .assert(SemanticsMatcher.expectValue(SemanticsProperties.Role, Role.Button))
                    .assertIsNotEnabled()
                val increase = onNodeWithContentDescription("Increase Medication quantity")
                    .assert(SemanticsMatcher.expectValue(SemanticsProperties.Role, Role.Button))
                    .assertIsEnabled()
                increase.performClick()
                assertEquals(1, increments)

                val decreaseLeft = decrease.getUnclippedBoundsInRoot().left
                val increaseLeft = increase.getUnclippedBoundsInRoot().left
                assertEquals(direction == LayoutDirection.Ltr, decreaseLeft < increaseLeft)
                onNode(SemanticsMatcher.expectValue(SemanticsProperties.StateDescription, "5")).assertExists()
            }
        }
    }
}

class StepperControlScreenshotTest {
    @get:Rule val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test fun light() = paparazzi.snapshot { stepperScreenshotContent(dark = false) }

    @Test fun dark() = paparazzi.snapshot { stepperScreenshotContent(dark = true) }

    @Test fun errorAtMinimum() = paparazzi.snapshot {
        stepperScreenshotContent(dark = false, error = "Quantity is unavailable", canDecrement = false)
    }

    @Test fun disabled() = paparazzi.snapshot { stepperScreenshotContent(dark = true, disabled = true) }

    @Test fun rtl() = paparazzi.snapshot {
        CompositionLocalProvider(LocalLayoutDirection provides LayoutDirection.Rtl) {
            stepperScreenshotContent(dark = false)
        }
    }
}

class StepperControlLargeTextScreenshotTest {
    @get:Rule val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2f))

    @Test fun fontScaleTwo() = paparazzi.snapshot { stepperScreenshotContent(dark = false, displayValue = "1,250") }
}

@androidx.compose.runtime.Composable
private fun stepperScreenshotContent(
    dark: Boolean,
    error: String = "",
    disabled: Boolean = false,
    canDecrement: Boolean = true,
    displayValue: String = "5",
) {
    MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
        Surface(modifier = Modifier.fillMaxSize(), color = if (dark) NativeUITheme.dark.background else NativeUITheme.light.background) {
            FirstlightStepperControl(
                state = StepperRendererState(
                    stepperNode(displayValue = displayValue, disabled = disabled, error = error, canDecrement = canDecrement),
                ),
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
    label: String = "Quantity",
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
            "label" to label,
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
