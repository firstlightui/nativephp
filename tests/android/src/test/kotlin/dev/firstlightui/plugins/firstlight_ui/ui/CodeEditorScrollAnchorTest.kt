package dev.firstlightui.plugins.firstlight_ui.ui

import java.io.File
import org.json.JSONObject
import org.junit.Assert.assertEquals
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import org.robolectric.annotation.Config

@RunWith(RobolectricTestRunner::class)
@Config(sdk = [29])
class CodeEditorScrollAnchorTest {
    @Test fun `every shared scroll-anchor case maps to the expected line`() {
        val cases = JSONObject(fixtureFile().readText()).getJSONArray("cases")
        val failures = mutableListOf<String>()

        for (index in 0 until cases.length()) {
            val case = cases.getJSONObject(index)
            val expected = if (case.isNull("expected_line")) null else case.getInt("expected_line")
            val actual = CodeEditorScrollAnchor.anchorLine(
                case.getString("before"),
                case.getString("after"),
                case.getInt("anchor_line"),
            )
            if (actual != expected) failures += "${case.getString("name")}: expected $expected got $actual"
        }

        assertEquals(failures.joinToString("\n"), 0, failures.size)
        assertEquals(true, cases.length() > 0)
    }

    @Test fun `out of range anchors fall back`() {
        assertEquals(null, CodeEditorScrollAnchor.anchorLine("a\nb", "a\nb", 5))
        assertEquals(null, CodeEditorScrollAnchor.anchorLine("a\nb", "a\nb", -1))
    }

    private fun fixtureFile(): File =
        generateSequence(File("").absoluteFile) { it.parentFile }
            .map { File(it, "tests/fixtures/code-editor/scroll-anchor.json") }
            .first { it.isFile }
}
