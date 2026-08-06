package dev.firstlightui.plugins.firstlight_ui

import android.content.Context
import com.nativephp.mobile.ui.nativerender.NativeRootHostRegistry
import dev.firstlightui.plugins.firstlight_ui.ui.FirstlightFeedbackCenterHost

@Suppress("UNUSED_PARAMETER")
fun registerFirstlightUI(context: Context) {
    // The generated NativePHP init call supplies the Context; this host does
    // not retain it because composition resolves the current Activity context.
    NativeRootHostRegistry.register(
        "firstlight.feedback-center",
        consumes = "firstlight.feedback-center",
    ) { root, content ->
        val center = root.children.firstOrNull { it.type == "firstlight.feedback-center" }
        FirstlightFeedbackCenterHost(centerNode = center, content = content)
    }
}
