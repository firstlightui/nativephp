package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.ExperimentalFoundationApi
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.foundation.text.input.InputTransformation
import androidx.compose.foundation.text.input.TextFieldBuffer
import androidx.compose.foundation.text.input.TextFieldState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.runtime.snapshotFlow
import androidx.compose.ui.Modifier
import androidx.compose.ui.focus.focusRequester
import androidx.compose.ui.text.TextRange
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import com.nativephp.plugins.native_ui.NativeUITheme
import kotlinx.coroutines.delay

data class CodeEditorRendererConfiguration(
    val nodeId: Int,
    val value: String,
    val label: String,
    val helper: String,
    val error: String,
    val required: Boolean,
    val disabled: Boolean,
    val readOnly: Boolean,
    val language: CodeEditorLanguage,
    val lineNumbers: Boolean,
    val wrap: Boolean,
    val indent: Int,
    val follow: Boolean,
    val minLines: Int,
    val maxLines: Int,
    val syncMode: String,
    val debounceMilliseconds: Int,
    val accessibilityLabel: String,
    val accessibilityHint: String,
    val onChangeCallback: Int,
    val labels: Map<String, String>,
) {
    constructor(node: NativeUINode) : this(
        nodeId = node.id,
        value = node.props.getString("value"),
        label = node.props.getString("label"),
        helper = node.props.getString("helper"),
        error = node.props.getString("error"),
        required = node.props.getBool("required"),
        disabled = node.props.getBool("disabled"),
        readOnly = node.props.getBool("read_only"),
        language = CodeEditorLanguage.from(node.props.getString("language", "plain")),
        lineNumbers = node.props.getBool("line_numbers", true),
        wrap = node.props.getBool("wrap", true),
        indent = node.props.getInt("indent", 2).takeIf { it == 2 || it == 4 } ?: 2,
        follow = node.props.getBool("follow") && node.props.getBool("read_only"),
        minLines = node.props.getInt("min_lines", 6).coerceAtLeast(1),
        maxLines = node.props.getInt("max_lines", 20).coerceAtLeast(node.props.getInt("min_lines", 6).coerceAtLeast(1)),
        syncMode = node.props.getString("sync_mode", "live"),
        debounceMilliseconds = node.props.getInt("debounce_ms", 300).coerceAtLeast(50),
        accessibilityLabel = node.props.getString("a11y_label").ifEmpty { node.props.getString("label") },
        accessibilityHint = node.props.getString("a11y_hint"),
        onChangeCallback = node.props.getCallbackId("on_change"),
        labels = CODE_EDITOR_LABEL_FALLBACKS.mapValues { (prop, fallback) ->
            node.props.getString(prop).ifEmpty { fallback }
        },
    )

    val editable: Boolean get() = !disabled && !readOnly

    fun label(prop: String): String = labels[prop] ?: CODE_EDITOR_LABEL_FALLBACKS[prop].orEmpty()
}

internal val CODE_EDITOR_LABEL_FALLBACKS = linkedMapOf(
    "done_label" to "Done",
    "indent_label" to "Indent",
    "outdent_label" to "Outdent",
    "undo_label" to "Undo",
    "redo_label" to "Redo",
    "toggle_comment_label" to "Toggle comment",
    "left_brace_label" to "Insert left brace",
    "right_brace_label" to "Insert right brace",
    "left_bracket_label" to "Insert left bracket",
    "right_bracket_label" to "Insert right bracket",
    "left_parenthesis_label" to "Insert left parenthesis",
    "right_parenthesis_label" to "Insert right parenthesis",
    "double_quote_label" to "Insert double quote",
    "single_quote_label" to "Insert single quote",
    "colon_label" to "Insert colon",
    "comma_label" to "Insert comma",
    "hyphen_label" to "Insert hyphen",
    "hash_label" to "Insert hash",
    "vertical_bar_label" to "Insert vertical bar",
    "equals_label" to "Insert equals sign",
    "dollar_sign_label" to "Insert dollar sign",
    "underscore_label" to "Insert underscore",
)

data class CodeEditorRendererEvent(val callbackId: Int, val nodeId: Int, val value: String) {
    val wireName = "TEXT_CHANGE"
}

