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
import org.junit.Assert.assertTrue
import org.junit.Rule
import org.junit.Test

class StatusLabelContractTest {
    @Test
    fun `renderer configuration decodes static text and accessibility`() {
        val configuration = StatusLabelRendererConfiguration(node())

        assertEquals("Awaiting review", configuration.label)
        assertEquals(StatusLabelTone.Warning, configuration.tone)
        assertEquals("Referral status: awaiting review", configuration.accessibilityLabel)
        assertEquals("Updated by the referrals team", configuration.accessibilityHint)
        assertFalse(configuration.isInteractive)
    }

    @Test
    fun `malformed native tone falls back to neutral without crashing`() {
        assertEquals(
            StatusLabelTone.Neutral,
            StatusLabelRendererConfiguration(node(tone = "paused")).tone,
        )
    }

    @Test
    fun `server publication updates display metadata by stable node id`() {
        val state = StatusLabelRendererState(node())

        assertTrue(state.serverPublished(NativeUITree(node(label = "Ready", tone = "success"))))
        assertEquals("Ready", state.configuration.label)
        assertEquals(StatusLabelTone.Success, state.configuration.tone)
    }

    @Test
    fun `every tone resolves to opaque contrast safe colours`() {
        StatusLabelTone.entries.forEach { tone ->
            val colours = resolveStatusLabelTokenColors(NativeUITheme.light, tone)

            assertEquals(1f, colours.background.alpha)
            assertEquals(1f, colours.foreground.alpha)
            assertTrue("$tone contrast", statusLabelContrastRatio(colours.foreground, colours.background) >= 4.5f)
        }
    }

    @Test
    fun `screen reader text uses the override and supplementary hint`() {
        assertEquals(
            "Referral status: awaiting review. Updated by the referrals team",
            firstlightStatusLabelDescription(
                label = "Awaiting review",
                accessibilityLabel = "Referral status: awaiting review",
                accessibilityHint = "Updated by the referrals team",
            ),
        )
        assertEquals(
            "Draft",
            firstlightStatusLabelDescription(
                label = "Draft",
                accessibilityLabel = "",
                accessibilityHint = "",
            ),
        )
    }

    private fun node(
        label: String = "Awaiting review",
        tone: String = "warning",
    ) = NativeUINode(
        id = 7,
        props = GenericProps(
            mapOf(
                "label" to label,
                "tone" to tone,
                "a11y_label" to "Referral status: awaiting review",
                "a11y_hint" to "Updated by the referrals team",
            ),
        ),
    )
}

class StatusLabelScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test
    fun light() = snapshot(dark = false)

    @Test
    fun dark() = snapshot(dark = true)

    private fun snapshot(dark: Boolean) {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
                val tokens = if (dark) NativeUITheme.dark else NativeUITheme.light
                Surface(modifier = Modifier.fillMaxSize()) {
                    Column(
                        modifier = Modifier.padding(16.dp),
                        verticalArrangement = androidx.compose.foundation.layout.Arrangement.spacedBy(12.dp),
                    ) {
                        StatusLabelTone.entries.forEach { tone ->
                            FirstlightStatusLabelControl(
                                label = if (tone == StatusLabelTone.Warning) {
                                    "Awaiting review from the referrals team"
                                } else {
                                    tone.name
                                },
                                colors = resolveStatusLabelTokenColors(tokens, tone),
                                accessibilityDescription = "${tone.name} status",
                            )
                        }
                    }
                }
            }
        }
    }
}

class StatusLabelLargeTextScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2.0f))

    @Test
    fun fontScaleTwo() {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = darkColorScheme()) {
                val tokens = NativeUITheme.dark
                Surface(modifier = Modifier.fillMaxSize()) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        FirstlightStatusLabelControl(
                            label = "Awaiting review from the referrals team",
                            colors = resolveStatusLabelTokenColors(tokens, StatusLabelTone.Warning),
                            accessibilityDescription = "Referral status: awaiting review",
                        )
                    }
                }
            }
        }
    }
}
