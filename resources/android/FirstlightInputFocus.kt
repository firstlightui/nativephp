package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.rememberUpdatedState
import com.nativephp.mobile.ui.nativerender.KeyboardFocusPolicy

/** Register the editor's current buffer with Mobile's synchronous flush-before-press policy. */
@Composable
internal fun firstlightInputFocus(
    owner: Any,
    flush: () -> Unit,
    focusChanged: (Boolean) -> Unit,
): (Boolean) -> Unit {
    val currentFlush by rememberUpdatedState(flush)
    DisposableEffect(owner) {
        onDispose { KeyboardFocusPolicy.fieldBlurred(owner) }
    }

    return { focused ->
        if (focused) {
            KeyboardFocusPolicy.fieldFocused(owner, keepsFocus = false) { currentFlush() }
        } else {
            KeyboardFocusPolicy.fieldBlurred(owner)
        }
        focusChanged(focused)
    }
}
