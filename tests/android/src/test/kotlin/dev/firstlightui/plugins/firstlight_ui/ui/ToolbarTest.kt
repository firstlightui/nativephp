package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import app.cash.paparazzi.DeviceConfig
import app.cash.paparazzi.Paparazzi
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Rule
import org.junit.Test

internal fun toolbarItemNode(
    id: Int,
    icon: String,
    label: String,
    callbackId: Int = id * 10,
    primary: Boolean = false,
    disabled: Boolean = false,
    badge: String = "",
    menuJson: String = "",
) = NativeUINode(
    id = id,
    type = "firstlight.toolbar-item",
    onPress = callbackId,
    props = GenericProps(
        mapOf(
            "icon" to icon,
            "a11y_label" to label,
            "primary" to primary,
            "disabled" to disabled,
            "badge" to badge,
            "menu_json" to menuJson,
        ),
    ),
)

internal fun notesToolbarNode(id: Int = 1) = NativeUINode(
    id = id,
    type = "firstlight.toolbar",
    props = GenericProps(emptyMap()),
    children = listOf(
        toolbarItemNode(2, "checklist", "Checklist"),
        toolbarItemNode(3, "attach_file", "Attach", callbackId = 0, menuJson = """[{"cb":31,"label":"Photo","icon":"photo","icon_variant":"","role":"","disabled":false},{"cb":0,"label":"File","icon":"","icon_variant":"","role":"","disabled":true}]"""),
        toolbarItemNode(4, "draw", "Markup", badge = "3"),
        toolbarItemNode(5, "edit_square", "New note", primary = true),
    ),
)

class ToolbarContractTest {
    @After
    fun clearRegistry() {
        FirstlightToolbarRegistry.entries.clear()
    }

    @Test
    fun `configuration splits the primary action from the grouped actions`() {
        val configuration = ToolbarConfiguration(notesToolbarNode())

        assertEquals(listOf("Checklist", "Attach", "Markup"), configuration.items.map { it.accessibilityLabel })
        assertEquals("New note", configuration.primary?.accessibilityLabel)
        assertEquals("3", configuration.items[2].badge)
        assertEquals("", configuration.items[0].badge)
    }

    @Test
    fun `the newest toolbar on screen wins and a removed one drops out`() {
        val leaving = ToolbarConfiguration(notesToolbarNode(id = 1))
        val arriving = ToolbarConfiguration(notesToolbarNode(id = 100))

        FirstlightToolbarRegistry.publish(leaving)
        FirstlightToolbarRegistry.publish(arriving)
        FirstlightToolbarRegistry.publish(leaving.copy(primary = null))
        assertEquals(100, FirstlightToolbarRegistry.current?.nodeId)
        assertEquals(2, FirstlightToolbarRegistry.entries.size)

        FirstlightToolbarRegistry.remove(100)
        assertEquals(1, FirstlightToolbarRegistry.current?.nodeId)
        assertNull(FirstlightToolbarRegistry.current?.primary)

        FirstlightToolbarRegistry.remove(1)
        assertNull(FirstlightToolbarRegistry.current)
    }

    @Test
    fun `the toolbar clears the tab bar only when the screen shows one`() {
        fun root(type: String, hidden: Boolean) =
            NativeUINode(id = 0, type = type, props = GenericProps(mapOf("hide_tab_bar" to hidden)))

        assertTrue(root("native_root_tabs", hidden = false).showsTabBar())
        assertFalse(root("native_root_tabs", hidden = true).showsTabBar())
        assertFalse(root("native_root_stack", hidden = false).showsTabBar())
    }
}

class ToolbarScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test
    fun light() = snapshot(dark = false)

    @Test
    fun dark() = snapshot(dark = true)

    private fun snapshot(dark: Boolean) {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
                Surface(modifier = Modifier.fillMaxSize()) {
                    Box(Modifier.fillMaxSize()) {
                        FirstlightFloatingToolbar(
                            configuration = ToolbarConfiguration(notesToolbarNode()),
                            modifier = Modifier.align(Alignment.BottomCenter).padding(bottom = 16.dp),
                            onPress = { _, _ -> },
                        )
                    }
                }
            }
        }
    }
}
