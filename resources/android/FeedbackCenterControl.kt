package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.Canvas
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.ColorScheme
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Snackbar
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.focus.onFocusChanged
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.platform.testTag
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.semantics.text
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.unit.dp
import com.nativephp.plugins.native_ui.NativeUITheme
import com.nativephp.plugins.native_ui.NativeUITokens

class FeedbackCenterAnnouncementState {
    private var announcedId: String? = null
    var snapshot: String? = null
        private set

    fun update(visible: FeedbackCenterItemConfiguration?) {
        if (visible == null) {
            announcedId = null
            snapshot = null
        } else if (announcedId != visible.feedbackId) {
            announcedId = visible.feedbackId
            snapshot = visible.message
        }
    }

    fun consume(visibleId: String?): Boolean {
        if (visibleId == null) {
            announcedId = null
            snapshot = null
            return false
        }
        if (announcedId == visibleId) return false
        announcedId = visibleId
        return true
    }
}

enum class FeedbackCenterToneColorRole {
    InverseSurface,
    Success,
    Accent,
    Destructive,
}

data class FeedbackCenterRenderingPolicy(val tone: FeedbackCenterTone) {
    val colorRole: FeedbackCenterToneColorRole = when (tone) {
        FeedbackCenterTone.Default -> FeedbackCenterToneColorRole.InverseSurface
        FeedbackCenterTone.Success -> FeedbackCenterToneColorRole.Success
        FeedbackCenterTone.Warning -> FeedbackCenterToneColorRole.Accent
        FeedbackCenterTone.Danger -> FeedbackCenterToneColorRole.Destructive
    }
    val iconTestTag: String = "firstlight-feedback-tone-${tone.wireName}"

    companion object {
        const val ConstrainedWidthDp = 480
        const val ReflowFontScale = 1.5f

        fun actionOnNewLine(maxWidthDp: Int, fontScale: Float): Boolean =
            maxWidthDp < ConstrainedWidthDp || fontScale >= ReflowFontScale
    }
}

internal data class FeedbackCenterColors(
    val container: Color,
    val content: Color,
    val action: Color,
    val dismiss: Color,
    val accent: Color,
)

@Composable
private fun feedbackCenterColors(tone: FeedbackCenterTone): FeedbackCenterColors =
    feedbackCenterColors(
        tone,
        MaterialTheme.colorScheme,
        if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light,
    )

internal fun feedbackCenterColors(
    tone: FeedbackCenterTone,
    scheme: ColorScheme,
    tokens: NativeUITokens,
): FeedbackCenterColors {
    return when (FeedbackCenterRenderingPolicy(tone).colorRole) {
        FeedbackCenterToneColorRole.InverseSurface -> FeedbackCenterColors(
            container = scheme.inverseSurface,
            content = scheme.inverseOnSurface,
            action = scheme.inversePrimary,
            dismiss = scheme.inverseOnSurface,
            accent = scheme.inversePrimary,
        )
        FeedbackCenterToneColorRole.Success -> FeedbackCenterColors(
            container = tokens.success,
            content = tokens.onSuccess,
            action = tokens.onSuccess,
            dismiss = tokens.onSuccess,
            accent = tokens.onSuccess,
        )
        FeedbackCenterToneColorRole.Accent -> FeedbackCenterColors(
            container = tokens.accent,
            content = tokens.onAccent,
            action = tokens.onAccent,
            dismiss = tokens.onAccent,
            accent = tokens.onAccent,
        )
        FeedbackCenterToneColorRole.Destructive -> FeedbackCenterColors(
            container = tokens.destructive,
            content = tokens.onDestructive,
            action = tokens.onDestructive,
            dismiss = tokens.onDestructive,
            accent = tokens.onDestructive,
        )
    }
}

