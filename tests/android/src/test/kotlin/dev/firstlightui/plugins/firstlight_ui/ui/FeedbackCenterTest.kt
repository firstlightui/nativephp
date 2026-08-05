package dev.firstlightui.plugins.firstlight_ui.ui

import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.Button
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalLayoutDirection
import androidx.compose.ui.platform.testTag
import androidx.compose.ui.semantics.LiveRegionMode
import androidx.compose.ui.semantics.SemanticsActions
import androidx.compose.ui.semantics.SemanticsProperties
import androidx.compose.ui.test.assertHeightIsAtLeast
import androidx.compose.ui.test.assertCountEquals
import androidx.compose.ui.test.assertHasClickAction
import androidx.compose.ui.test.ExperimentalTestApi
import androidx.compose.ui.test.assertIsDisplayed
import androidx.compose.ui.test.assertWidthIsAtLeast
import androidx.compose.ui.test.hasAnyChild
import androidx.compose.ui.test.hasContentDescription
import androidx.compose.ui.test.hasTestTag
import androidx.compose.ui.test.hasText
import androidx.compose.ui.test.assert
import androidx.compose.ui.test.performClick
import androidx.compose.ui.test.performSemanticsAction
import androidx.compose.ui.test.onNodeWithContentDescription
import androidx.compose.ui.test.onNodeWithText
import androidx.compose.ui.test.runComposeUiTest
import androidx.compose.ui.unit.LayoutDirection
import androidx.compose.ui.unit.dp
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleOwner
import androidx.lifecycle.LifecycleRegistry
import androidx.lifecycle.compose.LocalLifecycleOwner
import app.cash.paparazzi.DeviceConfig
import app.cash.paparazzi.Paparazzi
import android.content.Context
import android.view.accessibility.AccessibilityManager
import androidx.test.core.app.ApplicationProvider
import com.nativephp.mobile.ui.nativerender.NativeRootHostRegistry
import dev.firstlightui.plugins.firstlight_ui.registerFirstlightUI
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Rule
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import org.robolectric.annotation.Config

class FeedbackCenterQueueTest {
    @Test
    fun `reconciliation preserves fifo and refreshes callbacks`() {
        val state = FeedbackCenterQueueState(nowMillis = 100_000)
        state.reconcile(listOf(item("one", action = 11), item("two", timeout = 22)), 100_000)
        state.advanceBy(1_000, "one")

        state.reconcile(listOf(item("one", action = 101), item("two", timeout = 202)), 101_000)

        assertEquals("one", state.visible?.feedbackId)
        assertEquals(101, state.visible?.actionCallback)
        assertEquals(listOf("two"), state.pendingIds)
        assertEquals(1_000, state.elapsedMillis)
    }

    @Test
    fun `completion tombstone blocks stale frames until an absence epoch`() {
        val state = FeedbackCenterQueueState(0)
        state.reconcile(listOf(item("one"), item("two")), 0)
        assertEquals(FeedbackCenterWireEvent.Press(12, 1), state.timeout("one", 4_000))
        assertEquals("two", state.visible?.feedbackId)

        state.reconcile(listOf(item("one"), item("two")), 4_001)
        assertEquals(emptyList<String>(), state.pendingIds)
        state.reconcile(listOf(item("two")), 4_002)
        state.reconcile(listOf(item("one"), item("two")), 4_003)
        assertEquals(listOf("one"), state.pendingIds)
    }

    @Test
    fun `copy update preserves order elapsed and refreshed timeout callback`() {
        val state = FeedbackCenterQueueState(10_000)
        state.reconcile(listOf(item("one", message = "Saved")), 10_000)
        state.advanceBy(2_000, "one")

        state.reconcile(
            listOf(item("one", message = "Appointment saved", tone = "success", timeout = 112)),
            12_000,
        )

        assertEquals("Appointment saved", state.visible?.message)
        assertEquals(FeedbackCenterTone.Success, state.visible?.tone)
        assertEquals(112, state.visible?.timeoutCallback)
        assertEquals(2_000, state.elapsedMillis)
    }

    @Test
    fun `programmatic cancellation advances silently`() {
        val state = FeedbackCenterQueueState(0)
        state.reconcile(listOf(item("one"), item("two")), 0)

        assertNull(state.reconcile(listOf(item("two")), 1_000))
        assertEquals("two", state.visible?.feedbackId)
        assertEquals(0, state.elapsedMillis)
    }

