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
class CodeEditorEditingTest {
    @Test fun `every shared fixture case produces the expected text and selection`() {
        val cases = JSONObject(fixtureFile().readText()).getJSONArray("cases")
        val failures = mutableListOf<String>()

        for (index in 0 until cases.length()) {
            val case = cases.getJSONObject(index)
            val before = MarkedText.parse(case.getString("before"))
            val expected = MarkedText.parse(case.getString("after"))
            val actual = CodeEditorEditing.apply(
                before.text,
                before.selection,
                operation(case.getString("operation")),
                CodeEditorLanguage.from(case.getString("language")),
                case.getInt("indent"),
            )
            if (actual.text != expected.text || actual.selection != expected.selection) {
                failures += "${case.getString("name")}: expected ${case.getString("after")} " +
                    "got ${MarkedText.render(actual.text, actual.selection)}"
            }
        }

        assertEquals(failures.joinToString("\n"), 0, failures.size)
        assertEquals(true, cases.length() > 0)
    }

    @Test fun `unknown language decodes as plain`() {
        assertEquals(CodeEditorLanguage.Plain, CodeEditorLanguage.from("ruby"))
        assertEquals(CodeEditorLanguage.Plain, CodeEditorLanguage.from(null))
    }

    private fun operation(raw: String): CodeEditorOperation = when {
        raw == "return" -> CodeEditorOperation.Return
        raw == "indent" -> CodeEditorOperation.Indent
        raw == "outdent" -> CodeEditorOperation.Outdent
        raw == "toggle-comment" -> CodeEditorOperation.ToggleComment
        raw.startsWith("insert:") -> CodeEditorOperation.Insert(raw.removePrefix("insert:"))
        else -> error("Unknown fixture operation $raw")
    }

    private fun fixtureFile(): File =
        generateSequence(File("").absoluteFile) { it.parentFile }
            .map { File(it, "tests/fixtures/code-editor/editing.json") }
            .first { it.isFile }
}

private data class MarkedText(val text: String, val selection: CodeEditorSelection) {
    companion object {
        private const val CURSOR = '‸'
        private const val START = '«'
        private const val END = '»'

        fun parse(marked: String): MarkedText {
            val cursor = marked.indexOf(CURSOR)
            if (cursor >= 0) {
                return MarkedText(marked.removeRange(cursor, cursor + 1), CodeEditorSelection(cursor, cursor))
            }
            val start = marked.indexOf(START)
            val end = marked.indexOf(END) - 1
            val text = marked.replace(START.toString(), "").replace(END.toString(), "")
            return MarkedText(text, CodeEditorSelection(start, end))
        }

        fun render(text: String, selection: CodeEditorSelection): String =
            if (selection.collapsed) {
                text.substring(0, selection.start) + CURSOR + text.substring(selection.start)
            } else {
                text.substring(0, selection.start) + START +
                    text.substring(selection.start, selection.end) + END + text.substring(selection.end)
            }
    }
}
