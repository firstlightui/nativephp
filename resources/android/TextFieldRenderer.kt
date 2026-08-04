package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.text.KeyboardActions
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
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardCapitalization
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.TextFieldValue
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import com.nativephp.plugins.native_ui.NativeUITheme
import kotlinx.coroutines.delay

sealed interface TextFieldRendererEvent {
    val wireName: String
    data class Change(val callbackId: Int, val nodeId: Int, val value: String) : TextFieldRendererEvent { override val wireName = "TEXT_CHANGE" }
    data class Submit(val callbackId: Int, val nodeId: Int, val value: String) : TextFieldRendererEvent { override val wireName = "SUBMIT" }
    data class Press(val callbackId: Int, val nodeId: Int) : TextFieldRendererEvent { override val wireName = "PRESS" }
}

class TextFieldRendererEvents(private val send: (TextFieldRendererEvent) -> Unit) {
    fun dispatch(event: TextFieldRendererEvent) = send(event)

    companion object {
        val native = TextFieldRendererEvents { event ->
            when (event) {
                is TextFieldRendererEvent.Change -> NativeUIBridge.sendTextChangeEvent(event.callbackId, event.nodeId, event.value)
                is TextFieldRendererEvent.Submit -> NativeUIBridge.sendSubmitEvent(event.callbackId, event.nodeId, event.value)
                is TextFieldRendererEvent.Press -> NativeUIBridge.sendPressEvent(event.callbackId, event.nodeId)
            }
        }
    }
}

data class TextFieldRendererConfiguration(
    val nodeId: Int,
    val value: String,
    val label: String,
    val placeholder: String,
    val helper: String,
    val error: String,
    val required: Boolean,
    val disabled: Boolean,
    val readOnly: Boolean,
    val secure: Boolean,
    val keyboard: String,
    val contentType: String,
    val autocapitalize: String,
    val autocorrectPolicy: String,
    val submitLabel: String,
    val leadingIcon: String,
    val leadingIconVariant: String,
    val trailingIcon: String,
    val trailingIconVariant: String,
    val trailingAccessibilityLabel: String,
    val clearable: Boolean,
    val revealable: Boolean,
    val syncMode: String,
    val debounceMilliseconds: Int,
    val accessibilityLabel: String,
    val accessibilityHint: String,
    val onChangeCallback: Int,
    val onSubmitCallback: Int,
    val onPressCallback: Int,
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
        secure = node.props.getBool("secure"),
        keyboard = node.props.getString("keyboard", "text"),
        contentType = node.props.getString("content_type"),
        autocapitalize = node.props.getString("autocapitalize"),
        autocorrectPolicy = node.props.getString("autocorrect_policy", "default"),
        submitLabel = node.props.getString("submit_label"),
        leadingIcon = node.props.getString("leading_icon"),
        leadingIconVariant = node.props.getString("leading_icon_variant"),
        trailingIcon = node.props.getString("trailing_icon"),
        trailingIconVariant = node.props.getString("trailing_icon_variant"),
        trailingAccessibilityLabel = node.props.getString("trailing_a11y_label"),
        clearable = node.props.getBool("clearable"),
        revealable = node.props.getBool("revealable"),
        syncMode = node.props.getString("sync_mode", "live"),
        debounceMilliseconds = node.props.getInt("debounce_ms", 300).coerceAtLeast(50),
        accessibilityLabel = node.props.getString("a11y_label").ifEmpty { node.props.getString("label") },
        accessibilityHint = node.props.getString("a11y_hint"),
        onChangeCallback = node.props.getCallbackId("on_change"),
        onSubmitCallback = node.props.getCallbackId("on_submit"),
        onPressCallback = node.onPress,
    )
}

class TextFieldRendererState(node: NativeUINode) {
    var configuration by mutableStateOf(TextFieldRendererConfiguration(node)); private set
    var draft by mutableStateOf(TextFieldValue(configuration.value)); private set
    var lastCommitted = configuration.value; private set
    var pendingServerValue: String? = null; private set
    var focused = false; private set
    var revealed by mutableStateOf(false); private set

