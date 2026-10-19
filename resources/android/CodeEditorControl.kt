package dev.firstlightui.plugins.firstlight_ui.ui

import android.content.res.Configuration
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.ExperimentalFoundationApi
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.interaction.collectIsFocusedAsState
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ExperimentalLayoutApi
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.isImeVisible
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.sizeIn
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.text.input.TextFieldLineLimits
import androidx.compose.foundation.text.input.TextFieldDecorator
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.SideEffect
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.snapshotFlow
import androidx.compose.runtime.withFrameNanos
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clipToBounds
import androidx.compose.ui.focus.onFocusChanged
import androidx.compose.ui.graphics.SolidColor
import androidx.compose.ui.input.key.Key
import androidx.compose.ui.input.key.KeyEventType
import androidx.compose.ui.input.key.isShiftPressed
import androidx.compose.ui.input.key.key
import androidx.compose.ui.input.key.onPreviewKeyEvent
import androidx.compose.ui.input.key.type
import androidx.compose.ui.layout.Layout
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.platform.LocalLayoutDirection
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.error
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.text.TextLayoutResult
import androidx.compose.ui.text.drawText
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.input.KeyboardCapitalization
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.text.rememberTextMeasurer
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.Constraints
import androidx.compose.ui.unit.IntOffset
import androidx.compose.ui.unit.IntRect
import androidx.compose.ui.unit.IntSize
import androidx.compose.ui.unit.LayoutDirection
import androidx.compose.ui.unit.dp
import androidx.compose.ui.window.Popup
import androidx.compose.ui.window.PopupPositionProvider
import androidx.compose.ui.window.PopupProperties
import kotlinx.coroutines.flow.first
import com.nativephp.mobile.ui.MaterialIcon
import com.nativephp.plugins.native_ui.NativeUITokens

internal sealed interface CodeEditorKeyAction {
    data class Edit(val operation: CodeEditorOperation) : CodeEditorKeyAction
    data object Undo : CodeEditorKeyAction
    data object Redo : CodeEditorKeyAction
}

/** One key-row key. [icon] is a Material Symbols name; otherwise [text] is shown. */
internal data class CodeEditorKey(
    val labelProp: String,
    val action: CodeEditorKeyAction,
    val text: String? = null,
    val icon: String? = null,
)

private val SYMBOL_LABEL_PROPS = mapOf(
    "{" to "left_brace_label", "}" to "right_brace_label",
    "[" to "left_bracket_label", "]" to "right_bracket_label",
    "(" to "left_parenthesis_label", ")" to "right_parenthesis_label",
    "\"" to "double_quote_label", "'" to "single_quote_label",
    ":" to "colon_label", "," to "comma_label", "-" to "hyphen_label", "#" to "hash_label",
    "|" to "vertical_bar_label", "=" to "equals_label", "$" to "dollar_sign_label", "_" to "underscore_label",
)

internal fun codeEditorSymbols(language: CodeEditorLanguage): List<String> = when (language) {
    CodeEditorLanguage.Json -> listOf("{", "}", "[", "]", "\"", ":", ",")
    CodeEditorLanguage.Yaml -> listOf(":", "-", "#", "\"", "'", "|")
    CodeEditorLanguage.Env -> listOf("=", "#", "\"", "$", "{", "}", "_")
    CodeEditorLanguage.Plain -> listOf("(", ")", "[", "]", "{", "}", "\"", "'")
}

internal fun codeEditorKeys(language: CodeEditorLanguage): List<CodeEditorKey> = buildList {
    add(CodeEditorKey("indent_label", CodeEditorKeyAction.Edit(CodeEditorOperation.Indent), icon = "format_indent_increase"))
    add(CodeEditorKey("outdent_label", CodeEditorKeyAction.Edit(CodeEditorOperation.Outdent), icon = "format_indent_decrease"))
    add(CodeEditorKey("undo_label", CodeEditorKeyAction.Undo, icon = "undo"))
    add(CodeEditorKey("redo_label", CodeEditorKeyAction.Redo, icon = "redo"))
    codeEditorSymbols(language).forEach { symbol ->
        add(CodeEditorKey(SYMBOL_LABEL_PROPS.getValue(symbol), CodeEditorKeyAction.Edit(CodeEditorOperation.Insert(symbol)), text = symbol))
    }
    if (language.hasLineComments) {
        add(CodeEditorKey("toggle_comment_label", CodeEditorKeyAction.Edit(CodeEditorOperation.ToggleComment)))
    }
}

internal val codeEditorKeyboardOptions = KeyboardOptions(
    capitalization = KeyboardCapitalization.None,
    autoCorrectEnabled = false,
)

