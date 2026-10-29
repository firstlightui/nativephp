package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.disabled
import androidx.compose.ui.semantics.onClick
import androidx.compose.ui.semantics.role
import androidx.compose.ui.semantics.stateDescription
import com.nativephp.mobile.ui.nativerender.KeyboardFocusPolicy
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode

/**
 * Android renderer for `firstlight.button`. Drawing and touch stay with Mobile
 * UI's Material 3 renderer. NativePHP chains a role-less clickable onto the
 * modifier of every node with `@press`, so TalkBack focused a plain View.
 * Replacing the semantics on that modifier, as List Item does, exposes one
 * named Button whose accessibility click sends the press once.
 */
object FirstlightButtonRenderer {
    @Composable
    fun Render(node: NativeUINode, modifier: Modifier) {
        val enabled = !node.props.getBool("disabled") && !node.props.getBool("loading")
        val callbackId = node.props.getCallbackId("on_press").let { if (it != 0) it else node.onPress }

        com.nativephp.plugins.native_ui.ui.ButtonRenderer.Render(
            node,
            modifier.clearAndSetSemantics {
                role = Role.Button
                contentDescription = firstlightButtonDescription(node)
                if (node.props.getBool("loading")) stateDescription = "Loading"
                if (!enabled) {
                    disabled()
                } else {
                    onClick {
                        if (callbackId != 0) {
                            KeyboardFocusPolicy.dismissForInteractiveTap()
                            NativeUIBridge.sendPressEvent(callbackId, node.id)
                        }
                        callbackId != 0
                    }
                }
            },
        )
    }
}

internal fun firstlightButtonDescription(node: NativeUINode): String {
    val name = node.props.getString("a11y_label").ifEmpty { node.props.getString("label") }

    return listOf(name, node.props.getString("a11y_hint")).filter { it.isNotEmpty() }.joinToString(". ")
}
