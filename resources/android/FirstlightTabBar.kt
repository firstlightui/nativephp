package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.ExperimentalLayoutApi
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.isImeVisible
import androidx.compose.material3.Badge
import androidx.compose.material3.BadgedBox
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.NavigationItemIconPosition
import androidx.compose.material3.ShortNavigationBar
import androidx.compose.material3.ShortNavigationBarItem
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import com.nativephp.mobile.ui.MaterialIcon
import com.nativephp.mobile.ui.nativerender.NativeTabDescriptor
import com.nativephp.mobile.ui.nativerender.NativeTabPresentationContext

/**
 * Android tab bar selected with `TabBar::presentation('firstlight:tabs')`. NativePHP still owns the
 * tabs, selection and navigation; this only draws the bar, the way Material's guidance asks:
 * hidden while the keyboard is up, so it never covers the field being edited, and a short bar with
 * labels beside the icons when the window is short, such as a phone in landscape. iOS keeps the
 * system tab bar, which already does both.
 */
object FirstlightTabBar {
    const val IDENTIFIER = "firstlight:tabs"

    /** Shorter than this, the 80 dp bar and an app bar leave too little room for content. */
    const val CompactHeightDp = 480

    enum class Form(val height: Dp) { Hidden(0.dp), Short(64.dp), Standard(80.dp) }

    fun form(hidden: Boolean, imeVisible: Boolean, screenHeightDp: Int): Form = when {
        hidden || imeVisible -> Form.Hidden
        screenHeightDp < CompactHeightDp -> Form.Short
        else -> Form.Standard
    }

    @OptIn(ExperimentalLayoutApi::class)
    @Composable
    fun currentForm(hidden: Boolean): Form =
        form(hidden, WindowInsets.isImeVisible, LocalConfiguration.current.screenHeightDp)

    @Composable
    fun Render(context: NativeTabPresentationContext) {
        when (currentForm(context.isHidden)) {
            Form.Hidden -> Unit
            Form.Short -> ShortNavigationBar {
                context.tabs.forEach { tab ->
                    ShortNavigationBarItem(
                        selected = tab.id == context.selectedTabIdentifier,
                        onClick = { context.activate(tab.id) },
                        icon = { TabIcon(tab) },
                        label = { Text(tab.label) },
                        iconPosition = NavigationItemIconPosition.Start,
                    )
                }
            }
            Form.Standard -> NavigationBar {
                context.tabs.forEach { tab ->
                    NavigationBarItem(
                        selected = tab.id == context.selectedTabIdentifier,
                        onClick = { context.activate(tab.id) },
                        icon = { TabIcon(tab) },
                        label = { Text(tab.label) },
                    )
                }
            }
        }
    }

    @Composable
    private fun TabIcon(tab: NativeTabDescriptor) {
        if (tab.badge != null || tab.showsNews) {
            BadgedBox(badge = { Badge { tab.badge?.let { Text(it) } } }) {
                MaterialIcon(name = tab.icon, contentDescription = tab.accessibilityLabel)
            }
        } else {
            MaterialIcon(name = tab.icon, contentDescription = tab.accessibilityLabel)
        }
    }
}
