package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import com.nativephp.plugins.native_ui.NativeUITheme
import java.time.LocalTime
import java.time.ZoneId

sealed interface TimePickerRendererEvent {
    val wireName: String

    data class Change(val callbackId: Int, val nodeId: Int, val value: String) : TimePickerRendererEvent {
        override val wireName = "SELECT_CHANGE"
    }
}

class TimePickerRendererEvents(private val send: (TimePickerRendererEvent) -> Unit) {
    fun dispatch(event: TimePickerRendererEvent) = send(event)

    companion object {
        val native = TimePickerRendererEvents { event ->
            when (event) {
                is TimePickerRendererEvent.Change -> NativeUIBridge.sendSelectChangeEvent(
                    event.callbackId,
                    event.nodeId,
                    event.value,
                )
            }
        }
    }
}

data class TimePickerRendererConfiguration(
    val nodeId: Int,
    val hasValue: Boolean,
    val value: String,
    val label: String,
    val placeholder: String,
    val helper: String,
    val error: String,
    val required: Boolean,
    val disabled: Boolean,
    val locale: String,
    val timezone: String,
    val accessibilityLabel: String,
    val accessibilityHint: String,
    val onChangeCallback: Int,
) {
    constructor(node: NativeUINode) : this(
        nodeId = node.id,
        hasValue = node.props.getBool("has_value"),
        value = node.props.getString("value"),
        label = node.props.getString("label"),
        placeholder = node.props.getString("placeholder"),
        helper = node.props.getString("helper"),
        error = node.props.getString("error"),
        required = node.props.getBool("required"),
        disabled = node.props.getBool("disabled"),
        locale = node.props.getString("locale"),
        timezone = node.props.getString("timezone"),
        accessibilityLabel = node.props.getString("a11y_label").ifEmpty { node.props.getString("label") },
        accessibilityHint = node.props.getString("a11y_hint"),
        onChangeCallback = node.props.getCallbackId("on_change"),
    )

    val acceptedValue: String? get() = value.takeIf { hasValue }
    val presentationFingerprint: List<Any>
        get() = listOf(hasValue, value, locale, timezone, disabled)
}

class TimePickerRendererState(node: NativeUINode) {
    var configuration by mutableStateOf(TimePickerRendererConfiguration(node)); private set
    var draft by mutableStateOf<String?>(null); private set
    var isPresented by mutableStateOf(false); private set
    var presentationVersion by mutableIntStateOf(0); private set

    fun open(currentTime: LocalTime = localCurrentTime(configuration.timezone)): Boolean {
        if (configuration.disabled || configuration.onChangeCallback == 0) return false

        draft = configuration.acceptedValue ?: currentTime.toCanonicalTime()
        presentationVersion += 1
        isPresented = true
        return true
    }

    fun userSelected(value: String) {
        if (isPresented) draft = value
    }

    fun cancel() {
        draft = null
        isPresented = false
    }

    fun confirm(): TimePickerRendererEvent? {
        val selected = draft
        val published = configuration
        cancel()

        if (selected == null || published.disabled || published.onChangeCallback == 0) return null
        if (selected == published.acceptedValue) return null

        return TimePickerRendererEvent.Change(
            callbackId = published.onChangeCallback,
            nodeId = published.nodeId,
            value = selected,
        )
    }

    fun serverPublished(tree: NativeUITree) {
        val node = tree.root.findTimePickerNode(configuration.nodeId) ?: return
        publish(TimePickerRendererConfiguration(node))
    }

    fun serverPublished(node: NativeUINode) {
        if (node.id == configuration.nodeId) publish(TimePickerRendererConfiguration(node))
    }

    private fun publish(published: TimePickerRendererConfiguration) {
        val presentationChanged = published.presentationFingerprint != configuration.presentationFingerprint
        configuration = published
        if (isPresented && presentationChanged) cancel()
    }

    companion object {
        fun localCurrentTime(timezone: String): LocalTime = LocalTime.now(
            timezone.takeIf(String::isNotEmpty)?.let(ZoneId::of) ?: ZoneId.systemDefault(),
        ).withSecond(0).withNano(0)
    }
}

internal fun LocalTime.toCanonicalTime(): String = "%02d:%02d".format(hour, minute)

object TimePickerRenderer {
    @Composable
    fun Render(
        node: NativeUINode,
        modifier: Modifier,
        events: TimePickerRendererEvents = TimePickerRendererEvents.native,
    ) {
        val state = remember(node.id) { TimePickerRendererState(node) }
        LaunchedEffect(node.props, node.onPress) { state.serverPublished(node) }
        NativeUIBridge.currentTree.value?.let { tree ->
            LaunchedEffect(tree) { state.serverPublished(tree) }
        }

        FirstlightTimePickerControl(
            state = state,
            tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light,
            onOpen = state::open,
            onCancel = state::cancel,
            onSelect = state::userSelected,
            onConfirm = { state.confirm()?.let(events::dispatch) },
            modifier = modifier,
        )
    }
}

private fun NativeUINode.findTimePickerNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findTimePickerNode(id)?.let { return it } }
    return null
}
