package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
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
import androidx.compose.ui.text.input.TextFieldValue
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import kotlinx.coroutines.delay

sealed interface SearchFieldRendererEvent {
    val wireName: String

    data class Change(val callbackId: Int, val nodeId: Int, val value: String) : SearchFieldRendererEvent {
        override val wireName = "TEXT_CHANGE"
    }

    data class Submit(val callbackId: Int, val nodeId: Int, val value: String) : SearchFieldRendererEvent {
        override val wireName = "SUBMIT"
    }
}

class SearchFieldRendererEvents(private val send: (SearchFieldRendererEvent) -> Unit) {
    fun dispatch(event: SearchFieldRendererEvent) = send(event)

    companion object {
        val native = SearchFieldRendererEvents { event ->
            when (event) {
                is SearchFieldRendererEvent.Change -> NativeUIBridge.sendTextChangeEvent(event.callbackId, event.nodeId, event.value)
                is SearchFieldRendererEvent.Submit -> NativeUIBridge.sendSubmitEvent(event.callbackId, event.nodeId, event.value)
            }
        }
    }
}

data class SearchFieldRendererConfiguration(
    val nodeId: Int,
    val value: String,
    val placeholder: String,
    val disabled: Boolean,
    val autocapitalize: String,
    val autocorrectPolicy: String,
    val syncMode: String,
    val debounceMilliseconds: Int,
    val accessibilityLabel: String,
    val accessibilityHint: String,
    val onChangeCallback: Int,
    val onSubmitCallback: Int,
) {
    constructor(node: NativeUINode) : this(
        nodeId = node.id,
        value = node.props.getString("value"),
        placeholder = node.props.getString("placeholder"),
        disabled = node.props.getBool("disabled"),
        autocapitalize = node.props.getString("autocapitalize"),
        autocorrectPolicy = node.props.getString("autocorrect_policy", "default"),
        syncMode = node.props.getString("sync_mode", "live"),
        debounceMilliseconds = node.props.getInt("debounce_ms", 300).coerceAtLeast(50),
        accessibilityLabel = node.props.getString("a11y_label"),
        accessibilityHint = node.props.getString("a11y_hint"),
        onChangeCallback = node.props.getCallbackId("on_change"),
        onSubmitCallback = node.props.getCallbackId("on_submit"),
    )
}

class SearchFieldRendererState(node: NativeUINode) {
    var configuration by mutableStateOf(SearchFieldRendererConfiguration(node)); private set
    var draft by mutableStateOf(TextFieldValue(configuration.value)); private set
    var lastCommitted = configuration.value; private set
    var pendingServerValue: String? = null; private set
    var focused = false; private set

    fun userChanged(value: String): SearchFieldRendererEvent? = userChanged(draft.copy(text = value))

    fun userChanged(value: TextFieldValue): SearchFieldRendererEvent? {
        if (configuration.disabled) return null
        draft = value
        return if (configuration.syncMode == "live") commit() else null
    }

    fun focusChanged(value: Boolean): SearchFieldRendererEvent? {
        focused = value
        return if (value) null else commit()
    }

    fun flush(): SearchFieldRendererEvent? = commit()

    fun submit(): List<SearchFieldRendererEvent> {
        if (configuration.disabled) return emptyList()
        return buildList {
            commit()?.let(::add)
            if (configuration.onSubmitCallback != 0) {
                add(SearchFieldRendererEvent.Submit(
                    configuration.onSubmitCallback,
                    configuration.nodeId,
                    draft.text,
                ))
            }
        }
    }

    fun clear(): SearchFieldRendererEvent? {
        if (configuration.disabled) return null
        draft = TextFieldValue("")
        return commit()
    }

    fun serverPublished(tree: NativeUITree) {
        val published = tree.root.findSearchFieldNode(configuration.nodeId)
            ?.let(::SearchFieldRendererConfiguration)
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

    private fun commit(): SearchFieldRendererEvent? {
        if (draft.text == lastCommitted) return null
        lastCommitted = draft.text
        return configuration.onChangeCallback.takeIf { it != 0 }?.let {
            SearchFieldRendererEvent.Change(it, configuration.nodeId, draft.text)
        }
    }
}

object SearchFieldRenderer {
    @Composable
    fun Render(
        node: NativeUINode,
        modifier: Modifier,
        events: SearchFieldRendererEvents = SearchFieldRendererEvents.native,
    ) {
        val state = remember(node.id) { SearchFieldRendererState(node) }
        NativeUIBridge.currentTree.value?.let { tree ->
            LaunchedEffect(tree) { state.serverPublished(tree) }
        }
        LaunchedEffect(state.draft.text) {
            if (state.configuration.syncMode == "debounce") {
                delay(state.configuration.debounceMilliseconds.toLong())
                state.flush()?.let(events::dispatch)
            }
        }

        FirstlightSearchFieldControl(
            configuration = state.configuration,
            draft = state.draft,
            onValueChange = { state.userChanged(it)?.let(events::dispatch) },
            onClear = { state.clear()?.let(events::dispatch) },
            onSubmit = { state.submit().forEach(events::dispatch) },
            modifier = modifier.onFocusChanged {
                state.focusChanged(it.isFocused)?.let(events::dispatch)
            },
        )
    }
}

internal fun searchFieldKeyboardOptions(configuration: SearchFieldRendererConfiguration) = KeyboardOptions(
    capitalization = when (configuration.autocapitalize) {
        "none" -> KeyboardCapitalization.None
        "words" -> KeyboardCapitalization.Words
        "characters" -> KeyboardCapitalization.Characters
        else -> KeyboardCapitalization.Sentences
    },
    autoCorrectEnabled = when (configuration.autocorrectPolicy) {
        "enabled" -> true
        "disabled" -> false
        else -> null
    },
    imeAction = ImeAction.Search,
)

internal fun searchFieldKeyboardActions(submit: () -> Unit) = KeyboardActions(onSearch = { submit() })

private fun NativeUINode.findSearchFieldNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findSearchFieldNode(id)?.let { return it } }
    return null
}
