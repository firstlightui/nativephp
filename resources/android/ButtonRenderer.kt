package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.ui.layout.SubcomposeLayout
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.platform.LocalLayoutDirection
import androidx.compose.ui.unit.Constraints
import androidx.compose.ui.unit.Density
import androidx.compose.ui.unit.LayoutDirection
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.disabled
import androidx.compose.ui.semantics.onClick
import androidx.compose.ui.semantics.role
import androidx.compose.ui.semantics.stateDescription
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.KeyboardFocusPolicy
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode

/**
 * Android renderer for `firstlight.button`. Drawing and touch stay with Mobile
 * UI's Material 3 renderer. NativePHP chains a role-less clickable onto the
 * modifier of every node with `@press`, so TalkBack focused a plain View.
 * Replacing the semantics on that modifier, as List Item does, exposes one
 * named Button whose accessibility click sends the press once.
 */
object FirstlightButtonRenderer {
    @Composable
    fun Render(node: NativeUINode, modifier: Modifier) {
        val enabled = !node.props.getBool("disabled") && !node.props.getBool("loading")
        val callbackId = node.props.getCallbackId("on_press").let { if (it != 0) it else node.onPress }

        val rightToLeft = LocalLayoutDirection.current == LayoutDirection.Rtl
        // A new node only when an icon is mirrored: nodes compare by identity, so a copy on
        // every recomposition would stop Mobile UI's renderer from skipping.
        val mirrored = firstlightButtonMirroredProps(node.props, rightToLeft)
        val drawn = if (mirrored === node.props) node else node.copy(props = mirrored)
        val buttonModifier = modifier.firstlightPressHaptic(node, enabled = enabled && callbackId != 0).clearAndSetSemantics {
            role = Role.Button
            contentDescription = firstlightButtonDescription(node)
            if (node.props.getBool("loading")) stateDescription = "Loading"
            if (!enabled) {
                disabled()
            } else {
                onClick {
                    if (callbackId != 0) {
                        KeyboardFocusPolicy.dismissForInteractiveTap()
                        NativeUIBridge.sendPressEvent(callbackId, node.id)
                    }
                    callbackId != 0
                }
            }
        }

        if (LocalDensity.current.fontScale <= 1f) {
            com.nativephp.plugins.native_ui.ui.ButtonRenderer.Render(drawn, buttonModifier)
        } else {
            FirstlightFitLabelWidth(buttonModifier) {
                com.nativephp.plugins.native_ui.ui.ButtonRenderer.Render(drawn, Modifier)
            }
        }
    }
}

/** Swaps directional icon names for their mirror in right-to-left layouts. */
internal fun firstlightButtonMirroredProps(props: GenericProps, rightToLeft: Boolean): GenericProps =
    listOf("leading_icon", "trailing_icon").fold(props) { current, key ->
        val name = current.getString(key)
        val mirrored = firstlightDirectionalIconName(name, rightToLeft)
        if (mirrored == name) current else current.with(key, mirrored)
    }

/**
 * Mobile UI draws button labels on one line without wrapping, so at large
 * font scales a long label ran past the button's edge and was cut off. When
 * the content is wider than the space it has, this lowers the font scale just
 * enough for it to fit, keeping at least the default scale.
 *
 * [modifier] is the node's own, applied here rather than to [content], so its
 * size and parent data (such as a row weight) still reach the parent layout,
 * and the constraints it produces fill the button as before.
 */
@Composable
internal fun FirstlightFitLabelWidth(modifier: Modifier, content: @Composable () -> Unit) {
    val density = LocalDensity.current
    SubcomposeLayout(modifier) { constraints ->
        val natural = subcompose("natural", content)
        val naturalWidth = natural.maxOfOrNull { it.maxIntrinsicWidth(Constraints.Infinity) } ?: 0
        val fits = !constraints.hasBoundedWidth || naturalWidth <= constraints.maxWidth

        val placeables = if (fits) {
            natural.map { it.measure(constraints) }
        } else {
            // Text width doesn't grow exactly in proportion to the font scale, and padding
            // and icons don't grow at all, so the scale is corrected over a few trials.
            fun scaled(slot: Any, scale: Float) = subcompose(slot) {
                CompositionLocalProvider(LocalDensity provides Density(density.density, scale), content = content)
            }
            var scale = density.fontScale
            var width = naturalWidth
            var trial = 0
            while (width > constraints.maxWidth && scale > 1f && trial < 4) {
                scale = (scale * constraints.maxWidth / width * 0.99f).coerceAtLeast(1f)
                width = scaled("trial$trial", scale).maxOfOrNull { it.maxIntrinsicWidth(Constraints.Infinity) } ?: 0
                trial++
            }
            scaled("shrunk", scale).map { it.measure(constraints) }
        }
        val width = placeables.maxOfOrNull { it.width } ?: constraints.minWidth
        val height = placeables.maxOfOrNull { it.height } ?: constraints.minHeight
        layout(width, height) {
            placeables.forEach { it.placeRelative(0, 0) }
        }
    }
}

internal fun firstlightButtonDescription(node: NativeUINode): String {
    val name = node.props.getString("a11y_label").ifEmpty { node.props.getString("label") }

    return listOf(name, node.props.getString("a11y_hint")).filter { it.isNotEmpty() }.joinToString(". ")
}