    @Test
    fun `action manual and timeout complete once and are bound to semantic id`() {
        val actions = FeedbackCenterQueueState(0)
        actions.reconcile(listOf(item("one", action = 51), item("two", action = 52)), 0)
        assertEquals(FeedbackCenterWireEvent.Press(51, 1), actions.action("one", 1_000))
        assertNull(actions.action("one", 2_000))
        assertEquals("two", actions.visible?.feedbackId)
        assertEquals(0, actions.elapsedMillis)

        val held = FeedbackCenterQueueState(0)
        held.reconcile(
            listOf(
                item("one", hold = true, timeout = 0, manual = 61),
                item("two", hold = true, timeout = 0, manual = 62),
            ),
            0,
        )
        assertNull(held.advanceBy(100_000, "one"))
        assertNull(held.timeout("one", 100_000))
        assertEquals(FeedbackCenterWireEvent.Press(61, 1), held.manualDismiss("one", 100_000))
        assertNull(held.manualDismiss("one", 100_001))
        assertEquals("two", held.visible?.feedbackId)

        val automatic = FeedbackCenterQueueState(0)
        automatic.reconcile(listOf(item("automatic", timeout = 72, manual = 73), item("next")), 0)
        assertEquals(
            FeedbackCenterWireEvent.Press(73, 1),
            automatic.manualDismiss("automatic", 0),
        )
        assertNull(automatic.manualDismiss("automatic", 1))
        assertEquals("next", automatic.visible?.feedbackId)

        val timed = FeedbackCenterQueueState(0)
        timed.reconcile(listOf(item("automatic", timeout = 72, manual = 73)), 0)
        assertEquals(
            FeedbackCenterWireEvent.Press(72, 1),
            timed.advanceBy(timed.remainingMillis, "automatic"),
        )
        assertNull(timed.timeout("automatic", 100_000))
    }

    @Test
    fun `stale timeout cannot complete or charge the next item`() {
        val state = FeedbackCenterQueueState(0)
        state.reconcile(listOf(item("one"), item("two", timeout = 22)), 0)

        assertEquals(FeedbackCenterWireEvent.Press(12, 1), state.timeout("one", 4_000))
        assertNull(state.timeout("one", 100_000))
        assertEquals("two", state.visible?.feedbackId)
        assertEquals(0, state.elapsedMillis)
        assertNull(state.pause(5_000))
        assertEquals(1_000, state.elapsedMillis)
    }

    @Test
    fun `pause resume excludes suspended time and repeated calls are safe`() {
        val state = FeedbackCenterQueueState(100_000)
        state.reconcile(listOf(item("one")), 100_000)
        assertNull(state.pause(101_000))
        assertTrue(state.isPaused)
        assertEquals(1_000, state.elapsedMillis)
        assertNull(state.pause(110_000))
        assertNull(state.resume(111_000))
        assertNull(state.resume(111_500))
        assertNull(state.pause(112_000))
        assertEquals(2_000, state.elapsedMillis)
    }

    @Test
    fun `pause at deadline expires once before suspending`() {
        val state = FeedbackCenterQueueState(0)
        state.reconcile(listOf(item("one"), item("two", timeout = 22)), 0)

        assertEquals(FeedbackCenterWireEvent.Press(12, 1), state.pause(4_000))
        assertTrue(state.isPaused)
        assertEquals("two", state.visible?.feedbackId)
        assertNull(state.pause(40_000))
        assertEquals(0, state.elapsedMillis)
    }

    @Test
    fun `malformed callbacks duplicate ids and invalid semantic data fail closed`() {
        val state = FeedbackCenterQueueState(0)
        state.reconcile(
            listOf(
                item("automatic", timeout = 12, manual = 0),
                item("held", hold = true, timeout = 0, manual = 0),
            ),
            0,
        )
        assertNull(state.visible)

        val partial = FeedbackCenterItemConfiguration(itemNode("partial", actionLabel = "Undo"))
        assertNull(partial.actionLabel)
        assertNull(partial.actionCallback)
        assertFalse(item(" ").isEligible)
        assertFalse(item("blank-message", message = " ").isEligible)
        assertFalse(item("bad-tone", tone = "critical").isEligible)

        state.reconcile(listOf(item("duplicate"), item("duplicate"), item("valid")), 1_000)
        assertEquals("valid", state.visible?.feedbackId)
        assertEquals(emptyList<String>(), state.pendingIds)
    }

    @Test
    fun `duration policy accounts elapsed before shortening and expires exactly once`() {
        val state = FeedbackCenterQueueState(0) { configuration ->
            val base = FeedbackCenterTiming.automaticBaseMillis(configuration.message, configuration.hasAction)
            base * 2
        }
        state.reconcile(listOf(item("one"), item("two", timeout = 22)), 0)

        state.advanceBy(5_000, "one")
        assertEquals(
            FeedbackCenterWireEvent.Press(12, 1),
            state.setTimingPolicy(
                { configuration -> FeedbackCenterTiming.automaticBaseMillis(configuration.message, configuration.hasAction) },
                5_000,
            ),
        )
        assertEquals("two", state.visible?.feedbackId)
        assertNull(state.timingPolicyChanged(5_000))
    }

    @Test
    fun `copy shortening to elapsed expires through refreshed callback exactly once`() {
        val state = FeedbackCenterQueueState(0)
        val longCopy = "Long feedback copy ".repeat(30)
        state.reconcile(listOf(item("one", message = longCopy), item("two", timeout = 22)), 0)
        assertNull(state.advanceBy(5_000, "one"))

        assertEquals(
            FeedbackCenterWireEvent.Press(112, 1),
            state.reconcile(listOf(item("one", timeout = 112), item("two", timeout = 22)), 5_000),
        )
        assertEquals("two", state.visible?.feedbackId)
        assertNull(state.reconcile(listOf(item("one", timeout = 112), item("two", timeout = 22)), 5_000))
    }

