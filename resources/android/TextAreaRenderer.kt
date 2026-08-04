package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.focus.onFocusChanged
import androidx.compose.ui.text.input.KeyboardCapitalization
import androidx.compose.ui.text.input.TextFieldValue
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import com.nativephp.plugins.native_ui.NativeUITheme
import kotlinx.coroutines.delay

data class TextAreaRendererConfiguration(
    val nodeId: Int,
    val value: String,
    val label: String,
    val placeholder: String,
    val helper: String,
    val error: String,
    val required: Boolean,
    val disabled: Boolean,
    val readOnly: Boolean,
    val minLines: Int,
    val maxLines: Int,
    val autocapitalize: String,
    val autocorrectPolicy: String,
    val syncMode: String,
    val debounceMilliseconds: Int,
    val accessibilityLabel: String,
    val accessibilityHint: String,
    val onChangeCallback: Int,
) {
    constructor(node: NativeUINode) : this(
        nodeId = node.id,
        value = node.props.getString("value"),
        label = node.props.getString("label"),
        placeholder = node.props.getString("placeholder"),
        helper = node.props.getString("helper"),
        error = node.props.getString("error"),
        required = node.props.getBool("required"),
        disabled = node.props.getBool("disabled"),
        readOnly = node.props.getBool("read_only"),
        minLines = node.props.getInt("min_lines", 3).coerceAtLeast(1),
        maxLines = node.props.getInt("max_lines", 8).coerceAtLeast(node.props.getInt("min_lines", 3).coerceAtLeast(1)),
        autocapitalize = node.props.getString("autocapitalize"),
        autocorrectPolicy = node.props.getString("autocorrect_policy", "default"),
        syncMode = node.props.getString("sync_mode", "live"),
        debounceMilliseconds = node.props.getInt("debounce_ms", 300).coerceAtLeast(50),
        accessibilityLabel = node.props.getString("a11y_label").ifEmpty { node.props.getString("label") },
        accessibilityHint = node.props.getString("a11y_hint"),
        onChangeCallback = node.props.getCallbackId("on_change"),
    )
}

data class TextAreaRendererEvent(val callbackId: Int, val nodeId: Int, val value: String) {
    val wireName = "TEXT_CHANGE"
}

class TextAreaRendererEvents(private val send: (TextAreaRendererEvent) -> Unit) {
    fun dispatch(event: TextAreaRendererEvent) = send(event)

    companion object {
        val native = TextAreaRendererEvents { event ->
            NativeUIBridge.sendTextChangeEvent(event.callbackId, event.nodeId, event.value)
        }
    }
}

class TextAreaRendererState(node: NativeUINode) {
    var configuration by mutableStateOf(TextAreaRendererConfiguration(node)); private set
    var draft by mutableStateOf(TextFieldValue(configuration.value)); private set
    var lastCommitted = configuration.value; private set
    var pendingServerValue: String? = null; private set
    var focused = false; private set

    fun userChanged(value: TextFieldValue): TextAreaRendererEvent? {
        if (configuration.disabled || configuration.readOnly) return null
        draft = value
        return if (configuration.syncMode == "live") commitIfNeeded() else null
    }

    fun focusChanged(value: Boolean): TextAreaRendererEvent? {
        if (value) {
            focused = true
            return null
        }

        val hadUncommittedEdit = draft.text != lastCommitted
        focused = false
        val event = commitIfNeeded()
        if (!hadUncommittedEdit) applyPendingServerValue()
        return event
    }

    fun flush(): TextAreaRendererEvent? = commitIfNeeded()

    fun serverPublished(tree: NativeUITree) {
        val published = tree.root.findTextAreaNode(configuration.nodeId)
            ?.let(::TextAreaRendererConfiguration)
            ?: return
        configuration = published

        if (focused) {
            pendingServerValue = published.value.takeUnless { it == lastCommitted }
        } else {
            draft = TextFieldValue(published.value)
            lastCommitted = published.value
            pendingServerValue = null
        }
    }

    private fun commitIfNeeded(): TextAreaRendererEvent? {
        if (draft.text == lastCommitted) return null
        lastCommitted = draft.text
        return configuration.onChangeCallback.takeIf { it != 0 }?.let {
            TextAreaRendererEvent(it, configuration.nodeId, draft.text)
        }
    }

    private fun applyPendingServerValue() {
        val pending = pendingServerValue ?: return
        draft = TextFieldValue(pending)
        lastCommitted = pending
        pendingServerValue = null
    }
}

object TextAreaRenderer {
    @Composable
    fun Render(
        node: NativeUINode,
        modifier: Modifier,
        events: TextAreaRendererEvents = TextAreaRendererEvents.native,
    ) {
        val state = remember(node.id) { TextAreaRendererState(node) }

        NativeUIBridge.currentTree.value?.let { tree ->
            LaunchedEffect(tree) { state.serverPublished(tree) }
        }

        LaunchedEffect(state.draft.text) {
            if (state.configuration.syncMode == "debounce") {
                delay(state.configuration.debounceMilliseconds.toLong())
                state.flush()?.let(events::dispatch)
            }
        }

        FirstlightTextAreaControl(
            configuration = state.configuration,
            draft = state.draft,
            tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light,
            onValueChange = { state.userChanged(it)?.let(events::dispatch) },
            modifier = modifier.onFocusChanged { state.focusChanged(it.isFocused)?.let(events::dispatch) },
        )
    }
}

internal fun textAreaKeyboardOptions(configuration: TextAreaRendererConfiguration) = KeyboardOptions(
    capitalization = when (configuration.autocapitalize) {
        "none" -> KeyboardCapitalization.None
        "sentences" -> KeyboardCapitalization.Sentences
        "words" -> KeyboardCapitalization.Words
        "characters" -> KeyboardCapitalization.Characters
        else -> KeyboardCapitalization.Unspecified
    },
    autoCorrectEnabled = when (configuration.autocorrectPolicy) {
        "enabled" -> true
        "disabled" -> false
        else -> null
    },
)

private fun NativeUINode.findTextAreaNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findTextAreaNode(id)?.let { return it } }
    return null
}
