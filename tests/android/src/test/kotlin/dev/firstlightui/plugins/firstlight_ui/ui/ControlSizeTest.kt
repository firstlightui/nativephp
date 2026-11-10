package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Button
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.lightColorScheme
import androidx.compose.ui.Modifier
import androidx.compose.ui.layout.onSizeChanged
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import app.cash.paparazzi.DeviceConfig
import app.cash.paparazzi.Paparazzi
import com.nativephp.plugins.native_ui.NativeUITheme
import org.junit.Assert.assertEquals
import org.junit.Rule
import org.junit.Test

/**
 * `size="lg"` Segmented and Pill Group options are as tall as Button
 * `size="lg"` at the default font scale and grow with it at larger scales,
 * measured with layoutlib's real text metrics.
 */
class ControlSizeTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test
    fun largeMatchesLargeButton() = assertLargeMatchesLargeButton(paparazzi, expectedDp = 48f, toleranceDp = 0f)
}

class ControlSizeLargeTextTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2.0f))

    @Test
    // The button's 20 sp label sits in a 20 sp line; lg's title label keeps its
    // own 24 sp line, so at double font scale lg runs about 2 dp taller.
    fun largeTracksLargeButtonAtFontScaleTwo() = assertLargeMatchesLargeButton(paparazzi, expectedDp = 64f, toleranceDp = 3f)
}

private fun assertLargeMatchesLargeButton(paparazzi: Paparazzi, expectedDp: Float, toleranceDp: Float) {
    val heights = mutableMapOf<String, Int>()
    var density = 1f

    paparazzi.snapshot {
        density = androidx.compose.ui.platform.LocalDensity.current.density
        MaterialTheme(colorScheme = lightColorScheme()) {
            val tokens = NativeUITheme.light
            Surface(modifier = Modifier.fillMaxSize()) {
                Column(
                    modifier = Modifier.padding(16.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp),
                ) {
                    LargeButtonReference(Modifier.onSizeChanged { heights["button"] = it.height })
                    FirstlightSegmentedControl(
                        labels = listOf("Ward", "Patients"),
                        enabled = listOf(true, true),
                        selectedIndex = 0,
                        groupEnabled = true,
                        label = "",
                        helper = "",
                        error = null,
                        onSelection = {},
                        modifier = Modifier.onSizeChanged { heights["segmented"] = it.height },
                        colors = segmentedButtonColors(tokens),
                        accessibilityLabel = "View",
                        size = FirstlightControlSize.Lg,
                    )
                    FirstlightPillGroupControl(
                        labels = listOf("Ward", "Patients"),
                        values = listOf("ward", "patients"),
                        enabled = listOf(true, true),
                        selectedValues = listOf("ward"),
                        groupEnabled = true,
                        awaitingPublication = false,
                        label = "",
                        helper = "",
                        error = null,
                        onSelection = {},
                        modifier = Modifier.onSizeChanged { heights["pills"] = it.height },
                        colors = pillGroupColors(tokens),
                        accessibilityLabel = "View",
                        size = FirstlightControlSize.Lg,
                    )
                }
            }
        }
    }

    val button = heights.getValue("button") / density
    assertEquals("Button lg", expectedDp, button, 0.5f)
    assertEquals("Segmented lg", button, heights.getValue("segmented") / density, toleranceDp + 0.5f)
    assertEquals("Pill Group lg", button, heights.getValue("pills") / density, toleranceDp + 0.5f)
}

/** Mobile UI 0.9's Button `size="lg"`: at least 48 dp, 20 × 12 dp padding, 20 sp label. */
@androidx.compose.runtime.Composable
private fun LargeButtonReference(modifier: Modifier) {
    Button(
        onClick = {},
        modifier = modifier.defaultMinSize(minHeight = 48.dp),
        contentPadding = PaddingValues(horizontal = 20.dp, vertical = 12.dp),
    ) {
        Text(text = "Admit", fontSize = 20.sp, maxLines = 1, softWrap = false)
    }
}
