package dev.firstlightui.plugins.firstlight_ui.ui

import android.content.ActivityNotFoundException
import android.content.Context
import android.graphics.Bitmap
import android.graphics.ImageDecoder
import android.net.Uri
import android.provider.OpenableColumns
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.ActivityResultLauncher
import androidx.activity.result.PickVisualMediaRequest
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableFloatStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.platform.LocalContext
import androidx.core.content.FileProvider
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import com.nativephp.plugins.native_ui.NativeUITheme
import java.io.File
import java.util.UUID
import kotlin.math.floor
import kotlin.math.roundToInt
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

enum class MediaMode {
    Image,
    Document,
}

enum class MediaCropMode {
    Optional,
    Required,
}

sealed interface MediaCropPolicy {
    data object None : MediaCropPolicy
    data object OptionalFreeform : MediaCropPolicy
    data object RequiredFreeform : MediaCropPolicy
    data class RequiredAspect(val raw: String) : MediaCropPolicy

    val requiresCropSheet: Boolean
        get() = this !is None

    val allowsSkip: Boolean
        get() = this is OptionalFreeform

    val aspectRatio: Float?
        get() = when (this) {
            is RequiredAspect -> {
                val parts = raw.split(':')
                if (parts.size == 2) {
                    val width = parts[0].toFloatOrNull()
                    val height = parts[1].toFloatOrNull()
                    if (width != null && height != null && width > 0f && height > 0f) width / height else null
                } else {
                    null
                }
            }
            else -> null
        }

    /**
     * The size of the committed region: the largest rectangle with the
     * policy's aspect (or the image's own aspect when freeform), divided by
     * zoom.
     */
    fun cropSize(imageWidth: Int, imageHeight: Int, zoom: Float): Pair<Int, Int> {
        val ratio = aspectRatio ?: (imageWidth.toFloat() / imageHeight)
        var width = imageWidth.toFloat()
        var height = width / ratio
        if (height > imageHeight) {
            height = imageHeight.toFloat()
            width = height * ratio
        }
        val scale = zoom.coerceIn(1f, 4f)
        return (width / scale).roundToInt().coerceIn(1, imageWidth) to
            (height / scale).roundToInt().coerceIn(1, imageHeight)
    }

    /** Limits a pan offset (image pixels from the centred position) so the region never leaves the image. */
    fun clampedOffset(offset: MediaCropOffset, imageWidth: Int, imageHeight: Int, zoom: Float): MediaCropOffset {
        val (cropWidth, cropHeight) = cropSize(imageWidth, imageHeight, zoom)
        val maxX = ((imageWidth - cropWidth) / 2f).coerceAtLeast(0f)
        val maxY = ((imageHeight - cropHeight) / 2f).coerceAtLeast(0f)
        // + 0f turns a clamped -0f into 0f so a centred axis compares equal.
        return MediaCropOffset(offset.x.coerceIn(-maxX, maxX) + 0f, offset.y.coerceIn(-maxY, maxY) + 0f)
    }

    /**
     * The committed region: [cropSize] centred in the image, moved by
     * [offset] and kept inside the image. The crop sheet previews exactly
     * this region.
     */
    fun cropRect(imageWidth: Int, imageHeight: Int, zoom: Float, offset: MediaCropOffset = MediaCropOffset()): MediaCropRect {
        val (cropWidth, cropHeight) = cropSize(imageWidth, imageHeight, zoom)
        val maxX = imageWidth - cropWidth
        val maxY = imageHeight - cropHeight
        return MediaCropRect(
            left = floor(maxX / 2f + offset.x).toInt().coerceIn(0, maxX),
            top = floor(maxY / 2f + offset.y).toInt().coerceIn(0, maxY),
            width = cropWidth,
            height = cropHeight,
        )
    }

    companion object {
        fun resolve(mode: MediaMode, aspect: String, crop: MediaCropMode?): MediaCropPolicy {
            if (mode != MediaMode.Image) return None
            if (aspect.isNotEmpty()) return RequiredAspect(aspect)
            return when (crop) {
                MediaCropMode.Optional -> OptionalFreeform
                MediaCropMode.Required -> RequiredFreeform
                null -> None
            }
        }
    }
}

data class MediaCropRect(val left: Int, val top: Int, val width: Int, val height: Int)

