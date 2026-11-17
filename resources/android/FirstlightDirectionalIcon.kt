package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.runtime.Composable
import androidx.compose.ui.platform.LocalLayoutDirection
import androidx.compose.ui.unit.LayoutDirection

private val mirroredIconNames = listOf(
    "chevron_right" to "chevron_left",
    "arrow_forward" to "arrow_back",
    "arrow_forward_ios" to "arrow_back_ios",
    "navigate_next" to "navigate_before",
    "keyboard_arrow_right" to "keyboard_arrow_left",
    "arrow_right" to "arrow_left",
    // Mobile's aliases; `chevron.right` and `arrow.right` normalise to the names above.
    "forward" to "back",
).flatMap { (forward, back) -> listOf(forward to back, back to forward) }.toMap()

/**
 * Material's icon font draws the same glyph in both directions, unlike its
 * auto-mirrored vector icons, so a forward chevron kept pointing right in a
 * right-to-left layout. Directional names swap for their mirror there.
 */
internal fun firstlightDirectionalIconName(name: String, rightToLeft: Boolean): String {
    if (!rightToLeft) return name

    return mirroredIconNames[name.lowercase().replace('-', '_').replace('.', '_')] ?: name
}

@Composable
internal fun firstlightDirectionalIconName(name: String): String =
    firstlightDirectionalIconName(name, LocalLayoutDirection.current == LayoutDirection.Rtl)
