package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.error
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.unit.dp
import com.nativephp.mobile.ui.MaterialIcon
import com.nativephp.plugins.native_ui.NativeUITokens

@Composable
internal fun FirstlightMediaControl(
    configuration: MediaRendererConfiguration,
    tokens: NativeUITokens,
    onPick: () -> Unit,
    onClear: () -> Unit,
    modifier: Modifier = Modifier,
) {
    val supporting = configuration.supportingText
    Column(
        modifier = modifier
            .fillMaxWidth()
            .semantics(mergeDescendants = true) {
                contentDescription = listOf(
                    configuration.accessibilityLabel,
                    "Required".takeIf { configuration.required }.orEmpty(),
                    configuration.accessibilityHint,
                    supporting,
                ).filter(String::isNotEmpty).joinToString(". ")
                if (configuration.error.isNotEmpty()) {
                    error(configuration.error)
                }
            },
        verticalArrangement = Arrangement.spacedBy(6.dp),
    ) {
        if (configuration.label.isNotEmpty()) {
            Text(
                text = if (configuration.required) "${configuration.label} *" else configuration.label,
                style = MaterialTheme.typography.titleSmall,
                color = tokens.onSurface,
            )
        }

        Row(
            modifier = Modifier
                .fillMaxWidth()
                .clip(RoundedCornerShape(10.dp))
                .background(tokens.surfaceVariant)
                .then(
                    if (configuration.error.isNotEmpty()) {
                        Modifier.border(1.dp, tokens.destructive, RoundedCornerShape(10.dp))
                    } else {
                        Modifier
                    },
                )
                .padding(12.dp),
            horizontalArrangement = Arrangement.spacedBy(12.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Box(
                modifier = Modifier
                    .size(72.dp)
                    .clip(RoundedCornerShape(10.dp))
                    .background(tokens.surface),
                contentAlignment = Alignment.Center,
            ) {
                MaterialIcon(
                    name = when {
                        configuration.hasValue && configuration.mode == MediaMode.Document -> "description"
                        configuration.hasValue -> "image"
                        configuration.mode == MediaMode.Document -> "upload_file"
                        else -> "add_a_photo"
                    },
                    contentDescription = null,
                )
            }

            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Button(
                    onClick = onPick,
                    enabled = configuration.isInteractive,
                    modifier = Modifier.defaultMinSize(minHeight = 48.dp),
                ) {
                    Text(if (configuration.hasValue) "Replace" else "Choose")
                }
                if (configuration.canClear) {
                    OutlinedButton(
                        onClick = onClear,
                        modifier = Modifier.defaultMinSize(minHeight = 48.dp),
                    ) {
                        Text("Clear")
                    }
                }
            }
        }

        if (supporting.isNotEmpty()) {
            Text(
                text = supporting,
                style = MaterialTheme.typography.bodySmall,
                color = if (configuration.error.isEmpty()) tokens.onSurfaceVariant else tokens.destructive,
            )
        }
    }
}

@Composable
internal fun FirstlightMediaCropSheet(
    policy: MediaCropPolicy,
    zoom: Float,
    onConfirm: () -> Unit,
    onCancel: () -> Unit,
    onSkip: () -> Unit,
    onZoomIn: () -> Unit,
    onZoomOut: () -> Unit,
) {
    AlertDialog(
        onDismissRequest = onCancel,
        title = { Text("Crop") },
        text = {
            Column(
                modifier = Modifier.fillMaxWidth(),
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(16.dp),
            ) {
                val ratio = policy.aspectRatio ?: 1f
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(220.dp)
                        .clip(RoundedCornerShape(12.dp))
                        .background(MaterialTheme.colorScheme.surfaceVariant)
                        .graphicsLayer(scaleX = zoom, scaleY = zoom),
                    contentAlignment = Alignment.Center,
                ) {
                    MaterialIcon(
                        name = "image",
                        contentDescription = "Crop preview",
                    )
                    if (policy.aspectRatio != null) {
                        Box(
                            modifier = Modifier
                                .size(width = 140.dp, height = (140f / ratio).dp)
                                .border(2.dp, MaterialTheme.colorScheme.onSurfaceVariant, RoundedCornerShape(2.dp)),
                        )
                    }
                }

                Row(horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                    IconButton(
                        onClick = onZoomOut,
                        modifier = Modifier.defaultMinSize(minWidth = 48.dp, minHeight = 48.dp),
                    ) {
                        MaterialIcon(name = "zoom_out", contentDescription = "Zoom out")
                    }
                    IconButton(
                        onClick = onZoomIn,
                        modifier = Modifier.defaultMinSize(minWidth = 48.dp, minHeight = 48.dp),
                    ) {
                        MaterialIcon(name = "zoom_in", contentDescription = "Zoom in")
                    }
                }
            }
        },
        confirmButton = {
            Row {
                if (policy.allowsSkip) {
                    TextButton(onClick = onSkip, modifier = Modifier.defaultMinSize(minHeight = 48.dp)) {
                        Text("Skip")
                    }
                }
                TextButton(onClick = onConfirm, modifier = Modifier.defaultMinSize(minHeight = 48.dp)) {
                    Text("Confirm")
                }
            }
        },
        dismissButton = {
            TextButton(onClick = onCancel, modifier = Modifier.defaultMinSize(minHeight = 48.dp)) {
                Text("Cancel")
            }
        },
    )
}