/** Image pixels from the centred crop region; positive moves it right and down. */
data class MediaCropOffset(val x: Float = 0f, val y: Float = 0f)

/** A direction for the accessible move actions on the crop region. */
enum class MediaCropDirection { Left, Right, Up, Down }

sealed interface MediaRendererEvent {
    val wireName: String

    data class Change(val callbackId: Int, val nodeId: Int, val tempPath: String) : MediaRendererEvent {
        override val wireName = "TEXT_CHANGE"
    }

    data class Clear(val callbackId: Int, val nodeId: Int) : MediaRendererEvent {
        override val wireName = "PRESS"
    }
}

class MediaRendererEvents(private val send: (MediaRendererEvent) -> Unit) {
    fun dispatch(event: MediaRendererEvent) = send(event)

    companion object {
        val native = MediaRendererEvents { event ->
            when (event) {
                is MediaRendererEvent.Change -> NativeUIBridge.sendTextChangeEvent(
                    event.callbackId,
                    event.nodeId,
                    event.tempPath,
                )
                is MediaRendererEvent.Clear -> NativeUIBridge.sendPressEvent(
                    event.callbackId,
                    event.nodeId,
                )
            }
        }
    }
}

data class MediaRendererConfiguration(
    val nodeId: Int,
    val mode: MediaMode,
    val label: String,
    val helper: String,
    val error: String,
    val required: Boolean,
    val disabled: Boolean,
    val disk: String,
    val directory: String,
    val aspect: String,
    val crop: MediaCropMode?,
    val hasValue: Boolean,
    val path: String,
    val mime: String,
    val size: Int,
    val width: Int?,
    val height: Int?,
    val previewPath: String,
    val accessibilityLabel: String,
    val accessibilityHint: String,
    val confirmLabel: String,
    val cancelLabel: String,
    val clearLabel: String,
    val skipLabel: String,
    val cropLabel: String,
    val zoomInLabel: String,
    val zoomOutLabel: String,
    val moveCropLeftLabel: String,
    val moveCropRightLabel: String,
    val moveCropUpLabel: String,
    val moveCropDownLabel: String,
    val chooseMediaLabel: String,
    val photoLibraryLabel: String,
    val cameraLabel: String,
    val browseFilesLabel: String,
    val onChangeCallback: Int,
    val onClearCallback: Int,
) {
    constructor(node: NativeUINode) : this(
        nodeId = node.id,
        mode = when (node.props.getString("mode")) {
            "document" -> MediaMode.Document
            else -> MediaMode.Image
        },
        label = node.props.getString("label"),
        helper = node.props.getString("helper"),
        error = node.props.getString("error"),
        required = node.props.getBool("required"),
        disabled = node.props.getBool("disabled"),
        disk = node.props.getString("disk").ifEmpty { "mobile_public" },
        directory = node.props.getString("directory").ifEmpty { "media" },
        aspect = node.props.getString("aspect"),
        crop = when (node.props.getString("crop")) {
            "optional" -> MediaCropMode.Optional
            "required" -> MediaCropMode.Required
            else -> null
        },
        hasValue = node.props.getBool("has_value"),
        path = node.props.getString("path"),
        mime = node.props.getString("mime"),
        size = node.props.getInt("size"),
        width = node.props.getInt("width").takeIf { it > 0 },
        height = node.props.getInt("height").takeIf { it > 0 },
        previewPath = node.props.getString("preview_path"),
        accessibilityLabel = node.props.getString("a11y_label").ifEmpty { node.props.getString("label") },
        accessibilityHint = node.props.getString("a11y_hint"),
        confirmLabel = node.props.getString("confirm_label").ifEmpty { "Confirm" },
        cancelLabel = node.props.getString("cancel_label").ifEmpty { "Cancel" },
        clearLabel = node.props.getString("clear_label").ifEmpty { "Clear" },
        skipLabel = node.props.getString("skip_label").ifEmpty { "Skip" },
        cropLabel = node.props.getString("crop_label").ifEmpty { "Crop" },
        zoomInLabel = node.props.getString("zoom_in_label").ifEmpty { "Zoom in" },
        zoomOutLabel = node.props.getString("zoom_out_label").ifEmpty { "Zoom out" },
        moveCropLeftLabel = node.props.getString("move_crop_left_label").ifEmpty { "Move crop left" },
        moveCropRightLabel = node.props.getString("move_crop_right_label").ifEmpty { "Move crop right" },
        moveCropUpLabel = node.props.getString("move_crop_up_label").ifEmpty { "Move crop up" },
        moveCropDownLabel = node.props.getString("move_crop_down_label").ifEmpty { "Move crop down" },
        chooseMediaLabel = node.props.getString("choose_media_label").ifEmpty { "Choose media" },
        photoLibraryLabel = node.props.getString("photo_library_label").ifEmpty { "Photo Library" },
        cameraLabel = node.props.getString("camera_label").ifEmpty { "Camera" },
        browseFilesLabel = node.props.getString("browse_files_label").ifEmpty { "Browse Files" },
        onChangeCallback = node.props.getCallbackId("on_change"),
        onClearCallback = node.props.getCallbackId("on_clear"),
    )

    val supportingText: String
        get() = error.ifEmpty { helper }

    val isInteractive: Boolean
        get() = !disabled

    val canClear: Boolean
        get() = hasValue && isInteractive && onClearCallback != 0

    val cropPolicy: MediaCropPolicy
        get() = MediaCropPolicy.resolve(mode, aspect, crop)
}