    @Test
    fun `policy shortening while paused expires once on resume`() {
        val state = FeedbackCenterQueueState(0) { item ->
            FeedbackCenterTiming.automaticBaseMillis(item.message, item.hasAction) * 2
        }
        state.reconcile(listOf(item("one"), item("two", timeout = 22)), 0)
        assertNull(state.pause(5_000))
        assertNull(
            state.setTimingPolicy(
                { item -> FeedbackCenterTiming.automaticBaseMillis(item.message, item.hasAction) },
                100_000,
            ),
        )
        assertEquals(5_000, state.elapsedMillis)
        assertEquals(FeedbackCenterWireEvent.Press(12, 1), state.resume(200_000))
        assertNull(state.resume(201_000))
    }

    @Test
    fun `empty center resets timing and releases the completed id for a later epoch`() {
        val state = FeedbackCenterQueueState(0)
        state.reconcile(listOf(item("one")), 0)
        assertEquals(FeedbackCenterWireEvent.Press(12, 1), state.timeout("one", 4_000))

        assertNull(state.reconcile(emptyList(), 4_001))
        assertNull(state.visible)
        assertEquals(emptyList<String>(), state.pendingIds)
        assertEquals(0, state.elapsedMillis)

        state.reconcile(listOf(item("one")), 4_002)
        assertEquals("one", state.visible?.feedbackId)
    }

    @Test
    fun `automatic base duration has a minimum cap and action extension`() {
        val short = FeedbackCenterTiming.automaticBaseMillis("Saved", hasAction = false)
        val long = FeedbackCenterTiming.automaticBaseMillis("Long feedback copy ".repeat(30), hasAction = false)
        val action = FeedbackCenterTiming.automaticBaseMillis("Saved", hasAction = true)

        assertEquals(4_000, short)
        assertEquals(10_000, long)
        assertTrue(action > short)
    }

    @Test
    fun `completion hands the clock to the next visible item`() {
        val state = FeedbackCenterQueueState(0)
        state.reconcile(listOf(item("one", action = 51), item("two")), 0)

        assertEquals(FeedbackCenterWireEvent.Press(51, 1), state.action("one", 100_000))
        assertNull(state.pause(101_000))
        assertEquals("two", state.visible?.feedbackId)
        assertEquals(1_000, state.elapsedMillis)
    }

    @Test
    fun `timing extension preserves elapsed time`() {
        val state = FeedbackCenterQueueState(0)
        state.reconcile(listOf(item("one")), 0)

        assertNull(
            state.setTimingPolicy(
                { item -> FeedbackCenterTiming.automaticBaseMillis(item.message, item.hasAction) * 2 },
                2_000,
            ),
        )
        assertEquals(2_000, state.elapsedMillis)
        assertEquals(6_000, state.remainingMillis)
    }

    private fun item(
        id: String,
        message: String = "Message",
        tone: String = "default",
        hold: Boolean = false,
        action: Int = 0,
        timeout: Int = 12,
        manual: Int = 13,
    ) = FeedbackCenterItemConfiguration(
        itemNode(
            id = id,
            message = message,
            tone = tone,
            hold = hold,
            actionLabel = action.takeIf { it != 0 }?.let { "Undo" },
            action = action,
            timeout = timeout,
            manual = manual,
        ),
    )

    private fun itemNode(
        id: String,
        message: String = "Message",
        tone: String = "default",
        hold: Boolean = false,
        actionLabel: String? = null,
        action: Int = 0,
        timeout: Int = 12,
        manual: Int = 13,
    ): NativeUINode {
        val props = mutableMapOf<String, Any>(
            "feedback_id" to id,
            "message" to message,
            "tone" to tone,
            "hold" to hold,
            "on_timeout" to timeout,
            "on_manual" to manual,
        )
        actionLabel?.let { props["action_label"] = it }
        if (action != 0) props["on_action"] = action

        return NativeUINode(
            id = 1,
            props = GenericProps(props),
            onPress = 0,
            children = emptyList(),
        )
    }
}

