package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import app.cash.paparazzi.DeviceConfig
import app.cash.paparazzi.Paparazzi
import com.nativephp.plugins.native_ui.NativeUITheme
import org.junit.Rule
import org.junit.Test

class SegmentedControlScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test
    fun light() = snapshot(dark = false)

    @Test
    fun dark() = snapshot(dark = true)

    @Test
    fun disabledOption() {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = lightColorScheme()) {
                val tokens = NativeUITheme.light
                Surface(modifier = Modifier.fillMaxSize()) {
                    FirstlightSegmentedControl(
                        labels = listOf("Open", "Needs review", "Closed"),
                        enabled = listOf(true, false, true),
                        selectedIndex = 0,
                        groupEnabled = true,
                        label = "Referral status",
                        helper = "Select the active queue",
                        error = null,
                        onSelection = {},
                        modifier = Modifier.padding(16.dp),
                        colors = segmentedButtonColors(tokens),
                        labelColor = tokens.onSurface,
                        helperColor = tokens.onSurfaceVariant,
                        errorColor = tokens.destructive,
                    )
                }
            }
        }
    }

    @Test
    fun error() {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = lightColorScheme()) {
                val tokens = NativeUITheme.light
                Surface(modifier = Modifier.fillMaxSize()) {
                    FirstlightSegmentedControl(
                        labels = listOf("Mine", "All"),
                        enabled = listOf(true, true),
                        selectedIndex = null,
                        groupEnabled = true,
                        label = "Queue",
                        helper = "Choose a queue",
                        error = "Select a queue",
                        onSelection = {},
                        modifier = Modifier.padding(16.dp),
                        colors = segmentedButtonColors(tokens),
                        labelColor = tokens.onSurface,
                        helperColor = tokens.onSurfaceVariant,
                        errorColor = tokens.destructive,
                        required = true,
                    )
                }
            }
        }
    }

    private fun snapshot(dark: Boolean) {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
                val tokens = if (dark) NativeUITheme.dark else NativeUITheme.light
                Surface(modifier = Modifier.fillMaxSize()) {
                    FirstlightSegmentedControl(
                        labels = listOf("Mine", "All"),
                        enabled = listOf(true, true),
                        selectedIndex = 1,
                        groupEnabled = true,
                        label = "Queue",
                        helper = "Choose a queue",
                        error = null,
                        onSelection = {},
                        modifier = Modifier.padding(16.dp),
                        colors = segmentedButtonColors(tokens),
                        labelColor = tokens.onSurface,
                        helperColor = tokens.onSurfaceVariant,
                        errorColor = tokens.destructive,
                    )
                }
            }
        }
    }
}

class SegmentedControlLargeTextScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2.0f))

    @Test
    fun fontScaleTwo() {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = darkColorScheme()) {
                val tokens = NativeUITheme.dark
                Surface(modifier = Modifier.fillMaxSize()) {
                    FirstlightSegmentedControl(
                        labels = listOf("Assigned to me", "All referrals"),
                        enabled = listOf(true, true),
                        selectedIndex = 0,
                        groupEnabled = true,
                        label = "Referral queue",
                        helper = "Choose which referrals to show",
                        error = null,
                        onSelection = {},
                        modifier = Modifier.padding(16.dp),
                        colors = segmentedButtonColors(tokens),
                        labelColor = tokens.onSurface,
                        helperColor = tokens.onSurfaceVariant,
                        errorColor = tokens.destructive,
                    )
                }
            }
        }
    }
}
