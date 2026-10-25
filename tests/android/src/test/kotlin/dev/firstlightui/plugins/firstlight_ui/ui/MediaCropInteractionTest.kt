package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.material3.MaterialTheme
import androidx.compose.ui.graphics.ImageBitmap
import androidx.compose.ui.semantics.SemanticsActions
import androidx.compose.ui.test.ExperimentalTestApi
import androidx.compose.ui.test.onNodeWithTag
import androidx.compose.ui.test.performTouchInput
import androidx.compose.ui.test.runComposeUiTest
import androidx.compose.ui.test.pinch
import androidx.compose.ui.test.swipe
import androidx.compose.ui.geometry.Offset
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import org.robolectric.annotation.GraphicsMode

@OptIn(ExperimentalTestApi::class)
@RunWith(RobolectricTestRunner::class)
@GraphicsMode(GraphicsMode.Mode.NATIVE)
class MediaCropInteractionTest {
    @Test
    fun `dragging the photo and TalkBack move actions pan the region within the image`() = runComposeUiTest {
        val state = MediaRendererState(
            NativeUINode(
                id = 7,
                type = "firstlight.media",
                props = GenericProps(mutableMapOf<String, Any>("mode" to "image", "aspect" to "1:1", "label" to "Cover")),
                onPress = 0,
                children = emptyList(),
            ),
        )
        state.beginCrop(400, 300)
        state.zoomCropTo(2f)
        setContent {
            MaterialTheme {
                FirstlightMediaCropSheet(
                    policy = state.configuration.cropPolicy,
                    zoom = state.cropZoom,
                    offset = state.cropOffset,
                    image = ImageBitmap(400, 300),
                    onConfirm = {},
                    onCancel = {},
                    onSkip = {},
                    onZoomIn = state::zoomIn,
                    onZoomOut = state::zoomOut,
                    onZoomBy = state::zoomCropBy,
                    onPan = state::panCrop,
                    onMove = state::moveCrop,
                )
            }
        }

        val region = onNodeWithTag(MEDIA_CROP_REGION_TAG)
        val actions = region.fetchSemanticsNode().config[SemanticsActions.CustomActions]
        assertEquals(listOf("Move crop left", "Move crop right", "Move crop up", "Move crop down"), actions.map { it.label })
        runOnIdle { assertTrue(actions.first { it.label == "Move crop right" }.action()) }
        assertEquals(MediaCropOffset(15f, 0f), state.cropOffset)

        // The frame shows 150 image pixels across. Dragging the photo right and
        // down moves the region left and up, and repeated drags stop at the
        // image's top-left corner.
        region.performTouchInput { swipe(start = center, end = Offset(right - 2f, bottom - 2f), durationMillis = 300) }
        waitForIdle()
        // Half the frame is 75 image pixels: past the start but short of the corner.
        assertTrue(state.cropOffset.x < -40f && state.cropOffset.x > -60f)
        assertTrue(state.cropOffset.y < -55f && state.cropOffset.y > -75f)
        repeat(2) {
            region.performTouchInput { swipe(start = center, end = Offset(right - 2f, bottom - 2f), durationMillis = 300) }
            waitForIdle()
        }
        assertEquals(MediaCropOffset(-125f, -75f), state.cropOffset)
        assertEquals(MediaCropRect(0, 0, 150, 150), state.configuration.cropPolicy.cropRect(400, 300, state.cropZoom, state.cropOffset))

        // Spreading two fingers zooms in; the corner pan is re-clamped to the
        // smaller region's limits rather than left outside them.
        region.performTouchInput {
            pinch(
                start0 = center - Offset(20f, 0f), end0 = center - Offset(width / 2f - 4f, 0f),
                start1 = center + Offset(20f, 0f), end1 = center + Offset(width / 2f - 4f, 0f),
                durationMillis = 400,
            )
        }
        waitForIdle()
        assertTrue("pinch zoomed to ${state.cropZoom}", state.cropZoom > 2.5f)
        val limit = state.configuration.cropPolicy.clampedOffset(MediaCropOffset(-1000f, -1000f), 400, 300, state.cropZoom)
        assertTrue(state.cropOffset.x >= limit.x && state.cropOffset.y >= limit.y)
    }
}