@OptIn(ExperimentalTestApi::class)
@RunWith(RobolectricTestRunner::class)
class FeedbackCenterSemanticsTest {
    @Test
    fun `production host owns exactly one id keyed live region and keeps same id announcement stable`() = runComposeUiTest {
        val events = mutableListOf<Pair<Int, Int>>()
        var now = 0L
        val runtime = FeedbackCenterHostRuntime(
            initialNowMillis = now,
            recommendTimeoutMillis = { base, _ -> base },
            sendPress = { callback, node -> events += callback to node },
            nowMillis = { now },
        )
        var center by mutableStateOf(centerNode(
            feedbackNode(id = "one", nodeId = 1, message = "Original message", action = 51),
            feedbackNode(id = "two", nodeId = 2, message = "Second message", hold = true, timeout = 0, manual = 62),
        ))
        setContent {
            MaterialTheme {
                FirstlightFeedbackCenterHost(centerNode = center, runtime = runtime) { Text("Screen") }
            }
        }

        val liveRegion = androidx.compose.ui.test.SemanticsMatcher.keyIsDefined(SemanticsProperties.LiveRegion)
        onAllNodes(liveRegion).assertCountEquals(1)
        onNode(liveRegion).assert(
            androidx.compose.ui.test.SemanticsMatcher.expectValue(
                SemanticsProperties.LiveRegion,
                LiveRegionMode.Polite,
            ),
        )
        onNodeWithText("Original message").assertIsDisplayed()
        onNode(hasTestTag("firstlight-feedback-tone-success"), useUnmergedTree = true).assertIsDisplayed()
        onNodeWithText("Undo").assertIsDisplayed().assertHasClickAction().assertWidthIsAtLeast(48.dp).assertHeightIsAtLeast(48.dp)
        onNodeWithContentDescription("Feedback tone").assertDoesNotExist()

        runOnIdle {
            center = centerNode(
                feedbackNode(id = "one", nodeId = 11, message = "Updated visible copy", tone = "danger", action = 151, manual = 161),
                feedbackNode(id = "two", nodeId = 2, message = "Second message", hold = true, timeout = 0, manual = 62),
            )
        }
        onAllNodes(liveRegion).assertCountEquals(1)
        onNodeWithText("Original message").assertIsDisplayed()
        onNodeWithText("Updated visible copy", useUnmergedTree = true).assertIsDisplayed()
        onNode(hasTestTag("firstlight-feedback-tone-danger"), useUnmergedTree = true).assertIsDisplayed()
        onNode(hasTestTag("firstlight-feedback-tone-success"), useUnmergedTree = true).assertDoesNotExist()
        runOnIdle {
            assertEquals("Updated visible copy", runtime.visible?.message)
            assertEquals(FeedbackCenterTone.Danger, runtime.visible?.tone)
            assertEquals(FeedbackCenterToneColorRole.ErrorContainer, FeedbackCenterRenderingPolicy(runtime.visible!!.tone).colorRole)
            assertEquals(151, runtime.visible?.actionCallback)
            assertEquals(161, runtime.visible?.manualCallback)
        }

        onNodeWithText("Undo").performClick()
        runOnIdle { assertEquals(listOf(151 to 11), events) }
        onAllNodes(liveRegion).assertCountEquals(1)
        onNodeWithText("Second message").assertIsDisplayed()

        onNode(androidx.compose.ui.test.SemanticsMatcher.keyIsDefined(SemanticsActions.Dismiss))
            .performSemanticsAction(SemanticsActions.Dismiss)
        runOnIdle { assertEquals(listOf(151 to 11, 62 to 2), events) }
        onAllNodes(liveRegion).assertCountEquals(0)
    }

    @Test
    fun `host semantics dismiss completes held id once and stale result cannot affect next item`() = runComposeUiTest {
        val events = mutableListOf<Pair<Int, Int>>()
        val runtime = FeedbackCenterHostRuntime(
            initialNowMillis = 0,
            recommendTimeoutMillis = { base, _ -> base },
            sendPress = { callback, node -> events += callback to node },
            nowMillis = { 0 },
        )
        var center by mutableStateOf(centerNode(
            feedbackNode(id = "one", nodeId = 1, hold = true, timeout = 0, manual = 61),
            feedbackNode(id = "two", nodeId = 2, hold = true, timeout = 0, manual = 62),
        ))
        setContent {
            MaterialTheme {
                FirstlightFeedbackCenterHost(centerNode = center, runtime = runtime) { Text("Screen") }
            }
        }

        val dismiss = androidx.compose.ui.test.SemanticsMatcher.keyIsDefined(SemanticsActions.Dismiss)
        onAllNodes(dismiss).assertCountEquals(1)
        onNode(dismiss).performSemanticsAction(SemanticsActions.Dismiss)
        runOnIdle {
            assertEquals(listOf(61 to 1), events)
            assertEquals("two", runtime.visible?.feedbackId)
            runtime.snackbarDismissed("one")
            runtime.snackbarDismissed("one")
            assertEquals(listOf(61 to 1), events)
            assertEquals("two", runtime.visible?.feedbackId)
            center = centerNode(
                feedbackNode(id = "one", nodeId = 11, hold = true, timeout = 0, manual = 161),
                feedbackNode(id = "two", nodeId = 2, hold = true, timeout = 0, manual = 62),
            )
        }
        runOnIdle {
            assertEquals("two", runtime.visible?.feedbackId)
            assertEquals(emptyList<String>(), runtime.pendingIds)
        }
    }

