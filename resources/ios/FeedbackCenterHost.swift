import Accessibility
import SwiftUI

private struct FeedbackCenterSchedule: Equatable {
    let feedbackID: String?
    let suspended: Bool
    let held: Bool
    let duration: TimeInterval
}

struct FirstlightFeedbackCenterHost<Content: View>: View {
    let centerNode: NativeUINode?
    let content: Content

    @Environment(\.scenePhase) private var scenePhase
    @Environment(\.accessibilityReduceMotion) private var reduceMotion
    @Environment(\.accessibilityVoiceOverEnabled) private var voiceOverEnabled
    @Environment(\.accessibilitySwitchControlEnabled) private var switchControlEnabled
    @Environment(\.colorScheme) private var colorScheme
    @ObservedObject private var themeStore = NativeUITheme.shared

    @State private var queue = FeedbackCenterQueueState(now: 0)
    @State private var announcements = FeedbackCenterAnnouncementState()
    @State private var accessibilityControlFocused = false

    init(centerNode: NativeUINode?, @ViewBuilder content: () -> Content) {
        self.centerNode = centerNode
        self.content = content()
    }

    private var configurations: [FeedbackCenterItemConfiguration] {
        centerNode?.children
            .filter { $0.type == "firstlight.feedback-item" }
            .map(FeedbackCenterItemConfiguration.init(node:)) ?? []
    }

    private var isSuspended: Bool {
        scenePhase != .active || accessibilityControlFocused
    }

    private var schedule: FeedbackCenterSchedule {
        FeedbackCenterSchedule(
            feedbackID: queue.visible?.feedbackID,
            suspended: isSuspended,
            held: queue.visible?.hold ?? true,
            duration: queue.visibleDuration
        )
    }

    var body: some View {
        ZStack(alignment: .bottom) {
            content

            if let visible = queue.visible {
                FirstlightFeedbackCenterControl(
                    configuration: visible,
                    tokens: themeStore.resolve(for: colorScheme),
                    onAction: performAction,
                    onDismiss: performManualDismiss,
                    onAccessibilityFocusChanged: { focused in
                        accessibilityControlFocused = focused
                    }
                )
                .id(visible.feedbackID)
                .padding(.horizontal, 12)
                .safeAreaPadding(.bottom, 12)
                .transition(FeedbackCenterPresentation.transition(
                    reduceMotion: reduceMotion
                ))
                .zIndex(1)
            }
        }
        .animation(
            reduceMotion ? .easeOut(duration: 0.15) : .spring(duration: 0.32),
            value: queue.visible?.feedbackID
        )
        .onAppear {
            updateAssistiveTechnology()
            reconcile(configurations)
            updateSuspension()
            announceVisibleItem()
        }
        .onChange(of: configurations) { _, configurations in
            reconcile(configurations)
        }
        .onChange(of: scenePhase) { _, _ in
            updateSuspension()
        }
        .onChange(of: accessibilityControlFocused) { _, _ in
            updateSuspension()
        }
        .onChange(of: voiceOverEnabled) { _, _ in
            updateAssistiveTechnology()
        }
        .onChange(of: switchControlEnabled) { _, _ in
            updateAssistiveTechnology()
        }
        .onChange(of: queue.visible?.feedbackID) { _, _ in
            announceVisibleItem()
        }
        .task(id: schedule) {
            await scheduleAutomaticTimeout()
        }
    }

    private func reconcile(_ configurations: [FeedbackCenterItemConfiguration]) {
        let previousVisibleID = queue.visible?.feedbackID
        queue.reconcile(configurations, now: Self.now)
        if queue.visible?.feedbackID != previousVisibleID {
            accessibilityControlFocused = false
        }
        updateSuspension()
    }

    private func updateSuspension() {
        if isSuspended {
            queue.pause(at: Self.now)
        } else {
            queue.resume(at: Self.now)
        }
    }

    private func updateAssistiveTechnology() {
        queue.setAssistiveTechnologyActive(
            voiceOverEnabled || switchControlEnabled
        )
    }

    private func performAction() {
        accessibilityControlFocused = false
        send(queue.action())
    }

    private func performManualDismiss() {
        accessibilityControlFocused = false
        send(queue.manualDismiss())
    }

    private func scheduleAutomaticTimeout() async {
        guard !schedule.suspended,
              !schedule.held,
              queue.visible != nil,
              queue.remaining.isFinite,
              queue.remaining > 0 else { return }

        let delay = queue.remaining
        do {
            try await Task.sleep(for: .seconds(delay))
        } catch {
            return
        }
        guard !Task.isCancelled else { return }

        send(queue.advance(by: queue.remaining))
    }

    private func send(_ event: FeedbackCenterWireEvent?) {
        guard case let .press(callbackID, nodeID)? = event else { return }
        NativeUIBridge.sendPressEvent(callbackID, nodeId: nodeID)
    }

    private func announceVisibleItem() {
        if let message = announcements.consume(visible: queue.visible) {
            AccessibilityNotification.Announcement(message).post()
        }
    }

    nonisolated private static var now: TimeInterval {
        Date.timeIntervalSinceReferenceDate
    }
}