@OptIn(ExperimentalFoundationApi::class, ExperimentalLayoutApi::class)
@Composable
internal fun FirstlightCodeEditorControl(
    state: CodeEditorRendererState,
    tokens: NativeUITokens,
    onOperation: (CodeEditorOperation) -> Unit,
    onUndo: () -> Unit,
    onRedo: () -> Unit,
    onFocusChanged: (Boolean) -> Unit,
    modifier: Modifier = Modifier,
    keyRowVisible: Boolean? = null,
) {
    val configuration = state.configuration
    val interactionSource = remember { MutableInteractionSource() }
    val isFocused by interactionSource.collectIsFocusedAsState()
    val verticalScroll = rememberScrollState()
    val horizontalScroll = rememberScrollState()
    val layout = remember { mutableStateOf<(() -> TextLayoutResult?)?>(null) }
    val supporting = configuration.error.ifEmpty { configuration.helper }
    val description = listOf(configuration.accessibilityLabel, configuration.accessibilityHint)
        .filter(String::isNotEmpty)
        .joinToString(". ")
    val textStyle = MaterialTheme.typography.bodyLarge.copy(
        fontFamily = FontFamily.Monospace,
        color = MaterialTheme.colorScheme.onSurface.copy(alpha = if (configuration.disabled) 0.38f else 1f),
    )
    val colors = OutlinedTextFieldDefaults.colors()
    val gutterMeasurer = rememberTextMeasurer()
    val lineCount = remember(state.text.text) { codeEditorLineStarts(state.text.text).size }
    val gutterDigits = lineCount.toString().length.coerceAtLeast(2)
    val gutterWidth = remember(gutterDigits, textStyle) {
        gutterMeasurer.measure("9".repeat(gutterDigits), textStyle).size.width
    }
    val inputTransformation = remember(configuration.language, configuration.indent, configuration.editable) {
        if (configuration.editable) CodeEditorInputTransformation(configuration.language, configuration.indent) else null
    }

    SideEffect {
        state.viewport = { codeEditorViewport(layout.value?.invoke(), state.text.text, verticalScroll.value, verticalScroll.maxValue) }
    }

    LaunchedEffect(state.scrollRequest) {
        val request = state.scrollRequest ?: return@LaunchedEffect
        snapshotFlow { layout.value?.invoke()?.layoutInput?.text?.length }
            .first { length -> length == state.text.text.length }
        withFrameNanos { }
        val result = layout.value?.invoke() ?: return@LaunchedEffect
        when (request) {
            is CodeEditorScrollRequest.ToEnd -> verticalScroll.scrollTo(verticalScroll.maxValue)
            is CodeEditorScrollRequest.ToLine -> {
                val offset = codeEditorLineStartOffset(state.text.text, request.line)
                val top = result.getLineTop(result.getLineForOffset(offset)).toInt()
                verticalScroll.scrollTo(top + request.offsetIntoLine)
            }
        }
    }

    val decorator = TextFieldDecorator { innerTextField ->
        OutlinedTextFieldDefaults.DecorationBox(
            // DecorationBox only uses the value to decide whether the label floats.
            value = if (state.text.text.isEmpty()) "" else " ",
            innerTextField = {
                CompositionLocalProvider(LocalLayoutDirection provides LayoutDirection.Ltr) {
                    CodeEditorTextArea(
                        gutterWidth = if (configuration.lineNumbers) gutterWidth else 0,
                        horizontalScroll = horizontalScroll.takeUnless { configuration.wrap },
                        gutter = {
                            CodeEditorGutter(
                                layout = { layout.value?.invoke() },
                                text = state.text.text,
                                scroll = { verticalScroll.value },
                                style = textStyle.copy(
                                    color = tokens.onSurfaceVariant.copy(alpha = if (configuration.disabled) 0.38f else 1f),
                                    textAlign = TextAlign.End,
                                ),
                            )
                        },
                        text = innerTextField,
                    )
                }
            },
            enabled = !configuration.disabled,
            singleLine = false,
            visualTransformation = VisualTransformation.None,
            interactionSource = interactionSource,
            isError = configuration.error.isNotEmpty(),
            label = configuration.label.takeIf(String::isNotEmpty)?.let { label ->
                { Text(if (configuration.required) "$label *" else label) }
            },
            supportingText = supporting.takeIf(String::isNotEmpty)?.let { text ->
                {
                    Text(
                        text = text,
                        color = if (configuration.error.isEmpty()) tokens.onSurfaceVariant else tokens.destructive,
                    )
                }
            },
            colors = colors,
            container = {
                OutlinedTextFieldDefaults.Container(
                    enabled = !configuration.disabled,
                    isError = configuration.error.isNotEmpty(),
                    interactionSource = interactionSource,
                    colors = colors,
                )
            },
        )
    }

    androidx.compose.foundation.text.BasicTextField(
        state = state.text,
        modifier = modifier
            .fillMaxWidth()
            .onFocusChanged { onFocusChanged(it.isFocused) }
            .onPreviewKeyEvent { event ->
                if (configuration.editable && event.type == KeyEventType.KeyDown && event.key == Key.Tab) {
                    onOperation(if (event.isShiftPressed) CodeEditorOperation.Outdent else CodeEditorOperation.Indent)
                    true
                } else {
                    false
                }
            }
            .semantics {
                if (description.isNotEmpty()) contentDescription = description
                if (configuration.error.isNotEmpty()) error(configuration.error)
            },
        enabled = !configuration.disabled,
        readOnly = configuration.readOnly,
        inputTransformation = inputTransformation,
        textStyle = textStyle,
        keyboardOptions = codeEditorKeyboardOptions,
        lineLimits = TextFieldLineLimits.MultiLine(configuration.minLines, configuration.maxLines),
        onTextLayout = { getResult -> layout.value = getResult },
        interactionSource = interactionSource,
        cursorBrush = SolidColor(MaterialTheme.colorScheme.primary),
        decorator = decorator,
        scrollState = verticalScroll,
    )

    val deviceConfiguration = LocalConfiguration.current
    val hardwareKeyboard = deviceConfiguration.keyboard == Configuration.KEYBOARD_QWERTY &&
        deviceConfiguration.hardKeyboardHidden == Configuration.HARDKEYBOARDHIDDEN_NO
    val showKeyRow = keyRowVisible
        ?: (isFocused && configuration.editable && WindowInsets.isImeVisible && !hardwareKeyboard)
    if (showKeyRow) {
        if (keyRowVisible != null) {
            CodeEditorKeyRow(configuration, state, onOperation, onUndo, onRedo)
        } else {
            Popup(
                popupPositionProvider = CodeEditorKeyRowPosition,
                properties = PopupProperties(focusable = false),
            ) {
                CodeEditorKeyRow(configuration, state, onOperation, onUndo, onRedo)
            }
        }
    }
}

