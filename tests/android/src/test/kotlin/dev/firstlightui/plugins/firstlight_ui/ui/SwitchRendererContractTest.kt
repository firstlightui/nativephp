package dev.firstlightui.plugins.firstlight_ui.ui

import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test

class SwitchRendererContractTest {
    @Test
    fun proposalKeepsAcceptedValueAndDeduplicatesUntilPublication() {
        val state = SwitchRendererState(node(value = false))

        assertEquals(SwitchRendererEvent.ToggleChange(41, 7, true), state.proposeChange())
        assertFalse(state.configuration.value)
        assertNull(state.proposeChange())
    }

    @Test
    fun identicalRejectedPublicationClearsPending() {
        val state = SwitchRendererState(node(value = false))
        state.proposeChange()

        assertFalse(state.serverPublished(tree(value = false)))
        assertFalse(state.configuration.value)
        assertEquals(SwitchRendererEvent.ToggleChange(41, 7, true), state.proposeChange())
    }

    @Test
    fun acceptedAndProgrammaticPublicationsUpdateOnlyAcceptedState() {
        val state = SwitchRendererState(node(value = false))

        assertTrue(state.serverPublished(tree(value = true)))
        assertTrue(state.configuration.value)
        assertEquals(SwitchRendererEvent.ToggleChange(41, 7, false), state.proposeChange())
        assertTrue(state.configuration.value)
    }

    @Test
    fun disabledSwitchDoesNotPropose() {
        val state = SwitchRendererState(node(value = false, disabled = true))

        assertNull(state.proposeChange())
        assertFalse(state.configuration.value)
    }

    @Test
    fun configurationUsesVisibleLabelAsAccessibilityFallbackAndErrorPrecedence() {
        val configuration = SwitchRendererConfiguration(
            node(
                value = true,
                helper = "Receive new activity updates.",
                error = "Notifications are required",
                a11yLabel = "",
            ),
        )

        assertEquals("Notifications", configuration.accessibilityLabel)
        assertEquals("Notifications are required", configuration.supportingText)
    }

    @Test
    fun everyPublicationReplacesMetadataAndClearsPendingByStableNodeId() {
        val state = SwitchRendererState(node(value = false))
        state.proposeChange()

        val published = node(
            value = false,
            helper = "Rejected by an administrator",
            error = "Notifications cannot be disabled",
        )
        val tree = NativeUITree(
            NativeUINode(id = 1, props = GenericProps(), children = listOf(published)),
        )

        assertFalse(state.serverPublished(tree))
        assertEquals("Rejected by an administrator", state.configuration.helper)
        assertEquals("Notifications cannot be disabled", state.configuration.error)
        assertEquals(SwitchRendererEvent.ToggleChange(41, 7, true), state.proposeChange())
    }

    private fun node(
        value: Boolean,
        disabled: Boolean = false,
        helper: String = "Receive new activity updates.",
        error: String = "",
        a11yLabel: String = "Receive notifications",
    ) = NativeUINode(
        id = 7,
        props = GenericProps(
            mapOf(
                "value" to value,
                "label" to "Notifications",
                "helper" to helper,
                "error" to error,
                "disabled" to disabled,
                "a11y_label" to a11yLabel,
                "a11y_hint" to "Controls notification delivery",
                "on_change" to 41,
            ),
        ),
    )

    private fun tree(value: Boolean) = NativeUITree(node(value))
}