    @Test
    fun `host dismiss semantics enumerate and route automatic actionable held and empty states`() = runComposeUiTest {
        val events = mutableListOf<Pair<Int, Int>>()
        val runtime = FeedbackCenterHostRuntime(
            initialNowMillis = 0,
            recommendTimeoutMillis = { _, _ -> 100_000 },
            sendPress = { callback, node -> events += callback to node },
            nowMillis = { 0 },
        )
        var center by mutableStateOf(centerNode(
            feedbackNode(id = "automatic", nodeId = 3, timeout = 73, manual = 63),
            feedbackNode(id = "actionable", nodeId = 4, action = 54, timeout = 74, manual = 64),
            feedbackNode(id = "held", nodeId = 5, hold = true, timeout = 0, manual = 65),
        ))
        setContent {
            MaterialTheme {
                FirstlightFeedbackCenterHost(centerNode = center, runtime = runtime) { Text("Screen") }
            }
        }

        val dismiss = androidx.compose.ui.test.SemanticsMatcher.keyIsDefined(SemanticsActions.Dismiss)
        val liveRegion = androidx.compose.ui.test.SemanticsMatcher.keyIsDefined(SemanticsProperties.LiveRegion)
        onAllNodes(dismiss).assertCountEquals(1)
        onAllNodes(liveRegion).assertCountEquals(1)
        onNodeWithText("Undo").assertDoesNotExist()
        onNodeWithContentDescription("Dismiss feedback").assertDoesNotExist()

        runOnIdle {
            center = centerNode(
                feedbackNode(id = "automatic", nodeId = 13, timeout = 173, manual = 163),
                feedbackNode(id = "actionable", nodeId = 4, action = 54, timeout = 74, manual = 64),
                feedbackNode(id = "held", nodeId = 5, hold = true, timeout = 0, manual = 65),
            )
        }
        onNode(dismiss).performSemanticsAction(SemanticsActions.Dismiss)
        waitForIdle()
        onAllNodes(dismiss).assertCountEquals(1)
        onAllNodes(liveRegion).assertCountEquals(1)
        runOnIdle {
            assertEquals(listOf(163 to 13), events)
            assertEquals("actionable", runtime.visible?.feedbackId)
            runtime.snackbarDismissed("automatic")
            runtime.snackbarDismissed("automatic")
            assertEquals(listOf(163 to 13), events)
        }

        onNodeWithText("Undo").assertIsDisplayed()
        onNodeWithContentDescription("Dismiss feedback").assertDoesNotExist()
        runOnIdle {
            center = centerNode(
                feedbackNode(id = "automatic", nodeId = 13, timeout = 173, manual = 163),
                feedbackNode(id = "actionable", nodeId = 14, action = 154, timeout = 174, manual = 164),
                feedbackNode(id = "held", nodeId = 5, hold = true, timeout = 0, manual = 65),
            )
        }
        onNode(dismiss).performSemanticsAction(SemanticsActions.Dismiss)
        waitForIdle()
        onAllNodes(dismiss).assertCountEquals(1)
        onAllNodes(liveRegion).assertCountEquals(1)
        runOnIdle {
            assertEquals(listOf(163 to 13, 164 to 14), events)
            assertEquals("held", runtime.visible?.feedbackId)
            runtime.snackbarDismissed("actionable")
            assertEquals(listOf(163 to 13, 164 to 14), events)
        }

        onNodeWithContentDescription("Dismiss feedback").assertIsDisplayed()
        onNode(dismiss).performSemanticsAction(SemanticsActions.Dismiss)
        waitForIdle()
        onAllNodes(dismiss).assertCountEquals(0)
        onAllNodes(liveRegion).assertCountEquals(0)
        onNodeWithContentDescription("Dismiss feedback").assertDoesNotExist()
        runOnIdle {
            assertEquals(listOf(163 to 13, 164 to 14, 65 to 5), events)
            assertNull(runtime.visible)
            runtime.snackbarDismissed("held")
            assertEquals(listOf(163 to 13, 164 to 14, 65 to 5), events)
        }
    }

    @Test
    fun `programmatic reconciliation cancels snackbar lifecycle without a dismiss callback`() = runComposeUiTest {
        val events = mutableListOf<Pair<Int, Int>>()
        val runtime = FeedbackCenterHostRuntime(
            initialNowMillis = 0,
            recommendTimeoutMillis = { base, _ -> base },
            sendPress = { callback, node -> events += callback to node },
            nowMillis = { 0 },
        )
        var center by mutableStateOf(centerNode(
            feedbackNode(id = "programmatic", nodeId = 7, hold = true, timeout = 0, manual = 67),
        ))
        val liveRegion = androidx.compose.ui.test.SemanticsMatcher.keyIsDefined(SemanticsProperties.LiveRegion)
        setContent {
            MaterialTheme {
                FirstlightFeedbackCenterHost(centerNode = center, runtime = runtime) { Text("Screen") }
            }
        }

        onAllNodes(liveRegion).assertCountEquals(1)
        runOnIdle { center = centerNode() }
        onAllNodes(liveRegion).assertCountEquals(0)
        runOnIdle {
            assertNull(runtime.visible)
            assertEquals(emptyList<Pair<Int, Int>>(), events)
        }
    }