sealed interface MediaSheet {
    data object SourceChooser : MediaSheet
    data object Crop : MediaSheet
}

class MediaRendererState(node: NativeUINode) {
    var configuration by mutableStateOf(MediaRendererConfiguration(node))
        private set
    var sheet by mutableStateOf<MediaSheet?>(null)
        private set
    var cropZoom by mutableFloatStateOf(1f)
        private set
    var cropOffset by mutableStateOf(MediaCropOffset())
        private set
    private var cropImageWidth = 0
    private var cropImageHeight = 0
    var pendingTempPath by mutableStateOf<String?>(null)
        private set

    fun serverPublished(tree: NativeUITree): Boolean {
        val published = tree.root.findNode(configuration.nodeId) ?: return false
        return serverPublished(published)
    }

    fun serverPublished(node: NativeUINode): Boolean {
        if (node.id != configuration.nodeId) return false
        configuration = MediaRendererConfiguration(node)
        pendingTempPath = null
        return true
    }

    fun openSourceChooser(): Boolean {
        if (!configuration.isInteractive) return false
        sheet = MediaSheet.SourceChooser
        return true
    }

    fun dismissSheet() {
        sheet = null
        cropZoom = 1f
        cropOffset = MediaCropOffset()
    }

    fun beginCrop(imageWidth: Int, imageHeight: Int) {
        cropImageWidth = imageWidth
        cropImageHeight = imageHeight
        cropZoom = 1f
        cropOffset = MediaCropOffset()
        sheet = MediaSheet.Crop
    }

    fun zoomIn() = zoomCropTo(cropZoom + 0.25f)

    fun zoomOut() = zoomCropTo(cropZoom - 0.25f)

    /** Sets zoom within 1..4 (buttons and pinch) and re-clamps the pan, so zooming out pulls the region back inside the image. */
    fun zoomCropTo(zoom: Float) {
        cropZoom = zoom.coerceIn(1f, 4f)
        cropOffset = clamped(cropOffset)
    }

    /** Pinch: multiplies the current zoom by [factor]. */
    fun zoomCropBy(factor: Float) = zoomCropTo(cropZoom * factor)

    /** Moves the region by a delta in image pixels, stopping at the image edges. */
    fun panCrop(dx: Float, dy: Float) {
        cropOffset = clamped(MediaCropOffset(cropOffset.x + dx, cropOffset.y + dy))
    }

    /** Accessible move: a tenth of the region on that axis. */
    fun moveCrop(direction: MediaCropDirection) {
        if (cropImageWidth <= 0 || cropImageHeight <= 0) return
        val (width, height) = configuration.cropPolicy.cropSize(cropImageWidth, cropImageHeight, cropZoom)
        when (direction) {
            MediaCropDirection.Left -> panCrop(-width / 10f, 0f)
            MediaCropDirection.Right -> panCrop(width / 10f, 0f)
            MediaCropDirection.Up -> panCrop(0f, -height / 10f)
            MediaCropDirection.Down -> panCrop(0f, height / 10f)
        }
    }