    fun userChanged(value: String): TextFieldRendererEvent? = userChanged(draft.copy(text = value))
    fun userChanged(value: TextFieldValue): TextFieldRendererEvent? {
        if (configuration.disabled || configuration.readOnly) return null
        draft = value
        return if (configuration.syncMode == "live") commit() else null
    }
    fun focusChanged(value: Boolean): TextFieldRendererEvent? { focused = value; return if (value) null else commit() }
    fun flush() = commit()
    fun submit(): List<TextFieldRendererEvent> = buildList {
        commit()?.let(::add)
        if (configuration.onSubmitCallback != 0) add(TextFieldRendererEvent.Submit(configuration.onSubmitCallback, configuration.nodeId, draft.text))
    }
    fun clear(): TextFieldRendererEvent? {
        if (!configuration.clearable || configuration.disabled || configuration.readOnly) return null
        draft = TextFieldValue("")
        return commit()
    }
    fun toggleReveal() { if (configuration.revealable) revealed = !revealed }
    fun serverPublished(tree: NativeUITree) {
        val published = tree.root.findNode(configuration.nodeId)?.let(::TextFieldRendererConfiguration) ?: return
        configuration = published
        if (focused) {
            pendingServerValue = published.value.takeUnless { it == lastCommitted }
        } else {
            draft = TextFieldValue(published.value); lastCommitted = published.value; pendingServerValue = null
        }
    }
    private fun commit(): TextFieldRendererEvent? {
        if (draft.text == lastCommitted) return null
        lastCommitted = draft.text
        return configuration.onChangeCallback.takeIf { it != 0 }?.let { TextFieldRendererEvent.Change(it, configuration.nodeId, draft.text) }
    }
}

object TextFieldRenderer {
    @Composable
    fun Render(node: NativeUINode, modifier: Modifier, events: TextFieldRendererEvents = TextFieldRendererEvents.native) {
        val state = remember(node.id) { TextFieldRendererState(node) }
        NativeUIBridge.currentTree.value?.let { tree -> LaunchedEffect(tree) { state.serverPublished(tree) } }
        LaunchedEffect(state.draft.text) {
            if (state.configuration.syncMode == "debounce") {
                delay(state.configuration.debounceMilliseconds.toLong())
                state.flush()?.let(events::dispatch)
            }
        }
        FirstlightTextFieldControl(
            configuration = state.configuration,
            draft = state.draft,
            revealed = state.revealed,
            tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light,
            onValueChange = { state.userChanged(it)?.let(events::dispatch) },
            onClear = { state.clear()?.let(events::dispatch) },
            onReveal = state::toggleReveal,
            onTrailingPress = { state.configuration.onPressCallback.takeIf { it != 0 }?.let { events.dispatch(TextFieldRendererEvent.Press(it, state.configuration.nodeId)) } },
            onSubmit = { state.submit().forEach(events::dispatch) },
            modifier = modifier.onFocusChanged { state.focusChanged(it.isFocused)?.let(events::dispatch) },
        )
    }
}

internal fun textFieldKeyboardOptions(configuration: TextFieldRendererConfiguration) = KeyboardOptions(
    capitalization = when (configuration.autocapitalize) {
        "sentences" -> KeyboardCapitalization.Sentences
        "words" -> KeyboardCapitalization.Words
        "characters" -> KeyboardCapitalization.Characters
        else -> KeyboardCapitalization.None
    },
    autoCorrectEnabled = when (configuration.autocorrectPolicy) { "enabled" -> true; "disabled" -> false; else -> null },
    keyboardType = when (configuration.keyboard) {
        "email" -> KeyboardType.Email; "phone" -> KeyboardType.Phone; "url" -> KeyboardType.Uri
        "number" -> KeyboardType.Number; "decimal" -> KeyboardType.Decimal; else -> KeyboardType.Text
    },
    imeAction = when (configuration.submitLabel) {
        "done" -> ImeAction.Done; "go" -> ImeAction.Go; "next" -> ImeAction.Next
        "search" -> ImeAction.Search; "send" -> ImeAction.Send; else -> ImeAction.Default
    },
)

internal fun textFieldKeyboardActions(configuration: TextFieldRendererConfiguration, submit: () -> Unit) = KeyboardActions(
    onDone = { submit() }, onGo = { submit() }, onNext = { submit() }, onSearch = { submit() }, onSend = { submit() },
)

private fun NativeUINode.findNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { it.findNode(id)?.let { node -> return node } }
    return null
}
