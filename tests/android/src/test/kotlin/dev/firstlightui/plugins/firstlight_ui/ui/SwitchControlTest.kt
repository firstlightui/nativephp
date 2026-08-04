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
import com.nativephp.plugins.native_ui.NativeUITheme
import org.junit.Rule
import org.junit.Test

class SwitchControlTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test fun light() = snapshot(dark = false)
    @Test fun dark() = snapshot(dark = true)
    @Test fun disabledOn() = snapshot(dark = false, value = true, disabled = true)
    @Test fun error() = snapshot(dark = false, error = "Notifications are required")
    @Test fun longLabel() = snapshot(
        dark = false,
        label = "Receive appointment reminders and important account activity notifications",
    )

    private fun snapshot(
        dark: Boolean,
        value: Boolean = false,
        disabled: Boolean = false,
        label: String = "Notifications",
        error: String = "",
    ) {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
                val tokens = if (dark) NativeUITheme.dark else NativeUITheme.light
                Surface(modifier = Modifier.fillMaxSize()) {
                    FirstlightSwitchControl(
                        value = value,
                        label = label,
                        helper = "Receive updates about new activity.",
                        error = error,
                        disabled = disabled,
                        accessibilityLabel = label,
                        accessibilityHint = "Controls notification delivery",
                        tokens = tokens,
                        onProposal = {},
                        modifier = Modifier.padding(16.dp),
                    )
                }
            }
        }
    }
}

class SwitchControlLargeTextTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2.0f))

    @Test
    fun fontScaleTwo() {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = darkColorScheme()) {
                Surface(modifier = Modifier.fillMaxSize()) {
                    FirstlightSwitchControl(
                        value = true,
                        label = "Receive appointment reminders and important account activity notifications",
                        helper = "Receive updates about new activity.",
                        error = "",
                        disabled = false,
                        accessibilityLabel = "Notifications",
                        accessibilityHint = "Controls notification delivery",
                        tokens = NativeUITheme.dark,
                        onProposal = {},
                        modifier = Modifier.padding(16.dp),
                    )
                }
            }
        }
    }
}
