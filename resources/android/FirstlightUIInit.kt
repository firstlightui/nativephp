package dev.firstlightui.plugins.firstlight_ui

import android.content.Context
import com.nativephp.mobile.ui.nativerender.NativeRootHostRegistry
import dev.firstlightui.plugins.firstlight_ui.ui.FirstlightFeedbackCenterHost
import dev.firstlightui.plugins.firstlight_ui.ui.FirstlightToolbarHost

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
        FirstlightFeedbackCenterHost(centerNode = center, content = content)
    }
}
