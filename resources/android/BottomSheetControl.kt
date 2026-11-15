package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.BottomSheetDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.Text
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.heading
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.unit.dp
import com.nativephp.plugins.native_ui.NativeUITokens

/**
 * Material 3 modal bottom sheet sized to its content. The partially expanded
 * stop is skipped so content taller than half the screen opens fully expanded
 * instead of parking its lower controls off-screen; short content still opens
 * at its own height. Drag down, scrim tap, and system back request dismissal.
 * A title is Material heading text under the drag handle, above the scrolling
 * body; Material's modal bottom sheet has no top bar or close button.
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FirstlightBottomSheetControl(
    configuration: BottomSheetRendererConfiguration,
    tokens: NativeUITokens,
    onDismiss: () -> Unit,
    modifier: Modifier = Modifier,
    footer: @Composable () -> Unit = {},
    scrollBody: Boolean = true,
    content: @Composable ColumnScope.() -> Unit,
) {
    if (!configuration.visible) return

    ModalBottomSheet(
        onDismissRequest = onDismiss,
        sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true),
        containerColor = tokens.surface,
        contentColor = tokens.onSurface,
        scrimColor = BottomSheetDefaults.ScrimColor,
    ) {
        Column(
            modifier = modifier
                .fillMaxWidth()
                .semantics {
                    if (configuration.accessibilityLabel.isNotEmpty()) {
                        contentDescription = configuration.accessibilityLabel
                    }
                },
        ) {
            if (configuration.title.isNotEmpty()) {
                Text(
                    text = configuration.title,
                    color = tokens.onSurface,
                    style = MaterialTheme.typography.titleLarge,
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(start = 16.dp, end = 16.dp, bottom = 8.dp)
                        .semantics { heading() },
                )
            }
            Column(
                modifier = Modifier.weight(1f, fill = false).then(
                    if (scrollBody) Modifier.verticalScroll(rememberScrollState()) else Modifier,
                ),
                content = content,
            )
            footer()
        }
    }
}
