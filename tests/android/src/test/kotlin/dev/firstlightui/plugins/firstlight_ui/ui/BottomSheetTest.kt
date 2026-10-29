package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.material3.MaterialTheme
import androidx.compose.ui.Modifier
import androidx.compose.ui.test.ExperimentalTestApi
import androidx.compose.ui.test.assertIsDisplayed
import androidx.compose.ui.test.getBoundsInRoot
import androidx.compose.ui.test.getUnclippedBoundsInRoot
import androidx.compose.ui.test.hasScrollToIndexAction
import androidx.compose.ui.test.onNodeWithContentDescription
import androidx.compose.ui.test.onNodeWithText
import androidx.compose.ui.test.performScrollTo
import androidx.compose.ui.test.performScrollToIndex
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
    fun `authored scroll view stays bounded with and without a pinned footer`() {
        for (pinned in listOf(false, true)) {
            runComposeUiTest {
                val scroller = NativeUINode(
                    id = 30,
                    type = "scroll_view",
                    props = GenericProps(),
                    children = (2..25).map { child(it, "Nested item $it", 64) },
                )
                val body = NativeUINode(id = 31, type = "column", props = GenericProps(), children = listOf(scroller))
                val children = listOf(body) + if (pinned) listOf(child(32, "Total and Save", 96)) else emptyList()
                setContent {
                    MaterialTheme {
                        FirstlightBottomSheetRenderer.Render(sheetNode(children = children, pinnedFooter = pinned), Modifier)
                    }
                }
                val before = if (pinned) onNodeWithText("Total and Save").getBoundsInRoot() else null
                onNode(hasScrollToIndexAction()).performScrollToIndex(23)
                onNodeWithText("Nested item 25").assertIsDisplayed()
                val last = onNodeWithText("Nested item 25").getBoundsInRoot()
                assertEquals(onNodeWithText("Nested item 25").getUnclippedBoundsInRoot(), last)
                if (pinned) {
                    onNodeWithText("Total and Save").assertIsDisplayed()
                    assertEquals(before, onNodeWithText("Total and Save").getBoundsInRoot())
                    assertTrue(last.bottom <= before!!.top)
                }
            }
        }
    }

    @Test
    fun `oversized content scrolls while the final footer stays visible`() = runComposeUiTest {
        val children = (2..25).map { child(it, "Bill item $it", 64) } + child(26, "Total and Save", 96)
        setContent {
            MaterialTheme {
                FirstlightBottomSheetRenderer.Render(sheetNode(children = children, pinnedFooter = true), Modifier)
            }
        }
        onNodeWithText("Total and Save").assertIsDisplayed()
        val before = onNodeWithText("Total and Save").getBoundsInRoot()
        onNodeWithText("Bill item 25").performScrollTo().assertIsDisplayed()
        assertEquals(before, onNodeWithText("Total and Save").getBoundsInRoot())
    }

    @Test
    fun `oversized content scrolls without a footer`() = runComposeUiTest {
        setContent {
            MaterialTheme {
                FirstlightBottomSheetRenderer.Render(sheetNode(children = (2..25).map { child(it, "Item $it", 64) }), Modifier)
            }
        }
        onNodeWithText("Item 25").performScrollTo().assertIsDisplayed()
    }

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

    private fun sheetNode(visible: Boolean = true, children: List<NativeUINode> = emptyList(), pinnedFooter: Boolean = false) = NativeUINode(
        id = 1,
        type = "firstlight.bottom-sheet",
        props = GenericProps(mapOf(
            "visible" to visible,
            "a11y_label" to "Filters sheet",
            "on_dismiss" to 31,
            "pinned_footer" to pinnedFooter,
        )),
        children = children,
    )

    private fun child(id: Int, label: String, height: Int) = NativeUINode(
        id = id,
        props = GenericProps(mapOf("label" to label, "height" to height)),
    )
}
