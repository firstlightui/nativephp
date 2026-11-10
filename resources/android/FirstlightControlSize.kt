package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.material3.MaterialTheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp

/**
 * The `size` prop shared by Segmented and Pill Group. `lg` matches Button
 * `size="lg"`, which Mobile UI draws at least 48 dp tall with 12 dp above and
 * below its label. Both controls already keep a 48 dp minimum for touch, so
 * `lg` keeps that height, uses a one-step larger title label, and pads it like
 * the button so the option grows with font scale as the button does.
 */
enum class FirstlightControlSize {
    Md,
    Lg;

    val labelStyle: TextStyle
        @Composable get() = when (this) {
            Md -> MaterialTheme.typography.labelLarge
            Lg -> MaterialTheme.typography.titleMedium
        }

    /** Label padding that, with the [inset] the control already applies, totals the button's 12 dp. */
    fun labelVerticalPadding(inset: Dp = 0.dp): Dp = when (this) {
        Md -> 0.dp
        Lg -> 12.dp - inset
    }

    companion object {
        fun fromWire(value: String): FirstlightControlSize = if (value == "lg") Lg else Md
    }
}
