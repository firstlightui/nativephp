package dev.firstlightui.plugins.firstlight_ui.ui

import android.content.Context
import android.media.AudioAttributes
import android.os.Build
import android.os.VibrationAttributes
import android.os.VibrationEffect
import android.os.Vibrator
import android.os.VibratorManager
import android.provider.Settings
import android.view.HapticFeedbackConstants
import android.view.View
import androidx.compose.foundation.gestures.awaitEachGesture
import androidx.compose.foundation.gestures.awaitFirstDown
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.runtime.staticCompositionLocalOf
import androidx.compose.ui.Modifier
import androidx.compose.ui.input.pointer.PointerEventPass
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.platform.LocalView
import com.nativephp.mobile.ui.nativerender.NativeUINode

/**
 * Firstlight's haptic vocabulary (spec/reference/haptics.md). Each meaning
 * plays Android's action-oriented [HapticFeedbackConstants] constant, which
 * honours the user's touch feedback setting and needs no permission.
 */
enum class FirstlightHaptic(val wire: String) {
    Success("success"),
    Warning("warning"),
    Error("error"),
    Selection("selection"),
    Tick("tick"),
    ToggleOn("toggle-on"),
    ToggleOff("toggle-off"),
    Press("press"),
    Keypress("keypress"),
    LongPress("long-press"),
    ThresholdActivate("threshold-activate"),
    ThresholdDeactivate("threshold-deactivate"),
    DragStart("drag-start"),
    Alignment("alignment"),
    Increase("increase"),
    Decrease("decrease"),
    LevelChange("level-change"),
    Start("start"),
    Stop("stop"),
    PathComplete("path-complete"),
    None("none");

    companion object {
        fun fromWire(value: String): FirstlightHaptic? = entries.firstOrNull { it.wire == value }
    }
}

object FirstlightHaptics {
    private const val PREFERENCES = "firstlight_haptics"
    private const val ENABLED = "enabled"

    val impactWeights = listOf("light", "medium", "heavy", "soft", "rigid")

    /**
     * The constant [haptic] plays on [sdk], or null for none. Constants newer
     * than [sdk] fall back as androidx's HapticFeedbackConstantsCompat does,
     * so Android 10 still gets a clear click rather than nothing.
     */
    fun feedbackConstant(haptic: FirstlightHaptic, sdk: Int = Build.VERSION.SDK_INT): Int? {
        val modern = sdk >= 34
        val r = sdk >= 30
        return when (haptic) {
            FirstlightHaptic.Success, FirstlightHaptic.PathComplete ->
                if (r) HapticFeedbackConstants.CONFIRM else HapticFeedbackConstants.VIRTUAL_KEY
            // Android has no warning constant; a warning is a rejection of what was asked.
            FirstlightHaptic.Warning, FirstlightHaptic.Error ->
                if (r) HapticFeedbackConstants.REJECT else HapticFeedbackConstants.LONG_PRESS
            FirstlightHaptic.Selection, FirstlightHaptic.Alignment, FirstlightHaptic.Increase,
            FirstlightHaptic.Decrease, FirstlightHaptic.LevelChange ->
                if (modern) HapticFeedbackConstants.SEGMENT_TICK else HapticFeedbackConstants.CONTEXT_CLICK
            FirstlightHaptic.Tick ->
                if (modern) HapticFeedbackConstants.SEGMENT_FREQUENT_TICK else HapticFeedbackConstants.CLOCK_TICK
            FirstlightHaptic.ToggleOn ->
                if (modern) HapticFeedbackConstants.TOGGLE_ON else HapticFeedbackConstants.CONTEXT_CLICK
            FirstlightHaptic.ToggleOff ->
                if (modern) HapticFeedbackConstants.TOGGLE_OFF else HapticFeedbackConstants.CLOCK_TICK
            FirstlightHaptic.Press -> HapticFeedbackConstants.VIRTUAL_KEY
            FirstlightHaptic.Keypress -> HapticFeedbackConstants.KEYBOARD_TAP
            FirstlightHaptic.LongPress -> HapticFeedbackConstants.LONG_PRESS
            FirstlightHaptic.ThresholdActivate ->
                if (modern) HapticFeedbackConstants.GESTURE_THRESHOLD_ACTIVATE else HapticFeedbackConstants.CONTEXT_CLICK
            FirstlightHaptic.ThresholdDeactivate ->
                if (modern) HapticFeedbackConstants.GESTURE_THRESHOLD_DEACTIVATE else HapticFeedbackConstants.CLOCK_TICK
            FirstlightHaptic.DragStart ->
                if (modern) HapticFeedbackConstants.DRAG_START else HapticFeedbackConstants.LONG_PRESS
            FirstlightHaptic.Start ->
                if (r) HapticFeedbackConstants.GESTURE_START else HapticFeedbackConstants.VIRTUAL_KEY
            FirstlightHaptic.Stop ->
                if (r) HapticFeedbackConstants.GESTURE_END else HapticFeedbackConstants.CONTEXT_CLICK
            FirstlightHaptic.None -> null
        }
    }