class CodeEditorRendererEvents(private val send: (CodeEditorRendererEvent) -> Unit) {
    fun dispatch(event: CodeEditorRendererEvent) = send(event)

    companion object {
        val native = CodeEditorRendererEvents { event ->
            NativeUIBridge.sendTextChangeEvent(event.callbackId, event.nodeId, event.value)
        }
    }
}

/** First visible logical line, its pixel offset into that line, and whether the view is pinned to the end. */
data class CodeEditorViewport(val firstVisibleLine: Int, val offsetIntoLine: Int, val pinned: Boolean)

sealed interface CodeEditorScrollRequest {
    val generation: Int

    data class ToEnd(override val generation: Int) : CodeEditorScrollRequest
    data class ToLine(val line: Int, val offsetIntoLine: Int, override val generation: Int) : CodeEditorScrollRequest
}

@OptIn(ExperimentalFoundationApi::class)
class CodeEditorRendererState(node: NativeUINode) {
    var configuration by mutableStateOf(CodeEditorRendererConfiguration(node)); private set
    val text = TextFieldState(configuration.value)
    var lastCommitted = configuration.value; private set
    var pendingServerValue: String? = null; private set
    var focused = false; private set
    var scrollRequest by mutableStateOf<CodeEditorScrollRequest?>(
        if (configuration.follow) CodeEditorScrollRequest.ToEnd(0) else null,
    ); private set

    /** Supplied by the control once laid out; null before the first layout. */
    var viewport: () -> CodeEditorViewport? = { null }

    private var generation = 0

    val currentText: String get() = text.text.toString()

    fun textChanged(): CodeEditorRendererEvent? {
        if (!configuration.editable) return null
        return if (configuration.syncMode == "live") commitIfNeeded() else null
    }

    fun perform(operation: CodeEditorOperation): CodeEditorRendererEvent? {
        if (!configuration.editable) return null
        val before = currentText
        val selection = text.selection
        val result = CodeEditorEditing.apply(
            before,
            CodeEditorSelection(selection.min, selection.max),
            operation,
            configuration.language,
            configuration.indent,
        )
        if (result.text == before && result.selection == CodeEditorSelection(selection.min, selection.max)) return null
        text.edit {
            replaceChangedRange(before, result.text)
            this.selection = TextRange(result.selection.start, result.selection.end)
        }
        return textChanged()
    }

    fun undo(): CodeEditorRendererEvent? {
        if (!configuration.editable || !text.undoState.canUndo) return null
        text.undoState.undo()
        return textChanged()
    }

    fun redo(): CodeEditorRendererEvent? {
        if (!configuration.editable || !text.undoState.canRedo) return null
        text.undoState.redo()
        return textChanged()
    }

    fun focusChanged(value: Boolean): CodeEditorRendererEvent? {
        if (value) {
            focused = true
            return null
        }

        val hadUncommittedEdit = currentText != lastCommitted
        focused = false
        val event = commitIfNeeded()
        if (!hadUncommittedEdit) applyPendingServerValue()
        return event
    }

    /** A read-only editor applies held replacements once the person's selection collapses. */
    fun selectionChanged() {
        if (configuration.readOnly && text.selection.collapsed) applyPendingServerValue()
    }

    fun flush(): CodeEditorRendererEvent? = commitIfNeeded()

    fun serverPublished(tree: NativeUITree) {
        val published = tree.root.findCodeEditorNode(configuration.nodeId)
            ?.let(::CodeEditorRendererConfiguration)
            ?: return
        configuration = published
        val value = published.value

        when {
            focused && !published.readOnly ->
                pendingServerValue = value.takeUnless { it == lastCommitted }
            published.readOnly && !text.selection.collapsed ->
                pendingServerValue = value.takeUnless { it == currentText }
            else -> replaceWithServerValue(value)
        }
    }

    private fun applyPendingServerValue() {
        val pending = pendingServerValue ?: return
        replaceWithServerValue(pending)
    }

    private fun replaceWithServerValue(value: String) {
        pendingServerValue = null
        lastCommitted = value
        val before = currentText
        if (value == before) return

        val port = viewport()
        val cursor = text.selection.min.coerceAtMost(value.length)
        text.edit {
            replaceChangedRange(before, value)
            selection = TextRange(cursor)
        }
        text.undoState.clearHistory()
        scrollRequest = scrollRequestFor(before, value, port)
    }

