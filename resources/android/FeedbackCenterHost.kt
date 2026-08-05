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
import androidx.compose.material3.SnackbarResult
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
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.delay

internal class FeedbackCenterHostRuntime(
    initialNowMillis: Long,
    recommendTimeoutMillis: (baseMillis: Int, contentFlags: Int) -> Int,
    private val sendPress: (callbackId: Int, nodeId: Int) -> Unit,
    private val nowMillis: () -> Long,
) {
    private val queue = FeedbackCenterQueueState(
        nowMillis = initialNowMillis,
        timeoutPolicy = androidFeedbackTimeoutPolicy(recommendTimeoutMillis),
    )
    private val announcements = FeedbackCenterAnnouncementState()

    val visible: FeedbackCenterItemConfiguration? get() = queue.visible
    val pendingIds: List<String> get() = queue.pendingIds
    val remainingMillis: Long get() = queue.remainingMillis
    val isPaused: Boolean get() = queue.isPaused
    val elapsedMillis: Long get() = queue.elapsedMillis
    val announcementMessage: String? get() = announcements.snapshot

    fun reconcile(configurations: List<FeedbackCenterItemConfiguration>): Boolean =
        apply(queue.reconcile(configurations, nowMillis()))

    fun action(feedbackId: String): Boolean = apply(queue.action(feedbackId, nowMillis()))

    fun manualDismiss(feedbackId: String): Boolean =
        apply(queue.manualDismiss(feedbackId, nowMillis()))

    fun snackbarDismissed(feedbackId: String): Boolean = manualDismiss(feedbackId)

    fun timeout(feedbackId: String): Boolean = apply(queue.timeout(feedbackId, nowMillis()))

    fun pause(): Boolean = apply(queue.pause(nowMillis()))

    fun resume(): Boolean = apply(queue.resume(nowMillis()))

    private fun apply(event: FeedbackCenterWireEvent?): Boolean {
        if (event is FeedbackCenterWireEvent.Press) {
            sendPress(event.callbackId, event.nodeId)
        }
        announcements.update(queue.visible)
        return event != null
    }
}

@Composable
fun FirstlightFeedbackCenterHost(
    centerNode: NativeUINode?,
    content: @Composable () -> Unit,
) {
    val context = LocalContext.current
    val accessibilityManager = remember(context) {
        context.getSystemService(Context.ACCESSIBILITY_SERVICE) as AccessibilityManager
    }
    val runtime = remember(accessibilityManager) {
        FeedbackCenterHostRuntime(
            initialNowMillis = nowMillis(),
            recommendTimeoutMillis = accessibilityManager::getRecommendedTimeoutMillis,
            sendPress = NativeUIBridge::sendPressEvent,
            nowMillis = ::nowMillis,
        )
    }
    FirstlightFeedbackCenterHost(centerNode, runtime, content)
}

