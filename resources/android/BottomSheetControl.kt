package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.material3.BottomSheetDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.semantics
import com.nativephp.plugins.native_ui.NativeUITokens

/**
 * Material 3 modal bottom sheet sized to its content. The partially expanded
 * stop is skipped so content taller than half the screen opens fully expanded
 * instead of parking its lower controls off-screen; short content still opens
 * at its own height. Drag down, scrim tap, and system back request dismissal.
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FirstlightBottomSheetControl(
    configuration: BottomSheetRendererConfiguration,
    tokens: NativeUITokens,
    onDismiss: () -> Unit,
    modifier: Modifier = Modifier,
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
            content = content,
        )
    }
}