    private fun clamped(offset: MediaCropOffset): MediaCropOffset {
        if (cropImageWidth <= 0 || cropImageHeight <= 0) return MediaCropOffset()
        return configuration.cropPolicy.clampedOffset(offset, cropImageWidth, cropImageHeight, cropZoom)
    }

    fun commitChange(tempPath: String): MediaRendererEvent.Change? {
        if (configuration.onChangeCallback == 0) return null
        pendingTempPath = tempPath
        return MediaRendererEvent.Change(
            callbackId = configuration.onChangeCallback,
            nodeId = configuration.nodeId,
            tempPath = tempPath,
        )
    }

    fun clear(): MediaRendererEvent.Clear? {
        if (!configuration.canClear) return null
        return MediaRendererEvent.Clear(
            callbackId = configuration.onClearCallback,
            nodeId = configuration.nodeId,
        )
    }
}

object MediaRenderer {
    @Composable
    fun Render(
        node: NativeUINode,
        modifier: Modifier = Modifier,
        events: MediaRendererEvents = MediaRendererEvents.native,
    ) {
        firstlightValidationAlert(node.id)
        val state = remember(node.id) { MediaRendererState(node) }
        LaunchedEffect(node.props) { state.serverPublished(node) }
        NativeUIBridge.currentTree.value?.let { tree ->
            LaunchedEffect(tree) { state.serverPublished(tree) }
        }

        val context = LocalContext.current
        val scope = rememberCoroutineScope()
        var cropImage by remember(node.id) { mutableStateOf<Bitmap?>(null) }
        var cameraFile by remember(node.id) { mutableStateOf<File?>(null) }

        fun commit(path: String?) {
            path?.let(state::commitChange)?.let(events::dispatch)
        }

        fun handleImage(load: () -> Bitmap?) {
            scope.launch {
                val image = withContext(Dispatchers.IO) { runCatching(load).getOrNull() } ?: return@launch
                if (state.configuration.cropPolicy.requiresCropSheet) {
                    cropImage = image
                    state.beginCrop(image.width, image.height)
                } else {
                    commit(withContext(Dispatchers.IO) { MediaTempFiles.writeJpeg(context, image) })
                }
            }
        }

        fun commitCrop(crop: Boolean) {
            val image = cropImage ?: return
            val rect = state.configuration.cropPolicy.cropRect(image.width, image.height, state.cropZoom, state.cropOffset)
            cropImage = null
            state.dismissSheet()
            scope.launch {
                commit(
                    withContext(Dispatchers.IO) {
                        val output = if (crop) {
                            Bitmap.createBitmap(image, rect.left, rect.top, rect.width, rect.height)
                        } else {
                            image
                        }
                        MediaTempFiles.writeJpeg(context, output)
                    },
                )
            }
        }

        val photoPicker = rememberLauncherForActivityResult(ActivityResultContracts.PickVisualMedia()) { uri ->
            state.dismissSheet()
            if (uri != null) handleImage { MediaTempFiles.decode(context, uri) }
        }
        val camera = rememberLauncherForActivityResult(ActivityResultContracts.TakePicture()) { saved ->
            state.dismissSheet()
            val file = cameraFile
            cameraFile = null
            if (saved && file != null) {
                handleImage { MediaTempFiles.decode(context, Uri.fromFile(file)) }
            } else {
                file?.delete()
            }
        }
        val documentPicker = rememberLauncherForActivityResult(ActivityResultContracts.OpenDocument()) { uri ->
            state.dismissSheet()
            if (uri != null) {
                scope.launch {
                    commit(withContext(Dispatchers.IO) { runCatching { MediaTempFiles.copy(context, uri) }.getOrNull() })
                }
            }
        }

        FirstlightMediaControl(
            configuration = state.configuration,
            tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light,
            onPick = {
                if (!state.configuration.isInteractive) return@FirstlightMediaControl
                if (state.configuration.mode == MediaMode.Document) {
                    documentPicker.launchSafely(arrayOf("*/*"))
                } else {
                    state.openSourceChooser()
                }
            },
            onClear = { state.clear()?.let(events::dispatch) },
            modifier = modifier,
        )

        if (state.sheet is MediaSheet.SourceChooser) {
            FirstlightMediaSourceChooser(
                title = state.configuration.chooseMediaLabel,
                photoLibraryLabel = state.configuration.photoLibraryLabel,
                cameraLabel = state.configuration.cameraLabel,
                cancelLabel = state.configuration.cancelLabel,
                onPhotoLibrary = {
                    state.dismissSheet()
                    photoPicker.launchSafely(
                        PickVisualMediaRequest(ActivityResultContracts.PickVisualMedia.ImageOnly),
                    )
                },
                onCamera = {
                    state.dismissSheet()
                    val file = MediaTempFiles.newFile(context, "jpg")
                    cameraFile = file
                    if (!camera.launchSafely(MediaTempFiles.shareableUri(context, file))) {
                        cameraFile = null
                    }
                },
                onCancel = { state.dismissSheet() },
            )
        }

        val image = cropImage
        if (state.sheet is MediaSheet.Crop && image != null) {
            FirstlightMediaCropSheet(
                policy = state.configuration.cropPolicy,
                zoom = state.cropZoom,
                offset = state.cropOffset,
                image = image.asImageBitmap(),
                confirmLabel = state.configuration.confirmLabel,
                cancelLabel = state.configuration.cancelLabel,
                skipLabel = state.configuration.skipLabel,
                cropLabel = state.configuration.cropLabel,
                zoomInLabel = state.configuration.zoomInLabel,
                zoomOutLabel = state.configuration.zoomOutLabel,
                moveCropLeftLabel = state.configuration.moveCropLeftLabel,
                moveCropRightLabel = state.configuration.moveCropRightLabel,
                moveCropUpLabel = state.configuration.moveCropUpLabel,
                moveCropDownLabel = state.configuration.moveCropDownLabel,
                onConfirm = { commitCrop(crop = true) },
                onCancel = {
                    cropImage = null
                    state.dismissSheet()
                },
                onSkip = {
                    if (state.configuration.cropPolicy.allowsSkip) commitCrop(crop = false)
                },
                onZoomIn = state::zoomIn,
                onZoomOut = state::zoomOut,
                onZoomBy = state::zoomCropBy,
                onPan = state::panCrop,
                onMove = state::moveCrop,
            )
        }
    }
}

