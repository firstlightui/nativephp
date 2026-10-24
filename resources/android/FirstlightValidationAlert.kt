package dev.firstlightui.plugins.firstlight_ui.ui

import android.content.Context
import android.view.View
import android.view.accessibility.AccessibilityManager
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.remember
import androidx.compose.ui.focus.FocusRequester
import androidx.compose.ui.platform.LocalView
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode

/**
 * One-time screen-reader alert PHP attaches to the first error-bound element
 * of the frame published after a failed interaction.
 */
data class FirstlightValidationAlert(
    val revision: Int,
    val announcement: String,
    val movesFocus: Boolean,
) {
    constructor(node: NativeUINode) : this(
        revision = node.props.getInt("validation_alert", 0),
        announcement = node.props.getString("validation_announcement"),
        movesFocus = node.props.getBool("validation_focus"),
    )

    val isEmpty: Boolean
        get() = revision == 0 || (announcement.isEmpty() && !movesFocus)
}

/**
 * Acts on each revision once, including on the frame that creates the node,
 * and only while TalkBack (touch exploration) is running. Focus moves first so
 * TalkBack reads the field with its error; the announcement then carries every
 * message.
 */
class FirstlightValidationAlertRuntime(
    private val screenReaderRunning: () -> Boolean,
    private val requestFocus: () -> Boolean,
    private val announce: (String) -> Unit,
) {
    private var handledRevision = 0

    fun handle(alert: FirstlightValidationAlert?) {
        if (alert == null || alert.isEmpty || alert.revision == handledRevision) return
        handledRevision = alert.revision
        if (!screenReaderRunning()) return

        if (alert.movesFocus) requestFocus()
        if (alert.announcement.isNotEmpty()) announce(alert.announcement)
    }
}

/**
 * Runs the node's validation alert and returns the requester a text input
 * attaches with `Modifier.focusRequester`. Controls that do not attach it
 * announce without moving focus: Android moves input focus only to text input.
 */
@Composable
fun firstlightValidationAlert(nodeId: Int): FocusRequester {
    val view = LocalView.current
    val requester = remember(nodeId) { FocusRequester() }
    val runtime = remember(nodeId, view) {
        val accessibility = view.context.getSystemService(Context.ACCESSIBILITY_SERVICE) as AccessibilityManager
        FirstlightValidationAlertRuntime(
            screenReaderRunning = { accessibility.isEnabled && accessibility.isTouchExplorationEnabled },
            requestFocus = { runCatching { requester.requestFocus() }.isSuccess },
            announce = { view.firstlightAnnounce(it) },
        )
    }
    val alert = NativeUIBridge.currentTree.value?.root?.findValidationNode(nodeId)?.let(::FirstlightValidationAlert)

    LaunchedEffect(alert) { runtime.handle(alert) }

    return requester
}

// TalkBack still speaks View announcements; Compose has no node-scoped
// announcement API for an alert that is not attached to a live region.
@Suppress("DEPRECATION")
private fun View.firstlightAnnounce(text: String) = announceForAccessibility(text)

private fun NativeUINode.findValidationNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findValidationNode(id)?.let { return it } }
    return null
}
