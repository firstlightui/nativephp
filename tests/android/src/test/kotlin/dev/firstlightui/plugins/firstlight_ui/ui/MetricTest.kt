package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.semantics.SemanticsProperties
import androidx.compose.ui.test.ExperimentalTestApi
import androidx.compose.ui.test.SemanticsMatcher
import androidx.compose.ui.test.assert
import androidx.compose.ui.test.assertCountEquals
import androidx.compose.ui.test.onAllNodesWithText
import androidx.compose.ui.test.onNodeWithContentDescription
import androidx.compose.ui.test.runComposeUiTest
import androidx.compose.ui.unit.dp
import app.cash.paparazzi.DeviceConfig
import app.cash.paparazzi.Paparazzi
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import org.junit.Assert.assertEquals
import org.junit.Rule
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner

@RunWith(RobolectricTestRunner::class)
@OptIn(ExperimentalTestApi::class)
class MetricTest {
    @Test fun `metric is one static accessibility element without repeated child fragments`() = runComposeUiTest {
        setContent {
            MaterialTheme {
                MetricControl("Events", "5", "last 10h ago", Color.Black, Color.Red,
                    "Events: 5, last 10h ago", Modifier)
            }
        }
        onNodeWithContentDescription("Events: 5, last 10h ago")
            .assert(SemanticsMatcher.keyNotDefined(SemanticsProperties.Role))
        onAllNodesWithText("5").assertCountEquals(0)
        onAllNodesWithText("Events").assertCountEquals(0)
        onAllNodesWithText("last 10h ago").assertCountEquals(0)
    }

    @Test fun `native decoding preserves formatted zero detail and accessible override`() {
        val configuration = MetricRendererConfiguration(NativeUINode(id = 7, props = GenericProps(mapOf(
            "label" to "Users", "value" to "0", "detail" to "affected",
            "a11y_label" to "No affected users", "a11y_hint" to "Since release",
        ))))
        assertEquals("0", configuration.value)
        assertEquals("affected", configuration.detail)
        assertEquals("No affected users. Since release", configuration.accessibilityDescription)
    }
}

class MetricScreenshotTest {
    @get:Rule val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test fun light() = snapshot(false)
    @Test fun dark() = snapshot(true)

    private fun snapshot(dark: Boolean) {
        paparazzi.snapshot {
            FirstlightTestAppearance(dark) {
                Surface(Modifier.fillMaxSize()) {
                    Column(Modifier.padding(20.dp), verticalArrangement = Arrangement.spacedBy(20.dp)) {
                        listOf(
                            mapOf("label" to "Events", "value" to "5", "detail" to "last 10h ago"),
                            mapOf("label" to "Users", "value" to "0", "detail" to "affected", "tone" to "info"),
                            mapOf("label" to "Response", "value" to "234 ms", "detail" to "last check", "tone" to "success"),
                            mapOf("label" to "Pending", "value" to "12", "tone" to "warning"),
                            mapOf("label" to "Failures", "value" to "3", "detail" to "since release", "tone" to "danger"),
                        ).forEachIndexed { index, props ->
                            MetricRenderer.Render(NativeUINode(id = index, props = GenericProps(props)), Modifier)
                        }
                    }
                }
            }
        }
    }
}

class MetricLargeTextScreenshotTest {
    @get:Rule val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2.0f))

    @Test fun longValueAtFontScaleTwo() {
        paparazzi.snapshot {
            FirstlightTestAppearance(false) {
                Surface(Modifier.fillMaxSize()) {
                    MetricRenderer.Render(NativeUINode(id = 7, props = GenericProps(mapOf(
                        "label" to "Response", "value" to "1,234.50 milliseconds", "detail" to "last check", "tone" to "info",
                    ))), Modifier.padding(20.dp))
                }
            }
        }
    }
}
