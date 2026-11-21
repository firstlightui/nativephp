package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.semantics.SemanticsActions
import androidx.compose.ui.semantics.SemanticsProperties
import androidx.compose.ui.test.ExperimentalTestApi
import androidx.compose.ui.test.onNodeWithTag
import androidx.compose.ui.test.onNodeWithText
import androidx.compose.ui.test.performTouchInput
import androidx.compose.ui.test.runComposeUiTest
import androidx.compose.ui.test.swipe
import androidx.compose.ui.unit.dp
import app.cash.paparazzi.DeviceConfig
import app.cash.paparazzi.Paparazzi
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Assume.assumeTrue
import org.junit.Rule
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import org.robolectric.annotation.GraphicsMode

// Published: Tab bar = [home, inbox, reports], More = [files]. Five slots per row.
// Callbacks match tests/Feature/ReorderListElementTest.php and the iOS contract tests:
// row r, slot s -> 100 + 10r + s, except Home (locked) and each row's current place.
internal fun reorderListNode(): NativeUINode {
    val current = listOf(listOf(0, 1, 2), listOf(1), listOf(2), listOf(4))
    val callbacks = (0 until 4).flatMap { row ->
        (0 until 5).map { slot -> if (row == 0 || slot in current[row]) "0" else (100 + 10 * row + slot).toString() }
    }
    return NativeUINode(
        id = 9,
        type = "firstlight.reorder-list",
        props = GenericProps(
            mutableMapOf<String, Any>(
                "section_keys" to listOf("bar", "more"),
                "section_labels" to listOf("Tab bar", "More"),
                "section_lengths" to listOf("3", "1"),
                "row_values" to listOf("home", "inbox", "reports", "files"),
                "row_labels" to listOf("Home", "Inbox", "Reports", "Files"),
                "row_supporting" to listOf("", "", "Weekly", ""),
                "row_icons" to listOf("home", "inbox", "", "folder"),
                "row_locked" to listOf("1", "0", "0", "0"),
                "move_slot_count" to 5,
                "move_callbacks" to callbacks,
                "label" to "Tabs",
                "helper" to "Up to 5 tabs. Home stays first.",
                "error" to "",
                "disabled" to false,
                "move_up_label" to "Move up",
                "move_down_label" to "Move down",
                "move_to_labels" to listOf("Move to Tab bar", "Move to More"),
                "locked_label" to "Locked",
                "position_label" to ":position of :count",
            ),
        ),
        onPress = 0,
        children = emptyList(),
    )
}

class ReorderListRendererContractTest {
    @Test
    fun `callback looks up the slot for each section and position`() {
        val configuration = ReorderListRendererConfiguration(reorderListNode())

        assertEquals(listOf(listOf(0, 1, 2), listOf(3)), configuration.publishedSections)
        assertEquals(110, configuration.callback(row = 1, section = 0, position = 0))
        assertEquals(0, configuration.callback(row = 1, section = 0, position = 1))
        assertEquals(114, configuration.callback(row = 1, section = 1, position = 1))
        assertEquals(133, configuration.callback(row = 3, section = 0, position = 3))
        assertEquals("beyond the end", 0, configuration.callback(row = 1, section = 1, position = 2))
        assertEquals("locked", 0, configuration.callback(row = 0, section = 1, position = 0))
        assertEquals("2 of 3", configuration.spokenPosition(2, 3))
    }

    @Test
    fun `drop across a header lands in that section`() {
        val entries = ReorderListEntry.entries(listOf(listOf(0, 1, 2), listOf(3)), showsHeaders = true)
        // [h0, r0, r1, r2, h1, r3]. Final indices: Inbox after Files is index 5; Files after Home is 2.
        assertEquals(1 to 1, ReorderListEntry.destination(entries, from = 2, to = 5))
        assertEquals(0 to 1, ReorderListEntry.destination(entries, from = 5, to = 2))
        // Dropping Reports just past the More header puts it first in More.
        assertEquals(1 to 0, ReorderListEntry.destination(entries, from = 3, to = 4))
    }

