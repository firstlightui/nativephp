package dev.firstlightui.plugins.firstlight_ui

import android.content.Context
import androidx.compose.foundation.layout.ExperimentalLayoutApi
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.isImeVisible
import androidx.compose.ui.platform.LocalConfiguration
import com.nativephp.mobile.ui.nativerender.NativeRootHostRegistry
import com.nativephp.mobile.ui.nativerender.NativeTabPresentationRegistry
import dev.firstlightui.plugins.firstlight_ui.ui.FeedbackCenterRenderingPolicy
import dev.firstlightui.plugins.firstlight_ui.ui.FirstlightFeedbackCenterHost
import dev.firstlightui.plugins.firstlight_ui.ui.FirstlightTabBar
import dev.firstlightui.plugins.firstlight_ui.ui.FirstlightToolbarHost

@OptIn(ExperimentalLayoutApi::class)
@Suppress("UNUSED_PARAMETER")
fun registerFirstlightUI(context: Context) {
    // The generated NativePHP init call supplies the Context; this host does
    // not retain it because composition resolves the current Activity context.
    // Hosts apply innermost first, so Feedback Center snackbars draw above the toolbar.
    NativeRootHostRegistry.register("firstlight.toolbar") { root, content ->
        FirstlightToolbarHost(root = root, content = content)
    }
    NativeRootHostRegistry.register(
        "firstlight.feedback-center",
        consumes = "firstlight.feedback-center",
    ) { root, content ->
        val center = root.children.firstOrNull { it.type == "firstlight.feedback-center" }
        FirstlightFeedbackCenterHost(
            centerNode = center,
            tabBarHeight = FeedbackCenterRenderingPolicy.tabBarHeight(
                root,
                imeVisible = WindowInsets.isImeVisible,
                screenHeightDp = LocalConfiguration.current.screenHeightDp,
            ),
            content = content,
        )
    }
    NativeTabPresentationRegistry.register(FirstlightTabBar.IDENTIFIER) { tabs ->
        FirstlightTabBar.Render(tabs)
    }
}
