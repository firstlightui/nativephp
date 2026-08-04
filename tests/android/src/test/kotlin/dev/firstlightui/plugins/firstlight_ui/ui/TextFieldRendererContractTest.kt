package dev.firstlightui.plugins.firstlight_ui.ui

import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test

class TextFieldRendererContractTest {
    @Test
    fun `configuration decodes input hints icons and variants`() {
        val configuration = TextFieldRendererConfiguration(node())

        assertEquals("email", configuration.keyboard)
        assertEquals("email", configuration.contentType)
        assertEquals("none", configuration.autocapitalize)
        assertEquals("disabled", configuration.autocorrectPolicy)
        assertEquals("send", configuration.submitLabel)
        assertEquals("envelope", configuration.leadingIcon)
        assertEquals("outlined", configuration.leadingIconVariant)
        assertEquals(43, configuration.onPressCallback)
    }

    @Test
    fun `focused acknowledgements preserve drafts and corrections wait`() {
        val state = TextFieldRendererState(node(value = "server"))
        state.focusChanged(true)

        assertEquals(TextFieldRendererEvent.Change(41, 7, "local"), state.userChanged("local"))
        state.serverPublished(tree(node(value = "local")))
        assertEquals("local", state.draft.text)

        state.serverPublished(tree(node(value = "corrected")))
        assertEquals("local", state.draft.text)
        assertEquals("corrected", state.pendingServerValue)
    }

    @Test
    fun `blur submit clear and reveal follow semantic event rules`() {
        val blur = TextFieldRendererState(node(syncMode = "blur"))
        blur.focusChanged(true)
        assertNull(blur.userChanged("edited"))
        assertEquals(listOf("TEXT_CHANGE", "SUBMIT"), blur.submit().map { it.wireName })

        val clear = TextFieldRendererState(node(value = "query", clearable = true))
        clear.focusChanged(true)
        assertEquals(TextFieldRendererEvent.Change(41, 7, ""), clear.clear())
        assertEquals("", clear.draft.text)
        assertTrue(clear.focused)

        val reveal = TextFieldRendererState(node(secure = true, revealable = true))
        assertFalse(reveal.revealed)
        reveal.toggleReveal()
        assertTrue(reveal.revealed)
        assertEquals("draft", reveal.draft.text)
    }

    @Test
    fun `disabled and read only fields reject edits and clear`() {
        val disabled = TextFieldRendererState(node(disabled = true, clearable = true))
        val readOnly = TextFieldRendererState(node(readOnly = true, clearable = true))

        assertNull(disabled.userChanged("changed"))
        assertNull(disabled.clear())
        assertNull(readOnly.userChanged("changed"))
        assertNull(readOnly.clear())
    }

    private fun node(
        value: String = "draft",
        syncMode: String = "live",
        secure: Boolean = false,
        disabled: Boolean = false,
        readOnly: Boolean = false,
        clearable: Boolean = false,
        revealable: Boolean = false,
    ) = NativeUINode(
        id = 7,
        props = GenericProps(
            mapOf(
                "value" to value,
                "label" to "Email",
                "placeholder" to "you@example.com",
                "helper" to "Supporting",
                "error" to "",
                "keyboard" to "email",
                "content_type" to "email",
                "autocapitalize" to "none",
                "autocorrect_policy" to "disabled",
                "submit_label" to "send",
                "leading_icon" to "envelope",
                "leading_icon_variant" to "outlined",
                "trailing_icon" to "send",
                "trailing_icon_variant" to "filled",
                "trailing_a11y_label" to "Send",
                "sync_mode" to syncMode,
                "debounce_ms" to 300,
                "secure" to secure,
                "disabled" to disabled,
                "read_only" to readOnly,
                "clearable" to clearable,
                "revealable" to revealable,
                "on_change" to 41,
                "on_submit" to 42,
            ),
        ),
        onPress = 43,
    )

    private fun tree(target: NativeUINode) = NativeUITree(
        root = NativeUINode(id = 99, props = GenericProps(), children = listOf(target)),
    )
}