    @Test
    fun `move shows the draft and ignores moves until PHP publishes`() {
        val state = ReorderListRendererState(reorderListNode())

        assertEquals(ReorderListRendererEvent.Press(113, 9), state.userMoved(row = 1, section = 1, position = 0))
        assertEquals(listOf(listOf(0, 2), listOf(1, 3)), state.sections)
        assertNull(state.userMoved(row = 2, section = 0, position = 0))

        assertTrue(state.serverPublished(NativeUITree(root = reorderListNode())))
        assertEquals(listOf(listOf(0, 1, 2), listOf(3)), state.sections)
        assertFalse(state.isAwaitingPublication)
    }

    @Test
    fun `locked and unchanged moves emit nothing`() {
        val state = ReorderListRendererState(reorderListNode())

        assertNull(state.userMoved(row = 0, section = 1, position = 0))
        assertNull(state.userMoved(row = 2, section = 0, position = 2))
        assertFalse(state.isAwaitingPublication)
    }
}

@OptIn(ExperimentalTestApi::class)
@RunWith(RobolectricTestRunner::class)
@GraphicsMode(GraphicsMode.Mode.NATIVE)
class ReorderListInteractionTest {
    @Test
    fun `dragging a handle below the More header proposes one move into More`() = runComposeUiTest {
        val moves = mutableListOf<Triple<Int, Int, Int>>()
        val configuration = ReorderListRendererConfiguration(reorderListNode())
        setContent {
            MaterialTheme {
                FirstlightReorderListControl(
                    configuration = configuration,
                    sections = configuration.publishedSections,
                    onMove = { row, section, position -> moves += Triple(row, section, position); true },
                )
            }
        }

        // Reports is the last tab; drag it down past the More header and Files.
        onNodeWithTag(REORDER_LIST_HANDLE_TAG + 2, useUnmergedTree = true).performTouchInput {
            swipe(start = center, end = Offset(center.x, center.y + 600f), durationMillis = 400)
        }
        waitForIdle()

        assertEquals(listOf(Triple(2, 1, 1)), moves)
    }

    @Test
    fun `TalkBack reads the place and offers every move as an action`() = runComposeUiTest {
        val moves = mutableListOf<Triple<Int, Int, Int>>()
        val configuration = ReorderListRendererConfiguration(reorderListNode())
        setContent {
            MaterialTheme {
                FirstlightReorderListControl(
                    configuration = configuration,
                    sections = configuration.publishedSections,
                    onMove = { row, section, position -> moves += Triple(row, section, position); true },
                )
            }
        }

        val inbox = onNodeWithText("Inbox").fetchSemanticsNode().config
        assertEquals("Tab bar, 2 of 3", inbox[SemanticsProperties.StateDescription])
        val actions = inbox[SemanticsActions.CustomActions]
        assertEquals(listOf("Move up", "Move down", "Move to More"), actions.map { it.label })
        runOnIdle { assertTrue(actions.first { it.label == "Move to More" }.action()) }
        assertEquals(listOf(Triple(1, 1, 1)), moves)

        val home = onNodeWithText("Home").fetchSemanticsNode().config
        assertEquals("Tab bar, 1 of 3, Locked", home[SemanticsProperties.StateDescription])
        assertTrue(home.getOrElseNullable(SemanticsActions.CustomActions) { null }.isNullOrEmpty())
    }
}

class ReorderListControlScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test
    fun `Material light and dark evidence is controller gated`() {
        assumeTrue(System.getenv("FIRSTLIGHT_VERIFY_REORDER_LIST_SNAPSHOTS") == "1")

        paparazzi.snapshot("light") { reorderListScreenshotContent(dark = false) }
        paparazzi.snapshot("dark") { reorderListScreenshotContent(dark = true) }
    }
}

@Composable
private fun reorderListScreenshotContent(dark: Boolean) {
    FirstlightTestAppearance(dark = dark) {
        Surface(modifier = Modifier.fillMaxSize(), color = MaterialTheme.colorScheme.surfaceContainer) {
            ReorderListRenderer.Render(
                node = reorderListNode(),
                modifier = Modifier.padding(16.dp),
                events = ReorderListRendererEvents { },
            )
        }
    }
}
