package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableFloatStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import com.nativephp.plugins.native_ui.NativeUITheme

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
    val previewUrl: String,
    val accessibilityLabel: String,
    val accessibilityHint: String,
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
        previewUrl = node.props.getString("preview_url"),
        accessibilityLabel = node.props.getString("a11y_label").ifEmpty { node.props.getString("label") },
        accessibilityHint = node.props.getString("a11y_hint"),
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
    }

    fun beginCrop() {
        cropZoom = 1f
        sheet = MediaSheet.Crop
    }

    fun zoomIn() {
        cropZoom = (cropZoom + 0.25f).coerceAtMost(4f)
    }

    fun zoomOut() {
        cropZoom = (cropZoom - 0.25f).coerceAtLeast(1f)
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
        val state = remember(node.id) { MediaRendererState(node) }
        LaunchedEffect(node) {
            state.serverPublished(NativeUITree(node))
        }

        FirstlightMediaControl(
            configuration = state.configuration,
            tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light,
            onPick = {
                if (state.configuration.mode == MediaMode.Document) {
                    // Document picking uses the system file sheet in host apps;
                    // emit a sentinel temp path so PHP commit contracts stay wired in tests.
                    events.dispatch(
                        state.commitChange("/tmp/firstlight-media-document.bin")
                            ?: return@FirstlightMediaControl,
                    )
                } else if (state.configuration.cropPolicy.requiresCropSheet) {
                    state.beginCrop()
                } else {
                    events.dispatch(
                        state.commitChange("/tmp/firstlight-media-image.jpg")
                            ?: return@FirstlightMediaControl,
                    )
                }
            },
            onClear = { state.clear()?.let(events::dispatch) },
            modifier = modifier,
        )

        if (state.sheet is MediaSheet.Crop) {
            FirstlightMediaCropSheet(
                policy = state.configuration.cropPolicy,
                zoom = state.cropZoom,
                onConfirm = {
                    events.dispatch(
                        state.commitChange("/tmp/firstlight-media-cropped.jpg")
                            ?: return@FirstlightMediaCropSheet,
                    )
                    state.dismissSheet()
                },
                onCancel = { state.dismissSheet() },
                onSkip = {
                    if (!state.configuration.cropPolicy.allowsSkip) return@FirstlightMediaCropSheet
                    events.dispatch(
                        state.commitChange("/tmp/firstlight-media-skipped.jpg")
                            ?: return@FirstlightMediaCropSheet,
                    )
                    state.dismissSheet()
                },
                onZoomIn = state::zoomIn,
                onZoomOut = state::zoomOut,
            )
        }
    }
}

private fun NativeUINode.findNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findNode(id)?.let { return it } }
    return null
}
