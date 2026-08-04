package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Row
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
import org.junit.Assert.assertTrue
import org.junit.Rule
import org.junit.Test

class BadgeContractTest {
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
            MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
                val tokens = if (dark) NativeUITheme.dark else NativeUITheme.light
                Surface(modifier = Modifier.fillMaxSize()) {
                    Row(modifier = Modifier.padding(16.dp)) {
                        StatusLabelTone.entries.forEach { tone ->
                            FirstlightBadgeControl(
                                label = if (tone == StatusLabelTone.Danger) "99+" else "New",
                                colors = resolveStatusLabelTokenColors(tokens, tone),
                                accessibilityDescription = "${tone.name} badge",
                                modifier = Modifier.padding(4.dp),
                            )
                        }
                    }
                }
            }
        }
    }
}
