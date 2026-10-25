package dev.firstlightui.plugins.firstlight_ui.ui

import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import org.junit.Assert.assertEquals
import org.junit.Test

class ButtonContractTest {
    @Test
    fun `the accessible name prefers the a11y label and adds the hint`() {
        assertEquals("settings recipe. Opens the bundled article", firstlightButtonDescription(button()))
        assertEquals("Save. Saves the form", firstlightButtonDescription(button(label = "Save", a11yLabel = "", hint = "Saves the form")))
        assertEquals("Save", firstlightButtonDescription(button(label = "Save", a11yLabel = "", hint = "")))
    }
}

private fun button(
    label: String = "→ settings recipe",
    a11yLabel: String = "settings recipe",
    hint: String = "Opens the bundled article",
) = NativeUINode(
    id = 12,
    type = "firstlight.button",
    props = GenericProps(mapOf("label" to label, "variant" to "ghost", "a11y_label" to a11yLabel, "a11y_hint" to hint)),
    onPress = 52,
)
