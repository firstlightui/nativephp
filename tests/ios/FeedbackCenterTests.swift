import XCTest

@testable import FirstlightIOSControls

final class FeedbackCenterTests: XCTestCase {
    func testReconcilePreservesFIFOAndRefreshesCallbacksWithoutRestarting() {
        var state = FeedbackCenterQueueState(now: 100)
        state.reconcile([item(id: "one", action: 11), item(id: "two", timeout: 22)], now: 100)
        state.advance(by: 1)

        state.reconcile([item(id: "one", action: 101), item(id: "two", timeout: 202)], now: 101)

        XCTAssertEqual(state.visible?.feedbackID, "one")
        XCTAssertEqual(state.visible?.actionCallback, 101)
        XCTAssertEqual(state.pendingIDs, ["two"])
        XCTAssertEqual(state.elapsed, 1)
    }

    func testCompletionTombstoneBlocksStaleFramesUntilAbsence() {
        var state = FeedbackCenterQueueState(now: 0)
        state.reconcile([item(id: "one"), item(id: "two")], now: 0)
        XCTAssertEqual(state.timeout(), .press(callbackID: 12, nodeID: 1))
        XCTAssertEqual(state.visible?.feedbackID, "two")

        state.reconcile([item(id: "one"), item(id: "two")], now: 1)
        XCTAssertEqual(state.pendingIDs, [])
        state.reconcile([item(id: "two")], now: 2)
        state.reconcile([item(id: "one"), item(id: "two")], now: 3)
        XCTAssertEqual(state.pendingIDs, ["one"])
    }

    func testCopyAndToneUpdateInPlaceWithoutChangingElapsedTime() {
        var state = FeedbackCenterQueueState(now: 10)
        state.reconcile([item(id: "one", message: "Saved", tone: "default")], now: 10)
        state.advance(by: 2)

        state.reconcile([item(id: "one", message: "Appointment saved", tone: "success")], now: 12)

        XCTAssertEqual(state.visible?.message, "Appointment saved")
        XCTAssertEqual(state.visible?.tone, .success)
        XCTAssertEqual(state.elapsed, 2)
    }

    func testProgrammaticAbsenceAdvancesWithoutEmittingAnEvent() {
        var state = FeedbackCenterQueueState(now: 0)
        state.reconcile([item(id: "one"), item(id: "two")], now: 0)

        state.reconcile([item(id: "two")], now: 1)

        XCTAssertEqual(state.visible?.feedbackID, "two")
        XCTAssertEqual(state.elapsed, 0)
        XCTAssertEqual(state.pendingIDs, [])
    }

    func testActionCompletesOnceAndUsesTheActionCallback() {
        var state = FeedbackCenterQueueState(now: 0)
        state.reconcile([item(id: "one", action: 51)], now: 0)

        XCTAssertEqual(state.action(), .press(callbackID: 51, nodeID: 1))
        XCTAssertNil(state.action())
        XCTAssertNil(state.visible)
    }

    func testHeldAndAutomaticItemsUseOnlyTheirEligibleDismissal() {
        var held = FeedbackCenterQueueState(now: 0)
        held.reconcile([item(id: "held", hold: true, timeout: 0, manual: 71)], now: 0)
        XCTAssertNil(held.advance(by: 100))
        XCTAssertNil(held.timeout())
        XCTAssertEqual(held.manualDismiss(), .press(callbackID: 71, nodeID: 1))
        XCTAssertNil(held.manualDismiss())

        var automatic = FeedbackCenterQueueState(now: 0)
        automatic.reconcile([item(id: "automatic", timeout: 72)], now: 0)
        XCTAssertNil(automatic.manualDismiss())
        XCTAssertEqual(
            automatic.advance(by: automatic.remaining),
            .press(callbackID: 72, nodeID: 1)
        )
        XCTAssertNil(automatic.timeout())
    }

