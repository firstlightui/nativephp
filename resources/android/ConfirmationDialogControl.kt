package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.material3.AlertDialog
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import com.nativephp.plugins.native_ui.NativeUITokens

enum class FirstlightConfirmationDialogTone(val wireName: String) {
    Default("default"),
    Destructive("destructive");

    companion object {
        fun fromWire(value: String): FirstlightConfirmationDialogTone = entries.firstOrNull {
            it.wireName == value
        } ?: Default
    }
}

@Composable
internal fun ConfirmationDialogControl(
    state: ConfirmationDialogRendererState,
    tokens: NativeUITokens,
    onConfirm: () -> Unit,
    onDismiss: () -> Unit,
    modifier: Modifier = Modifier,
) {
    if (!state.isPresented) return

    val configuration = state.configuration

    AlertDialog(
        onDismissRequest = onDismiss,
        confirmButton = {
            TextButton(
                onClick = onConfirm,
                colors = ButtonDefaults.textButtonColors(
                    contentColor = if (configuration.tone == FirstlightConfirmationDialogTone.Destructive) {
                        tokens.destructive
                    } else {
                        tokens.primary
                    },
                ),
            ) {
                Text(configuration.confirmLabel)
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text(configuration.cancelLabel)
            }
        },
        title = { Text(configuration.title) },
        text = { Text(configuration.message) },
        modifier = modifier,
    )
}
