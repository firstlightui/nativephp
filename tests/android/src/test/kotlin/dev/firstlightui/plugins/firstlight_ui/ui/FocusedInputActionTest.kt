package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Column
import androidx.compose.material3.MaterialTheme
import androidx.compose.runtime.DisposableEffect
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.SemanticsActions
import androidx.compose.ui.test.ExperimentalTestApi
import androidx.compose.ui.test.hasSetTextAction
import androidx.compose.ui.test.onNodeWithContentDescription
import androidx.compose.ui.test.performClick
import androidx.compose.ui.test.performSemanticsAction
import androidx.compose.ui.test.performTextInput
import androidx.compose.ui.test.runComposeUiTest
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.KeyboardFocusPolicy
import com.nativephp.mobile.ui.nativerender.NativeElementBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import org.junit.Assert.assertEquals
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.ParameterizedRobolectricTestRunner

@OptIn(ExperimentalTestApi::class)
@RunWith(ParameterizedRobolectricTestRunner::class)
class FocusedInputActionTest(private val kind: String, private val mode: String, private val action: String) {
    companion object {
        @JvmStatic
        @ParameterizedRobolectricTestRunner.Parameters(name = "{0}-{1}-{2}")
        fun cases() = listOf("text-field", "text-area", "search-field", "code-editor").flatMap { kind ->
            listOf("blur", "debounce").flatMap { mode ->
                listOf("button", "icon-button").map { action -> arrayOf(kind, mode, action) }
            }
        }
    }

    @Test fun `an action sees pending text before its press and debounce never sends it twice`() = runComposeUiTest {
        val sent = mutableListOf<String>()
        val node = NativeUINode(id = 7, props = GenericProps(mapOf(
            "value" to "", "label" to "Draft", "a11y_label" to "Draft",
            "sync_mode" to mode, "debounce_ms" to 500, "on_change" to 41,
            "language" to "plain", "min_lines" to 2,
        )))
        val save = NativeUINode(id = 8, onPress = 42, props = GenericProps(mapOf(
            "label" to "Save", "a11y_label" to "Save", "icon" to "check",
        )))
        mainClock.autoAdvance = false

        setContent {
            DisposableEffect(Unit) {
                // Blur can arrive after the action; the policy must publish synchronously.
                KeyboardFocusPolicy.clearFocus = {}
                NativeElementBridge.onPressEvent = { callbackId, nodeId -> sent += "press:$callbackId:$nodeId" }
                onDispose {
                    KeyboardFocusPolicy.clearFocus = null
                    NativeElementBridge.onPressEvent = null
                }
            }
            MaterialTheme {
                Column {
                    when (kind) {
                        "text-field" -> TextFieldRenderer.Render(node, Modifier, TextFieldRendererEvents {
                            if (it is TextFieldRendererEvent.Change) sent += "change:${it.value}"
                        })
                        "text-area" -> TextAreaRenderer.Render(node, Modifier, TextAreaRendererEvents {
                            sent += "change:${it.value}"
                        })
                        "search-field" -> SearchFieldRenderer.Render(node, Modifier, SearchFieldRendererEvents {
                            if (it is SearchFieldRendererEvent.Change) sent += "change:${it.value}"
                        })
                        "code-editor" -> CodeEditorRenderer.Render(node, Modifier, CodeEditorRendererEvents {
                            sent += "change:${it.value}"
                        })
                    }
                    when (action) {
                        "button" -> FirstlightButtonRenderer.Render(save, Modifier)
                        "icon-button" -> IconButtonRenderer.Render(save, Modifier)
                    }
                }
            }
        }

        onNode(hasSetTextAction()).performClick().performTextInput("latest draft")
        runOnIdle { assertEquals(emptyList<String>(), sent) }
        onNodeWithContentDescription("Save").performSemanticsAction(SemanticsActions.OnClick) { it() }
        runOnIdle { assertEquals(listOf("change:latest draft", "press:42:8"), sent) }

        mainClock.advanceTimeBy(1000)
        waitForIdle()
        runOnIdle { assertEquals(listOf("change:latest draft", "press:42:8"), sent) }
    }
}
