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

data class StatusLabelRendererConfiguration(
    val label: String,
    val tone: StatusLabelTone,
    val accessibilityLabel: String,
    val accessibilityHint: String,
) {
    constructor(node: NativeUINode) : this(
        label = node.props.getString("label"),
        tone = StatusLabelTone.fromWire(node.props.getString("tone", "neutral")),
        accessibilityLabel = node.props.getString("a11y_label").ifEmpty {
            node.props.getString("label")
        },
        accessibilityHint = node.props.getString("a11y_hint"),
    )

    val isInteractive = false
}

class StatusLabelRendererState(node: NativeUINode) {
    private val nodeId = node.id

    var configuration by mutableStateOf(StatusLabelRendererConfiguration(node))
        private set

    fun serverPublished(tree: NativeUITree): Boolean {
        val node = tree.root.findStatusLabelNode(nodeId) ?: return false
        val published = StatusLabelRendererConfiguration(node)
        if (published == configuration) return false

        configuration = published
        return true
    }
}

object StatusLabelRenderer {
    @Composable
    fun Render(node: NativeUINode, modifier: Modifier) {
        val state = remember(node.id) { StatusLabelRendererState(node) }
        val publishedTree = NativeUIBridge.currentTree.value
        LaunchedEffect(publishedTree) {
            publishedTree?.let(state::serverPublished)
        }

        val tokens = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light
        val configuration = state.configuration

        FirstlightStatusLabelControl(
            label = configuration.label,
            colors = resolveStatusLabelTokenColors(tokens, configuration.tone),
            accessibilityDescription = firstlightStatusLabelDescription(
                label = configuration.label,
                accessibilityLabel = configuration.accessibilityLabel,
                accessibilityHint = configuration.accessibilityHint,
            ),
            modifier = modifier,
        )
    }
}

private fun NativeUINode.findStatusLabelNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findStatusLabelNode(id)?.let { return it } }
    return null
}
