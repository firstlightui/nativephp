package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.ExperimentalFoundationApi
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.text.input.placeCursorAtEnd
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.SemanticsProperties
import androidx.compose.ui.test.ExperimentalTestApi
import androidx.compose.ui.test.SemanticsMatcher
import androidx.compose.ui.test.assert
import androidx.compose.ui.test.assertCountEquals
import androidx.compose.ui.test.assertHeightIsAtLeast
import androidx.compose.ui.test.assertWidthIsAtLeast
import androidx.compose.ui.test.hasContentDescription
import androidx.compose.ui.test.hasSetTextAction
import androidx.compose.ui.test.onAllNodesWithText
import androidx.compose.ui.test.performClick
import androidx.compose.ui.test.performScrollTo
import androidx.compose.ui.test.performTextInput
import androidx.compose.ui.test.requestFocus
import androidx.compose.ui.test.runComposeUiTest
import androidx.compose.ui.text.TextRange
import androidx.compose.ui.unit.dp
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
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner

@OptIn(ExperimentalFoundationApi::class)
@RunWith(RobolectricTestRunner::class)
class CodeEditorContractTest {
    @Test fun `configuration decodes code field metadata defaults and chrome fallbacks`() {
        val configuration = CodeEditorRendererConfiguration(codeEditorNode(extra = mapOf("indent_label" to "Indenter")))
        assertEquals(CodeEditorLanguage.Json, configuration.language)
        assertEquals(4, configuration.indent)
        assertEquals(false, configuration.wrap)
        assertEquals(true, configuration.lineNumbers)
        assertEquals(8, configuration.minLines)
        assertEquals(24, configuration.maxLines)
        assertEquals("Webhook payload", configuration.accessibilityLabel)
        assertEquals("Indenter", configuration.label("indent_label"))
        assertEquals("Insert left brace", configuration.label("left_brace_label"))

        val defaults = CodeEditorRendererConfiguration(NativeUINode(id = 1, props = GenericProps(mapOf("label" to "Code"))))
        assertEquals(CodeEditorLanguage.Plain, defaults.language)
        assertEquals(2, defaults.indent)
        assertTrue(defaults.wrap)
        assertEquals(6, defaults.minLines)
        assertEquals(20, defaults.maxLines)
    }

    @Test fun `follow only applies to read only editors`() {
        assertFalse(CodeEditorRendererConfiguration(codeEditorNode(extra = mapOf("follow" to true))).follow)
        assertTrue(CodeEditorRendererConfiguration(codeEditorNode(readOnly = true, extra = mapOf("follow" to true))).follow)
    }

    @Test fun `live edits publish and focused acknowledgements keep the buffer and selection`() {
        val state = CodeEditorRendererState(codeEditorNode(value = "{}"))
        state.focusChanged(true)
        state.text.edit { replace(0, length, "{\"a\": 1}"); selection = TextRange(2, 3) }

        assertEquals(CodeEditorRendererEvent(41, 7, "{\"a\": 1}"), state.textChanged())
        state.serverPublished(tree(codeEditorNode(value = "{\"a\": 1}")))
        assertEquals(TextRange(2, 3), state.text.selection)

        state.serverPublished(tree(codeEditorNode(value = "{\"b\": 2}")))
        assertEquals("{\"a\": 1}", state.currentText)
        assertEquals("{\"b\": 2}", state.pendingServerValue)
        assertNull(state.focusChanged(false))
        assertEquals("{\"b\": 2}", state.currentText)
    }

    @Test fun `blur and debounce keep edits local until flushed`() {
        val blur = CodeEditorRendererState(codeEditorNode(syncMode = "blur"))
        blur.focusChanged(true)
        blur.text.edit { replace(0, length, "A=1") }
        assertNull(blur.textChanged())
        assertEquals(CodeEditorRendererEvent(41, 7, "A=1"), blur.focusChanged(false))

        val debounce = CodeEditorRendererState(codeEditorNode(syncMode = "debounce"))
        debounce.focusChanged(true)
        debounce.text.edit { replace(0, length, "B=2") }
        assertNull(debounce.textChanged())
        assertEquals(CodeEditorRendererEvent(41, 7, "B=2"), debounce.flush())
        assertNull(debounce.flush())
    }

