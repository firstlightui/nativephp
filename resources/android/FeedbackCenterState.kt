package dev.firstlightui.plugins.firstlight_ui.ui

import com.nativephp.mobile.ui.nativerender.NativeUINode
import kotlin.math.max
import kotlin.math.min

enum class FeedbackCenterTone(val wireName: String) {
    Default("default"),
    Success("success"),
    Warning("warning"),
    Danger("danger");

    companion object {
        fun fromWire(value: String): FeedbackCenterTone? = entries.firstOrNull { it.wireName == value }
    }
}

sealed interface FeedbackCenterWireEvent {
    data class Press(val callbackId: Int, val nodeId: Int) : FeedbackCenterWireEvent
}

object FeedbackCenterTiming {
    const val MinimumDurationMillis = 4_000L
    const val MaximumMessageDurationMillis = 10_000L
    const val ActionExtensionMillis = 2_000L

    fun automaticBaseMillis(message: String, hasAction: Boolean): Long {
        val additionalReadingMillis = max(0, message.length - 40).toLong() * 1_000L / 40L
        val messageDuration = min(MaximumMessageDurationMillis, MinimumDurationMillis + additionalReadingMillis)
        return messageDuration + if (hasAction) ActionExtensionMillis else 0L
    }
}

data class FeedbackCenterItemConfiguration(
    val nodeId: Int,
    val feedbackId: String,
    val message: String,
    val tone: FeedbackCenterTone,
    val hold: Boolean,
    val actionLabel: String?,
    val actionCallback: Int?,
    val timeoutCallback: Int?,
    val manualCallback: Int?,
    private val hasValidIdentity: Boolean,
    private val hasValidMessage: Boolean,
    private val hasValidTone: Boolean,
) {
    constructor(node: NativeUINode) : this(
        nodeId = node.id,
        feedbackId = node.props.getString("feedback_id"),
        message = node.props.getString("message"),
        tone = FeedbackCenterTone.fromWire(node.props.getString("tone", "default")) ?: FeedbackCenterTone.Default,
        hold = node.props.getBool("hold"),
        actionLabel = validActionLabel(node),
        actionCallback = validActionCallback(node),
        timeoutCallback = callback(node.props.getCallbackId("on_timeout")),
        manualCallback = callback(node.props.getCallbackId("on_manual")),
        hasValidIdentity = node.props.getString("feedback_id").isNotBlank(),
        hasValidMessage = node.props.getString("message").isNotBlank(),
        hasValidTone = FeedbackCenterTone.fromWire(node.props.getString("tone", "default")) != null,
    )

    val hasAction: Boolean get() = actionLabel != null && actionCallback != null

    val isEligible: Boolean
        get() = hasValidIdentity && hasValidMessage && hasValidTone &&
            manualCallback != null && (hold || timeoutCallback != null)

    companion object {
        private fun callback(value: Int): Int? = value.takeIf { it != 0 }

        private fun validActionLabel(node: NativeUINode): String? {
            val label = node.props.getString("action_label").trim()
            return label.takeIf { it.isNotEmpty() && validActionCallback(node) != null }
        }

        private fun validActionCallback(node: NativeUINode): Int? {
            val label = node.props.getString("action_label").trim()
            return callback(node.props.getCallbackId("on_action")).takeIf { label.isNotEmpty() }
        }
    }
}

