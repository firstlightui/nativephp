package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp

@Composable
fun MetricControl(
    label: String,
    value: String,
    detail: String,
    foreground: Color,
    valueColor: Color,
    accessibilityDescription: String,
    modifier: Modifier = Modifier,
) {
    Column(
        modifier = modifier.clearAndSetSemantics { contentDescription = accessibilityDescription },
        verticalArrangement = Arrangement.spacedBy(4.dp),
    ) {
        Text(label, color = foreground, style = MaterialTheme.typography.bodyMedium)
        Text(value, color = valueColor, style = MaterialTheme.typography.headlineMedium.copy(
            fontFeatureSettings = "tnum", fontWeight = FontWeight.SemiBold,
        ))
        if (detail.isNotEmpty()) Text(detail, color = foreground, style = MaterialTheme.typography.bodySmall)
    }
}
