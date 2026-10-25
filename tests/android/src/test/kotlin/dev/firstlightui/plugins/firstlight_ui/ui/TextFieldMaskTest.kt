package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.material3.MaterialTheme
import androidx.compose.ui.semantics.SemanticsProperties
import androidx.compose.ui.test.ExperimentalTestApi
import androidx.compose.ui.test.SemanticsMatcher
import androidx.compose.ui.test.assert
import androidx.compose.ui.test.hasSetTextAction
import androidx.compose.ui.test.performTextInput
import androidx.compose.ui.test.performTextInputSelection
import androidx.compose.ui.test.requestFocus
import androidx.compose.ui.test.runComposeUiTest
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.text.TextRange
import androidx.compose.ui.text.input.TextFieldValue
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import com.nativephp.plugins.native_ui.NativeUITheme
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner

class TextFieldMaskTest {
    private val card = FirstlightTextMask.of("#### #### #### ####")!!
    private val phone = FirstlightTextMask.of("(###) ###-####")!!
    private val postcode = FirstlightTextMask.of("A#A #A#")!!

    @Test fun `pattern without slots is not a mask`() {
        assertNull(FirstlightTextMask.of(""))
        assertNull(FirstlightTextMask.of("(---)"))
        assertEquals(16, card.slotCount)
    }

    @Test fun `format shows literals only before filled slots`() {
        assertEquals("", card.format(""))
        assertEquals("4111", card.format("4111"))
        assertEquals("4111 1", card.format("41111"))
        assertEquals("(4", phone.format("4"))
        assertEquals("(415) 555", phone.format("415555"))
        assertEquals("(415) 555-0123", phone.format("4155550123"))
        assertEquals("12%", FirstlightTextMask.of("##%")!!.format("12"))
    }

    @Test fun `conform accepts raw and formatted values and drops what does not fit`() {
        assertEquals("4111111111111111", card.conform("4111111111111111"))
        assertEquals("4111111111111111", card.conform("4111 1111-1111 1111 9999"))
        assertEquals("4155550123", phone.conform("(415) 555-0123"))
        assertEquals("k1a0b1", postcode.conform("k1a 0b1"))
        assertEquals("K", postcode.conform("1KA"))
        assertEquals("a1b2", FirstlightTextMask.of("**-**")!!.conform("a1-b2"))
        assertEquals("4111", card.conform("٤١١١"))
        val international = FirstlightTextMask.of("+61 ### ###")!!
        assertEquals("614123", international.conform("614123"))
        assertEquals("412345", international.conform("+61 412 345"))
    }

    @Test fun `offset mapping keeps the caret beside raw characters`() {
        assertEquals(0, card.displayOffset(0, 5))
        assertEquals(4, card.displayOffset(4, 5))
        assertEquals(6, card.displayOffset(5, 5))
        assertEquals(19, card.displayOffset(16, 16))
        assertEquals(4, card.rawOffset(5, 5))
        assertEquals(5, card.rawOffset(6, 5))
        assertEquals(1, phone.rawOffset(2, 1))
    }

    @Test fun `raw buffer edits reject what does not fit and stop when full`() {
        assertEquals("41112" to 5, card.edit("4111", "41112", 5))
        assertEquals("4111" to 4, card.edit("4111", "4111x", 5))
        assertEquals("41111" to 2, card.edit("4111", "41111", 2))
        val full = "4111111111111111"
        assertEquals(full to 16, card.edit(full, full + "2", 17))
        assertEquals("4155550123" to 10, phone.edit("", "(415) 555-0123", 14))
        assertEquals("M1A" to 1, postcode.replace("K1A", 0, 0, "M"))
        // Compose deletes across a displayed literal in the raw buffer: "4111 2" backspace after the space.
        assertEquals("4112" to 3, card.edit("41112", "4112", 3))
    }
}

@OptIn(ExperimentalTestApi::class)
@RunWith(RobolectricTestRunner::class)
class TextFieldMaskEditingTest {
    @Test fun `typing through the Material field formats the display and publishes raw slots`() = runComposeUiTest {
        val events = mutableListOf<TextFieldRendererEvent>()
        val state = TextFieldRendererState(maskedNode(mask = "#### #### #### ####"))
        setContent { MaterialTheme { control(state, events) } }

        val field = onNode(hasSetTextAction())
        field.requestFocus()
        field.performTextInput("4111")
        field.performTextInput("1x2")
        field.assert(SemanticsMatcher.expectValue(SemanticsProperties.EditableText, AnnotatedString("4111 12")))
        assertEquals("411112", state.draft.text)
        assertEquals(TextFieldRendererEvent.Change(41, 7, "411112"), events.last())

        field.performTextInputSelection(TextRange(2))
        field.performTextInput("9")
        assertEquals("4191112", state.draft.text)
        assertEquals(TextRange(3), state.draft.selection)
        field.assert(SemanticsMatcher.expectValue(SemanticsProperties.EditableText, AnnotatedString("4191 112")))
    }

    @Test fun `pasted formatted text fills slots and input stops when full`() = runComposeUiTest {
        val state = TextFieldRendererState(maskedNode(mask = "(###) ###-####"))
        setContent { MaterialTheme { control(state, mutableListOf()) } }

        val field = onNode(hasSetTextAction())
        field.requestFocus()
        field.performTextInput("(415) 555-0123")
        field.performTextInput("9")
        assertEquals("4155550123", state.draft.text)
        field.assert(SemanticsMatcher.expectValue(SemanticsProperties.EditableText, AnnotatedString("(415) 555-0123")))
    }

    @Test fun `server values are conformed without publishing and acknowledgements compare raw values`() {
        val state = TextFieldRendererState(maskedNode(mask = "#### ####", value = "4111-1111-99", syncMode = "blur"))
        assertEquals("41111111", state.draft.text)
        state.focusChanged(true)
        assertNull(state.focusChanged(false))

        val live = TextFieldRendererState(maskedNode(mask = "#### ####"))
        live.focusChanged(true)
        assertEquals(TextFieldRendererEvent.Change(41, 7, "41112"), live.userChanged(TextFieldValue("41112", TextRange(5))))
        live.serverPublished(NativeUITree(root = NativeUINode(id = 99, props = GenericProps(), children = listOf(maskedNode(mask = "#### ####", value = "4111 2")))))
        assertNull(live.pendingServerValue)
        assertEquals("41112", live.draft.text)
    }

    @androidx.compose.runtime.Composable
    private fun control(state: TextFieldRendererState, events: MutableList<TextFieldRendererEvent>) {
        FirstlightTextFieldControl(
            configuration = state.configuration,
            draft = state.draft,
            revealed = false,
            tokens = NativeUITheme.light,
            onValueChange = { value -> state.userChanged(value)?.let(events::add) },
            onClear = {},
            onReveal = {},
            onTrailingPress = {},
            onSubmit = {},
        )
    }

    private fun maskedNode(mask: String, value: String = "", syncMode: String = "live") = NativeUINode(
        id = 7,
        props = GenericProps(
            mapOf(
                "value" to value,
                "label" to "Card number",
                "mask" to mask,
                "keyboard" to "number",
                "sync_mode" to syncMode,
                "on_change" to 41,
            ),
        ),
    )
}