    /** The app's own switch, set from PHP with Haptics::setEnabled(). */
    fun isEnabled(context: Context): Boolean =
        context.getSharedPreferences(PREFERENCES, Context.MODE_PRIVATE).getBoolean(ENABLED, true)

    fun setEnabled(context: Context, enabled: Boolean) {
        context.getSharedPreferences(PREFERENCES, Context.MODE_PRIVATE).edit().putBoolean(ENABLED, enabled).apply()
    }

    fun perform(view: View, haptic: FirstlightHaptic): Boolean {
        val constant = feedbackConstant(haptic) ?: return false
        if (!isEnabled(view.context)) return false
        return view.performHapticFeedback(constant)
    }

    /**
     * A physical impact from the vibrator's own primitives, scaled by
     * [intensity]. Touch usage keeps it under the touch feedback setting.
     */
    fun impact(context: Context, weight: String, intensity: Float): Boolean {
        if (!isEnabled(context) || intensity <= 0f) return false
        if (Build.VERSION.SDK_INT < 33 &&
            Settings.System.getInt(context.contentResolver, Settings.System.HAPTIC_FEEDBACK_ENABLED, 1) == 0
        ) {
            return false
        }

        val vibrator = vibrator(context)?.takeIf(Vibrator::hasVibrator) ?: return false
        val effect = primitiveImpact(vibrator, weight, intensity.coerceAtMost(1f))
            ?: VibrationEffect.createPredefined(
                when (weight) {
                    "light", "soft" -> VibrationEffect.EFFECT_TICK
                    "heavy" -> VibrationEffect.EFFECT_HEAVY_CLICK
                    else -> VibrationEffect.EFFECT_CLICK
                },
            )

        if (Build.VERSION.SDK_INT >= 33) {
            vibrator.vibrate(effect, VibrationAttributes.createForUsage(VibrationAttributes.USAGE_TOUCH))
        } else {
            @Suppress("DEPRECATION")
            vibrator.vibrate(
                effect,
                AudioAttributes.Builder()
                    .setUsage(AudioAttributes.USAGE_ASSISTANCE_SONIFICATION)
                    .setContentType(AudioAttributes.CONTENT_TYPE_SONIFICATION)
                    .build(),
            )
        }
        return true
    }

    private fun primitiveImpact(vibrator: Vibrator, weight: String, intensity: Float): VibrationEffect? {
        if (Build.VERSION.SDK_INT < 30) return null
        val primitive = when (weight) {
            "light" -> VibrationEffect.Composition.PRIMITIVE_TICK
            "soft" -> if (Build.VERSION.SDK_INT >= 31) VibrationEffect.Composition.PRIMITIVE_LOW_TICK else VibrationEffect.Composition.PRIMITIVE_TICK
            "heavy" -> if (Build.VERSION.SDK_INT >= 31) VibrationEffect.Composition.PRIMITIVE_THUD else VibrationEffect.Composition.PRIMITIVE_CLICK
            else -> VibrationEffect.Composition.PRIMITIVE_CLICK
        }
        if (!vibrator.areAllPrimitivesSupported(primitive)) return null
        return VibrationEffect.startComposition().addPrimitive(primitive, intensity).compose()
    }

    private fun vibrator(context: Context): Vibrator? =
        if (Build.VERSION.SDK_INT >= 31) {
            (context.getSystemService(Context.VIBRATOR_MANAGER_SERVICE) as? VibratorManager)?.defaultVibrator
        } else {
            @Suppress("DEPRECATION")
            context.getSystemService(Context.VIBRATOR_SERVICE) as? Vibrator
        }
}

/** Plays a haptic for a renderer. Tests provide a recorder through [LocalFirstlightHaptics]. */
fun interface FirstlightHapticPlayer {
    fun play(haptic: FirstlightHaptic)
}

val LocalFirstlightHaptics = staticCompositionLocalOf<FirstlightHapticPlayer?> { null }

private val SilentHaptics = FirstlightHapticPlayer { }

/**
 * The player a control uses for [node]: silent when the node says
 * `haptic="none"`, otherwise the host view's haptics.
 */
@Composable
fun rememberFirstlightHaptics(node: NativeUINode): FirstlightHapticPlayer {
    val view = LocalView.current
    val viewPlayer = remember(view) { FirstlightHapticPlayer { FirstlightHaptics.perform(view, it) } }
    if (node.props.getString("haptic") == FirstlightHaptic.None.wire) return SilentHaptics
    return LocalFirstlightHaptics.current ?: viewPlayer
}

/**
 * Plays a button's `haptic` as the finger lands, as Android's own keys do,
 * without consuming the touch the button's click handling needs.
 */
@Composable
fun Modifier.firstlightPressHaptic(node: NativeUINode, enabled: Boolean): Modifier {
    val haptic = FirstlightHaptic.fromWire(node.props.getString("haptic"))
    val player = rememberFirstlightHaptics(node)
    if (!enabled || haptic == null || haptic == FirstlightHaptic.None) return this

    return pointerInput(haptic, player) {
        awaitEachGesture {
            awaitFirstDown(requireUnconsumed = false, pass = PointerEventPass.Initial)
            player.play(haptic)
        }
    }
}
