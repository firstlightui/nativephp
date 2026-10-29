package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.RenderNode
import com.nativephp.plugins.native_ui.NativeUITheme

data class BottomSheetRendererConfiguration(
    val nodeId: Int,
    val visible: Boolean,
    val accessibilityLabel: String,
    val dismissCallback: Int,
    val pinnedFooter: Boolean = false,
) {
    constructor(node: NativeUINode) : this(
        nodeId = node.id,
        visible = node.props.getBool("visible"),
        accessibilityLabel = node.props.getString("a11y_label"),
        dismissCallback = node.props.getCallbackId("on_dismiss"),
        pinnedFooter = node.props.getBool("pinned_footer"),
    )
}

/**
 * Android expression of `firstlight.bottom-sheet`, paired with the iOS
 * `BottomSheetRenderer`. Mobile UI's renderer is not used because Firstlight
 * publishes no detents, and its `medium,large` fallback opens tall content at
 * the half-height stop with the rest off-screen.
 */
object FirstlightBottomSheetRenderer {
    @Composable
    fun Render(node: NativeUINode, modifier: Modifier) {
        val configuration = BottomSheetRendererConfiguration(node)

        FirstlightBottomSheetControl(
            configuration = configuration,
            tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light,
            onDismiss = {
                if (configuration.dismissCallback != 0) {
                    NativeUIBridge.sendSheetDismissEvent(configuration.dismissCallback, configuration.nodeId)
                }
            },
            modifier = modifier,
            footer = {
                if (configuration.pinnedFooter) node.children.lastOrNull()?.let { RenderNode(it) }
            },
        ) {
            val body = if (configuration.pinnedFooter) node.children.dropLast(1) else node.children
            body.forEach { RenderNode(it) }
        }
    }
}
