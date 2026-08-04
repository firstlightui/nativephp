package dev.firstlightui.plugins.firstlight_ui.ui

import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test

class SegmentedSelectionStateTest {
    @Test
    fun `enabled selection requests an event without changing the server value`() {
        val state = SegmentedSelectionState(selectedWireValue = "mine")

        assertTrue(state.select("all", enabled = true))
        assertEquals("mine", state.selectedWireValue)
        assertTrue(state.select("all", enabled = true))
    }

    @Test
    fun `disabled selection is a no-op`() {
        val state = SegmentedSelectionState(selectedWireValue = "mine")

        assertFalse(state.select("all", enabled = false))
        assertEquals("mine", state.selectedWireValue)
    }

    @Test
    fun `server publication changes the authoritative selection`() {
        val state = SegmentedSelectionState(selectedWireValue = "mine")
        state.select("all", enabled = true)

        state.reconcile(serverSelectedWireValue = "all")

        assertEquals("all", state.selectedWireValue)
    }

    @Test
    fun `unchanged server publication leaves the authoritative selection alone`() {
        val state = SegmentedSelectionState(selectedWireValue = "mine")
        state.select("all", enabled = true)

        state.reconcile(serverSelectedWireValue = "mine")

        assertEquals("mine", state.selectedWireValue)
    }

    @Test
    fun `authored empty value remains distinct from null selection`() {
        val values = listOf("", "all")

        assertNull(selectedIndex(hasSelection = false, selectedValue = "", optionValues = values))
        assertEquals(0, selectedIndex(hasSelection = true, selectedValue = "", optionValues = values))
    }
}
