package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.TextRange
import androidx.compose.ui.text.input.TextFieldValue
import androidx.compose.ui.unit.dp
import app.cash.paparazzi.DeviceConfig
import app.cash.paparazzi.Paparazzi
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import com.nativephp.plugins.native_ui.NativeUITheme
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Rule
import org.junit.Test

class TextAreaContractTest {
    @Test fun `configuration decodes multiline field metadata`() {
        val configuration = TextAreaRendererConfiguration(node())
        assertEquals(4, configuration.minLines)
        assertEquals(10, configuration.maxLines)
        assertEquals("sentences", configuration.autocapitalize)
        assertEquals("disabled", configuration.autocorrectPolicy)
        assertEquals("Appointment notes", configuration.accessibilityLabel)
    }

    @Test fun `focused edits preserve selection composition and live publication`() {
        val state = TextAreaRendererState(node(value = "server"))
        state.focusChanged(true)
        val edited = TextFieldValue(
            text = "local draft",
            selection = TextRange(2, 7),
            composition = TextRange(1, 5),
        )

        assertEquals(TextAreaRendererEvent(41, 7, "local draft"), state.userChanged(edited))
        state.serverPublished(tree(node(value = "local draft")))
        assertEquals(edited, state.draft)

        state.serverPublished(tree(node(value = "corrected")))
        assertEquals(edited, state.draft)
        assertEquals("corrected", state.pendingServerValue)
        assertNull(state.focusChanged(false))
        assertEquals("corrected", state.draft.text)
    }

    @Test fun `blur and debounce keep edits local until flushed`() {
        val blur = TextAreaRendererState(node(syncMode = "blur"))
        blur.focusChanged(true)
        assertNull(blur.userChanged(TextFieldValue("two\nlines", TextRange(9))))
        assertEquals(TextAreaRendererEvent(41, 7, "two\nlines"), blur.focusChanged(false))

        val debounce = TextAreaRendererState(node(syncMode = "debounce"))
        debounce.focusChanged(true)
        assertNull(debounce.userChanged(TextFieldValue("debounced draft")))
        assertEquals(TextAreaRendererEvent(41, 7, "debounced draft"), debounce.flush())
        assertNull(debounce.flush())
    }

    @Test fun `disabled and read only fields reject native edits`() {
        val disabled = TextAreaRendererState(node(disabled = true))
        val readOnly = TextAreaRendererState(node(readOnly = true))
        assertNull(disabled.userChanged(TextFieldValue("changed")))
        assertNull(readOnly.userChanged(TextFieldValue("changed")))
        assertEquals("draft", disabled.draft.text)
        assertEquals("draft", readOnly.draft.text)
    }

    @Test fun `unfocused programmatic publication replaces text without event`() {
        val state = TextAreaRendererState(node())
        state.serverPublished(tree(node(value = "programmatic\nupdate")))
        assertEquals("programmatic\nupdate", state.draft.text)
        assertNull(state.flush())
    }

    private fun node(
        value: String = "draft",
        syncMode: String = "live",
        disabled: Boolean = false,
        readOnly: Boolean = false,
    ) = NativeUINode(
        id = 7,
        props = GenericProps(mapOf(
            "value" to value,
            "label" to "Clinical notes",
            "placeholder" to "Add notes",
            "helper" to "Relevant details only",
            "error" to "",
            "required" to true,
            "disabled" to disabled,
            "read_only" to readOnly,
            "min_lines" to 4,
            "max_lines" to 10,
            "autocapitalize" to "sentences",
            "autocorrect_policy" to "disabled",
            "sync_mode" to syncMode,
            "debounce_ms" to 300,
            "a11y_label" to "Appointment notes",
            "a11y_hint" to "Enter relevant clinical details",
            "on_change" to 41,
        )),
    )

    private fun tree(target: NativeUINode) = NativeUITree(
        root = NativeUINode(id = 99, props = GenericProps(), children = listOf(target)),
    )
}

class TextAreaScreenshotTest {
    @get:Rule val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test fun light() = snapshot(dark = false)
    @Test fun darkError() = snapshot(dark = true, error = "Add at least one observation")

    private fun snapshot(dark: Boolean, error: String = "") {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
                Surface(modifier = Modifier.fillMaxSize()) {
                    FirstlightTextAreaControl(
                        configuration = configuration(error),
                        draft = TextFieldValue("History\nObservation\nPlan"),
                        tokens = if (dark) NativeUITheme.dark else NativeUITheme.light,
                        onValueChange = {},
                        modifier = Modifier.padding(16.dp),
                    )
                }
            }
        }
    }
}

class TextAreaLargeTextScreenshotTest {
    @get:Rule val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2.0f))

    @Test fun fontScaleTwo() {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = lightColorScheme()) {
                Surface(modifier = Modifier.fillMaxSize()) {
                    FirstlightTextAreaControl(
                        configuration = configuration(""),
                        draft = TextFieldValue("A long clinical note that wraps across several accessible-size lines."),
                        tokens = NativeUITheme.light,
                        onValueChange = {},
                        modifier = Modifier.padding(16.dp),
                    )
                }
            }
        }
    }
}

private fun configuration(error: String) = TextAreaRendererConfiguration(
    nodeId = 7,
    value = "History\nObservation\nPlan",
    label = "Clinical notes",
    placeholder = "Add relevant history and observations",
    helper = "Relevant details only",
    error = error,
    required = true,
    disabled = false,
    readOnly = false,
    minLines = 4,
    maxLines = 8,
    autocapitalize = "sentences",
    autocorrectPolicy = "default",
    syncMode = "live",
    debounceMilliseconds = 300,
    accessibilityLabel = "Appointment notes",
    accessibilityHint = "Enter relevant clinical details",
    onChangeCallback = 41,
)