    @Test
    fun `production action explicit dismiss and focus route through one runtime`() = runComposeUiTest {
        mainClock.autoAdvance = false
        val events = mutableListOf<Pair<Int, Int>>()
        var now = 0L
        val runtime = FeedbackCenterHostRuntime(
            initialNowMillis = now,
            recommendTimeoutMillis = { _, flags ->
                if (flags and AccessibilityManager.FLAG_CONTENT_CONTROLS != 0) 100_000 else 100
            },
            sendPress = { callback, node -> events += callback to node },
            nowMillis = { now },
        )
        val owner = TestFeedbackLifecycleOwner(Lifecycle.State.RESUMED)
        var center by mutableStateOf(centerNode(
            feedbackNode(id = "action", nodeId = 1, action = 51),
            feedbackNode(id = "held", nodeId = 2, hold = true, timeout = 0, manual = 62),
        ))
        setContent {
            CompositionLocalProvider(LocalLifecycleOwner provides owner) {
                MaterialTheme {
                    FirstlightFeedbackCenterHost(centerNode = center, runtime = runtime) {
                        Button(onClick = {}, modifier = Modifier.testTag("outside-focus")) { Text("Outside") }
                    }
                }
            }
        }

        mainClock.advanceTimeBy(1_000)
        onNodeWithText("Undo").performSemanticsAction(SemanticsActions.RequestFocus)
        mainClock.advanceTimeByFrame()
        runOnIdle { assertTrue(runtime.isPaused) }
        onNode(hasTestTag("outside-focus")).performSemanticsAction(SemanticsActions.RequestFocus)
        mainClock.advanceTimeByFrame()
        runOnIdle { assertFalse(runtime.isPaused) }
        onNodeWithText("Undo").performClick()
        mainClock.advanceTimeBy(1_000)
        runOnIdle { assertEquals(listOf(51 to 1), events) }

        onNodeWithContentDescription("Dismiss feedback")
            .assertHasClickAction()
            .assertWidthIsAtLeast(48.dp)
            .assertHeightIsAtLeast(48.dp)
            .performSemanticsAction(SemanticsActions.RequestFocus)
        mainClock.advanceTimeByFrame()
        runOnIdle { assertTrue(runtime.isPaused) }
        onNode(hasTestTag("outside-focus")).performSemanticsAction(SemanticsActions.RequestFocus)
        mainClock.advanceTimeByFrame()
        runOnIdle { assertFalse(runtime.isPaused) }
        onNodeWithContentDescription("Dismiss feedback").performClick()
        mainClock.advanceTimeBy(1_000)
        runOnIdle {
            assertEquals(listOf(51 to 1, 62 to 2), events)
            runtime.snackbarDismissed("held")
            assertEquals(listOf(51 to 1, 62 to 2), events)
            assertNull(runtime.visible)
        }
    }

    @Test
    fun `production timeout advances once and stale timer cannot affect next item`() = runComposeUiTest {
        mainClock.autoAdvance = false
        val events = mutableListOf<Pair<Int, Int>>()
        var now = 0L
        val runtime = FeedbackCenterHostRuntime(
            initialNowMillis = now,
            recommendTimeoutMillis = { _, _ -> 1_000 },
            sendPress = { callback, node -> events += callback to node },
            nowMillis = { now },
        )
        setContent {
            MaterialTheme {
                FirstlightFeedbackCenterHost(
                    centerNode = centerNode(
                        feedbackNode(id = "timeout", nodeId = 3, timeout = 73),
                        feedbackNode(id = "next", nodeId = 4, hold = true, timeout = 0, manual = 84),
                    ),
                    runtime = runtime,
                ) { Text("Screen") }
            }
        }

        repeat(10) { mainClock.advanceTimeByFrame() }
        runOnIdle { assertEquals("timeout", runtime.visible?.feedbackId) }
        now = 1_000
        mainClock.advanceTimeBy(1_000)
        runOnIdle {
            assertEquals(listOf(73 to 3), events)
            assertEquals("next", runtime.visible?.feedbackId)
            runtime.timeout("timeout")
            assertEquals(listOf(73 to 3), events)
            assertEquals("next", runtime.visible?.feedbackId)
        }
    }

    @Test
    fun `production lifecycle below resumed pauses and resumed excludes background time`() = runComposeUiTest {
        mainClock.autoAdvance = false
        var now = 0L
        val runtime = FeedbackCenterHostRuntime(
            initialNowMillis = now,
            recommendTimeoutMillis = { _, _ -> 100_000 },
            sendPress = { _, _ -> },
            nowMillis = { now },
        )
        val owner = TestFeedbackLifecycleOwner(Lifecycle.State.CREATED)
        setContent {
            CompositionLocalProvider(LocalLifecycleOwner provides owner) {
                MaterialTheme {
                    FirstlightFeedbackCenterHost(
                        centerNode = centerNode(feedbackNode(id = "lifecycle", timeout = 73)),
                        runtime = runtime,
                    ) { Text("Screen") }
                }
            }
        }

        mainClock.advanceTimeBy(1_000)
        runOnIdle {
            assertTrue(runtime.isPaused)
            assertEquals(0, runtime.elapsedMillis)
        }

        now = 1_000
        runOnIdle { owner.moveTo(Lifecycle.State.RESUMED) }
        mainClock.advanceTimeBy(1_000)
        runOnIdle { assertFalse(runtime.isPaused) }

        now = 1_100
        runOnIdle { owner.moveTo(Lifecycle.State.CREATED) }
        mainClock.advanceTimeBy(1_000)
        runOnIdle {
            assertTrue(runtime.isPaused)
            assertEquals(100, runtime.elapsedMillis)
        }

        now = 2_000
        runOnIdle { owner.moveTo(Lifecycle.State.RESUMED) }
        mainClock.advanceTimeBy(1_000)
        runOnIdle {
            assertFalse(runtime.isPaused)
            assertEquals(100, runtime.elapsedMillis)
        }
    }

