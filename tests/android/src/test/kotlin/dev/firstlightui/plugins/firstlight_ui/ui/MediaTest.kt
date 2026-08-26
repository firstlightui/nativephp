package dev.firstlightui.plugins.firstlight_ui.ui

import app.cash.paparazzi.DeviceConfig
import app.cash.paparazzi.Paparazzi
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.plugins.native_ui.NativeUITheme
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Rule
import org.junit.Test

class MediaTest {
    @get:Rule
    val paparazzi = Paparazzi(
        deviceConfig = DeviceConfig.PIXEL_5,
        theme = "android:Theme.Material.Light.NoActionBar",
    )

    @Test
    fun `configuration decodes image field contract`() {
        val configuration = MediaRendererConfiguration(
            node(
                mode = "image",
                aspect = "1:1",
                crop = "required",
                hasValue = true,
                path = "avatars/a.jpg",
                mime = "image/jpeg",
                size = 1200,
                width = 100,
                height = 100,
                required = true,
                error = "Choose a photo.",
            ),
        )

        assertEquals(7, configuration.nodeId)
        assertEquals(MediaMode.Image, configuration.mode)
        assertEquals("Profile photo", configuration.label)
        assertEquals("1:1", configuration.aspect)
        assertEquals(MediaCropMode.Required, configuration.crop)
        assertTrue(configuration.hasValue)
        assertEquals("avatars/a.jpg", configuration.path)
        assertEquals(MediaCropPolicy.RequiredAspect("1:1"), configuration.cropPolicy)
        assertEquals(41, configuration.onChangeCallback)
        assertEquals(42, configuration.onClearCallback)
        assertEquals("Confirm", configuration.confirmLabel)
        assertEquals("Cancel", configuration.cancelLabel)
        assertEquals("Clear", configuration.clearLabel)
        assertEquals("Skip", configuration.skipLabel)
        assertEquals("Crop", configuration.cropLabel)
        assertEquals("Zoom in", configuration.zoomInLabel)
        assertEquals("Zoom out", configuration.zoomOutLabel)
        assertEquals("Choose media", configuration.chooseMediaLabel)
        assertEquals("Photo Library", configuration.photoLibraryLabel)
        assertEquals("Camera", configuration.cameraLabel)
        assertEquals("Browse Files", configuration.browseFilesLabel)
    }

    @Test
    fun `crop policy composition matches design`() {
        assertEquals(MediaCropPolicy.None, MediaCropPolicy.resolve(MediaMode.Image, "", null))
        assertEquals(
            MediaCropPolicy.RequiredAspect("4:3"),
            MediaCropPolicy.resolve(MediaMode.Image, "4:3", null),
        )
        assertEquals(
            MediaCropPolicy.OptionalFreeform,
            MediaCropPolicy.resolve(MediaMode.Image, "", MediaCropMode.Optional),
        )
        assertEquals(
            MediaCropPolicy.RequiredFreeform,
            MediaCropPolicy.resolve(MediaMode.Image, "", MediaCropMode.Required),
        )
        assertEquals(
            MediaCropPolicy.None,
            MediaCropPolicy.resolve(MediaMode.Document, "1:1", MediaCropMode.Required),
        )
        assertTrue(MediaCropPolicy.OptionalFreeform.allowsSkip)
        assertFalse(MediaCropPolicy.RequiredFreeform.allowsSkip)
        assertEquals(1f, MediaCropPolicy.RequiredAspect("1:1").aspectRatio)
    }

    @Test
    fun `clear and change events`() {
        val state = MediaRendererState(node(hasValue = true))
        assertEquals(MediaRendererEvent.Clear(42, 7), state.clear())
        assertEquals(
            MediaRendererEvent.Change(41, 7, "/tmp/photo.jpg"),
            state.commitChange("/tmp/photo.jpg"),
        )
    }

    @Test
    fun `empty and disabled fields do not clear`() {
        assertNull(MediaRendererState(node(hasValue = false)).clear())
        assertNull(MediaRendererState(node(hasValue = true, disabled = true)).clear())
    }

    @Test
    fun `zoom stays within bounds`() {
        val state = MediaRendererState(node())
        state.beginCrop()
        state.zoomOut()
        assertEquals(1f, state.cropZoom)
        repeat(20) { state.zoomIn() }
        assertEquals(4f, state.cropZoom)
        repeat(20) { state.zoomOut() }
        assertEquals(1f, state.cropZoom)
    }

    @Test
    fun `empty field paparazzi`() {
        paparazzi.snapshot {
            FirstlightMediaControl(
                configuration = MediaRendererConfiguration(node()),
                tokens = NativeUITheme.light,
                onPick = {},
                onClear = {},
            )
        }
    }

    @Test
    fun `value error and disabled paparazzi`() {
        paparazzi.snapshot(name = "value-error") {
            FirstlightMediaControl(
                configuration = MediaRendererConfiguration(
                    node(
                        hasValue = true,
                        path = "avatars/a.jpg",
                        error = "Choose a clearer photo.",
                    ),
                ),
                tokens = NativeUITheme.light,
                onPick = {},
                onClear = {},
            )
        }
        paparazzi.snapshot(name = "disabled") {
            FirstlightMediaControl(
                configuration = MediaRendererConfiguration(node(disabled = true)),
                tokens = NativeUITheme.light,
                onPick = {},
                onClear = {},
            )
        }
    }

    private fun node(
        mode: String = "image",
        aspect: String = "",
        crop: String = "",
        hasValue: Boolean = false,
        path: String = "",
        mime: String = "",
        size: Int = 0,
        width: Int? = null,
        height: Int? = null,
        required: Boolean = false,
        disabled: Boolean = false,
        helper: String = "Helper copy",
        error: String = "",
        changeCallback: Int = 41,
        clearCallback: Int = 42,
    ): NativeUINode {
        val props = mutableMapOf<String, Any>(
            "mode" to mode,
            "label" to "Profile photo",
            "helper" to helper,
            "error" to error,
            "required" to required,
            "disabled" to disabled,
            "disk" to "mobile_public",
            "directory" to "avatars",
            "aspect" to aspect,
            "crop" to crop,
            "has_value" to hasValue,
            "path" to path,
            "mime" to mime,
            "size" to size,
            "preview_url" to "",
            "a11y_label" to "Profile photo",
            "a11y_hint" to "Opens the media picker",
            "on_change" to changeCallback,
            "on_clear" to clearCallback,
        )
        if (width != null) props["width"] = width
        if (height != null) props["height"] = height

        return NativeUINode(
            id = 7,
            type = "firstlight.media",
            props = GenericProps(props),
            onPress = 0,
            children = emptyList(),
        )
    }
}