class FeedbackCenterQueueState(
    nowMillis: Long,
    timeoutPolicy: (FeedbackCenterItemConfiguration) -> Long = {
        FeedbackCenterTiming.automaticBaseMillis(it.message, it.hasAction)
    },
) {
    private val items = mutableListOf<FeedbackCenterItemConfiguration>()
    private val tombstones = mutableSetOf<String>()
    private var clockMillis = nowMillis
    private var timeoutPolicy = timeoutPolicy

    var elapsedMillis: Long = 0
        private set
    var isPaused: Boolean = false
        private set

    val visible: FeedbackCenterItemConfiguration? get() = items.firstOrNull()
    val pendingIds: List<String> get() = items.drop(1).map { it.feedbackId }
    val visibleDurationMillis: Long
        get() = visible?.takeUnless { it.hold }?.let(timeoutPolicy) ?: Long.MAX_VALUE
    val remainingMillis: Long get() = max(0L, visibleDurationMillis - elapsedMillis)

    fun setTimingPolicy(
        policy: (FeedbackCenterItemConfiguration) -> Long,
        nowMillis: Long,
    ): FeedbackCenterWireEvent? {
        val expectedId = visible?.feedbackId
        synchronize(nowMillis)
        timeoutPolicy = policy
        return timeoutIfDue(expectedId, nowMillis)
    }

    fun timingPolicyChanged(nowMillis: Long): FeedbackCenterWireEvent? {
        val expectedId = visible?.feedbackId
        synchronize(nowMillis)
        return timeoutIfDue(expectedId, nowMillis)
    }

    fun reconcile(
        published: List<FeedbackCenterItemConfiguration>,
        nowMillis: Long,
    ): FeedbackCenterWireEvent? {
        synchronize(nowMillis)

        val publishedIds = published.map { it.feedbackId }.filter { it.isNotBlank() }.toSet()
        tombstones.retainAll(publishedIds)

        val idCounts = published.groupingBy { it.feedbackId }.eachCount()
        val eligible = published.filter { it.isEligible && idCounts[it.feedbackId] == 1 }
        val eligibleById = eligible.associateBy { it.feedbackId }
        val previousVisibleId = visible?.feedbackId

        val reconciled = items.mapNotNull { eligibleById[it.feedbackId] }.toMutableList()
        val retainedIds = reconciled.mapTo(mutableSetOf()) { it.feedbackId }
        eligible.forEach { configuration ->
            if (retainedIds.add(configuration.feedbackId) && configuration.feedbackId !in tombstones) {
                reconciled += configuration
            }
        }

        items.clear()
        items += reconciled
        if (visible?.feedbackId != previousVisibleId || visible == null) {
            elapsedMillis = 0
            clockMillis = nowMillis
        }

        return timeoutIfDue(previousVisibleId, nowMillis)
    }

    fun action(feedbackId: String, nowMillis: Long): FeedbackCenterWireEvent? {
        if (visible?.feedbackId != feedbackId) return null
        synchronize(nowMillis)
        val current = visible ?: return null
        val callback = current.actionCallback ?: return null
        return complete(current.feedbackId, FeedbackCenterWireEvent.Press(callback, current.nodeId), nowMillis)
    }

    fun timeout(feedbackId: String, nowMillis: Long): FeedbackCenterWireEvent? {
        if (isPaused || visible?.feedbackId != feedbackId) return null
        synchronize(nowMillis)
        val current = visible?.takeUnless { it.hold } ?: return null
        val callback = current.timeoutCallback ?: return null
        return complete(current.feedbackId, FeedbackCenterWireEvent.Press(callback, current.nodeId), nowMillis)
    }

    fun manualDismiss(feedbackId: String, nowMillis: Long): FeedbackCenterWireEvent? {
        if (visible?.feedbackId != feedbackId) return null
        synchronize(nowMillis)
        val current = visible ?: return null
        val callback = current.manualCallback ?: return null
        return complete(current.feedbackId, FeedbackCenterWireEvent.Press(callback, current.nodeId), nowMillis)
    }

    fun advanceBy(intervalMillis: Long, feedbackId: String): FeedbackCenterWireEvent? {
        val current = visible
        if (intervalMillis <= 0 || isPaused || current?.feedbackId != feedbackId || current.hold) return null
        val advancedTime = clockMillis + intervalMillis
        synchronize(advancedTime)
        return timeoutIfDue(feedbackId, advancedTime)
    }

    fun pause(nowMillis: Long): FeedbackCenterWireEvent? {
        if (isPaused) return null
        val expectedId = visible?.feedbackId
        synchronize(nowMillis)
        val event = timeoutIfDue(expectedId, nowMillis)
        isPaused = true
        return event
    }

    fun resume(nowMillis: Long): FeedbackCenterWireEvent? {
        if (!isPaused) return null
        clockMillis = nowMillis
        isPaused = false
        return timeoutIfDue(visible?.feedbackId, nowMillis)
    }

    private fun synchronize(nowMillis: Long) {
        try {
            val current = visible
            if (nowMillis > clockMillis && !isPaused && current != null && !current.hold) {
                elapsedMillis = min(visibleDurationMillis, elapsedMillis + (nowMillis - clockMillis))
            }
        } finally {
            clockMillis = max(clockMillis, nowMillis)
        }
    }

    private fun complete(
        feedbackId: String,
        event: FeedbackCenterWireEvent,
        nowMillis: Long,
    ): FeedbackCenterWireEvent? {
        if (visible?.feedbackId != feedbackId) return null
        tombstones += feedbackId
        items.removeAt(0)
        elapsedMillis = 0
        clockMillis = max(clockMillis, nowMillis)
        return event
    }

    private fun timeoutIfDue(feedbackId: String?, nowMillis: Long): FeedbackCenterWireEvent? {
        if (
            isPaused || feedbackId == null || visible?.feedbackId != feedbackId ||
            visible?.hold != false || remainingMillis != 0L
        ) return null
        return timeout(feedbackId, nowMillis)
    }
}
