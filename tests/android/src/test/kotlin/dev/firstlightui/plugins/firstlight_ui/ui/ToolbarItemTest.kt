package dev.firstlightui.plugins.firstlight_ui.ui

import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class ToolbarItemContractTest {
    @Test
    fun `a menu item is enabled by its menu, and disabled entries never dispatch`() {
        val attach = ToolbarConfiguration(notesToolbarNode()).items[1]

        assertTrue(attach.enabled)
        assertEquals(listOf(31, 0), attach.menu.map { it.callbackId })
        assertFalse(attach.menu[1].enabled)
        assertFalse(ToolbarItemConfiguration(toolbarItemNode(9, "star", "Star", disabled = true)).enabled)
        assertFalse(ToolbarItemConfiguration(toolbarItemNode(9, "star", "Star", callbackId = 0)).enabled)
    }
}