@Composable
fun FirstlightFeedbackCenterControl(
    configuration: FeedbackCenterItemConfiguration,
    announcementMessage: String = configuration.message,
    actionOnNewLine: Boolean,
    onAction: () -> Unit,
    onDismiss: () -> Unit,
    onFocusChanged: (Boolean) -> Unit,
    modifier: Modifier = Modifier,
) {
    val colors = feedbackCenterColors(configuration.tone)
    var actionFocused by remember(configuration.feedbackId) { mutableStateOf(false) }
    var dismissFocused by remember(configuration.feedbackId) { mutableStateOf(false) }

    Snackbar(
        modifier = modifier.testTag("firstlight-feedback-snackbar"),
            action = if (configuration.hasAction) {
                {
                    TextButton(
                        onClick = onAction,
                        modifier = Modifier
                            .defaultMinSize(minWidth = 48.dp, minHeight = 48.dp)
                            .onFocusChanged {
                                actionFocused = it.isFocused
                                onFocusChanged(actionFocused || dismissFocused)
                            },
                        colors = ButtonDefaults.textButtonColors(contentColor = colors.action),
                    ) {
                        Text(configuration.actionLabel.orEmpty())
                    }
                }
            } else null,
            dismissAction = if (configuration.hold && configuration.manualCallback != null) {
                {
                    IconButton(
                        onClick = onDismiss,
                        modifier = Modifier
                            .size(48.dp)
                            .onFocusChanged {
                                dismissFocused = it.isFocused
                                onFocusChanged(actionFocused || dismissFocused)
                            }
                            .semantics {
                                contentDescription = "Dismiss feedback"
                            },
                    ) {
                        DismissIcon(colors.dismiss)
                    }
                }
            } else null,
            actionOnNewLine = actionOnNewLine,
            containerColor = colors.container,
            contentColor = colors.content,
            actionContentColor = colors.action,
            dismissActionContentColor = colors.dismiss,
    ) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            ToneIcon(configuration.tone, colors.accent)
            Spacer(Modifier.width(12.dp))
            Box(
                Modifier.clearAndSetSemantics {
                    text = AnnotatedString(announcementMessage)
                },
            ) {
                Text(configuration.message)
            }
        }
    }
}

@Composable
private fun ToneIcon(tone: FeedbackCenterTone, color: Color) {
    val renderingPolicy = FeedbackCenterRenderingPolicy(tone)
    Canvas(
        Modifier
            .size(20.dp)
            .testTag(renderingPolicy.iconTestTag)
            .clearAndSetSemantics {},
    ) {
        val stroke = Stroke(width = size.minDimension * 0.1f, cap = StrokeCap.Round)
        drawCircle(color = color, style = stroke)
        when (tone) {
            FeedbackCenterTone.Success -> {
                drawLine(color, Offset(size.width * .28f, size.height * .52f), Offset(size.width * .44f, size.height * .68f), strokeWidth = stroke.width, cap = StrokeCap.Round)
                drawLine(color, Offset(size.width * .44f, size.height * .68f), Offset(size.width * .74f, size.height * .34f), strokeWidth = stroke.width, cap = StrokeCap.Round)
            }
            FeedbackCenterTone.Warning -> {
                drawLine(color, Offset(size.width * .5f, size.height * .28f), Offset(size.width * .5f, size.height * .58f), strokeWidth = stroke.width, cap = StrokeCap.Round)
                drawCircle(color, size.minDimension * .055f, Offset(size.width * .5f, size.height * .73f))
            }
            FeedbackCenterTone.Danger -> {
                drawLine(color, Offset(size.width * .34f, size.height * .34f), Offset(size.width * .66f, size.height * .66f), strokeWidth = stroke.width, cap = StrokeCap.Round)
                drawLine(color, Offset(size.width * .66f, size.height * .34f), Offset(size.width * .34f, size.height * .66f), strokeWidth = stroke.width, cap = StrokeCap.Round)
            }
            FeedbackCenterTone.Default -> {
                drawCircle(color, size.minDimension * .055f, Offset(size.width * .5f, size.height * .3f))
                drawLine(color, Offset(size.width * .5f, size.height * .46f), Offset(size.width * .5f, size.height * .72f), strokeWidth = stroke.width, cap = StrokeCap.Round)
            }
        }
    }
}

@Composable
private fun DismissIcon(color: Color) {
    Canvas(Modifier.size(18.dp).clearAndSetSemantics {}) {
        val strokeWidth = size.minDimension * .12f
        drawLine(color, Offset(size.width * .25f, size.height * .25f), Offset(size.width * .75f, size.height * .75f), strokeWidth, StrokeCap.Round)
        drawLine(color, Offset(size.width * .75f, size.height * .25f), Offset(size.width * .25f, size.height * .75f), strokeWidth, StrokeCap.Round)
    }
}
