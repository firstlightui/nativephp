package com.clinically.plugins.firstlight_ui.ui

import androidx.compose.runtime.snapshots.Snapshot
import androidx.compose.runtime.snapshots.SnapshotStateObserver
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test

class SegmentedSelectionStateTest {
    @Test
    fun `optimistic tap invalidates a composition state read`() {
        val state = SegmentedSelectionState(selectedWireValue = "mine")
        var invalidations = 0
        val observer = SnapshotStateObserver { command -> command() }
        val scope = Any()
        observer.start()

        try {
            observer.observeReads(scope, { invalidations++ }) {
                state.selectedWireValue
            }

            state.select("all", enabled = true)
            Snapshot.sendApplyNotifications()

            assertEquals(1, invalidations)
        } finally {
            observer.stop()
        }
    }

    @Test
    fun `new enabled selection updates locally and requests exactly one emission`() {
        val state = SegmentedSelectionState(selectedWireValue = "mine")

        assertTrue(state.select("all", enabled = true))
        assertEquals("all", state.selectedWireValue)
        assertFalse(state.select("all", enabled = true))
    }

    @Test
    fun `disabled selection is a no-op`() {
        val state = SegmentedSelectionState(selectedWireValue = "mine")

        assertFalse(state.select("all", enabled = false))
        assertEquals("mine", state.selectedWireValue)
    }

    @Test
    fun `matching server echo keeps optimistic selection without emission`() {
        val state = SegmentedSelectionState(selectedWireValue = "mine")
        state.select("all", enabled = true)

        state.reconcile(publicationId = 2, serverSelectedWireValue = "all")

        assertEquals("all", state.selectedWireValue)
    }

    @Test
    fun `different server correction replaces optimistic selection without emission`() {
        val state = SegmentedSelectionState(selectedWireValue = "mine")
        state.select("all", enabled = true)

        state.reconcile(publicationId = 2, serverSelectedWireValue = "archived")

        assertEquals("archived", state.selectedWireValue)
    }

    @Test
    fun `new equal-tree publication corrects rejected optimistic selection`() {
        val state = SegmentedSelectionState(selectedWireValue = "mine")
        state.reconcile(publicationId = 1, serverSelectedWireValue = "mine")
        state.select("all", enabled = true)

        state.reconcile(publicationId = 2, serverSelectedWireValue = "mine")

        assertEquals("mine", state.selectedWireValue)
    }

    @Test
    fun `same publication never rewinds optimistic selection`() {
        val state = SegmentedSelectionState(selectedWireValue = "mine")
        state.reconcile(publicationId = 1, serverSelectedWireValue = "mine")
        state.select("all", enabled = true)

        state.reconcile(publicationId = 1, serverSelectedWireValue = "mine")

        assertEquals("all", state.selectedWireValue)
    }

    @Test
    fun `authored empty value remains distinct from null selection`() {
        val values = listOf("", "all")

        assertNull(selectedIndex(hasSelection = false, selectedValue = "", optionValues = values))
        assertEquals(0, selectedIndex(hasSelection = true, selectedValue = "", optionValues = values))
    }
}
