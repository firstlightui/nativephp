package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.material3.MaterialTheme
import androidx.compose.ui.Modifier
import androidx.compose.ui.test.ExperimentalTestApi
import androidx.compose.ui.test.assertIsDisplayed
import androidx.compose.ui.test.getBoundsInRoot
import androidx.compose.ui.test.onNodeWithContentDescription
import androidx.compose.ui.test.onNodeWithText
import androidx.compose.ui.test.runComposeUiTest
import androidx.compose.ui.unit.dp
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import org.robolectric.annotation.Config

@OptIn(ExperimentalTestApi::class)
@RunWith(RobolectricTestRunner::class)
@Config(qualifiers = "w412dp-h892dp")
class BottomSheetTest {
    @Test
    fun `content taller than half the screen opens fully expanded`() = runComposeUiTest {
        // Tipoff's settings sheet: title, switch, three selects, then a large button.
        val children = listOf(
            child(2, "Settings", 56),
            child(3, "Notifications", 56),
            child(4, "Region", 96),
            child(5, "League", 96),
            child(6, "Team", 96),
            child(7, "Save", 56),
        )
        setContent {
            MaterialTheme { FirstlightBottomSheetRenderer.Render(sheetNode(children = children), Modifier) }
        }

        onNodeWithText("Save").assertIsDisplayed()
    }

    @Test
    fun `short content opens at its own height`() = runComposeUiTest {
        setContent {
            MaterialTheme {
                FirstlightBottomSheetRenderer.Render(sheetNode(children = listOf(child(2, "Filters", 120))), Modifier)
            }
        }

        onNodeWithText("Filters").assertIsDisplayed()
        val sheet = onNodeWithContentDescription("Filters sheet").getBoundsInRoot()
        assertEquals(120.dp.value, (sheet.bottom - sheet.top).value, 1f)
    }

    @Test
    fun `configuration decodes presentation and dismissal`() {
        val configuration = BottomSheetRendererConfiguration(sheetNode())

        assertTrue(configuration.visible)
        assertEquals("Filters sheet", configuration.accessibilityLabel)
        assertEquals(31, configuration.dismissCallback)
        assertEquals(1, configuration.nodeId)
    }

    @Test
    fun `closed sheet presents nothing`() = runComposeUiTest {
        setContent {
            MaterialTheme {
                FirstlightBottomSheetRenderer.Render(
                    sheetNode(visible = false, children = listOf(child(2, "Filters", 120))),
                    Modifier,
                )
            }
        }

        onNodeWithText("Filters").assertDoesNotExist()
    }

    private fun sheetNode(visible: Boolean = true, children: List<NativeUINode> = emptyList()) = NativeUINode(
        id = 1,
        type = "firstlight.bottom-sheet",
        props = GenericProps(mapOf(
            "visible" to visible,
            "a11y_label" to "Filters sheet",
            "on_dismiss" to 31,
        )),
        children = children,
    )

    private fun child(id: Int, label: String, height: Int) = NativeUINode(
        id = id,
        props = GenericProps(mapOf("label" to label, "height" to height)),
    )
}