    @Test
    fun `replacing lifecycle owner disposes old observer and follows only the new owner`() = runComposeUiTest {
        mainClock.autoAdvance = false
        val runtime = FeedbackCenterHostRuntime(0, { _, _ -> 100_000 }, { _, _ -> }, { 0 })
        val oldOwner = TestFeedbackLifecycleOwner(Lifecycle.State.RESUMED)
        val newOwner = TestFeedbackLifecycleOwner(Lifecycle.State.RESUMED)
        var owner by mutableStateOf<LifecycleOwner>(oldOwner)
        setContent {
            CompositionLocalProvider(LocalLifecycleOwner provides owner) {
                MaterialTheme {
                    FirstlightFeedbackCenterHost(
                        centerNode(feedbackNode(id = "lifecycle", timeout = 73, manual = 63)),
                        runtime,
                    ) { Text("Screen") }
                }
            }
        }

        mainClock.advanceTimeByFrame()
        runOnIdle {
            assertFalse(runtime.isPaused)
            owner = newOwner
        }
        mainClock.advanceTimeByFrame()

        runOnIdle { oldOwner.moveTo(Lifecycle.State.CREATED) }
        mainClock.advanceTimeByFrame()
        runOnIdle { assertFalse(runtime.isPaused) }

        runOnIdle { newOwner.moveTo(Lifecycle.State.CREATED) }
        mainClock.advanceTimeByFrame()
        runOnIdle { assertTrue(runtime.isPaused) }

        runOnIdle { oldOwner.moveTo(Lifecycle.State.RESUMED) }
        mainClock.advanceTimeByFrame()
        runOnIdle { assertTrue(runtime.isPaused) }

        runOnIdle { newOwner.moveTo(Lifecycle.State.RESUMED) }
        mainClock.advanceTimeByFrame()
        runOnIdle { assertFalse(runtime.isPaused) }
    }

    @Test
    fun `passive automatic item does not publish inert explicit controls`() = runComposeUiTest {
        val runtime = FeedbackCenterHostRuntime(0, { base, _ -> base }, { _, _ -> }, { 0 })
        setContent {
            MaterialTheme {
                FirstlightFeedbackCenterHost(centerNode(feedbackNode(action = 0)), runtime) { Text("Screen") }
            }
        }

        onNodeWithText("Undo").assertDoesNotExist()
        onNodeWithContentDescription("Dismiss feedback").assertDoesNotExist()
    }

    @Test
    fun `rendering policy uses semantic tone and reflows constrained or scaled layouts`() {
        assertEquals(FeedbackCenterToneColorRole.InverseSurface, FeedbackCenterRenderingPolicy(FeedbackCenterTone.Default).colorRole)
        assertEquals(FeedbackCenterToneColorRole.TertiaryContainer, FeedbackCenterRenderingPolicy(FeedbackCenterTone.Success).colorRole)
        assertEquals(FeedbackCenterToneColorRole.SecondaryContainer, FeedbackCenterRenderingPolicy(FeedbackCenterTone.Warning).colorRole)
        assertEquals(FeedbackCenterToneColorRole.ErrorContainer, FeedbackCenterRenderingPolicy(FeedbackCenterTone.Danger).colorRole)
        assertTrue(FeedbackCenterRenderingPolicy.actionOnNewLine(maxWidthDp = 360, fontScale = 1f))
        assertTrue(FeedbackCenterRenderingPolicy.actionOnNewLine(maxWidthDp = 600, fontScale = 2f))
        assertFalse(FeedbackCenterRenderingPolicy.actionOnNewLine(maxWidthDp = 600, fontScale = 1f))
    }

}

private class TestFeedbackLifecycleOwner(initialState: Lifecycle.State) : LifecycleOwner {
    private val registry = LifecycleRegistry(this)
    override val lifecycle: Lifecycle get() = registry

    init { registry.currentState = initialState }

    fun moveTo(state: Lifecycle.State) {
        registry.currentState = state
    }
}

class FeedbackCenterAnnouncementStateTest {
    @Test
    fun `announcement is consumed once per newly visible semantic id`() {
        val announcements = FeedbackCenterAnnouncementState()

        assertTrue(announcements.consume("one"))
        assertFalse(announcements.consume("one"))
        assertTrue(announcements.consume("two"))
        assertFalse(announcements.consume("two"))
        assertFalse(announcements.consume(null))
        assertTrue(announcements.consume("one"))
    }
}

@OptIn(ExperimentalTestApi::class)
@RunWith(RobolectricTestRunner::class)
class FeedbackCenterInitContractTest {
    @Test
    fun `official init registers exact consumed publication and wraps matching center`() = runComposeUiTest {
        NativeRootHostRegistry.clearForTests()
        registerFirstlightUI(ApplicationProvider.getApplicationContext())

        assertTrue(NativeRootHostRegistry.consumes("firstlight_feedback_center"))
        val registration = NativeRootHostRegistry.registration("firstlight.feedback-center")
        assertEquals("firstlight_feedback_center", registration?.consumes)

        val center = NativeUINode(
            id = 10,
            type = "firstlight_feedback_center",
            props = GenericProps(),
            children = listOf(feedbackNode()),
        )
        val root = NativeUINode(id = 99, type = "root", props = GenericProps(), children = listOf(center))
        setContent {
            MaterialTheme {
                registration!!.host(root) { Text("Screen content") }
            }
        }

        onNodeWithText("Screen content").assertIsDisplayed()
        onNodeWithText("Appointment saved").assertIsDisplayed()
    }
}

