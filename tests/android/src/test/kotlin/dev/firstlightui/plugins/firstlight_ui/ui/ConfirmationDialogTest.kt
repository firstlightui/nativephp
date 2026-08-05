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

class ConfirmationDialogTest {
    @Test
    fun `configuration decodes presentation and action roles`() {
        val configuration = ConfirmationDialogRendererConfiguration(confirmationDialogNode())

        assertTrue(configuration.visible)
        assertEquals("Delete appointment?", configuration.title)
        assertEquals("This action cannot be undone.", configuration.message)
        assertEquals("Delete", configuration.confirmLabel)
        assertEquals("Keep appointment", configuration.cancelLabel)
        assertEquals(FirstlightConfirmationDialogTone.Destructive, configuration.tone)
        assertEquals(41, configuration.confirmCallback)
        assertEquals(42, configuration.dismissCallback)
        assertTrue(configuration.canPresent)
    }

    @Test
    fun `malformed tone defensively uses the default role`() {
        assertEquals(
            FirstlightConfirmationDialogTone.Default,
            ConfirmationDialogRendererConfiguration(confirmationDialogNode(tone = "warning")).tone,
        )
    }

    @Test
    fun `confirm dismisses and emits only once`() {
        val state = ConfirmationDialogRendererState(confirmationDialogNode())

        assertEquals(ConfirmationDialogRendererEvent.Press(41, 7), state.confirm())
        assertFalse(state.isPresented)
        assertNull(state.confirm())
        assertNull(state.dismiss())
    }

    @Test
    fun `cancel and system dismissal emit dismissal only once`() {
        val state = ConfirmationDialogRendererState(confirmationDialogNode())

        assertEquals(ConfirmationDialogRendererEvent.Dismiss(42, 7), state.dismiss())
        assertFalse(state.isPresented)
        assertNull(state.dismiss())
    }

    @Test
    fun `missing callbacks prevent presentation`() {
        assertFalse(
            ConfirmationDialogRendererState(
                confirmationDialogNode(confirmCallback = 0),
            ).isPresented,
        )
        assertFalse(
            ConfirmationDialogRendererState(
                confirmationDialogNode(dismissCallback = 0),
            ).isPresented,
        )
    }

    @Test
    fun `server visibility reconciles without reopening on copy only changes`() {
        val state = ConfirmationDialogRendererState(confirmationDialogNode())
        assertEquals(ConfirmationDialogRendererEvent.Dismiss(42, 7), state.dismiss())

        assertTrue(state.serverPublished(NativeUITree(confirmationDialogNode(message = "Updated details."))))
        assertFalse(state.isPresented)

        assertTrue(state.serverPublished(confirmationDialogNode(visible = false)))
        assertFalse(state.isPresented)

        assertTrue(state.serverPublished(confirmationDialogNode(visible = true)))
        assertTrue(state.isPresented)
    }

    @Test
    fun `programmatic closure emits nothing`() {
        val state = ConfirmationDialogRendererState(confirmationDialogNode())

        assertTrue(state.serverPublished(confirmationDialogNode(visible = false)))
        assertFalse(state.isPresented)
    }
}

class ConfirmationDialogScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test fun lightDestructive() = snapshot(dark = false)
    @Test fun darkDefault() = snapshot(dark = true, tone = "default")

    private fun snapshot(dark: Boolean, tone: String = "destructive") {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
                Surface {
                    ConfirmationDialogControl(
                        state = ConfirmationDialogRendererState(
                            confirmationDialogNode(tone = tone),
                        ),
                        tokens = if (dark) NativeUITheme.dark else NativeUITheme.light,
                        onConfirm = {},
                        onDismiss = {},
                        modifier = Modifier,
                    )
                }
            }
        }
    }
}

class ConfirmationDialogLargeTextScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2.0f))

    @Test
    fun fontScaleTwo() {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = lightColorScheme()) {
                ConfirmationDialogControl(
                    state = ConfirmationDialogRendererState(confirmationDialogNode()),
                    tokens = NativeUITheme.light,
                    onConfirm = {},
                    onDismiss = {},
                )
            }
        }
    }
}

private fun confirmationDialogNode(
    visible: Boolean = true,
    message: String = "This action cannot be undone.",
    tone: String = "destructive",
    confirmCallback: Int = 41,
    dismissCallback: Int = 42,
) = NativeUINode(
    id = 7,
    onPress = confirmCallback,
    props = GenericProps(
        mapOf(
            "visible" to visible,
            "title" to "Delete appointment?",
            "message" to message,
            "confirm_label" to "Delete",
            "cancel_label" to "Keep appointment",
            "tone" to tone,
            "on_dismiss" to dismissCallback,
        ),
    ),
)
