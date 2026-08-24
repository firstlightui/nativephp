package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.material3.AlertDialog
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import com.nativephp.plugins.native_ui.NativeUITokens

@Composable
internal fun AlertDialogControl(
    state: AlertDialogRendererState,
    tokens: NativeUITokens,
    onDismiss: () -> Unit,
    modifier: Modifier = Modifier,
) {
    if (!state.isPresented) return

    val configuration = state.configuration

    AlertDialog(
        onDismissRequest = onDismiss,
        confirmButton = {
            TextButton(
                onClick = onDismiss,
                colors = ButtonDefaults.textButtonColors(
                    contentColor = tokens.primary,
                ),
            ) {
                Text(configuration.actionLabel)
            }
        },
        title = { Text(configuration.title) },
        text = { Text(configuration.message) },
        modifier = modifier,
    )
}