    private fun scrollRequestFor(before: String, after: String, port: CodeEditorViewport?): CodeEditorScrollRequest? {
        generation += 1
        if (configuration.follow && (port == null || port.pinned)) return CodeEditorScrollRequest.ToEnd(generation)
        port ?: return null
        val line = CodeEditorScrollAnchor.anchorLine(before, after, port.firstVisibleLine) ?: return null
        return CodeEditorScrollRequest.ToLine(line, port.offsetIntoLine, generation)
    }

    private fun commitIfNeeded(): CodeEditorRendererEvent? {
        val current = currentText
        if (current == lastCommitted) return null
        lastCommitted = current
        return configuration.onChangeCallback.takeIf { it != 0 }?.let {
            CodeEditorRendererEvent(it, configuration.nodeId, current)
        }
    }
}

/** Routes typed Return and JSON closers through the shared engine so they land as one undoable edit. */
@OptIn(ExperimentalFoundationApi::class)
internal class CodeEditorInputTransformation(
    private val language: CodeEditorLanguage,
    private val indent: Int,
) : InputTransformation {
    override fun TextFieldBuffer.transformInput() {
        if (changes.changeCount != 1) return
        val range = changes.getRange(0)
        val original = changes.getOriginalRange(0)
        val inserted = asCharSequence().subSequence(range.min, range.max).toString()
        val operation = when {
            inserted == "\n" -> CodeEditorOperation.Return
            language == CodeEditorLanguage.Json && (inserted == "}" || inserted == "]") ->
                CodeEditorOperation.Insert(inserted)
            else -> return
        }
        if (original.min != originalSelection.min || original.max != originalSelection.max) return

        val result = CodeEditorEditing.apply(
            originalText.toString(),
            CodeEditorSelection(original.min, original.max),
            operation,
            language,
            indent,
        )
        replaceChangedRange(toString(), result.text)
        selection = TextRange(result.selection.start, result.selection.end)
    }
}

/** Replaces only the differing middle so large documents are not rewritten for small edits. */
internal fun TextFieldBuffer.replaceChangedRange(before: String, after: String) {
    var prefix = 0
    val limit = minOf(before.length, after.length)
    while (prefix < limit && before[prefix] == after[prefix]) prefix++
    var suffix = 0
    while (suffix < limit - prefix &&
        before[before.length - 1 - suffix] == after[after.length - 1 - suffix]
    ) {
        suffix++
    }
    replace(prefix, before.length - suffix, after.substring(prefix, after.length - suffix))
}

object CodeEditorRenderer {
    @Composable
    fun Render(
        node: NativeUINode,
        modifier: Modifier,
        events: CodeEditorRendererEvents = CodeEditorRendererEvents.native,
    ) {
        val validationFocus = firstlightValidationAlert(node.id)
        val state = remember(node.id) { CodeEditorRendererState(node) }

        NativeUIBridge.currentTree.value?.let { tree ->
            LaunchedEffect(tree) { state.serverPublished(tree) }
        }

        LaunchedEffect(state) {
            snapshotFlow { state.text.text }.collect { state.textChanged()?.let(events::dispatch) }
        }

        LaunchedEffect(state) {
            snapshotFlow { state.text.selection }.collect { state.selectionChanged() }
        }

        LaunchedEffect(state.text.text) {
            if (state.configuration.syncMode == "debounce") {
                delay(state.configuration.debounceMilliseconds.toLong())
                state.flush()?.let(events::dispatch)
            }
        }

        FirstlightCodeEditorControl(
            state = state,
            tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light,
            onOperation = { state.perform(it)?.let(events::dispatch) },
            onUndo = { state.undo()?.let(events::dispatch) },
            onRedo = { state.redo()?.let(events::dispatch) },
            onFocusChanged = { state.focusChanged(it)?.let(events::dispatch) },
            modifier = modifier.focusRequester(validationFocus),
        )
    }
}

private fun NativeUINode.findCodeEditorNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findCodeEditorNode(id)?.let { return it } }
    return null
}