/** A missing picker app or a host that declares CAMERA without granting it must not crash the screen. */
private fun <I> ActivityResultLauncher<I>.launchSafely(input: I): Boolean = try {
    launch(input)
    true
} catch (_: ActivityNotFoundException) {
    false
} catch (_: SecurityException) {
    false
}

internal object MediaTempFiles {
    private const val MAX_IMAGE_EDGE = 4096

    fun newFile(context: Context, extension: String): File =
        File(context.cacheDir, "firstlight-media-${UUID.randomUUID()}.$extension")

    /** NativePHP's Android host declares this FileProvider over the cache directory. */
    fun shareableUri(context: Context, file: File): Uri =
        FileProvider.getUriForFile(context, "${context.packageName}.fileprovider", file)

    fun decode(context: Context, uri: Uri): Bitmap =
        ImageDecoder.decodeBitmap(ImageDecoder.createSource(context.contentResolver, uri)) { decoder, info, _ ->
            decoder.allocator = ImageDecoder.ALLOCATOR_SOFTWARE
            val edge = maxOf(info.size.width, info.size.height)
            if (edge > MAX_IMAGE_EDGE) {
                decoder.setTargetSampleSize((edge + MAX_IMAGE_EDGE - 1) / MAX_IMAGE_EDGE)
            }
        }

    fun writeJpeg(context: Context, image: Bitmap): String? {
        val file = newFile(context, "jpg")
        return runCatching {
            file.outputStream().use { image.compress(Bitmap.CompressFormat.JPEG, 92, it) }
            file.absolutePath
        }.getOrNull()
    }

    fun copy(context: Context, uri: Uri): String? {
        val name = context.contentResolver.query(uri, arrayOf(OpenableColumns.DISPLAY_NAME), null, null, null)
            ?.use { cursor -> if (cursor.moveToFirst()) cursor.getString(0) else null }
            ?.replace(Regex("[^A-Za-z0-9._-]"), "_")
            ?: "document"
        val file = File(context.cacheDir, "firstlight-media-${UUID.randomUUID()}-$name")
        val input = context.contentResolver.openInputStream(uri) ?: return null
        input.use { source -> file.outputStream().use { source.copyTo(it) } }
        return file.absolutePath
    }
}

private fun NativeUINode.findNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findNode(id)?.let { return it } }
    return null
}