/** Places the key row at the bottom of the visible window frame, which ends at the keyboard. */
private object CodeEditorKeyRowPosition : PopupPositionProvider {
    override fun calculatePosition(
        anchorBounds: IntRect,
        windowSize: IntSize,
        layoutDirection: LayoutDirection,
        popupContentSize: IntSize,
    ) = IntOffset(0, windowSize.height - popupContentSize.height)
}

@OptIn(ExperimentalFoundationApi::class)
@Composable
internal fun CodeEditorKeyRow(
    configuration: CodeEditorRendererConfiguration,
    state: CodeEditorRendererState,
    onOperation: (CodeEditorOperation) -> Unit,
    onUndo: () -> Unit,
    onRedo: () -> Unit,
    modifier: Modifier = Modifier,
) {
    Surface(modifier = modifier.fillMaxWidth(), color = MaterialTheme.colorScheme.surfaceContainer, tonalElevation = 3.dp) {
        Column {
            HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)
            Row(Modifier.horizontalScroll(rememberScrollState()).padding(horizontal = 4.dp)) {
                codeEditorKeys(configuration.language).forEach { key ->
                    val label = configuration.label(key.labelProp)
                    val enabled = when (key.action) {
                        CodeEditorKeyAction.Undo -> state.text.undoState.canUndo
                        CodeEditorKeyAction.Redo -> state.text.undoState.canRedo
                        is CodeEditorKeyAction.Edit -> true
                    }
                    val onClick = {
                        when (val action = key.action) {
                            is CodeEditorKeyAction.Edit -> onOperation(action.operation)
                            CodeEditorKeyAction.Undo -> onUndo()
                            CodeEditorKeyAction.Redo -> onRedo()
                        }
                    }
                    val keyModifier = Modifier
                        .sizeIn(minWidth = 48.dp, minHeight = 48.dp)
                        .semantics { contentDescription = label }
                    if (key.icon != null) {
                        IconButton(onClick = onClick, enabled = enabled, modifier = keyModifier) {
                            Box(Modifier.clearAndSetSemantics { }) { MaterialIcon(key.icon, null) }
                        }
                    } else {
                        TextButton(
                            onClick = onClick,
                            enabled = enabled,
                            modifier = keyModifier,
                            contentPadding = PaddingValues(horizontal = 12.dp),
                        ) {
                            Text(
                                text = key.text ?: label,
                                modifier = Modifier.clearAndSetSemantics { },
                                style = if (key.text != null) {
                                    MaterialTheme.typography.titleMedium.copy(fontFamily = FontFamily.Monospace)
                                } else {
                                    MaterialTheme.typography.labelLarge
                                },
                            )
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun CodeEditorTextArea(
    gutterWidth: Int,
    horizontalScroll: androidx.compose.foundation.ScrollState?,
    gutter: @Composable () -> Unit,
    text: @Composable () -> Unit,
) {
    val gutterSpacing = with(LocalDensity.current) { 12.dp.roundToPx() }
    Layout(
        content = {
            Box { if (gutterWidth > 0) gutter() }
            Box(if (horizontalScroll != null) Modifier.horizontalScroll(horizontalScroll) else Modifier) { text() }
        },
    ) { measurables, constraints ->
        val reserved = if (gutterWidth > 0) gutterWidth + gutterSpacing else 0
        val textConstraints = constraints.copy(
            minWidth = (constraints.minWidth - reserved).coerceAtLeast(0),
            maxWidth = (constraints.maxWidth - reserved).coerceAtLeast(0),
        )
        val textPlaceable = measurables[1].measure(textConstraints)
        val gutterPlaceable = measurables[0].measure(Constraints.fixed(gutterWidth, textPlaceable.height))
        layout(reserved + textPlaceable.width, textPlaceable.height) {
            gutterPlaceable.place(0, 0)
            textPlaceable.place(reserved, 0)
        }
    }
}

@Composable
private fun CodeEditorGutter(
    layout: () -> TextLayoutResult?,
    text: CharSequence,
    scroll: () -> Int,
    style: androidx.compose.ui.text.TextStyle,
) {
    val measurer = rememberTextMeasurer()
    val lineStarts = remember(text) { codeEditorLineStarts(text) }

    // Layout and scroll are read while drawing so the gutter follows them without recomposing.
    // Partly visible first and last lines would otherwise draw over the field border.
    Canvas(Modifier.fillMaxSize().clipToBounds().clearAndSetSemantics { }) {
        val layout = layout() ?: return@Canvas
        val scroll = scroll()
        if (layout.lineCount == 0 || layout.layoutInput.text.length != text.length) return@Canvas
        val firstVisual = layout.getLineForVerticalPosition(scroll.toFloat())
        val lastVisual = layout.getLineForVerticalPosition(scroll + size.height)
        for (visual in firstVisual..lastVisual) {
            val start = layout.getLineStart(visual)
            if (start != 0 && text[start - 1] != '\n') continue
            val index = lineStarts.binarySearch(start)
            if (index < 0) continue
            val measured = measurer.measure((index + 1).toString(), style)
            drawText(
                textLayoutResult = measured,
                topLeft = androidx.compose.ui.geometry.Offset(
                    size.width - measured.size.width,
                    layout.getLineTop(visual) - scroll,
                ),
            )
        }
    }
}

internal fun codeEditorLineStarts(text: CharSequence): IntArray {
    val starts = mutableListOf(0)
    for (index in text.indices) if (text[index] == '\n') starts += index + 1
    return starts.toIntArray()
}

internal fun codeEditorLineStartOffset(text: CharSequence, line: Int): Int {
    var remaining = line
    if (remaining <= 0) return 0
    for (index in text.indices) {
        if (text[index] == '\n') {
            remaining -= 1
            if (remaining == 0) return index + 1
        }
    }
    return text.length
}

internal fun codeEditorViewport(
    layout: TextLayoutResult?,
    text: CharSequence,
    scroll: Int,
    maxScroll: Int,
): CodeEditorViewport? {
    layout ?: return null
    if (layout.layoutInput.text.length != text.length || layout.lineCount == 0) return null
    val visual = layout.getLineForVerticalPosition(scroll.toFloat())
    val visualStart = layout.getLineStart(visual)
    val logicalStart = if (visualStart == 0) 0 else text.lastIndexOf('\n', visualStart - 1) + 1
    var logicalLine = 0
    for (index in 0 until logicalStart) if (text[index] == '\n') logicalLine += 1
    val lineTop = layout.getLineTop(layout.getLineForOffset(logicalStart)).toInt()
    val lineHeight = (layout.getLineBottom(0) - layout.getLineTop(0)).toInt()
    return CodeEditorViewport(
        firstVisibleLine = logicalLine,
        offsetIntoLine = scroll - lineTop,
        pinned = maxScroll - scroll <= lineHeight,
    )
}
