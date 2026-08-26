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
import java.time.LocalDate
import java.time.ZoneId

sealed interface DatePickerRendererEvent {
    val wireName: String

    data class Change(val callbackId: Int, val nodeId: Int, val value: String) : DatePickerRendererEvent {
        override val wireName = "SELECT_CHANGE"
    }
}

class DatePickerRendererEvents(private val send: (DatePickerRendererEvent) -> Unit) {
    fun dispatch(event: DatePickerRendererEvent) = send(event)

    companion object {
        val native = DatePickerRendererEvents { event ->
            when (event) {
                is DatePickerRendererEvent.Change -> NativeUIBridge.sendSelectChangeEvent(
                    event.callbackId,
                    event.nodeId,
                    event.value,
                )
            }
        }
    }
}

data class DatePickerRendererConfiguration(
    val nodeId: Int,
    val hasValue: Boolean,
    val value: String,
    val min: String,
    val max: String,
    val label: String,
    val placeholder: String,
    val helper: String,
    val error: String,
    val required: Boolean,
    val disabled: Boolean,
    val locale: String,
    val timezone: String,
    val confirmLabel: String,
    val cancelLabel: String,
    val accessibilityLabel: String,
    val accessibilityHint: String,
    val onChangeCallback: Int,
) {
    constructor(node: NativeUINode) : this(
        nodeId = node.id,
        hasValue = node.props.getBool("has_value"),
        value = node.props.getString("value"),
        min = node.props.getString("min"),
        max = node.props.getString("max"),
        label = node.props.getString("label"),
        placeholder = node.props.getString("placeholder"),
        helper = node.props.getString("helper"),
        error = node.props.getString("error"),
        required = node.props.getBool("required"),
        disabled = node.props.getBool("disabled"),
        locale = node.props.getString("locale"),
        timezone = node.props.getString("timezone"),
        confirmLabel = node.props.getString("confirm_label").ifEmpty { "Confirm" },
        cancelLabel = node.props.getString("cancel_label").ifEmpty { "Cancel" },
        accessibilityLabel = node.props.getString("a11y_label").ifEmpty { node.props.getString("label") },
        accessibilityHint = node.props.getString("a11y_hint"),
        onChangeCallback = node.props.getCallbackId("on_change"),
    )

    val acceptedValue: String? get() = value.takeIf { hasValue }
    val presentationFingerprint: List<Any>
        get() = listOf(hasValue, value, min, max, locale, timezone, disabled)
}

class DatePickerRendererState(node: NativeUINode) {
    var configuration by mutableStateOf(DatePickerRendererConfiguration(node)); private set
    var draft by mutableStateOf<String?>(null); private set
    var isPresented by mutableStateOf(false); private set
    var presentationVersion by mutableIntStateOf(0); private set

    fun open(today: LocalDate = localToday(configuration.timezone)): Boolean {
        if (configuration.disabled || configuration.onChangeCallback == 0) return false

        draft = clamp(configuration.acceptedValue ?: today.toString())
        presentationVersion += 1
        isPresented = true
        return true
    }

    fun userSelected(value: String) {
        if (isPresented) draft = clamp(value)
    }

    fun cancel() {
        draft = null
        isPresented = false
    }

    fun confirm(): DatePickerRendererEvent? {
        val selected = draft
        val published = configuration
        cancel()

        if (selected == null || published.disabled || published.onChangeCallback == 0) return null
        if (selected == published.acceptedValue) return null

        return DatePickerRendererEvent.Change(
            callbackId = published.onChangeCallback,
            nodeId = published.nodeId,
            value = selected,
        )
    }

    fun serverPublished(tree: NativeUITree) {
        val node = tree.root.findDatePickerNode(configuration.nodeId) ?: return
        publish(DatePickerRendererConfiguration(node))
    }

    fun serverPublished(node: NativeUINode) {
        if (node.id == configuration.nodeId) publish(DatePickerRendererConfiguration(node))
    }

    private fun publish(published: DatePickerRendererConfiguration) {
        val presentationChanged = published.presentationFingerprint != configuration.presentationFingerprint
        configuration = published
        if (isPresented && presentationChanged) cancel()
    }

    private fun clamp(value: String): String = value
        .let { candidate -> configuration.min.takeIf(String::isNotEmpty)?.let { maxOf(candidate, it) } ?: candidate }
        .let { candidate -> configuration.max.takeIf(String::isNotEmpty)?.let { minOf(candidate, it) } ?: candidate }

    companion object {
        fun localToday(timezone: String): LocalDate = LocalDate.now(
            timezone.takeIf(String::isNotEmpty)?.let(ZoneId::of) ?: ZoneId.systemDefault(),
        )
    }
}

object FirstlightDatePickerRenderer {
    @Composable
    fun Render(
        node: NativeUINode,
        modifier: Modifier,
        events: DatePickerRendererEvents = DatePickerRendererEvents.native,
    ) {
        val state = remember(node.id) { DatePickerRendererState(node) }
        LaunchedEffect(node.props, node.onPress) { state.serverPublished(node) }
        NativeUIBridge.currentTree.value?.let { tree ->
            LaunchedEffect(tree) { state.serverPublished(tree) }
        }

        FirstlightDatePickerControl(
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

private fun NativeUINode.findDatePickerNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findDatePickerNode(id)?.let { return it } }
    return null
}