@RunWith(RobolectricTestRunner::class)
@Config(sdk = [29])
class FeedbackCenterAndroidApi29Test {
    @Test
    fun `api 29 accessibility timeout compiles and uses text icon and control flags`() {
        val context = ApplicationProvider.getApplicationContext<Context>()
        val manager = context.getSystemService(Context.ACCESSIBILITY_SERVICE) as AccessibilityManager
        val passive = FeedbackCenterItemConfiguration(feedbackNode(action = 0))
        val actionable = FeedbackCenterItemConfiguration(feedbackNode(action = 51))

        assertEquals(
            AccessibilityManager.FLAG_CONTENT_TEXT or AccessibilityManager.FLAG_CONTENT_ICONS,
            feedbackCenterContentFlags(passive),
        )
        assertEquals(
            AccessibilityManager.FLAG_CONTENT_TEXT or AccessibilityManager.FLAG_CONTENT_ICONS or AccessibilityManager.FLAG_CONTENT_CONTROLS,
            feedbackCenterContentFlags(actionable),
        )
        assertTrue(androidFeedbackTimeoutPolicy(manager)(actionable) >= FeedbackCenterTiming.MinimumDurationMillis)
    }

    @Test
    fun `injectable production timeout adapter receives exact base and content flags and returns duration`() {
        var capturedBase = 0
        var capturedFlags = 0
        val actionable = FeedbackCenterItemConfiguration(feedbackNode(action = 51, message = "Saved"))
        val policy = androidFeedbackTimeoutPolicy { base, flags ->
            capturedBase = base
            capturedFlags = flags
            12_345
        }

        assertEquals(12_345, policy(actionable))
        assertEquals(FeedbackCenterTiming.automaticBaseMillis("Saved", hasAction = true).toInt(), capturedBase)
        assertEquals(
            AccessibilityManager.FLAG_CONTENT_TEXT or AccessibilityManager.FLAG_CONTENT_ICONS or AccessibilityManager.FLAG_CONTENT_CONTROLS,
            capturedFlags,
        )
    }
}

class FeedbackCenterPaparazziCases {
    @get:Rule val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test fun lightDefault() = snapshot(FeedbackCenterTone.Default, dark = false)
    @Test fun darkDefault() = snapshot(FeedbackCenterTone.Default, dark = true)
    @Test fun success() = snapshot(FeedbackCenterTone.Success)
    @Test fun warning() = snapshot(FeedbackCenterTone.Warning)
    @Test fun danger() = snapshot(FeedbackCenterTone.Danger)
    @Test fun action() = snapshot(FeedbackCenterTone.Success, action = 51)
    @Test fun hold() = snapshot(FeedbackCenterTone.Warning, hold = true)
    @Test fun longCopy() = snapshot(FeedbackCenterTone.Default, message = "A deliberately long feedback message that wraps without truncating at larger text sizes and constrained widths.")

    private fun snapshot(
        tone: FeedbackCenterTone,
        dark: Boolean = false,
        action: Int = 0,
        hold: Boolean = false,
        message: String = "Appointment saved",
    ) {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
                Surface(Modifier.fillMaxSize()) {
                    Box {
                        FirstlightFeedbackCenterControl(
                            configuration = FeedbackCenterItemConfiguration(
                                feedbackNode(hold, action, if (hold) 0 else 12, 13, tone.wireName, message),
                            ),
                            actionOnNewLine = action != 0 && message.length > 60,
                            onAction = {},
                            onDismiss = {},
                            onFocusChanged = {},
                        )
                    }
                }
            }
        }
    }
}

class FeedbackCenterFontScalePaparazziCase {
    @get:Rule val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2f))

    @Test fun fontScaleTwo() {
        paparazzi.snapshot {
            MaterialTheme {
                FirstlightFeedbackCenterControl(
                    FeedbackCenterItemConfiguration(feedbackNode(action = 51)),
                    actionOnNewLine = true,
                    onAction = {},
                    onDismiss = {},
                    onFocusChanged = {},
                )
            }
        }
    }
}

class FeedbackCenterRtlPaparazziCase {
    @get:Rule val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test fun rtl() {
        paparazzi.snapshot {
            CompositionLocalProvider(LocalLayoutDirection provides LayoutDirection.Rtl) {
                MaterialTheme {
                    FirstlightFeedbackCenterControl(
                        FeedbackCenterItemConfiguration(feedbackNode(action = 51)),
                        actionOnNewLine = false,
                        onAction = {},
                        onDismiss = {},
                        onFocusChanged = {},
                    )
                }
            }
        }
    }
}

private fun feedbackNode(
    hold: Boolean = false,
    action: Int = 0,
    timeout: Int = 12,
    manual: Int = 13,
    tone: String = "success",
    message: String = "Appointment saved",
    id: String = "feedback-one",
    nodeId: Int = 1,
): NativeUINode {
    val props = mutableMapOf<String, Any>(
        "feedback_id" to id,
        "message" to message,
        "tone" to tone,
        "hold" to hold,
        "on_timeout" to timeout,
        "on_manual" to manual,
    )
    if (action != 0) {
        props["action_label"] = "Undo"
        props["on_action"] = action
    }
    return NativeUINode(id = nodeId, type = "firstlight.feedback-item", props = GenericProps(props))
}

private fun centerNode(vararg items: NativeUINode): NativeUINode = NativeUINode(
    id = 10,
    type = "firstlight_feedback_center",
    props = GenericProps(),
    children = items.toList(),
)
