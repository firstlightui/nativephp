package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.FlowRow
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.safeDrawingPadding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.AlertDialogDefaults
import androidx.compose.material3.BasicAlertDialog
import androidx.compose.material3.DatePickerDialog
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Surface
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.unit.dp
import androidx.compose.ui.window.DialogProperties

/**
 * Material's picker dialog is a fixed 360 dp wide and does not scroll; the calendar needs about
 * 568 dp of height. On a narrower screen, such as a small phone at its largest display size, the
 * calendar and clock dial don't fit, so the pickers switch to their keyboard input modes, which
 * Material recommends for compact screens. On a screen that is wide enough but short, such as a
 * phone in landscape, they keep the calendar and dial inside a dialog that fits the screen and
 * scrolls.
 */
internal fun pickerFitsDialogWidth(screenWidthDp: Int): Boolean = screenWidthDp >= 360 + 2 * 16

internal fun pickerFitsFullDialog(screenWidthDp: Int, screenHeightDp: Int): Boolean =
    pickerFitsDialogWidth(screenWidthDp) && screenHeightDp >= 568 + 2 * 16

/**
 * On a screen that is short but wide, such as a phone in landscape, the pickers put Material's
 * header beside the calendar or the time beside the clock dial, as Android's own pickers do in
 * landscape, in a dialog up to 560 dp wide.
 */
internal fun pickerUsesSideBySideLayout(screenWidthDp: Int, screenHeightDp: Int): Boolean =
    !pickerFitsFullDialog(screenWidthDp, screenHeightDp) && screenWidthDp >= 560 + 2 * 16

@Composable
internal fun pickerUsesSideBySideLayout(): Boolean =
    LocalConfiguration.current.let { pickerUsesSideBySideLayout(it.screenWidthDp, it.screenHeightDp) }

@Composable
internal fun pickerFitsDialogWidth(): Boolean = pickerFitsDialogWidth(LocalConfiguration.current.screenWidthDp)

@Composable
internal fun pickerFitsFullDialog(): Boolean =
    LocalConfiguration.current.let { pickerFitsFullDialog(it.screenWidthDp, it.screenHeightDp) }

@OptIn(ExperimentalMaterial3Api::class, androidx.compose.foundation.layout.ExperimentalLayoutApi::class)
@Composable
internal fun FirstlightPickerDialog(
    onDismissRequest: () -> Unit,
    confirmButton: @Composable () -> Unit,
    dismissButton: @Composable () -> Unit,
    content: @Composable ColumnScope.() -> Unit,
) {
    if (pickerFitsFullDialog()) {
        DatePickerDialog(
            onDismissRequest = onDismissRequest,
            confirmButton = confirmButton,
            dismissButton = dismissButton,
            content = content,
        )
        return
    }

    // A short screen can't show the whole calendar, and keyboard entry opens the keyboard, which
    // would cover the actions. The dialog draws edge to edge and pads for the system bars and the
    // keyboard, so its content shrinks and scrolls. The platform caps a dialog window at about
    // 320 dp on a phone in landscape, too narrow for the header beside the calendar, so that
    // layout uses the whole window and BasicAlertDialog's own 560 dp limit instead.
    BasicAlertDialog(
        onDismissRequest = onDismissRequest,
        properties = DialogProperties(
            decorFitsSystemWindows = false,
            usePlatformDefaultWidth = !pickerUsesSideBySideLayout(),
        ),
        modifier = Modifier.safeDrawingPadding(),
    ) {
        Surface(
            shape = AlertDialogDefaults.shape,
            color = AlertDialogDefaults.containerColor,
            tonalElevation = AlertDialogDefaults.TonalElevation,
        ) {
            Column {
                // Only the picker scrolls; the actions stay below it, so they stay visible when
                // the keyboard leaves little room.
                Column(Modifier.weight(1f, fill = false).verticalScroll(rememberScrollState())) {
                    content()
                }
                // Wraps rather than clipping when large text makes the labels wider than the dialog.
                FlowRow(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(start = 12.dp, end = 12.dp, bottom = 8.dp),
                    horizontalArrangement = Arrangement.spacedBy(8.dp, Alignment.End),
                ) {
                    dismissButton()
                    confirmButton()
                }
            }
        }
    }
}
