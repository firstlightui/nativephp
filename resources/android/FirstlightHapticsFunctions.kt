package dev.firstlightui.plugins.firstlight_ui

import android.app.Activity
import android.os.Handler
import android.os.Looper
import com.nativephp.mobile.bridge.BridgeError
import com.nativephp.mobile.bridge.BridgeFunction
import dev.firstlightui.plugins.firstlight_ui.ui.FirstlightHaptic
import dev.firstlightui.plugins.firstlight_ui.ui.FirstlightHaptics

/**
 * `Firstlight.Haptics.*` bridge functions, called by FirstlightUI\Facades\Haptics.
 * Controls play their own touch haptics; these play what PHP decides.
 */
object FirstlightHapticsFunctions {
    /** `{"haptic": "success"}` or `{"impact": "medium", "intensity": 0.7}`. */
    class Play(private val activity: Activity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val impact = parameters["impact"] as? String
            if (impact != null) {
                if (impact !in FirstlightHaptics.impactWeights) {
                    throw BridgeError.InvalidParameters("unknown impact weight '$impact'")
                }
                val intensity = (parameters["intensity"] as? Number)?.toFloat() ?: 1f
                Handler(Looper.getMainLooper()).post {
                    FirstlightHaptics.impact(activity, impact, intensity)
                }
                return emptyMap()
            }

            val name = parameters["haptic"] as? String
            val haptic = name?.let(FirstlightHaptic::fromWire)
                ?: throw BridgeError.InvalidParameters("unknown haptic '${name.orEmpty()}'")
            Handler(Looper.getMainLooper()).post {
                FirstlightHaptics.perform(activity.window.decorView, haptic)
            }
            return emptyMap()
        }
    }

    /** `{"enabled": false}` turns every Firstlight haptic off for this install. */
    class SetEnabled(private val activity: Activity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val enabled = parameters["enabled"] as? Boolean
                ?: throw BridgeError.InvalidParameters("enabled must be a boolean")
            FirstlightHaptics.setEnabled(activity, enabled)
            return emptyMap()
        }
    }
}
