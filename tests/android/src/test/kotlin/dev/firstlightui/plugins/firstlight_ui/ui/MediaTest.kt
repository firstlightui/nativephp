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
    fun `configuration decodes the preview path`() {
        val configuration = MediaRendererConfiguration(node(mode = "image", hasValue = true, previewPath = "/data/covers/a.jpg"))

        assertEquals("/data/covers/a.jpg", configuration.previewPath)
    }

    @Test
    fun `thumbnail decodes the stored image subsampled to the preview size`() {
        val file = java.io.File.createTempFile("firstlight-cover", ".png")
        val source = android.graphics.Bitmap.createBitmap(400, 300, android.graphics.Bitmap.Config.ARGB_8888)
        source.eraseColor(android.graphics.Color.RED)
        file.outputStream().use { source.compress(android.graphics.Bitmap.CompressFormat.PNG, 100, it) }

        val thumbnail = decodeMediaThumbnail(file.path, sizePx = 72)

        // 300 / 4 = 75 is the smallest power-of-two sample still at least 72.
        assertEquals(100, thumbnail?.width)
        assertEquals(75, thumbnail?.height)
        assertNull(decodeMediaThumbnail(file.path + ".missing", sizePx = 72))
        file.delete()
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
    fun `crop rect is the centred policy aspect divided by zoom`() {
        val square = MediaCropPolicy.RequiredAspect("1:1")
        assertEquals(MediaCropRect(500, 0, 3000, 3000), square.cropRect(4000, 3000, 1f))
        assertEquals(MediaCropRect(1250, 750, 1500, 1500), square.cropRect(4000, 3000, 2f))

        // A landscape aspect on a portrait photo is width-bound, not height-bound.
        assertEquals(
            MediaCropRect(0, 1156, 3000, 1688),
            MediaCropPolicy.RequiredAspect("16:9").cropRect(3000, 4000, 1f),
        )

        // Freeform keeps the image's own aspect; zoom alone narrows it.
        assertEquals(MediaCropRect(0, 0, 4000, 3000), MediaCropPolicy.RequiredFreeform.cropRect(4000, 3000, 1f))
        assertEquals(MediaCropRect(1000, 750, 2000, 1500), MediaCropPolicy.OptionalFreeform.cropRect(4000, 3000, 2f))
    }

    @Test
    fun `crop rect pans and clamps at zoom on a non-square image`() {
        // 16:9 on 4000x3000 at zoom 2 is 2000x1125, centred at (1000, 937).
        val wide = MediaCropPolicy.RequiredAspect("16:9")
        assertEquals(MediaCropRect(1000, 937, 2000, 1125), wide.cropRect(4000, 3000, 2f))

        // A corner pan lands on the bottom-left corner, not the centre.
        assertEquals(MediaCropRect(0, 1875, 2000, 1125), wide.cropRect(4000, 3000, 2f, MediaCropOffset(-5000f, 5000f)))
        // Just inside and just past each edge.
        assertEquals(MediaCropRect(1, 1874, 2000, 1125), wide.cropRect(4000, 3000, 2f, MediaCropOffset(-999f, 937f)))
        assertEquals(MediaCropRect(0, 1875, 2000, 1125), wide.cropRect(4000, 3000, 2f, MediaCropOffset(-1000f, 937.5f)))
        assertEquals(MediaCropRect(0, 1875, 2000, 1125), wide.cropRect(4000, 3000, 2f, MediaCropOffset(-1001f, 938f)))
        assertEquals(MediaCropRect(2000, 0, 2000, 1125), wide.cropRect(4000, 3000, 2f, MediaCropOffset(1001f, -938f)))
        assertEquals(MediaCropOffset(-1000f, 937.5f), wide.clampedOffset(MediaCropOffset(-1001f, 938f), 4000, 3000, 2f))
        assertEquals(MediaCropOffset(999f, -937f), wide.clampedOffset(MediaCropOffset(999f, -937f), 4000, 3000, 2f))
    }

    @Test
    fun `pan state clamps, resets and moves accessibly`() {
        val state = MediaRendererState(node(aspect = "1:1"))
        state.beginCrop(400, 300)

        // Zoom 2: a 150 square with 125 of slack each side horizontally, 75 vertically.
        state.zoomCropTo(2f)
        state.panCrop(1000f, -1000f)
        assertEquals(MediaCropOffset(125f, -75f), state.cropOffset)

        state.moveCrop(MediaCropDirection.Left)
        assertEquals(MediaCropOffset(110f, -75f), state.cropOffset)
        state.moveCrop(MediaCropDirection.Down)
        assertEquals(MediaCropOffset(110f, -60f), state.cropOffset)

        // Back at zoom 1 the square has no vertical slack, so it re-centres
        // there and keeps the horizontal choice inside the 50 that remains.
        state.zoomCropTo(1f)
        assertEquals(MediaCropOffset(50f, 0f), state.cropOffset)
        state.zoomCropTo(9f)
        assertEquals(4f, state.cropZoom)

        state.beginCrop(400, 300)
        assertEquals(MediaCropOffset(), state.cropOffset)
        assertEquals(1f, state.cropZoom)

        // Freeform at zoom 1 is the whole photo, so any pan stays centred.
        val freeform = MediaRendererState(node(crop = "optional"))
        freeform.beginCrop(400, 300)
        freeform.zoomCropTo(2f)
        freeform.panCrop(80f, 40f)
        freeform.zoomCropTo(1f)
        assertEquals(MediaCropOffset(), freeform.cropOffset)
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
        state.beginCrop(400, 300)
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
        previewPath: String = "",
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
            "preview_path" to previewPath,
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
