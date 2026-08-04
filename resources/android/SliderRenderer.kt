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
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeElementBridge
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import com.nativephp.plugins.native_ui.NativeUITheme
import kotlin.math.abs
import kotlin.math.max
import kotlin.math.round
import kotlinx.coroutines.delay

data class SliderRendererEvent(val callbackId: Int, val nodeId: Int, val value: Float) {
    val wireName = "SLIDER_CHANGE"
}

class SliderRendererEvents(private val send: (SliderRendererEvent) -> Unit) {
    fun dispatch(event: SliderRendererEvent) = send(event)

    companion object {
        val native = SliderRendererEvents { event ->
            NativeElementBridge.sendSliderChangeEvent(event.callbackId, event.nodeId, event.value)
        }
    }
}

data class SliderRendererConfiguration(
    val nodeId: Int,
    val value: Float,
    val min: Float,
    val max: Float,
    val step: Float,
    val intervalCount: Int,
    val label: String,
    val helper: String,
    val error: String,
    val disabled: Boolean,
    val syncMode: String,
    val debounceMilliseconds: Int,
    val accessibilityLabel: String,
    val accessibilityHint: String,
    val accessibilityValue: String,
    val onChangeCallback: Int,
) {
    constructor(node: NativeUINode) : this(
        nodeId = node.id,
        value = node.props.firstlightSliderFloat("value"),
        min = node.props.firstlightSliderFloat("min"),
        max = node.props.firstlightSliderFloat("max"),
        step = node.props.firstlightSliderFloat("step", 1f),
        intervalCount = node.props.getInt("interval_count"),
        label = node.props.getString("label"),
        helper = node.props.getString("helper"),
        error = node.props.getString("error"),
        disabled = node.props.getBool("disabled"),
        syncMode = node.props.getString("sync_mode", "live"),
        debounceMilliseconds = node.props.getInt("debounce_ms", 300).coerceAtLeast(50),
        accessibilityLabel = node.props.getString("a11y_label").ifEmpty { node.props.getString("label") },
        accessibilityHint = node.props.getString("a11y_hint"),
        accessibilityValue = node.props.getString("a11y_value"),
        onChangeCallback = node.props.getCallbackId("on_change"),
    )

    val isInteractive: Boolean get() = !disabled && onChangeCallback != 0
}

class SliderRendererState(configuration: SliderRendererConfiguration) {
    var configuration by mutableStateOf(configuration); private set
    var draft by mutableFloatStateOf(configuration.value); private set
    var lastEmitted = configuration.value; private set
    var isEditing by mutableStateOf(false); private set

    constructor(node: NativeUINode) : this(SliderRendererConfiguration(node))

    fun beginEditing(): Boolean {
        if (!configuration.isInteractive) return false
        isEditing = true
        return true
    }

    fun userChanged(value: Float): SliderRendererEvent? {
        if (!configuration.isInteractive) return null
        if (!isEditing) isEditing = true
        draft = snapped(value)
        return if (configuration.syncMode == "live") emitIfNeeded() else null
    }

    fun finishEditing(): SliderRendererEvent? {
        if (!isEditing) return null
        isEditing = false
        return if (configuration.syncMode == "live") null else emitIfNeeded()
    }

    fun flush(): SliderRendererEvent? {
        if (configuration.syncMode != "debounce") return null
        return emitIfNeeded()
    }

    fun serverPublished(tree: NativeUITree) {
        tree.root.findSliderNode(configuration.nodeId)?.let(::SliderRendererConfiguration)?.let(::publish)
    }

    fun serverPublished(published: SliderRendererConfiguration) {
        publish(published)
    }

    private fun publish(published: SliderRendererConfiguration) {
        configuration = published
        draft = published.value
        lastEmitted = published.value
        isEditing = false
    }

    private fun emitIfNeeded(): SliderRendererEvent? {
        if (approximatelyEqual(draft, lastEmitted)) return null
        lastEmitted = draft
        return SliderRendererEvent(configuration.onChangeCallback, configuration.nodeId, draft)
    }

    private fun snapped(value: Float): Float {
        val index = round((value - configuration.min) / configuration.step)
            .coerceIn(0f, configuration.intervalCount.toFloat())
        return configuration.min + (index * configuration.step)
    }

    private fun approximatelyEqual(left: Float, right: Float): Boolean =
        abs(left - right) <= max(abs(configuration.step) * 0.000001f, 0.000001f)
}

object FirstlightSliderRenderer {
    @Composable
    fun Render(
        node: NativeUINode,
        modifier: Modifier,
        events: SliderRendererEvents = SliderRendererEvents.native,
    ) {
        val state = remember(node.id) { SliderRendererState(node) }

        LaunchedEffect(node.props, node.onPress) { state.serverPublished(SliderRendererConfiguration(node)) }
        NativeUIBridge.currentTree.value?.let { tree ->
            LaunchedEffect(tree) { state.serverPublished(tree) }
        }

        LaunchedEffect(state.draft) {
            if (state.configuration.syncMode == "debounce" && state.isEditing) {
                delay(state.configuration.debounceMilliseconds.toLong())
                state.flush()?.let(events::dispatch)
            }
        }

        FirstlightSliderControl(
            state = state,
            tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light,
            onValueChange = { state.userChanged(it)?.let(events::dispatch) },
            onValueChangeFinished = { state.finishEditing()?.let(events::dispatch) },
            modifier = modifier,
        )
    }
}

private fun NativeUINode.findSliderNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findSliderNode(id)?.let { return it } }
    return null
}

private fun GenericProps.firstlightSliderFloat(key: String, default: Float = 0f): Float {
    val nativeGetter = javaClass.methods.firstOrNull { method ->
        method.name == "getFloat" && method.parameterTypes.size == 2
    }
    if (nativeGetter != null) {
        return (nativeGetter.invoke(this, key, default) as? Number)?.toFloat() ?: default
    }

    // The isolated JVM shim predates GenericProps.getFloat; its backing map is test-only.
    val backingField = javaClass.declaredFields.firstOrNull { it.name == "values" } ?: return default
    backingField.isAccessible = true
    @Suppress("UNCHECKED_CAST")
    val values = backingField.get(this) as? Map<String, Any> ?: return default
    return (values[key] as? Number)?.toFloat() ?: default
}
