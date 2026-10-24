package dev.firstlightui.plugins.firstlight_ui.ui

import android.content.Context
import android.view.accessibility.AccessibilityManager
import androidx.compose.material3.MaterialTheme
import androidx.compose.ui.Modifier
import androidx.compose.ui.test.ExperimentalTestApi
import androidx.compose.ui.test.assertIsFocused
import androidx.compose.ui.test.assertIsNotFocused
import androidx.compose.ui.test.hasSetTextAction
import androidx.compose.ui.test.runComposeUiTest
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import org.robolectric.RuntimeEnvironment
import org.robolectric.Shadows.shadowOf

class ValidationAlertRuntimeTest {
    private val actions = mutableListOf<String>()

    private fun runtime(screenReader: Boolean = true) = FirstlightValidationAlertRuntime(
        screenReaderRunning = { screenReader },
        requestFocus = { actions += "focus"; true },
        announce = { actions += "announce: $it" },
    )

    @Test
    fun `decodes the one-time alert PHP publishes`() {
        val alert = FirstlightValidationAlert(node(7, focus = true))

        assertEquals(FirstlightValidationAlert(7, "There are 2 errors. A. B.", true), alert)
        assertTrue(FirstlightValidationAlert(NativeUINode(id = 3, props = GenericProps(mapOf("error" to "A")))).isEmpty)
    }

    @Test
    fun `focuses before announcing and acts once per revision`() {
        val runtime = runtime()
        val alert = FirstlightValidationAlert(7, "There are 2 errors. A. B.", true)

        runtime.handle(alert)
        runtime.handle(alert)
        runtime.handle(alert.copy(revision = 8))

        assertEquals(
            listOf("focus", "announce: There are 2 errors. A. B.", "focus", "announce: There are 2 errors. A. B."),
            actions,
        )
    }

    @Test
    fun `inline alerts announce without focus and nothing happens without TalkBack`() {
        runtime().handle(FirstlightValidationAlert(4, "The email field is required.", false))
        runtime(screenReader = false).handle(FirstlightValidationAlert(5, "Ignored.", true))

        assertEquals(listOf("announce: The email field is required."), actions)
    }
}

@RunWith(RobolectricTestRunner::class)
@OptIn(ExperimentalTestApi::class)
class ValidationAlertFocusTest {
    @After
    fun clearTree() {
        NativeUIBridge.currentTree.value = null
    }

    @Test
    fun `the first invalid text field takes input focus while TalkBack runs`() = runComposeUiTest {
        val accessibility = RuntimeEnvironment.getApplication()
            .getSystemService(Context.ACCESSIBILITY_SERVICE) as AccessibilityManager
        shadowOf(accessibility).setEnabled(true)
        shadowOf(accessibility).setTouchExplorationEnabled(true)
        val field = textField(focus = true)
        NativeUIBridge.currentTree.value = NativeUITree(NativeUINode(id = 1, props = GenericProps(), children = listOf(field)))

        setContent { MaterialTheme { TextFieldRenderer.Render(field, Modifier) } }

        onNode(hasSetTextAction()).assertIsFocused()
    }

    @Test
    fun `a field alert without focus leaves input focus alone`() = runComposeUiTest {
        val accessibility = RuntimeEnvironment.getApplication()
            .getSystemService(Context.ACCESSIBILITY_SERVICE) as AccessibilityManager
        shadowOf(accessibility).setEnabled(true)
        shadowOf(accessibility).setTouchExplorationEnabled(true)
        val field = textField(focus = false)
        NativeUIBridge.currentTree.value = NativeUITree(NativeUINode(id = 1, props = GenericProps(), children = listOf(field)))

        setContent { MaterialTheme { TextFieldRenderer.Render(field, Modifier) } }

        onNode(hasSetTextAction()).assertIsNotFocused()
    }

    private fun textField(focus: Boolean) = NativeUINode(
        id = 7,
        type = "firstlight.text-field",
        props = GenericProps(
            mapOf(
                "label" to "Email",
                "value" to "",
                "error" to "The email field is required.",
                "validation_alert" to 12,
                "validation_announcement" to "The email field is required.",
                "validation_focus" to focus,
            ),
        ),
    )
}

private fun node(revision: Int, focus: Boolean) = NativeUINode(
    id = 3,
    props = GenericProps(
        mapOf(
            "validation_alert" to revision,
            "validation_announcement" to "There are 2 errors. A. B.",
            "validation_focus" to focus,
        ),
    ),
)
