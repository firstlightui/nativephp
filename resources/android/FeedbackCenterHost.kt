package dev.firstlightui.plugins.firstlight_ui.ui

import android.content.Context
import android.os.SystemClock
import android.view.accessibility.AccessibilityManager
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.BoxWithConstraints
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.SnackbarDuration
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.unit.dp
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleEventObserver
import androidx.lifecycle.compose.LocalLifecycleOwner
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import kotlinx.coroutines.delay

@Composable
fun FirstlightFeedbackCenterHost(
    centerNode: NativeUINode?,
    content: @Composable () -> Unit,
) {
    val context = LocalContext.current
    val accessibilityManager = remember(context) {
        context.getSystemService(Context.ACCESSIBILITY_SERVICE) as AccessibilityManager
    }
    val timeoutPolicy = remember(accessibilityManager) { androidFeedbackTimeoutPolicy(accessibilityManager) }
    val lifecycleOwner = LocalLifecycleOwner.current
    val snackbarHostState = remember { SnackbarHostState() }
    val announcements = remember { FeedbackCenterAnnouncementState() }
    val queue = remember { FeedbackCenterQueueState(nowMillis(), timeoutPolicy) }
    var revision by remember { mutableIntStateOf(0) }
    var lifecycleResumed by remember(lifecycleOwner) {
        mutableStateOf(lifecycleOwner.lifecycle.currentState.isAtLeast(Lifecycle.State.RESUMED))
    }
    var controlFocused by remember { mutableStateOf(false) }

    @Suppress("UNUSED_EXPRESSION")
    revision
    val configurations = centerNode?.children
        ?.filter { it.type == "firstlight.feedback-item" }
        ?.map(::FeedbackCenterItemConfiguration)
        .orEmpty()

    fun dispatch(event: FeedbackCenterWireEvent?) {
        val press = event as? FeedbackCenterWireEvent.Press ?: return
        NativeUIBridge.sendPressEvent(press.callbackId, press.nodeId)
    }

    fun mutate(block: FeedbackCenterQueueState.() -> FeedbackCenterWireEvent?) {
        dispatch(queue.block())
        revision += 1
    }

    DisposableEffect(lifecycleOwner) {
        val observer = LifecycleEventObserver { _, _ ->
            lifecycleResumed = lifecycleOwner.lifecycle.currentState.isAtLeast(Lifecycle.State.RESUMED)
        }
        lifecycleOwner.lifecycle.addObserver(observer)
        onDispose { lifecycleOwner.lifecycle.removeObserver(observer) }
    }

    LaunchedEffect(configurations) {
        val previousId = queue.visible?.feedbackId
        mutate { reconcile(configurations, nowMillis()) }
        if (queue.visible?.feedbackId != previousId) controlFocused = false
    }

    val suspended = !lifecycleResumed || controlFocused
    LaunchedEffect(suspended) {
        if (suspended) mutate { pause(nowMillis()) } else mutate { resume(nowMillis()) }
    }

    val visible = queue.visible
    val visibleId = visible?.feedbackId
    val announceVisible = remember(visibleId) { announcements.consume(visibleId) }
    val announcementMessage = remember(visibleId) { visible?.message.orEmpty() }

    LaunchedEffect(visibleId) {
        if (visibleId == null) return@LaunchedEffect
        snackbarHostState.showSnackbar(
            message = visible.message,
            duration = SnackbarDuration.Indefinite,
        )
    }

    val remainingMillis = queue.remainingMillis
    LaunchedEffect(visibleId, suspended, remainingMillis) {
        val scheduledId = visibleId ?: return@LaunchedEffect
        if (suspended || visible.hold || remainingMillis == Long.MAX_VALUE) return@LaunchedEffect
        if (remainingMillis > 0) delay(remainingMillis)
        mutate { timeout(scheduledId, nowMillis()) }
    }

    Box(Modifier.fillMaxSize()) {
        content()

        SnackbarHost(
            hostState = snackbarHostState,
            modifier = Modifier
                .align(Alignment.BottomCenter)
                .navigationBarsPadding()
                .imePadding()
                .padding(horizontal = 12.dp, vertical = 12.dp),
        ) { snackbarData ->
            val current = queue.visible
            if (current != null) {
                BoxWithConstraints {
                    val fontScale = LocalDensity.current.fontScale
                    val actionOnNewLine = FeedbackCenterRenderingPolicy.actionOnNewLine(
                        maxWidthDp = maxWidth.value.toInt(),
                        fontScale = fontScale,
                    )
                    FirstlightFeedbackCenterControl(
                        configuration = current,
                        announce = announceVisible,
                        announcementMessage = announcementMessage,
                        actionOnNewLine = actionOnNewLine,
                        onAction = {
                            snackbarData.dismiss()
                            controlFocused = false
                            mutate { action(current.feedbackId, nowMillis()) }
                        },
                        onDismiss = {
                            snackbarData.dismiss()
                            controlFocused = false
                            mutate { manualDismiss(current.feedbackId, nowMillis()) }
                        },
                        onFocusChanged = { focused ->
                            if (queue.visible?.feedbackId == current.feedbackId) controlFocused = focused
                        },
                    )
                }
            }
        }
    }
}

internal fun androidFeedbackTimeoutPolicy(
    accessibilityManager: AccessibilityManager,
): (FeedbackCenterItemConfiguration) -> Long = { configuration ->
    val base = FeedbackCenterTiming.automaticBaseMillis(configuration.message, configuration.hasAction)
        .coerceAtMost(Int.MAX_VALUE.toLong())
        .toInt()
    val contentFlags = feedbackCenterContentFlags(configuration)
    accessibilityManager.getRecommendedTimeoutMillis(base, contentFlags).toLong()
}

internal fun feedbackCenterContentFlags(configuration: FeedbackCenterItemConfiguration): Int =
    AccessibilityManager.FLAG_CONTENT_TEXT or AccessibilityManager.FLAG_CONTENT_ICONS or
        if (configuration.hasAction || configuration.hold) AccessibilityManager.FLAG_CONTENT_CONTROLS else 0

private fun nowMillis(): Long = SystemClock.elapsedRealtime()