    @Test fun `key row operations use the shared engine and undo as one edit`() {
        val state = CodeEditorRendererState(codeEditorNode(value = "{"))
        state.focusChanged(true)
        state.text.edit { placeCursorAtEnd() }

        assertEquals(CodeEditorRendererEvent(41, 7, "{\n    "), state.perform(CodeEditorOperation.Return))
        assertEquals(TextRange(6), state.text.selection)
        assertEquals(CodeEditorRendererEvent(41, 7, "{\n}"), state.perform(CodeEditorOperation.Insert("}")))

        assertEquals(CodeEditorRendererEvent(41, 7, "{\n    "), state.undo())
        assertEquals(CodeEditorRendererEvent(41, 7, "{\n}"), state.redo())
    }

    @Test fun `disabled and read only editors reject operations and undo`() {
        val disabled = CodeEditorRendererState(codeEditorNode(disabled = true))
        val readOnly = CodeEditorRendererState(codeEditorNode(readOnly = true))
        assertNull(disabled.perform(CodeEditorOperation.Indent))
        assertNull(readOnly.perform(CodeEditorOperation.Insert("{")))
        assertNull(readOnly.undo())
        assertEquals("{}", disabled.currentText)
        assertEquals("{}", readOnly.currentText)
    }

    @Test fun `unfocused programmatic replacement clears undo and emits nothing`() {
        val state = CodeEditorRendererState(codeEditorNode(value = "a"))
        state.text.edit { replace(0, length, "ab") }
        state.textChanged()
        assertTrue(state.text.undoState.canUndo)

        state.serverPublished(tree(codeEditorNode(value = "server")))
        assertEquals("server", state.currentText)
        assertFalse(state.text.undoState.canUndo)
        assertNull(state.flush())
        assertNull(state.textChanged())
    }

    @Test fun `read only selection holds replacements until it collapses`() {
        val state = CodeEditorRendererState(codeEditorNode(value = "line 1\nline 2", readOnly = true))
        state.text.edit { selection = TextRange(0, 6) }

        state.serverPublished(tree(codeEditorNode(value = "line 1\nline 2\nline 3", readOnly = true)))
        assertEquals("line 1\nline 2", state.currentText)
        assertEquals(TextRange(0, 6), state.text.selection)

        state.text.edit { selection = TextRange(3) }
        state.selectionChanged()
        assertEquals("line 1\nline 2\nline 3", state.currentText)
    }

    @Test fun `follow starts at the end and stays pinned across head trims`() {
        val node = codeEditorNode(value = "l1\nl2\nl3\n", readOnly = true, extra = mapOf("follow" to true))
        val state = CodeEditorRendererState(node)
        assertEquals(CodeEditorScrollRequest.ToEnd(0), state.scrollRequest)

        state.viewport = { CodeEditorViewport(firstVisibleLine = 1, offsetIntoLine = 0, pinned = true) }
        state.serverPublished(tree(codeEditorNode(value = "l2\nl3\nl4\n", readOnly = true, extra = mapOf("follow" to true))))
        assertEquals(CodeEditorScrollRequest.ToEnd(1), state.scrollRequest)
    }

    @Test fun `scrolled up views stay anchored to their content`() {
        val follow = mapOf("follow" to true)
        val state = CodeEditorRendererState(codeEditorNode(value = "l1\nl2\nl3\nl4\nl5\n", readOnly = true, extra = follow))
        state.viewport = { CodeEditorViewport(firstVisibleLine = 3, offsetIntoLine = 5, pinned = false) }

        state.serverPublished(tree(codeEditorNode(value = "l3\nl4\nl5\nl6\n", readOnly = true, extra = follow)))
        assertEquals(CodeEditorScrollRequest.ToLine(line = 1, offsetIntoLine = 5, generation = 1), state.scrollRequest)

        state.serverPublished(tree(codeEditorNode(value = "unrelated\n", readOnly = true, extra = follow)))
        assertNull(state.scrollRequest)
    }

