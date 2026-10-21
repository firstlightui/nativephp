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
    fun `warning pair is decoded independently for all surfaces and both appearances`() {
        val warningNode = node().copy(props = GenericProps(mapOf(
            "warning_color" to "#92400E", "on_warning_color" to "#FFFFFF",
            "dark_warning_color" to "#FDE68A", "dark_on_warning_color" to "#292524",
        )))
        val colors = StatusLabelRendererConfiguration(warningNode).warningColors
        assertEquals(colors, CalloutRendererConfiguration(warningNode).warningColors)
        assertEquals(colors, FeedbackCenterItemConfiguration(warningNode).warningColors)
        listOf(false, true).forEach { dark ->
            val pair = colors.resolve(dark)
            val status = resolveStatusLabelTokenColors(NativeUITheme.light, StatusLabelTone.Warning, colors, dark)
            val feedback = feedbackCenterColors(FeedbackCenterTone.Warning, lightColorScheme(), NativeUITheme.light, colors, dark)
            assertEquals(pair.first, status.background)
            assertEquals(pair.second, status.foreground)
            assertEquals(pair.first, feedback.container)
            assertEquals(pair.second, feedback.content)
        }
        assertEquals(androidx.compose.ui.graphics.Color(0xFFB45309), FirstlightWarningColors().resolve(false).first)
        assertEquals(androidx.compose.ui.graphics.Color(0xFFFBBF24), FirstlightWarningColors().resolve(true).first)
        assertEquals(androidx.compose.ui.graphics.Color.White, FirstlightWarningColors().resolve(false).second)
        assertEquals(androidx.compose.ui.graphics.Color(0xFF1C1917), FirstlightWarningColors().resolve(true).second)
    }

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
            FirstlightTestAppearance(dark) {
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
                                colors = resolveStatusLabelTokenColors(tokens, tone, isDark = dark),
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
                            colors = resolveStatusLabelTokenColors(tokens, StatusLabelTone.Warning, isDark = true),
                            accessibilityDescription = "Referral status: awaiting review",
                        )
                    }
                }
            }
        }
    }
}
