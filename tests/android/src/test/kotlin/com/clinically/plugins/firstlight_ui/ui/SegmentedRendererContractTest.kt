package com.clinically.plugins.firstlight_ui.ui

import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import com.nativephp.plugins.native_ui.NativeUITheme
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test

class SegmentedRendererContractTest {
    @Test
    fun `resolved dark selected content is opaque visible and contrast safe`() {
        val resolved = resolveSegmentedTokenColors(NativeUITheme.dark)

        assertEquals(1f, resolved.activeContainer.alpha)
        assertEquals(1f, resolved.activeContent.alpha)
        assertTrue(resolved.activeContent != resolved.activeContainer)
        assertTrue(contrastRatio(resolved.activeContent, resolved.activeContainer) >= 4.5f)
    }

    @Test
    fun `string tap waits for the server and emits every rejected attempt`() {
        val state = SegmentedRendererState(node(valueType = "string", selectedValue = "mine"))

        val first = state.userSelected(1)
        val repeated = state.userSelected(1)

        assertEquals(0, state.selectedIndex)
        assertEquals(SegmentedRendererEvent.SelectChange(41, 7, "all"), first)
        assertEquals(SegmentedRendererEvent.SelectChange(41, 7, "all"), repeated)
        assertEquals("SELECT_CHANGE", first?.wireName)
    }

    @Test
    fun `integer tap emits PRESS with the option callback`() {
        val state = SegmentedRendererState(node(valueType = "integer", selectedValue = "mine"))

        val event = state.userSelected(1)

        assertEquals(SegmentedRendererEvent.Press(52, 7), event)
        assertEquals("PRESS", event?.wireName)
    }

    @Test
    fun `disabled option and disabled group are no-ops`() {
        val optionDisabled = SegmentedRendererState(
            node(valueType = "string", selectedValue = "mine", optionEnabled = listOf("1", "0")),
        )
        val groupDisabled = SegmentedRendererState(
            node(valueType = "string", selectedValue = "mine", disabled = true),
        )

        assertNull(optionDisabled.userSelected(1))
        assertEquals(0, optionDisabled.selectedIndex)
        assertNull(groupDisabled.userSelected(1))
        assertEquals(0, groupDisabled.selectedIndex)
    }

    @Test
    fun `unchanged-value rejection needs no corrective publication`() {
        val state = SegmentedRendererState(node(valueType = "string", selectedValue = "mine"))
        state.userSelected(1)

        assertFalse(state.serverPublished(tree(node(valueType = "string", selectedValue = "mine"))))
        assertEquals(0, state.selectedIndex)
    }

    @Test
    fun `changed server publication advances the visible selection`() {
        val state = SegmentedRendererState(node(valueType = "string", selectedValue = "mine"))
        state.userSelected(1)

        assertTrue(state.serverPublished(tree(node(valueType = "string", selectedValue = "all"))))
        assertEquals(1, state.selectedIndex)
    }

    @Test
    fun `field semantics merge label hint required and error text`() {
        assertEquals(
            FirstlightFieldSemantics(
                contentDescription = "Queue selection. Choose one queue",
                stateDescription = "Required",
                error = "Select a queue",
            ),
            firstlightFieldSemantics(
                accessibilityLabel = "Queue selection",
                accessibilityHint = "Choose one queue",
                required = true,
                error = "Select a queue",
            ),
        )
    }

    @Test
    fun `authored empty string is distinct from no selection`() {
        val absent = SegmentedRendererConfiguration(
            node(valueType = "string", selectedValue = "", hasSelection = false, values = listOf("", "all")),
        )
        val authoredEmpty = SegmentedRendererConfiguration(
            node(valueType = "string", selectedValue = "", hasSelection = true, values = listOf("", "all")),
        )

        assertNull(absent.serverSelectedIndex)
        assertEquals(0, authoredEmpty.serverSelectedIndex)
    }

    private fun node(
        valueType: String,
        selectedValue: String,
        hasSelection: Boolean = true,
        values: List<String> = listOf("mine", "all"),
        optionEnabled: List<String> = listOf("1", "1"),
        disabled: Boolean = false,
    ) = NativeUINode(
        id = 7,
        props = GenericProps(
            mapOf(
                "option_values" to values,
                "option_labels" to listOf("Mine", "All"),
                "option_enabled" to optionEnabled,
                "option_callbacks" to listOf("51", "52"),
                "value_type" to valueType,
                "has_selection" to hasSelection,
                "selected_value" to selectedValue,
                "on_change" to 41,
                "disabled" to disabled,
                "label" to "Queue",
                "helper" to "Choose a queue",
                "error" to "",
                "required" to true,
                "a11y_label" to "Queue selection",
                "a11y_hint" to "Choose one queue",
            ),
        ),
    )

    private fun tree(target: NativeUINode) = NativeUITree(
        root = NativeUINode(
            id = 99,
            props = GenericProps(),
            children = listOf(target),
        ),
    )
}