@Composable
internal fun FirstlightFeedbackCenterHost(
    centerNode: NativeUINode?,
    runtime: FeedbackCenterHostRuntime,
    content: @Composable () -> Unit,
) {
    val lifecycleOwner = LocalLifecycleOwner.current
    val snackbarHostState = remember { SnackbarHostState() }
    var revision by remember { mutableIntStateOf(0) }
    var presentationEpoch by remember { mutableIntStateOf(0) }
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

    fun mutate(block: () -> Unit) {
        block()
        revision += 1
    }

    DisposableEffect(lifecycleOwner) {
        val observer = LifecycleEventObserver { _, _ ->
            lifecycleResumed = lifecycleOwner.lifecycle.currentState.isAtLeast(Lifecycle.State.RESUMED)
            mutate {
                if (!lifecycleResumed || controlFocused) runtime.pause() else runtime.resume()
            }
        }
        lifecycleOwner.lifecycle.addObserver(observer)
        onDispose { lifecycleOwner.lifecycle.removeObserver(observer) }
    }

    LaunchedEffect(configurations) {
        val previousId = runtime.visible?.feedbackId
        mutate { runtime.reconcile(configurations) }
        if (runtime.visible?.feedbackId != previousId) controlFocused = false
    }

    val suspended = !lifecycleResumed || controlFocused
    LaunchedEffect(suspended) {
        mutate { if (suspended) runtime.pause() else runtime.resume() }
    }

    val visible = runtime.visible
    val visibleId = visible?.feedbackId

    LaunchedEffect(visibleId, presentationEpoch) {
        val initiatingId = visibleId ?: return@LaunchedEffect
        val result = try {
            snackbarHostState.showSnackbar(
                message = runtime.announcementMessage ?: visible.message,
                duration = SnackbarDuration.Indefinite,
            )
        } catch (_: CancellationException) {
            return@LaunchedEffect
        }

        if (result == SnackbarResult.Dismissed) {
            val completed = runtime.snackbarDismissed(initiatingId)
            revision += 1
            if (!completed && runtime.visible?.feedbackId == initiatingId) {
                presentationEpoch += 1
            }
        }
    }

    val remainingMillis = runtime.remainingMillis
    LaunchedEffect(visibleId, suspended, remainingMillis) {
        val scheduledId = visibleId ?: return@LaunchedEffect
        if (suspended || visible.hold || remainingMillis == Long.MAX_VALUE) return@LaunchedEffect
        if (remainingMillis > 0) delay(remainingMillis)
        mutate { runtime.timeout(scheduledId) }
    }

    Box(Modifier.fillMaxSize()) {
        content()

        if (visible != null) {
            SnackbarHost(
                hostState = snackbarHostState,
                modifier = Modifier
                    .align(Alignment.BottomCenter)
                    .navigationBarsPadding()
                    .imePadding()
                    .padding(horizontal = 12.dp, vertical = 12.dp),
            ) { snackbarData ->
                val current = runtime.visible
                if (current != null) {
                    BoxWithConstraints {
                        val actionOnNewLine = FeedbackCenterRenderingPolicy.actionOnNewLine(
                            maxWidthDp = maxWidth.value.toInt(),
                            fontScale = LocalDensity.current.fontScale,
                        )
                        FirstlightFeedbackCenterControl(
                            configuration = current,
                            announcementMessage = runtime.announcementMessage ?: current.message,
                            actionOnNewLine = actionOnNewLine,
                            onAction = {
                                snackbarData.dismiss()
                                controlFocused = false
                                mutate { runtime.action(current.feedbackId) }
                            },
                            onDismiss = {
                                snackbarData.dismiss()
                                controlFocused = false
                                mutate { runtime.manualDismiss(current.feedbackId) }
                            },
                            onFocusChanged = { focused ->
                                if (runtime.visible?.feedbackId == current.feedbackId) {
                                    controlFocused = focused
                                }
                            },
                        )
                    }
                }
            }
        }
    }
}

internal fun androidFeedbackTimeoutPolicy(
    accessibilityManager: AccessibilityManager,
): (FeedbackCenterItemConfiguration) -> Long =
    androidFeedbackTimeoutPolicy(accessibilityManager::getRecommendedTimeoutMillis)

internal fun androidFeedbackTimeoutPolicy(
    recommendTimeoutMillis: (baseMillis: Int, contentFlags: Int) -> Int,
): (FeedbackCenterItemConfiguration) -> Long = { configuration ->
    val base = FeedbackCenterTiming.automaticBaseMillis(configuration.message, configuration.hasAction)
        .coerceAtMost(Int.MAX_VALUE.toLong())
        .toInt()
    recommendTimeoutMillis(base, feedbackCenterContentFlags(configuration)).toLong()
}

internal fun feedbackCenterContentFlags(configuration: FeedbackCenterItemConfiguration): Int =
    AccessibilityManager.FLAG_CONTENT_TEXT or AccessibilityManager.FLAG_CONTENT_ICONS or
        if (configuration.hasAction || configuration.hold) AccessibilityManager.FLAG_CONTENT_CONTROLS else 0

private fun nowMillis(): Long = SystemClock.elapsedRealtime()
