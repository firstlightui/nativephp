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

data class ToolbarMenuItem(
    val callbackId: Int,
    val label: String,
    val icon: String,
    val role: String,
    val disabled: Boolean,
) {
    val enabled: Boolean get() = !disabled && callbackId != 0
}

internal fun decodeToolbarMenu(json: String): List<ToolbarMenuItem> {
    if (json.isEmpty()) return emptyList()
    return runCatching {
        val array = JSONArray(json)
        (0 until array.length()).map { index ->
            val item = array.getJSONObject(index)
            ToolbarMenuItem(
                callbackId = item.getInt("cb"),
                label = item.getString("label"),
                icon = item.getString("icon"),
                role = item.getString("role"),
                disabled = item.getBoolean("disabled"),
            )
        }.filter { (it.callbackId != 0 || it.disabled) && it.label.isNotBlank() }
    }.getOrDefault(emptyList())
}

data class ToolbarItemConfiguration(
    val nodeId: Int,
    val icon: String,
    val accessibilityLabel: String,
    val primary: Boolean,
    val disabled: Boolean,
    val badge: String,
    val haptic: FirstlightHaptic?,
    val callbackId: Int,
    val menu: List<ToolbarMenuItem>,
) {
    constructor(node: NativeUINode) : this(
        nodeId = node.id,
        icon = node.props.getString("icon"),
        accessibilityLabel = node.props.getString("a11y_label"),
        primary = node.props.getBool("primary"),
        disabled = node.props.getBool("disabled"),
        badge = node.props.getString("badge"),
        haptic = FirstlightHaptic.fromWire(node.props.getString("haptic")),
        callbackId = node.onPress,
        menu = decodeToolbarMenu(node.props.getString("menu_json")),
    )

    val enabled: Boolean
        get() = !disabled && (if (menu.isEmpty()) callbackId != 0 else menu.any { it.enabled })
}

/** Never shown on its own: Toolbar renders its items. */
object ToolbarItemRenderer {
    @Composable
    @Suppress("UNUSED_PARAMETER")
    fun Render(node: NativeUINode, modifier: Modifier) = Unit
}
