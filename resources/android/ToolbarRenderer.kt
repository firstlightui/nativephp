package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.slideInVertically
import androidx.compose.animation.slideOutVertically
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material3.Badge
import androidx.compose.material3.BadgedBox
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.FloatingActionButton
import androidx.compose.material3.FloatingActionButtonDefaults
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.MenuDefaults
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateListOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.input.nestedscroll.NestedScrollConnection
import androidx.compose.ui.input.nestedscroll.NestedScrollSource
import androidx.compose.ui.input.nestedscroll.nestedScroll
import androidx.compose.ui.platform.LocalView
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.disabled
import androidx.compose.ui.semantics.isTraversalGroup
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.semantics.stateDescription
import androidx.compose.ui.unit.dp
import com.nativephp.mobile.ui.MaterialIcon
import com.nativephp.mobile.ui.nativerender.KeyboardFocusPolicy
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import org.json.JSONArray

data class ToolbarConfiguration(
    val nodeId: Int,
    val items: List<ToolbarItemConfiguration>,
    val primary: ToolbarItemConfiguration?,
) {
    constructor(node: NativeUINode) : this(
        nodeId = node.id,
        items = node.toolbarItems().filterNot { it.primary },
        primary = node.toolbarItems().firstOrNull { it.primary },
    )
}

private fun NativeUINode.toolbarItems(): List<ToolbarItemConfiguration> =
    children.filter { it.type == "firstlight.toolbar-item" }.map(::ToolbarItemConfiguration)

/**
 * Toolbars on screen, newest last. A screen leaving during a navigation
 * transition is still composed, so the newest registration wins and a
 * disposed one drops out.
 */
internal object FirstlightToolbarRegistry {
    val entries = mutableStateListOf<ToolbarConfiguration>()

    fun publish(configuration: ToolbarConfiguration) {
        val index = entries.indexOfFirst { it.nodeId == configuration.nodeId }
        if (index >= 0) entries[index] = configuration else entries.add(configuration)
    }

    fun remove(nodeId: Int) {
        entries.removeAll { it.nodeId == nodeId }
    }

    val current: ToolbarConfiguration? get() = entries.lastOrNull()
}

/** Draws nothing in place; the root host floats the toolbar over the screen. */
object ToolbarRenderer {
    @Composable
    fun Render(node: NativeUINode, modifier: Modifier) {
        var configuration by remember(node.id) { mutableStateOf(ToolbarConfiguration(node)) }
        val publishedTree = NativeUIBridge.currentTree.value
        LaunchedEffect(publishedTree) {
            publishedTree?.root?.findToolbarNode(node.id)?.let { configuration = ToolbarConfiguration(it) }
        }
        LaunchedEffect(configuration) { FirstlightToolbarRegistry.publish(configuration) }
        DisposableEffect(node.id) {
            onDispose { FirstlightToolbarRegistry.remove(node.id) }
        }
    }
}

/**
 * Root host: floats the current screen's toolbar at the bottom centre, above
 * the system navigation bar (and a tab bar), and hides it while the content
 * scrolls towards its end, as Material's exit-always scroll behaviour does.
 */
@Composable
fun FirstlightToolbarHost(root: NativeUINode, content: @Composable () -> Unit) {
    val configuration = FirstlightToolbarRegistry.current
    var shown by remember { mutableStateOf(true) }
    LaunchedEffect(configuration?.nodeId) { shown = true }

    val scroll = remember {
        object : NestedScrollConnection {
            override fun onPostScroll(consumed: Offset, available: Offset, source: NestedScrollSource): Offset {
                if (consumed.y < -1f) shown = false
                if (consumed.y > 1f || available.y > 1f) shown = true
                return Offset.Zero
            }
        }
    }

    val view = LocalView.current
    val haptics = LocalFirstlightHaptics.current
        ?: remember(view) { FirstlightHapticPlayer { FirstlightHaptics.perform(view, it) } }

    Box(Modifier.fillMaxSize().nestedScroll(scroll)) {
        content()

        if (configuration != null) {
            AnimatedVisibility(
                visible = shown,
                enter = slideInVertically { it } + fadeIn(),
                exit = slideOutVertically { it } + fadeOut(),
                modifier = Modifier
                    .align(Alignment.BottomCenter)
                    .navigationBarsPadding()
                    // Material's navigation bar is 80dp tall; a tab screen's
                    // toolbar floats above it rather than over the tabs. A
                    // screen that sets `$hidesTabBar` has no tabs to clear.
                    .padding(bottom = if (root.showsTabBar()) 96.dp else 16.dp),
            ) {
                FirstlightFloatingToolbar(configuration) { callbackId, item ->
                    if (callbackId == 0) return@FirstlightFloatingToolbar
                    item.haptic?.takeIf { it != FirstlightHaptic.None }?.let(haptics::play)
                    KeyboardFocusPolicy.dismissForInteractiveTap()
                    NativeUIBridge.sendPressEvent(callbackId, item.nodeId)
                }
            }
        }
    }
}

/** mobile-air draws a tab bar on a tabs root unless the screen hides it. */
internal fun NativeUINode.showsTabBar(): Boolean =
    type == "native_root_tabs" && !props.getBool("hide_tab_bar")

private fun NativeUINode.findToolbarNode(id: Int): NativeUINode? {
    if (this.id == id) return this
    children.forEach { child -> child.findToolbarNode(id)?.let { return it } }
    return null
}