    func testBackgroundPauseAndResumeExcludeSuspendedTime() {
        var state = FeedbackCenterQueueState(now: 100)
        state.reconcile([item(id: "one")], now: 100)
        state.pause(at: 101)

        XCTAssertTrue(state.isPaused)
        XCTAssertEqual(state.elapsed, 1)

        state.resume(at: 111)
        state.pause(at: 112)

        XCTAssertEqual(state.elapsed, 2)
        XCTAssertEqual(state.remaining, state.visibleDuration - 2, accuracy: 0.001)
    }

    func testMalformedOrMissingCallbacksFailClosed() {
        var state = FeedbackCenterQueueState(now: 0)
        state.reconcile([
            item(id: "automatic", timeout: 0),
            item(id: "held", hold: true, timeout: 0, manual: 0),
        ], now: 0)
        XCTAssertNil(state.visible)

        let partialAction = FeedbackCenterItemConfiguration(node: itemNode(
            id: "partial",
            actionLabel: "Undo",
            action: 0
        ))
        XCTAssertNil(partialAction.actionLabel)
        XCTAssertNil(partialAction.actionCallback)
        XCTAssertFalse(item(id: " ").isEligible)
        XCTAssertFalse(item(id: "blank-message", message: " ").isEligible)
        XCTAssertFalse(item(id: "bad-tone", tone: "critical").isEligible)

        state.reconcile([item(id: "duplicate"), item(id: "duplicate")], now: 1)
        XCTAssertNil(state.visible)
    }

    func testEmptyCenterResetsQueueTombstonesAndTiming() {
        var state = FeedbackCenterQueueState(now: 0)
        state.reconcile([item(id: "one")], now: 0)
        XCTAssertNotNil(state.timeout())

        state.reconcile([], now: 1)
        XCTAssertNil(state.visible)
        XCTAssertEqual(state.pendingIDs, [])
        XCTAssertEqual(state.elapsed, 0)

        state.reconcile([item(id: "one")], now: 2)
        XCTAssertEqual(state.visible?.feedbackID, "one")
    }

    func testAutomaticDurationPolicyHasMinimumCapActionAndAccessibleExtension() {
        let short = FeedbackCenterTiming.automaticDuration(
            message: "Saved",
            hasAction: false,
            assistiveTechnologyActive: false
        )
        let long = FeedbackCenterTiming.automaticDuration(
            message: String(repeating: "Long feedback copy ", count: 30),
            hasAction: false,
            assistiveTechnologyActive: false
        )
        let action = FeedbackCenterTiming.automaticDuration(
            message: "Saved",
            hasAction: true,
            assistiveTechnologyActive: false
        )
        let accessible = FeedbackCenterTiming.automaticDuration(
            message: "Saved",
            hasAction: false,
            assistiveTechnologyActive: true
        )

        XCTAssertEqual(short, 4)
        XCTAssertEqual(long, 10)
        XCTAssertGreaterThan(action, short)
        XCTAssertGreaterThan(accessible, short)
    }

    private func item(
        id: String,
        message: String = "Message",
        tone: String = "default",
        hold: Bool = false,
        action: Int = 0,
        timeout: Int = 12,
        manual: Int = 13
    ) -> FeedbackCenterItemConfiguration {
        FeedbackCenterItemConfiguration(node: itemNode(
            id: id,
            message: message,
            tone: tone,
            hold: hold,
            actionLabel: action == 0 ? nil : "Undo",
            action: action,
            timeout: timeout,
            manual: manual
        ))
    }

    private func itemNode(
        id: String,
        message: String = "Message",
        tone: String = "default",
        hold: Bool = false,
        actionLabel: String? = nil,
        action: Int = 0,
        timeout: Int = 12,
        manual: Int = 13
    ) -> NativeUINode {
        var props: [String: Any] = [
            "feedback_id": id,
            "message": message,
            "tone": tone,
            "hold": hold,
            "on_timeout": timeout,
            "on_manual": manual,
        ]
        if let actionLabel { props["action_label"] = actionLabel }
        if action != 0 { props["on_action"] = action }

        return NativeUINode(
            id: 1,
            type: "firstlight.feedback-item",
            layout: nil,
            style: nil,
            props: GenericProps(props),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
    }
}
