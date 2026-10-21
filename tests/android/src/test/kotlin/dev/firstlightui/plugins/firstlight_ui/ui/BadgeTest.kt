package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Surface
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import app.cash.paparazzi.DeviceConfig
import app.cash.paparazzi.Paparazzi
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Rule
import org.junit.Test

class BadgeContractTest {
    @Test fun `warning tokens decode and republish without changing display metadata`() {
        val original = node(tone = "warning")
        val custom = original.copy(props = GenericProps(mapOf(
            "label" to "3", "tone" to "warning", "a11y_label" to "3 unread messages", "a11y_hint" to "Open inbox",
            "warning_color" to "#92400E", "on_warning_color" to "#FFFFFF",
            "dark_warning_color" to "#FDE68A", "dark_on_warning_color" to "#292524",
        )))
        val state = BadgeRendererState(original)
        assertTrue(state.serverPublished(NativeUITree(custom)))
        val palette = state.configuration.warningColors
        assertEquals(FirstlightWarningColors(custom), palette)
        assertEquals(androidx.compose.ui.graphics.Color(0xFF92400E), palette.resolve(false).first)
        assertEquals(androidx.compose.ui.graphics.Color(0xFFFDE68A), palette.resolve(true).first)
        assertEquals("3 unread messages", state.configuration.accessibilityLabel)
        assertFalse(state.serverPublished(NativeUITree(custom)))
        assertTrue(state.serverPublished(NativeUITree(original)))
        assertEquals(FirstlightWarningColors(), state.configuration.warningColors)
    }

    @Test fun `configuration decodes static display and accessibility`() {
        val configuration = BadgeRendererConfiguration(node())
        assertEquals("3", configuration.label)
        assertEquals(StatusLabelTone.Danger, configuration.tone)
        assertEquals("3 unread messages", configuration.accessibilityLabel)
        assertEquals("3 unread messages. Open inbox", firstlightBadgeDescription("3", configuration.accessibilityLabel, configuration.accessibilityHint))
    }

    @Test fun `malformed tone falls back and stable publication updates`() {
        assertEquals(StatusLabelTone.Neutral, BadgeRendererConfiguration(node(tone = "other")).tone)
        val state = BadgeRendererState(node())
        assertTrue(state.serverPublished(NativeUITree(node(label = "New", tone = "info"))))
        assertEquals("New", state.configuration.label)
        assertFalse(state.serverPublished(NativeUITree(node(label = "New", tone = "info"))))
    }

    private fun node(label: String = "3", tone: String = "danger") = NativeUINode(
        id = 17,
        props = GenericProps(mapOf("label" to label, "tone" to tone, "a11y_label" to "3 unread messages", "a11y_hint" to "Open inbox")),
    )
}

class BadgeScreenshotTest {
    @get:Rule val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test fun light() = snapshot(false)
    @Test fun dark() = snapshot(true)

    private fun snapshot(dark: Boolean) {
        paparazzi.snapshot {
            FirstlightTestAppearance(dark) {
                Surface(modifier = Modifier.fillMaxSize()) {
                    Row(modifier = Modifier.padding(16.dp)) {
                        StatusLabelTone.entries.forEach { tone ->
                            FirstlightBadgeRenderer.Render(
                                node = NativeUINode(id = tone.ordinal + 1, props = GenericProps(mapOf(
                                    "label" to if (tone == StatusLabelTone.Danger) "99+" else "New",
                                    "tone" to tone.name.lowercase(),
                                ))),
                                modifier = Modifier.padding(4.dp),
                            )
                        }
                    }
                }
            }
        }
    }
}
