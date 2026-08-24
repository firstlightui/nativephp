package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.ui.Modifier
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

class AlertDialogTest {
    @Test
    fun `configuration decodes presentation and action copy`() {
        val configuration = AlertDialogRendererConfiguration(alertDialogNode())

        assertTrue(configuration.visible)
        assertEquals("Changes saved", configuration.title)
        assertEquals("Your profile was updated.", configuration.message)
        assertEquals("OK", configuration.actionLabel)
        assertEquals(42, configuration.dismissCallback)
        assertTrue(configuration.canPresent)
    }

    @Test
    fun `acknowledge and system dismissal emit only once`() {
        val state = AlertDialogRendererState(alertDialogNode())

        assertEquals(AlertDialogRendererEvent.Dismiss(42, 7), state.dismiss())
        assertFalse(state.isPresented)
        assertNull(state.dismiss())
    }

    @Test
    fun `missing callback prevents presentation`() {
        assertFalse(
            AlertDialogRendererState(
                alertDialogNode(dismissCallback = 0),
            ).isPresented,
        )
    }

    @Test
    fun `server visibility reconciles without reopening on copy only changes`() {
        val state = AlertDialogRendererState(alertDialogNode())
        assertEquals(AlertDialogRendererEvent.Dismiss(42, 7), state.dismiss())

        assertTrue(state.serverPublished(NativeUITree(alertDialogNode(message = "Updated copy."))))
        assertFalse(state.isPresented)

        assertTrue(state.serverPublished(alertDialogNode(visible = false)))
        assertFalse(state.isPresented)

        assertTrue(state.serverPublished(alertDialogNode(visible = true)))
        assertTrue(state.isPresented)
    }

    @Test
    fun `programmatic closure emits nothing`() {
        val state = AlertDialogRendererState(alertDialogNode())

        assertTrue(state.serverPublished(alertDialogNode(visible = false)))
        assertFalse(state.isPresented)
    }
}

class AlertDialogScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test fun light() = snapshot(dark = false)
    @Test fun dark() = snapshot(dark = true)

    private fun snapshot(dark: Boolean) {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
                Surface {
                    AlertDialogControl(
                        state = AlertDialogRendererState(alertDialogNode()),
                        tokens = if (dark) NativeUITheme.dark else NativeUITheme.light,
                        onDismiss = {},
                        modifier = Modifier,
                    )
                }
            }
        }
    }
}

class AlertDialogLargeTextScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2.0f))

    @Test
    fun fontScaleTwo() {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = lightColorScheme()) {
                AlertDialogControl(
                    state = AlertDialogRendererState(alertDialogNode()),
                    tokens = NativeUITheme.light,
                    onDismiss = {},
                )
            }
        }
    }
}

private fun alertDialogNode(
    visible: Boolean = true,
    message: String = "Your profile was updated.",
    dismissCallback: Int = 42,
) = NativeUINode(
    id = 7,
    onPress = 0,
    props = GenericProps(
        mapOf(
            "visible" to visible,
            "title" to "Changes saved",
            "message" to message,
            "action_label" to "OK",
            "on_dismiss" to dismissCallback,
        ),
    ),
)
