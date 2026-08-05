package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Column
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
import org.junit.Rule
import org.junit.Test

class CalloutContractTest {
    @Test
    fun `configuration decodes message tone action and accessibility`() {
        val configuration = CalloutRendererConfiguration(calloutNode())

        assertEquals("Your changes have not been submitted.", configuration.message)
        assertEquals(CalloutTone.Warning, configuration.tone)
        assertEquals("Review changes", configuration.actionLabel)
        assertEquals("Submission warning", configuration.accessibilityLabel)
        assertEquals("Review the form before continuing", configuration.accessibilityHint)
        assertEquals(41, configuration.callbackId)
        assertTrue(configuration.hasAction)
    }

    @Test
    fun `malformed native data falls back and suppresses incomplete actions`() {
        assertEquals(CalloutTone.Info, CalloutRendererConfiguration(calloutNode(tone = "critical")).tone)

        val labelOnly = CalloutRendererConfiguration(calloutNode(callbackId = 0))
        val callbackOnly = CalloutRendererConfiguration(calloutNode(actionLabel = ""))
        assertFalse(labelOnly.hasAction)
        assertNull(labelOnly.pressEvent())
        assertFalse(callbackOnly.hasAction)
        assertNull(callbackOnly.pressEvent())
    }

    @Test
    fun `press emits the standard event and programmatic updates do not`() {
        val state = CalloutRendererState(calloutNode())

        assertEquals(CalloutRendererEvent.Press(41, 7), state.configuration.pressEvent())
        assertTrue(state.serverPublished(NativeUITree(calloutNode(
            message = "The form is ready.",
            tone = "success",
            actionLabel = "",
            callbackId = 0,
        ))))
        assertEquals("The form is ready.", state.configuration.message)
        assertEquals(CalloutTone.Success, state.configuration.tone)
        assertNull(state.configuration.pressEvent())
    }

    @Test
    fun `tone owns distinct visual and accessible semantics`() {
        assertEquals(CalloutTone.entries.size, CalloutTone.entries.map(CalloutTone::iconName).toSet().size)
        assertEquals(
            "Warning: Check the form.",
            firstlightCalloutAccessibilityLabel("Check the form.", CalloutTone.Warning, ""),
        )
        assertEquals(
            "Custom warning",
            firstlightCalloutAccessibilityLabel("Check the form.", CalloutTone.Warning, "Custom warning"),
        )
        assertEquals(48.dp, firstlightCalloutActionMinimumHeight)
    }
}

class CalloutScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test fun light() = snapshot(dark = false)
    @Test fun dark() = snapshot(dark = true)

    private fun snapshot(dark: Boolean) {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
                val tokens = if (dark) NativeUITheme.dark else NativeUITheme.light
                Surface(modifier = Modifier.fillMaxSize()) {
                    Column(
                        modifier = Modifier.padding(16.dp),
                        verticalArrangement = androidx.compose.foundation.layout.Arrangement.spacedBy(12.dp),
                    ) {
                        CalloutTone.entries.forEach { tone ->
                            FirstlightCalloutControl(
                                configuration = CalloutRendererConfiguration(calloutNode(
                                    message = if (tone == CalloutTone.Warning) {
                                        "Your changes have not been submitted. Review the form before continuing."
                                    } else {
                                        "${tone.accessibilityName} message"
                                    },
                                    tone = tone.wireName,
                                    actionLabel = if (tone == CalloutTone.Warning) "Review changes" else "",
                                    callbackId = if (tone == CalloutTone.Warning) 41 else 0,
                                )),
                                tokens = tokens,
                                onPress = {},
                            )
                        }
                    }
                }
            }
        }
    }
}

class CalloutLargeTextScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2.0f))

    @Test
    fun fontScaleTwo() {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = lightColorScheme()) {
                FirstlightCalloutControl(
                    configuration = CalloutRendererConfiguration(calloutNode()),
                    tokens = NativeUITheme.light,
                    onPress = {},
                )
            }
        }
    }
}

private fun calloutNode(
    message: String = "Your changes have not been submitted.",
    tone: String = "warning",
    actionLabel: String = "Review changes",
    callbackId: Int = 41,
) = NativeUINode(
    id = 7,
    onPress = callbackId,
    props = GenericProps(mapOf(
        "message" to message,
        "tone" to tone,
        "action_label" to actionLabel,
        "a11y_label" to "Submission warning",
        "a11y_hint" to "Review the form before continuing",
    )),
)
