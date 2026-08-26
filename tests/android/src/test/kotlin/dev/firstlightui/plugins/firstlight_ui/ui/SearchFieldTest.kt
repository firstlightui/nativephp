package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.ui.text.TextRange
import androidx.compose.ui.text.input.TextFieldValue
import app.cash.paparazzi.DeviceConfig
import app.cash.paparazzi.Paparazzi
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Assume.assumeTrue
import org.junit.Rule
import org.junit.Test

class SearchFieldTest {
    @get:Rule val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test fun `configuration decodes focused search props`() {
        val configuration = SearchFieldRendererConfiguration(node())
        assertEquals("cardiology", configuration.value)
        assertEquals("Search specialties", configuration.placeholder)
        assertEquals("words", configuration.autocapitalize)
        assertEquals("disabled", configuration.autocorrectPolicy)
        assertEquals("Search specialties", configuration.accessibilityLabel)
        assertEquals(41, configuration.onChangeCallback)
        assertEquals(42, configuration.onSubmitCallback)
        assertEquals("Clear search", configuration.clearA11yLabel)
    }

    @Test fun `focused acknowledgements preserve selection composition and corrections wait`() {
        val state = SearchFieldRendererState(node(value = "server"))
        state.focusChanged(true)
        val local = TextFieldValue("local", selection = TextRange(2), composition = TextRange(1, 3))

        assertEquals(SearchFieldRendererEvent.Change(41, 7, "local"), state.userChanged(local))
        state.serverPublished(tree(node(value = "local")))
        assertEquals(local, state.draft)

        state.serverPublished(tree(node(value = "corrected")))
        assertEquals(local, state.draft)
        assertEquals("corrected", state.pendingServerValue)
    }

    @Test fun `blur flushes before submit and empty query still submits`() {
        val state = SearchFieldRendererState(node(value = "", syncMode = "blur"))
        state.focusChanged(true)
        assertNull(state.userChanged("referral"))
        assertEquals(listOf("TEXT_CHANGE", "SUBMIT"), state.submit().map { it.wireName })

        val empty = SearchFieldRendererState(node(value = ""))
        assertEquals(listOf(SearchFieldRendererEvent.Submit(42, 7, "")), empty.submit())
    }

    @Test fun `clear commits immediately retains focus and disabled controls are inert`() {
        val state = SearchFieldRendererState(node(value = "query", syncMode = "debounce"))
        state.focusChanged(true)
        assertEquals(SearchFieldRendererEvent.Change(41, 7, ""), state.clear())
        assertTrue(state.focused)

        val disabled = SearchFieldRendererState(node(disabled = true))
        assertNull(disabled.userChanged("changed"))
        assertNull(disabled.clear())
        assertTrue(disabled.submit().isEmpty())
    }

    @Test fun `Material light dark and large text snapshots are controller gated`() {
        assumeTrue(System.getenv("FIRSTLIGHT_VERIFY_SEARCH_FIELD_SNAPSHOTS") == "1")
        paparazzi.snapshot {
            MaterialTheme {
                Surface {
                    FirstlightSearchFieldControl(
                        configuration = SearchFieldRendererConfiguration(node()),
                        draft = TextFieldValue("Referral"),
                        onValueChange = {}, onClear = {}, onSubmit = {},
                    )
                }
            }
        }
    }

    private fun node(
        value: String = "cardiology",
        syncMode: String = "live",
        disabled: Boolean = false,
    ) = NativeUINode(
        id = 7,
        props = GenericProps(mapOf(
            "value" to value,
            "placeholder" to "Search specialties",
            "disabled" to disabled,
            "autocapitalize" to "words",
            "autocorrect_policy" to "disabled",
            "sync_mode" to syncMode,
            "debounce_ms" to 300,
            "a11y_label" to "Search specialties",
            "a11y_hint" to "Enter a specialty name",
            "on_change" to 41,
            "on_submit" to 42,
        )),
    )

    private fun tree(target: NativeUINode) = NativeUITree(
        root = NativeUINode(id = 99, props = GenericProps(), children = listOf(target)),
    )
}
