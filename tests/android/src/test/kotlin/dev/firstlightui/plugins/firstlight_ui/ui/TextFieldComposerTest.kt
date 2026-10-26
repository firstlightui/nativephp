package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalFocusManager
import androidx.compose.ui.semantics.SemanticsProperties
import androidx.compose.ui.test.ExperimentalTestApi
import androidx.compose.ui.test.SemanticsMatcher
import androidx.compose.ui.test.assert
import androidx.compose.ui.test.assertHeightIsAtLeast
import androidx.compose.ui.test.assertIsDisplayed
import androidx.compose.ui.test.assertIsNotFocused
import androidx.compose.ui.test.assertWidthIsAtLeast
import androidx.compose.ui.test.click
import androidx.compose.ui.test.hasSetTextAction
import androidx.compose.ui.test.onNodeWithContentDescription
import androidx.compose.ui.test.performClick
import androidx.compose.ui.test.performTextInput
import androidx.compose.ui.test.performTouchInput
import androidx.compose.ui.test.runComposeUiTest
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.unit.dp
import app.cash.paparazzi.DeviceConfig
import app.cash.paparazzi.Paparazzi
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.KeyboardFocusPolicy
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import org.junit.Rule
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner

class TextFieldComposerScreenshotTest {
    @get:Rule val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test fun noteRow() {
        paparazzi.snapshot { NoteComposer() }
    }

    @Test fun noteRowWithDraft() {
        paparazzi.snapshot { NoteComposer("A new clinical note") }
    }
}

@OptIn(ExperimentalTestApi::class)
@RunWith(RobolectricTestRunner::class)
class TextFieldComposerTest {
    @Test fun `weighted note field and send button stay visible and server clear is displayed after send`() = runComposeUiTest {
        try {
            setContent { NoteComposer() }
            val field = onNode(hasSetTextAction())
            val send = onNodeWithContentDescription("Send note")
            field.assertIsDisplayed().assertWidthIsAtLeast(200.dp).assertHeightIsAtLeast(48.dp)
            send.assertIsDisplayed().assertWidthIsAtLeast(48.dp).assertHeightIsAtLeast(48.dp)
            field.performClick().performTextInput("A new clinical note")
            send.performTouchInput { click() }
            field.assertIsNotFocused()

            // PHP has accepted the note and published the cleared model.
            runOnIdle {
                NativeUIBridge.currentTree.value = NativeUITree(root = NativeUINode(
                    id = 1, props = GenericProps(), children = listOf(noteField(), noteSend()),
                ))
            }
            field.assert(SemanticsMatcher.expectValue(SemanticsProperties.EditableText, AnnotatedString("")))
        } finally {
            NativeUIBridge.currentTree.value = null
        }
    }
}

@Composable
private fun NoteComposer(value: String = "") {
    val focus = LocalFocusManager.current
    DisposableEffect(focus) {
        // The production NativeUIContent supplies this same policy hook.
        KeyboardFocusPolicy.clearFocus = { focus.clearFocus() }
        onDispose { KeyboardFocusPolicy.clearFocus = null }
    }
    MaterialTheme {
        Surface(Modifier.fillMaxSize().clickable { focus.clearFocus() }) {
            Column(Modifier.verticalScroll(rememberScrollState()).padding(16.dp)) {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    TextFieldRenderer.Render(noteField(value), Modifier.weight(1f))
                    IconButtonRenderer.Render(noteSend(), Modifier)
                }
            }
        }
    }
}

private fun noteField(value: String = "") = NativeUINode(id = 7, props = GenericProps(mapOf(
    "value" to value, "placeholder" to "Add a note...", "a11y_label" to "Add a note",
    "sync_mode" to "live", "on_change" to 41,
)))

private fun noteSend() = NativeUINode(id = 8, onPress = 43, props = GenericProps(mapOf(
    "icon" to "send", "a11y_label" to "Send note",
)))