    @Test fun `editors without follow anchor even when at the end`() {
        val state = CodeEditorRendererState(codeEditorNode(value = "a\nb\n", readOnly = true))
        assertNull(state.scrollRequest)
        state.viewport = { CodeEditorViewport(firstVisibleLine = 1, offsetIntoLine = 0, pinned = true) }
        state.serverPublished(tree(codeEditorNode(value = "a\nb\nc\n", readOnly = true)))
        assertEquals(CodeEditorScrollRequest.ToLine(line = 1, offsetIntoLine = 0, generation = 1), state.scrollRequest)
    }

    @Test fun `key row bottom meets the keyboard below the status bar`() {
        // Visible frame [156, 1800) in screen coordinates; the popup window starts at the screen top.
        assertEquals(1800 - 120, codeEditorKeyRowOffset(visibleFrameBottom = 1800, windowOriginY = 0, popupHeight = 120).y)
        assertEquals(1800 - 40 - 120, codeEditorKeyRowOffset(visibleFrameBottom = 1800, windowOriginY = 40, popupHeight = 120).y)
    }

    @Test fun `key rows match the design table for every language`() {
        fun symbols(language: CodeEditorLanguage) = codeEditorKeys(language).mapNotNull { it.text }
        fun labels(language: CodeEditorLanguage) = codeEditorKeys(language).map { it.labelProp }

        assertEquals(listOf("{", "}", "[", "]", "\"", ":", ","), symbols(CodeEditorLanguage.Json))
        assertEquals(listOf(":", "-", "#", "\"", "'", "|"), symbols(CodeEditorLanguage.Yaml))
        assertEquals(listOf("=", "#", "\"", "$", "{", "}", "_"), symbols(CodeEditorLanguage.Env))
        assertEquals(listOf("(", ")", "[", "]", "{", "}", "\"", "'"), symbols(CodeEditorLanguage.Plain))
        CodeEditorLanguage.entries.forEach { language ->
            assertEquals(listOf("indent_label", "outdent_label", "undo_label", "redo_label"), labels(language).take(4))
            assertEquals(language.hasLineComments, "toggle_comment_label" in labels(language))
            labels(language).forEach { assertTrue(it, it in CODE_EDITOR_LABEL_FALLBACKS) }
        }
    }
}

@OptIn(ExperimentalTestApi::class, ExperimentalFoundationApi::class)
@RunWith(RobolectricTestRunner::class)
class CodeEditorSemanticsTest {
    @Test fun `typed return and json closers indent through the shared engine`() = runComposeUiTest {
        val state = CodeEditorRendererState(codeEditorNode(value = ""))
        setContent { MaterialTheme { control(state) } }

        val field = onNode(hasSetTextAction())
        field.requestFocus()
        field.performTextInput("{")
        field.performTextInput("\n")
        assertEquals("{\n    ", state.currentText)
        field.performTextInput("}")
        assertEquals("{\n}", state.currentText)

        state.text.undoState.undo()
        assertEquals("{\n    ", state.currentText)
    }

    @Test fun `key row exposes named keys at the touch target baseline and gutter stays silent`() = runComposeUiTest {
        val state = CodeEditorRendererState(codeEditorNode(value = "{}", language = "yaml"))
        var operations = listOf<CodeEditorOperation>()
        setContent { MaterialTheme { control(state, keyRowVisible = true, onOperation = { operations = operations + it }) } }

        onNode(hasContentDescription("Indent")).assertWidthIsAtLeast(48.dp).assertHeightIsAtLeast(48.dp)
        onNode(hasContentDescription("Insert hyphen")).assertWidthIsAtLeast(48.dp).performClick()
        onNode(hasContentDescription("Toggle comment")).performScrollTo().performClick()
        assertEquals(listOf(CodeEditorOperation.Insert("-"), CodeEditorOperation.ToggleComment), operations)

        onAllNodesWithText("1").assertCountEquals(0)
        onAllNodes(SemanticsMatcher.keyIsDefined(SemanticsProperties.LiveRegion)).assertCountEquals(0)
    }

    @Test fun `field exposes its name and validation error`() = runComposeUiTest {
        val state = CodeEditorRendererState(codeEditorNode(extra = mapOf("error" to "Payload must be valid JSON.")))
        setContent { MaterialTheme { control(state) } }

        onNode(hasSetTextAction())
            .assert(SemanticsMatcher.expectValue(SemanticsProperties.Error, "Payload must be valid JSON."))
            .assert(hasContentDescription("Webhook payload", substring = true))
    }

