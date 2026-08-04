package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import app.cash.paparazzi.DeviceConfig
import app.cash.paparazzi.Paparazzi
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import com.nativephp.plugins.native_ui.NativeUITheme
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Rule
import org.junit.Test

class IconButtonContractTest {
    @Test
    fun `configuration decodes the complete action contract`() {
        val configuration = IconButtonRendererConfiguration(node())

        assertEquals(7, configuration.nodeId)
        assertEquals("add_circle", configuration.icon)
        assertEquals("outlined", configuration.iconVariant)
        assertEquals(FirstlightIconButtonVariant.Success, configuration.variant)
        assertEquals(FirstlightIconButtonSize.Large, configuration.size)
        assertEquals("Add item", configuration.accessibilityLabel)
        assertEquals("Adds a blank item", configuration.accessibilityHint)
        assertEquals(41, configuration.callbackId)
        assertTrue(configuration.enabled)
    }

    @Test
    fun `malformed variant and size defensively use stable defaults`() {
        val configuration = IconButtonRendererConfiguration(node(variant = "accent", size = "xl"))

        assertEquals(FirstlightIconButtonVariant.Primary, configuration.variant)
        assertEquals(FirstlightIconButtonSize.Medium, configuration.size)
    }

    @Test
    fun `disabled loading and missing callbacks suppress activation`() {
        assertNull(IconButtonRendererConfiguration(node(disabled = true)).pressEvent())
        assertNull(IconButtonRendererConfiguration(node(loading = true)).pressEvent())
        assertNull(IconButtonRendererConfiguration(node(callbackId = 0)).pressEvent())
        assertEquals(
            IconButtonRendererEvent.Press(callbackId = 41, nodeId = 7),
            IconButtonRendererConfiguration(node()).pressEvent(),
        )
    }

    @Test
    fun `server publication updates state without emitting`() {
        val state = IconButtonRendererState(node())

        assertTrue(state.serverPublished(NativeUITree(node(icon = "delete", loading = true))))
        assertEquals("delete", state.configuration.icon)
        assertTrue(state.configuration.loading)
        assertNull(state.configuration.pressEvent())
        assertFalse(state.serverPublished(NativeUITree(node(icon = "delete", loading = true))))
    }

    @Test
    fun `every size retains a forty eight dp minimum target`() {
        FirstlightIconButtonSize.entries.forEach { size ->
            assertTrue(size.metrics.minimumTarget >= 48.dp)
            assertTrue(size.metrics.visualSize <= size.metrics.minimumTarget)
        }
    }

    @Test
    fun `accessibility description uses explicit action name and hint`() {
        assertEquals(
            "Add item. Adds a blank item",
            firstlightIconButtonDescription("Add item", "Adds a blank item"),
        )
        assertEquals("Add item", firstlightIconButtonDescription("Add item", ""))
    }

    private fun node(
        icon: String = "add_circle",
        variant: String = "success",
        size: String = "lg",
        disabled: Boolean = false,
        loading: Boolean = false,
        callbackId: Int = 41,
    ) = NativeUINode(
        id = 7,
        onPress = callbackId,
        props = GenericProps(
            mapOf(
                "icon" to icon,
                "icon_variant" to "outlined",
                "variant" to variant,
                "size" to size,
                "disabled" to disabled,
                "loading" to loading,
                "a11y_label" to "Add item",
                "a11y_hint" to "Adds a blank item",
            ),
        ),
    )
}

class IconButtonScreenshotTest {
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
                    Column(
                        modifier = Modifier.padding(16.dp),
                        verticalArrangement = androidx.compose.foundation.layout.Arrangement.spacedBy(12.dp),
                    ) {
                        FirstlightIconButtonVariant.entries.forEach { variant ->
                            Row(horizontalArrangement = androidx.compose.foundation.layout.Arrangement.spacedBy(12.dp)) {
                                FirstlightIconButtonSize.entries.forEach { size ->
                                    FirstlightIconButtonControl(
                                        configuration = IconButtonRendererConfiguration(
                                            node(variant = variant.wireName, size = size.wireName),
                                        ),
                                        tokens = tokens,
                                        onPress = {},
                                    )
                                }
                            }
                        }
                        FirstlightIconButtonControl(
                            configuration = IconButtonRendererConfiguration(node(disabled = true)),
                            tokens = tokens,
                            onPress = {},
                        )
                        FirstlightIconButtonControl(
                            configuration = IconButtonRendererConfiguration(node(loading = true)),
                            tokens = tokens,
                            onPress = {},
                        )
                    }
                }
            }
        }
    }

    private fun node(
        variant: String = "primary",
        size: String = "md",
        disabled: Boolean = false,
        loading: Boolean = false,
    ) = NativeUINode(
        id = 7,
        onPress = 41,
        props = GenericProps(
            mapOf(
                "icon" to "add",
                "variant" to variant,
                "size" to size,
                "disabled" to disabled,
                "loading" to loading,
                "a11y_label" to "Add item",
            ),
        ),
    )
}

class IconButtonLargeTextScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2.0f))

    @Test
    fun fontScaleTwo() {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = darkColorScheme()) {
                Surface(modifier = Modifier.fillMaxSize()) {
                    FirstlightIconButtonControl(
                        configuration = IconButtonRendererConfiguration(
                            NativeUINode(
                                id = 7,
                                onPress = 41,
                                props = GenericProps(
                                    mapOf(
                                        "icon" to "add",
                                        "variant" to "primary",
                                        "size" to "md",
                                        "a11y_label" to "Add item",
                                    ),
                                ),
                            ),
                        ),
                        tokens = NativeUITheme.dark,
                        onPress = {},
                        modifier = Modifier.padding(16.dp),
                    )
                }
            }
        }
    }
}
