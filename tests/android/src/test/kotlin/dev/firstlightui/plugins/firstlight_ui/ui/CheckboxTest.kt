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
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Assume.assumeTrue
import org.junit.Rule
import org.junit.Test

class CheckboxRendererContractTest {
    @Test
    fun configurationDecodesTheCompleteBooleanFieldContract() {
        val configuration = CheckboxRendererConfiguration(
            node(
                value = true,
                required = true,
                disabled = true,
                helper = "Read the terms first.",
                error = "Agreement is required.",
            ),
        )

        assertEquals(7, configuration.nodeId)
        assertTrue(configuration.value)
        assertEquals("I agree to the terms", configuration.label)
        assertEquals("Read the terms first.", configuration.helper)
        assertEquals("Agreement is required.", configuration.error)
        assertTrue(configuration.required)
        assertTrue(configuration.disabled)
        assertEquals("Accept the terms", configuration.accessibilityLabel)
        assertEquals("Required before continuing", configuration.accessibilityHint)
        assertEquals(41, configuration.onChangeCallback)
        assertEquals("Agreement is required.", configuration.supportingText)
    }

    @Test
    fun visibleLabelIsTheAccessibilityFallback() {
        val configuration = CheckboxRendererConfiguration(node(accessibilityLabel = ""))

        assertEquals("I agree to the terms", configuration.accessibilityLabel)
    }

    @Test
    fun proposalKeepsAcceptedValueAndDeduplicatesUntilPublication() {
        val state = CheckboxRendererState(node(value = false))

        assertEquals(
            CheckboxRendererEvent.CheckboxChange(41, 7, true),
            state.proposeChange(),
        )
        assertFalse(state.configuration.value)
        assertNull(state.proposeChange())
    }

    @Test
    fun identicalRejectedPublicationClearsPending() {
        val state = CheckboxRendererState(node(value = false))
        state.proposeChange()

        assertFalse(state.serverPublished(tree(node(value = false))))
        assertFalse(state.configuration.value)
        assertEquals(
            CheckboxRendererEvent.CheckboxChange(41, 7, true),
            state.proposeChange(),
        )
    }

    @Test
    fun acceptedAndProgrammaticPublicationsUpdateOnlyAcceptedState() {
        val state = CheckboxRendererState(node(value = false))

        assertTrue(state.serverPublished(tree(node(value = true))))
        assertTrue(state.configuration.value)
        assertEquals(
            CheckboxRendererEvent.CheckboxChange(41, 7, false),
            state.proposeChange(),
        )
        assertTrue(state.configuration.value)
    }

    @Test
    fun disabledAndMissingCallbackNeverPropose() {
        val disabled = CheckboxRendererState(node(disabled = true))
        val missingCallback = CheckboxRendererState(node(callbackId = 0))

        assertNull(disabled.proposeChange())
        assertNull(missingCallback.proposeChange())
    }

    @Test
    fun everyPublicationReplacesMetadataAndClearsPendingByStableNodeId() {
        val state = CheckboxRendererState(node(helper = "Original helper"))
        state.proposeChange()
        val published = node(
            helper = "Rejected by policy",
            error = "Agreement is still required.",
        )
        val nested = NativeUINode(
            id = 1,
            props = GenericProps(),
            children = listOf(published),
        )

        assertFalse(state.serverPublished(NativeUITree(nested)))
        assertEquals("Rejected by policy", state.configuration.helper)
        assertEquals("Agreement is still required.", state.configuration.error)
        assertEquals(
            CheckboxRendererEvent.CheckboxChange(41, 7, true),
            state.proposeChange(),
        )
    }

    @Test
    fun fieldSemanticsCombineAccessibleNameHintRequiredAndError() {
        val semantics = checkboxFieldSemantics(
            accessibilityLabel = "Accept the terms",
            accessibilityHint = "Required before continuing",
            required = true,
            error = "Agreement is required.",
        )

        assertEquals(
            "Accept the terms. Required. Required before continuing",
            semantics.contentDescription,
        )
        assertEquals("Agreement is required.", semantics.error)
    }

    private fun node(
        value: Boolean = false,
        required: Boolean = false,
        disabled: Boolean = false,
        helper: String = "Required before continuing.",
        error: String = "",
        accessibilityLabel: String = "Accept the terms",
        callbackId: Int = 41,
    ) = NativeUINode(
        id = 7,
        props = GenericProps(
            mapOf(
                "value" to value,
                "label" to "I agree to the terms",
                "helper" to helper,
                "error" to error,
                "required" to required,
                "disabled" to disabled,
                "a11y_label" to accessibilityLabel,
                "a11y_hint" to "Required before continuing",
                "on_change" to callbackId,
            ),
        ),
    )

    private fun tree(node: NativeUINode) = NativeUITree(node)
}

class CheckboxScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test fun light() = snapshot(dark = false)
    @Test fun dark() = snapshot(dark = true, value = true, required = true)
    @Test fun disabled() = snapshot(dark = false, value = true, disabled = true)
    @Test fun error() = snapshot(dark = false, error = "Agreement is required.")

    private fun snapshot(
        dark: Boolean,
        value: Boolean = false,
        required: Boolean = false,
        disabled: Boolean = false,
        error: String = "",
    ) {
        assumeTrue(System.getenv("FIRSTLIGHT_VERIFY_CHECKBOX_SNAPSHOTS") == "1")
        paparazzi.snapshot {
            MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
                Surface(modifier = Modifier.fillMaxSize()) {
                    FirstlightCheckboxControl(
                        value = value,
                        label = "I agree to the terms",
                        helper = "Required before continuing.",
                        error = error,
                        required = required,
                        disabled = disabled,
                        accessibilityLabel = "Accept the terms",
                        accessibilityHint = "Required before continuing",
                        tokens = if (dark) NativeUITheme.dark else NativeUITheme.light,
                        onProposal = {},
                        modifier = Modifier.padding(16.dp),
                    )
                }
            }
        }
    }
}

class CheckboxLargeTextScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2.0f))

    @Test
    fun fontScaleTwo() {
        assumeTrue(System.getenv("FIRSTLIGHT_VERIFY_CHECKBOX_SNAPSHOTS") == "1")
        paparazzi.snapshot {
            MaterialTheme(colorScheme = darkColorScheme()) {
                Surface(modifier = Modifier.fillMaxSize()) {
                    FirstlightCheckboxControl(
                        value = true,
                        label = "I agree to the terms and the privacy policy for this account",
                        helper = "Required before continuing.",
                        error = "",
                        required = true,
                        disabled = false,
                        accessibilityLabel = "Accept the terms and privacy policy",
                        accessibilityHint = "Required before continuing",
                        tokens = NativeUITheme.dark,
                        onProposal = {},
                        modifier = Modifier.padding(16.dp),
                    )
                }
            }
        }
    }
}
