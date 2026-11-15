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

@Composable
internal fun ToolbarIconAction(item: ToolbarItemConfiguration, onPress: (Int, ToolbarItemConfiguration) -> Unit) {
    var expanded by remember { mutableStateOf(false) }

    Box {
        IconButton(
            onClick = { if (item.menu.isEmpty()) onPress(item.callbackId, item) else expanded = true },
            enabled = item.enabled,
            modifier = Modifier.semantics {
                if (item.badge.isNotEmpty()) stateDescription = item.badge
            },
        ) {
            ToolbarIcon(item)
        }

        ToolbarMenu(item, expanded, onDismiss = { expanded = false }, onPress = onPress)
    }
}

@Composable
internal fun ToolbarPrimaryAction(item: ToolbarItemConfiguration, onPress: (Int, ToolbarItemConfiguration) -> Unit) {
    var expanded by remember { mutableStateOf(false) }

    Box {
        FloatingActionButton(
            onClick = {
                if (!item.enabled) return@FloatingActionButton
                if (item.menu.isEmpty()) onPress(item.callbackId, item) else expanded = true
            },
            shape = MaterialTheme.shapes.large,
            // The theme's primary token, which Native UI maps, rather than
            // Material's secondaryContainer, which apps never theme: that
            // drew Material 3's baseline lavender in every app.
            containerColor = MaterialTheme.colorScheme.primary,
            contentColor = MaterialTheme.colorScheme.onPrimary,
            elevation = FloatingActionButtonDefaults.elevation(),
            modifier = Modifier
                .alpha(if (item.enabled) 1f else 0.38f)
                .semantics {
                    if (!item.enabled) disabled()
                    if (item.badge.isNotEmpty()) stateDescription = item.badge
                },
        ) {
            ToolbarIcon(item)
        }

        ToolbarMenu(item, expanded, onDismiss = { expanded = false }, onPress = onPress)
    }
}

/** The item's menu; the destructive role only styles an entry, PHP owns any confirmation. */
@Composable
private fun ToolbarMenu(
    item: ToolbarItemConfiguration,
    expanded: Boolean,
    onDismiss: () -> Unit,
    onPress: (Int, ToolbarItemConfiguration) -> Unit,
) {
    DropdownMenu(expanded = expanded, onDismissRequest = onDismiss) {
        item.menu.forEach { entry ->
            val destructive = entry.role == "destructive"
            DropdownMenuItem(
                text = { Text(entry.label) },
                leadingIcon = entry.icon.takeIf(String::isNotEmpty)?.let { { MaterialIcon(it, contentDescription = null) } },
                enabled = entry.enabled,
                colors = if (destructive) {
                    MenuDefaults.itemColors(
                        textColor = MaterialTheme.colorScheme.error,
                        leadingIconColor = MaterialTheme.colorScheme.error,
                    )
                } else {
                    MenuDefaults.itemColors()
                },
                onClick = {
                    onDismiss()
                    onPress(entry.callbackId, item)
                },
            )
        }
    }
}

@Composable
private fun ToolbarIcon(item: ToolbarItemConfiguration) {
    BadgedBox(
        badge = {
            if (item.badge.isNotEmpty()) {
                Badge(modifier = Modifier.clearAndSetSemantics { }) { Text(item.badge) }
            }
        },
    ) {
        MaterialIcon(
            name = item.icon,
            contentDescription = item.accessibilityLabel,
            modifier = Modifier.size(24.dp),
        )
    }
}