    @androidx.compose.runtime.Composable
    private fun control(
        state: CodeEditorRendererState,
        keyRowVisible: Boolean? = null,
        onOperation: (CodeEditorOperation) -> Unit = { state.perform(it) },
    ) = Column {
        FirstlightCodeEditorControl(
            state = state,
            tokens = NativeUITheme.light,
            onOperation = onOperation,
            onUndo = { state.undo() },
            onRedo = { state.redo() },
            onFocusChanged = { state.focusChanged(it) },
            keyRowVisible = keyRowVisible,
        )
    }
}

class CodeEditorScreenshotTest {
    @get:Rule val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test fun jsonLight() = snapshot(dark = false, node = codeEditorNode(value = SAMPLE_JSON, extra = mapOf("wrap" to true)))
    @Test fun envDarkError() = snapshot(
        dark = true,
        node = codeEditorNode(value = SAMPLE_ENV, language = "env", extra = mapOf("error" to "APP_KEY is missing.")),
    )
    @Test fun yamlKeyRow() = snapshot(dark = false, node = codeEditorNode(value = SAMPLE_YAML, language = "yaml"), keyRow = true)
    @Test fun readOnlyNoLineNumbers() = snapshot(
        dark = false,
        node = codeEditorNode(value = SAMPLE_JSON, readOnly = true, extra = mapOf("line_numbers" to false)),
    )
    @Test fun disabledNoWrap() = snapshot(
        dark = true,
        node = codeEditorNode(value = "{\"a_very_long_key_that_does_not_wrap_on_narrow_phones\": true}", disabled = true),
    )

    private fun snapshot(dark: Boolean, node: NativeUINode, keyRow: Boolean = false) {
        paparazzi.snapshot { screenshotControl(dark, node, keyRow) }
    }
}

class CodeEditorLargeTextScreenshotTest {
    @get:Rule val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2.0f))

    @Test fun fontScaleTwo() {
        paparazzi.snapshot {
            screenshotControl(false, codeEditorNode(value = SAMPLE_ENV, language = "env"), keyRow = true)
        }
    }
}

@androidx.compose.runtime.Composable
private fun screenshotControl(dark: Boolean, node: NativeUINode, keyRow: Boolean) {
    MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
        Surface(modifier = Modifier.fillMaxSize()) {
            Column(Modifier.padding(16.dp)) {
                FirstlightCodeEditorControl(
                    state = CodeEditorRendererState(node),
                    tokens = if (dark) NativeUITheme.dark else NativeUITheme.light,
                    onOperation = {},
                    onUndo = {},
                    onRedo = {},
                    onFocusChanged = {},
                    keyRowVisible = keyRow,
                )
            }
        }
    }
}

private const val SAMPLE_JSON = "{\n  \"event\": \"deployment.finished\",\n  \"site\": 42,\n  \"tags\": [\"production\", \"eu\"]\n}"
private const val SAMPLE_ENV = "APP_ENV=production\nAPP_DEBUG=false\n# Mail\nMAIL_HOST=smtp.example.com\nMAIL_PORT=587"
private const val SAMPLE_YAML = "deploy:\n  branch: main\n  steps:\n    - composer install\n    - php artisan migrate"

internal fun codeEditorNode(
    value: String = "{}",
    language: String = "json",
    syncMode: String = "live",
    disabled: Boolean = false,
    readOnly: Boolean = false,
    extra: Map<String, Any> = emptyMap(),
) = NativeUINode(
    id = 7,
    props = GenericProps(mapOf(
        "value" to value,
        "label" to "Webhook payload",
        "helper" to "Sent as the request body.",
        "error" to "",
        "required" to true,
        "disabled" to disabled,
        "read_only" to readOnly,
        "language" to language,
        "line_numbers" to true,
        "wrap" to false,
        "indent" to 4,
        "follow" to false,
        "min_lines" to 8,
        "max_lines" to 24,
        "sync_mode" to syncMode,
        "debounce_ms" to 300,
        "a11y_label" to "",
        "a11y_hint" to "",
        "on_change" to 41,
    ) + extra),
)

private fun tree(target: NativeUINode) = NativeUITree(
    root = NativeUINode(id = 99, props = GenericProps(), children = listOf(target)),
)
