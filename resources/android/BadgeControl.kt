package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.material3.Badge
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.semantics

internal fun firstlightBadgeDescription(label: String, accessibilityLabel: String, accessibilityHint: String): String =
    listOf(accessibilityLabel.ifEmpty { label }, accessibilityHint).filter(String::isNotEmpty).joinToString(". ")

@Composable
fun FirstlightBadgeControl(
    label: String,
    colors: StatusLabelTokenColors,
    accessibilityDescription: String,
    modifier: Modifier = Modifier,
) {
    if (label.isEmpty()) return

    Badge(
        modifier = modifier.semantics(mergeDescendants = true) { contentDescription = accessibilityDescription },
        containerColor = colors.background,
        contentColor = colors.foreground,
    ) {
        Text(label)
    }
}
