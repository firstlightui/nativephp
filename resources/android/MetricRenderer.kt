package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.plugins.native_ui.NativeUITheme

data class MetricRendererConfiguration(val node: NativeUINode) {
    val label = node.props.getString("label")
    val value = node.props.getString("value")
    val detail = node.props.getString("detail")
    val tone = StatusLabelTone.fromWire(node.props.getString("tone", "neutral"))
    val accessibilityDescription = listOf(
        node.props.getString("a11y_label").ifEmpty {
            "$label: $value" + if (detail.isEmpty()) "" else ", $detail"
        },
        node.props.getString("a11y_hint"),
    ).filter(String::isNotEmpty).joinToString(". ")
}

object MetricRenderer {
    @Composable
    fun Render(node: NativeUINode, modifier: Modifier) {
        val configuration = MetricRendererConfiguration(node)
        val dark = isSystemInDarkTheme()
        val tokens = if (dark) NativeUITheme.dark else NativeUITheme.light
        val valueColor = when (configuration.tone) {
            StatusLabelTone.Neutral -> tokens.onSurface
            StatusLabelTone.Info -> tokens.primary
            StatusLabelTone.Success -> tokens.success
            StatusLabelTone.Warning -> FirstlightWarningColors(node).resolve(dark).first
            StatusLabelTone.Danger -> tokens.destructive
        }
        MetricControl(
            configuration.label, configuration.value, configuration.detail,
            tokens.onSurface, valueColor, configuration.accessibilityDescription, modifier,
        )
    }
}
