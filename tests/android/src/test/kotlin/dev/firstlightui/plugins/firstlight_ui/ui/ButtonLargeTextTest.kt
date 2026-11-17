package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.width
import androidx.compose.material3.Text
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.ui.Modifier
import androidx.compose.ui.layout.onSizeChanged
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.test.ExperimentalTestApi
import androidx.compose.ui.test.runComposeUiTest
import androidx.compose.ui.unit.Density
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.nativephp.mobile.ui.nativerender.GenericProps
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import org.robolectric.annotation.GraphicsMode

class ButtonMirroringTest {
    @Test
    fun `right to left swaps directional icons and leaves others alone`() {
        val props = GenericProps(mapOf("leading_icon" to "arrow-back", "trailing_icon" to "chevron_right", "label" to "Continue"))

        val rtl = firstlightButtonMirroredProps(props, rightToLeft = true)
        assertEquals("arrow_forward", rtl.getString("leading_icon"))
        assertEquals("chevron_left", rtl.getString("trailing_icon"))
        assertEquals("Continue", rtl.getString("label"))

        val ltr = firstlightButtonMirroredProps(props, rightToLeft = false)
        assertEquals("arrow-back", ltr.getString("leading_icon"))
        assertEquals("chevron_right", ltr.getString("trailing_icon"))
        assertEquals("delete", firstlightDirectionalIconName("delete", rightToLeft = true))
        assertEquals("chevron_left", firstlightDirectionalIconName("chevron.right", rightToLeft = true))
        assertEquals("back", firstlightDirectionalIconName("forward", rightToLeft = true))
        assertTrue(firstlightButtonMirroredProps(props, rightToLeft = false) === props)
    }
}

@OptIn(ExperimentalTestApi::class)
@RunWith(RobolectricTestRunner::class)
@GraphicsMode(GraphicsMode.Mode.NATIVE)
class ButtonLargeTextTest {
    private val label = "Hide activity indicator"

    @Test
    fun `a single line label shrinks to fit instead of overflowing at twice the font scale`() = runComposeUiTest {
        var unfitted = false
        var fitted = true
        setContent {
            CompositionLocalProvider(LocalDensity provides Density(LocalDensity.current.density, 2f)) {
                Row(Modifier.width(260.dp)) {
                    Text(label, fontSize = 16.sp, maxLines = 1, softWrap = false, onTextLayout = { unfitted = it.didOverflowWidth })
                }
                Row(Modifier.width(260.dp)) {
                    FirstlightFitLabelWidth(Modifier.fillMaxWidth()) {
                        Text(label, fontSize = 16.sp, maxLines = 1, softWrap = false, onTextLayout = { fitted = it.didOverflowWidth })
                    }
                }
            }
        }
        waitForIdle()

        assertTrue("the control label overflows without the fit", unfitted)
        assertFalse("the fitted label overflows", fitted)
    }

    @Test
    fun `row weights and fill width still reach the button`() = runComposeUiTest {
        val widths = IntArray(2)
        var rowWidth = 0
        setContent {
            CompositionLocalProvider(LocalDensity provides Density(LocalDensity.current.density, 2f)) {
                Row(Modifier.width(300.dp).onSizeChanged { rowWidth = it.width }) {
                    FirstlightFitLabelWidth(Modifier.weight(2f).onSizeChanged { widths[0] = it.width }) { Text("Save") }
                    FirstlightFitLabelWidth(Modifier.weight(1f).onSizeChanged { widths[1] = it.width }) { Text("No") }
                }
            }
        }
        waitForIdle()

        assertEquals(rowWidth * 2 / 3f, widths[0].toFloat(), 2f)
        assertEquals(rowWidth / 3f, widths[1].toFloat(), 2f)
    }
}
