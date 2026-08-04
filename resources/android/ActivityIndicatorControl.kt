package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.size
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.semantics.LiveRegionMode
import androidx.compose.ui.semantics.SemanticsPropertyReceiver
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.liveRegion
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp

enum class ActivityIndicatorSize(
    val wireName: String,
    val dimension: Dp,
) {
    Small("sm", 20.dp),
    Medium("md", 32.dp),
    Large("lg", 48.dp);

    companion object {
        fun fromWire(value: String): ActivityIndicatorSize = entries.firstOrNull {
            it.wireName == value
        } ?: Medium
    }
}

internal fun activityIndicatorSemantics(
    accessibilityLabel: String,
): SemanticsPropertyReceiver.() -> Unit = {
    contentDescription = accessibilityLabel
    liveRegion = LiveRegionMode.Polite
}

@Composable
fun FirstlightActivityIndicatorControl(
    size: ActivityIndicatorSize,
    accessibilityLabel: String,
    color: Color,
    modifier: Modifier = Modifier,
) {
    CircularProgressIndicator(
        modifier = modifier
            .size(size.dimension)
            .semantics(properties = activityIndicatorSemantics(accessibilityLabel)),
        color = color,
    )
}
