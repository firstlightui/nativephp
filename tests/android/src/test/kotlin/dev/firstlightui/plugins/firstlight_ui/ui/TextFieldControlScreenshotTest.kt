package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.TextFieldValue
import androidx.compose.ui.unit.dp
import app.cash.paparazzi.DeviceConfig
import app.cash.paparazzi.Paparazzi
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.plugins.native_ui.NativeUITheme
import org.junit.Rule
import org.junit.Test

class TextFieldControlScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test fun lightClearable() = snapshot(dark = false, error = "", clearable = true)
    @Test fun darkError() = snapshot(dark = true, error = "Enter a valid email", clearable = false)

    private fun snapshot(dark: Boolean, error: String, clearable: Boolean) {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
                val tokens = if (dark) NativeUITheme.dark else NativeUITheme.light
                Surface(modifier = Modifier.fillMaxSize()) {
                    FirstlightTextFieldControl(
                        configuration = screenshotConfiguration(error, clearable),
                        draft = TextFieldValue("person@example.com"),
                        revealed = false,
                        tokens = tokens,
                        onValueChange = {},
                        onClear = {},
                        onReveal = {},
                        onTrailingPress = {},
                        onSubmit = {},
                        modifier = Modifier.padding(16.dp),
                    )
                }
            }
        }
    }
}

class TextFieldControlLargeTextScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2f))

    @Test
    fun fontScaleTwo() {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = darkColorScheme()) {
                Surface(modifier = Modifier.fillMaxSize()) {
                    FirstlightTextFieldControl(
                        configuration = screenshotConfiguration("", false),
                        draft = TextFieldValue("A secure value"),
                        revealed = false,
                        tokens = NativeUITheme.dark,
                        onValueChange = {}, onClear = {}, onReveal = {},
                        onTrailingPress = {}, onSubmit = {},
                        modifier = Modifier.padding(16.dp),
                    )
                }
            }
        }
    }
}

private fun screenshotConfiguration(error: String, clearable: Boolean) = TextFieldRendererConfiguration(
    NativeUINode(
        id = 7,
        props = GenericProps(
            mapOf(
                "value" to "person@example.com",
                "label" to "Contact email",
                "placeholder" to "you@example.com",
                "helper" to "Used for appointment updates",
                "error" to error,
                "required" to true,
                "keyboard" to "email",
                "content_type" to "email",
                "leading_icon" to "email",
                "clearable" to clearable,
                "sync_mode" to "live",
            ),
        ),
    ),
)
