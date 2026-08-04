package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import com.nativephp.plugins.native_ui.NativeUITheme

data class ActivityIndicatorRendererConfiguration(
    val size: ActivityIndicatorSize,
    val accessibilityLabel: String,
) {
    constructor(node: NativeUINode) : this(
        size = ActivityIndicatorSize.fromWire(node.props.getString("size", "md")),
        accessibilityLabel = node.props.getString("a11y_label"),
    )

    val isInteractive = false
}

class ActivityIndicatorRendererState(node: NativeUINode) {
    private val nodeId = node.id

    var configuration by mutableStateOf(ActivityIndicatorRendererConfiguration(node))
        private set

    fun serverPublished(tree: NativeUITree): Boolean {
        val node = tree.root.findActivityIndicatorNode(nodeId) ?: return false
        val published = ActivityIndicatorRendererConfiguration(node)
        if (published == configuration) return false

        configuration = published
        return true
    }
}

object FirstlightActivityIndicatorRenderer {
    @Composable
    fun Render(node: NativeUINode, modifier: Modifier) {
        val state = remember(node.id) { ActivityIndicatorRendererState(node) }
        val publishedTree = NativeUIBridge.currentTree.value
        LaunchedEffect(publishedTree) {
            publishedTree?.let(state::serverPublished)
        }

        val tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light
        val configuration = state.configuration

        FirstlightActivityIndicatorControl(
            size = configuration.size,
            accessibilityLabel = configuration.accessibilityLabel,
            color = tokens.primary,
            modifier = modifier,
        )
    }
}

private fun NativeUINode.findActivityIndicatorNode(id: Int): NativeUINode? {
    if (this.id == id) return this

    children.forEach { child ->
        child.findActivityIndicatorNode(id)?.let { return it }
    }

    return null
}
