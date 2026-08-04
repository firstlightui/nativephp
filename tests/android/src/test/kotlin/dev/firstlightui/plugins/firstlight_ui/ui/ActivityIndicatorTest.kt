package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.LiveRegionMode
import androidx.compose.ui.semantics.SemanticsActions
import androidx.compose.ui.semantics.SemanticsConfiguration
import androidx.compose.ui.semantics.SemanticsProperties
import androidx.compose.ui.unit.dp
import app.cash.paparazzi.DeviceConfig
import app.cash.paparazzi.Paparazzi
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import com.nativephp.plugins.native_ui.NativeUITheme
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Rule
import org.junit.Test

class ActivityIndicatorContractTest {
    @Test
    fun `configuration decodes semantic size and required name`() {
        val configuration = ActivityIndicatorRendererConfiguration(
            node(size = "lg", label = "Loading appointments"),
        )

        assertEquals(ActivityIndicatorSize.Large, configuration.size)
        assertEquals("Loading appointments", configuration.accessibilityLabel)
        assertFalse(configuration.isInteractive)
    }

    @Test
    fun `malformed native size falls back to medium without crashing`() {
        assertEquals(
            ActivityIndicatorSize.Medium,
            ActivityIndicatorRendererConfiguration(node(size = "oversized")).size,
        )
    }

    @Test
    fun `identical publication does not change mounted configuration`() {
        val state = ActivityIndicatorRendererState(node())

        assertFalse(state.serverPublished(NativeUITree(node())))
        assertTrue(state.serverPublished(NativeUITree(node(size = "lg"))))
        assertEquals(ActivityIndicatorSize.Large, state.configuration.size)
        assertFalse(state.serverPublished(NativeUITree(node(size = "lg"))))
    }

    @Test
    fun `stable node can be reconciled from a nested published tree`() {
        val state = ActivityIndicatorRendererState(node(size = "sm"))
        val nested = NativeUINode(
            id = 2,
            props = GenericProps(),
            children = listOf(node(size = "lg")),
        )

        assertTrue(state.serverPublished(NativeUITree(nested)))
        assertEquals(ActivityIndicatorSize.Large, state.configuration.size)
    }

    @Test
    fun `semantic sizes map to bounded material dimensions`() {
        assertEquals(
            listOf(20.dp, 32.dp, 48.dp),
            ActivityIndicatorSize.entries.map { it.dimension },
        )
    }

    @Test
    fun `accessibility semantics announce politely without an action`() {
        val semantics = SemanticsConfiguration()
        activityIndicatorSemantics("Loading appointments")(semantics)

        assertEquals(
            listOf("Loading appointments"),
            semantics[SemanticsProperties.ContentDescription],
        )
        assertEquals(LiveRegionMode.Polite, semantics[SemanticsProperties.LiveRegion])
        assertFalse(semantics.contains(SemanticsActions.OnClick))
    }

    private fun node(
        size: String = "md",
        label: String = "Loading appointments",
    ) = NativeUINode(
        id = 7,
        props = GenericProps(
            mapOf(
                "size" to size,
                "a11y_label" to label,
            ),
        ),
    )
}

class ActivityIndicatorScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test
    fun light() = snapshot(dark = false)

    @Test
    fun dark() = snapshot(dark = true)

    private fun snapshot(dark: Boolean) {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
                val tokens = if (dark) NativeUITheme.dark else NativeUITheme.light

                Surface(modifier = Modifier.fillMaxSize()) {
                    Row(
                        modifier = Modifier.padding(24.dp),
                        horizontalArrangement = Arrangement.spacedBy(32.dp),
                    ) {
                        ActivityIndicatorSize.entries.forEach { size ->
                            FirstlightActivityIndicatorControl(
                                size = size,
                                accessibilityLabel = "Loading ${size.wireName}",
                                color = tokens.primary,
                            )
                        }
                    }
                }
            }
        }
    }
}
