package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.Image
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.aspectRatio
import androidx.compose.foundation.layout.heightIn
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ListItem
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.ui.graphics.ImageBitmap
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.semantics.Role
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
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
                        Text(configuration.clearLabel)
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
    image: ImageBitmap? = null,
    confirmLabel: String = "Confirm",
    cancelLabel: String = "Cancel",
    skipLabel: String = "Skip",
    cropLabel: String = "Crop",
    zoomInLabel: String = "Zoom in",
    zoomOutLabel: String = "Zoom out",
) {
    AlertDialog(
        onDismissRequest = onCancel,
        title = { Text(cropLabel) },
        text = {
            Column(
                modifier = Modifier.fillMaxWidth(),
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(16.dp),
            ) {
                // The frame shows exactly the region Confirm commits: a centred
                // crop at the policy (or image) aspect, magnified by zoom.
                val ratio = policy.aspectRatio
                    ?: image?.let { it.width.toFloat() / it.height }
                    ?: 1f
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .heightIn(max = 320.dp)
                        .aspectRatio(ratio, matchHeightConstraintsFirst = ratio < 1f)
                        .clip(RoundedCornerShape(12.dp))
                        .background(MaterialTheme.colorScheme.surfaceVariant),
                    contentAlignment = Alignment.Center,
                ) {
                    if (image != null) {
                        Image(
                            bitmap = image,
                            contentDescription = cropLabel,
                            contentScale = ContentScale.Crop,
                            modifier = Modifier
                                .matchParentSize()
                                .graphicsLayer(scaleX = zoom, scaleY = zoom),
                        )
                    } else {
                        MaterialIcon(name = "image", contentDescription = cropLabel)
                    }
                }

                Row(horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                    IconButton(
                        onClick = onZoomOut,
                        modifier = Modifier.defaultMinSize(minWidth = 48.dp, minHeight = 48.dp),
                    ) {
                        MaterialIcon(name = "zoom_out", contentDescription = zoomOutLabel)
                    }
                    IconButton(
                        onClick = onZoomIn,
                        modifier = Modifier.defaultMinSize(minWidth = 48.dp, minHeight = 48.dp),
                    ) {
                        MaterialIcon(name = "zoom_in", contentDescription = zoomInLabel)
                    }
                }
            }
        },
        confirmButton = {
            Row {
                if (policy.allowsSkip) {
                    TextButton(onClick = onSkip, modifier = Modifier.defaultMinSize(minHeight = 48.dp)) {
                        Text(skipLabel)
                    }
                }
                TextButton(onClick = onConfirm, modifier = Modifier.defaultMinSize(minHeight = 48.dp)) {
                    Text(confirmLabel)
                }
            }
        },
        dismissButton = {
            TextButton(onClick = onCancel, modifier = Modifier.defaultMinSize(minHeight = 48.dp)) {
                Text(cancelLabel)
            }
        },
    )
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
internal fun FirstlightMediaSourceChooser(
    title: String,
    photoLibraryLabel: String,
    cameraLabel: String,
    cancelLabel: String,
    onPhotoLibrary: () -> Unit,
    onCamera: () -> Unit,
    onCancel: () -> Unit,
) {
    ModalBottomSheet(onDismissRequest = onCancel) {
        Column(modifier = Modifier.fillMaxWidth().padding(bottom = 16.dp)) {
            Text(
                text = title,
                style = MaterialTheme.typography.titleMedium,
                modifier = Modifier.padding(horizontal = 24.dp, vertical = 8.dp),
            )
            MediaSourceRow(photoLibraryLabel, "photo_library", onPhotoLibrary)
            MediaSourceRow(cameraLabel, "photo_camera", onCamera)
            MediaSourceRow(cancelLabel, "close", onCancel)
        }
    }
}

@Composable
private fun MediaSourceRow(label: String, icon: String, onClick: () -> Unit) {
    ListItem(
        headlineContent = { Text(label) },
        leadingContent = { MaterialIcon(name = icon, contentDescription = null) },
        modifier = Modifier
            .fillMaxWidth()
            .defaultMinSize(minHeight = 56.dp)
            .clickable(role = Role.Button, onClick = onClick),
    )
}
