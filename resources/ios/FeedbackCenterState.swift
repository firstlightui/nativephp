import Foundation

enum FeedbackCenterTone: String, CaseIterable {
    case `default`
    case success
    case warning
    case danger
}

enum FeedbackCenterWireEvent: Equatable {
    case press(callbackID: Int, nodeID: Int)
}

enum FeedbackCenterTiming {
    static let minimumDuration: TimeInterval = 4
    static let maximumMessageDuration: TimeInterval = 10
    static let actionExtension: TimeInterval = 2
    static let assistiveTechnologyMultiplier: Double = 2

    static func automaticDuration(
        message: String,
        hasAction: Bool,
        assistiveTechnologyActive: Bool
    ) -> TimeInterval {
        let additionalReadingTime = max(0, Double(message.count - 40) / 40)
        var duration = min(
            maximumMessageDuration,
            minimumDuration + additionalReadingTime
        )

        if hasAction {
            duration += actionExtension
        }
        if assistiveTechnologyActive {
            duration *= assistiveTechnologyMultiplier
        }

        return duration
    }
}

struct FeedbackCenterItemConfiguration: Equatable, Identifiable {
    let nodeID: Int
    let feedbackID: String
    let message: String
    let tone: FeedbackCenterTone
    let hold: Bool
    let actionLabel: String?
    let actionCallback: Int?
    let timeoutCallback: Int?
    let manualCallback: Int?

    private let hasValidIdentity: Bool
    private let hasValidMessage: Bool
    private let hasValidTone: Bool

    var id: String { feedbackID }

    var isEligible: Bool {
        hasValidIdentity
            && hasValidMessage
            && hasValidTone
            && (hold ? manualCallback != nil : timeoutCallback != nil)
    }

    init(node: NativeUINode) {
        nodeID = node.id

        let decodedID = node.props.getString("feedback_id")
        feedbackID = decodedID
        hasValidIdentity = !decodedID.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty

        let decodedMessage = node.props.getString("message")
        message = decodedMessage
        hasValidMessage = !decodedMessage.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty

        let decodedTone = FeedbackCenterTone(
            rawValue: node.props.getString("tone", default: "default")
        )
        tone = decodedTone ?? .default
        hasValidTone = decodedTone != nil

        hold = node.props.getBool("hold")

        let decodedActionLabel = node.props.getString("action_label")
            .trimmingCharacters(in: .whitespacesAndNewlines)
        let decodedActionCallback = Self.callback(node.props.getCallbackId("on_action"))
        if !decodedActionLabel.isEmpty, let decodedActionCallback {
            actionLabel = decodedActionLabel
            actionCallback = decodedActionCallback
        } else {
            actionLabel = nil
            actionCallback = nil
        }

        timeoutCallback = Self.callback(node.props.getCallbackId("on_timeout"))
        manualCallback = Self.callback(node.props.getCallbackId("on_manual"))
    }

    init(
        nodeID: Int,
        feedbackID: String,
        message: String,
        tone: FeedbackCenterTone = .default,
        hold: Bool = false,
        actionLabel: String? = nil,
        actionCallback: Int? = nil,
        timeoutCallback: Int? = 1,
        manualCallback: Int? = nil
    ) {
        self.nodeID = nodeID
        self.feedbackID = feedbackID
        self.message = message
        self.tone = tone
        self.hold = hold

        let trimmedAction = actionLabel?.trimmingCharacters(in: .whitespacesAndNewlines)
        if let trimmedAction, !trimmedAction.isEmpty, let actionCallback, actionCallback != 0 {
            self.actionLabel = trimmedAction
            self.actionCallback = actionCallback
        } else {
            self.actionLabel = nil
            self.actionCallback = nil
        }

        self.timeoutCallback = Self.callback(timeoutCallback ?? 0)
        self.manualCallback = Self.callback(manualCallback ?? 0)
        hasValidIdentity = !feedbackID.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
        hasValidMessage = !message.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
        hasValidTone = true
    }

    func automaticDuration(assistiveTechnologyActive: Bool) -> TimeInterval {
        FeedbackCenterTiming.automaticDuration(
            message: message,
            hasAction: actionCallback != nil,
            assistiveTechnologyActive: assistiveTechnologyActive
        )
    }

    private static func callback(_ value: Int) -> Int? {
        value == 0 ? nil : value
    }
}

struct FeedbackCenterQueueState {
    private(set) var items: [FeedbackCenterItemConfiguration] = []
    private(set) var elapsed: TimeInterval = 0
    private(set) var isPaused = false

    private var tombstones: Set<String> = []
    private var clock: TimeInterval
    private var assistiveTechnologyActive: Bool

    init(now: TimeInterval, assistiveTechnologyActive: Bool = false) {
        clock = now
        self.assistiveTechnologyActive = assistiveTechnologyActive
    }

    var visible: FeedbackCenterItemConfiguration? { items.first }
    var pendingIDs: [String] { items.dropFirst().map(\.feedbackID) }

    var visibleDuration: TimeInterval {
        guard let visible, !visible.hold else { return .infinity }
        return visible.automaticDuration(
            assistiveTechnologyActive: assistiveTechnologyActive
        )
    }

    var remaining: TimeInterval {
        max(0, visibleDuration - elapsed)
    }

    mutating func setAssistiveTechnologyActive(_ active: Bool) {
        assistiveTechnologyActive = active
    }

    mutating func reconcile(
        _ published: [FeedbackCenterItemConfiguration],
        now: TimeInterval
    ) {
        synchronize(to: now)

        let publishedIDs = Set(
            published.lazy
                .map(\.feedbackID)
                .filter { !$0.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty }
        )
        tombstones.formIntersection(publishedIDs)

        let idCounts = Dictionary(grouping: published, by: \.feedbackID).mapValues(\.count)
        let eligible = published.filter {
            $0.isEligible && idCounts[$0.feedbackID] == 1
        }
        let eligibleByID = Dictionary(uniqueKeysWithValues: eligible.map { ($0.feedbackID, $0) })

        let previousVisibleID = visible?.feedbackID
        var reconciled = items.compactMap { eligibleByID[$0.feedbackID] }
        let retainedIDs = Set(reconciled.map(\.feedbackID))

        for configuration in eligible
        where !retainedIDs.contains(configuration.feedbackID)
            && !tombstones.contains(configuration.feedbackID) {
            reconciled.append(configuration)
        }

        items = reconciled
        if visible?.feedbackID != previousVisibleID {
            elapsed = 0
            clock = now
        } else if visible == nil {
            elapsed = 0
            clock = now
        }
    }

    mutating func action() -> FeedbackCenterWireEvent? {
        guard let visible, let callbackID = visible.actionCallback else { return nil }
        return complete(
            feedbackID: visible.feedbackID,
            event: .press(callbackID: callbackID, nodeID: visible.nodeID)
        )
    }

    mutating func timeout() -> FeedbackCenterWireEvent? {
        guard let visible, !visible.hold, let callbackID = visible.timeoutCallback else {
            return nil
        }
        return complete(
            feedbackID: visible.feedbackID,
            event: .press(callbackID: callbackID, nodeID: visible.nodeID)
        )
    }

    mutating func manualDismiss() -> FeedbackCenterWireEvent? {
        guard let visible, visible.hold, let callbackID = visible.manualCallback else {
            return nil
        }
        return complete(
            feedbackID: visible.feedbackID,
            event: .press(callbackID: callbackID, nodeID: visible.nodeID)
        )
    }

    @discardableResult
    mutating func advance(by interval: TimeInterval) -> FeedbackCenterWireEvent? {
        guard interval > 0 else { return nil }
        clock += interval

        guard !isPaused, let visible, !visible.hold else { return nil }
        elapsed = min(visibleDuration, elapsed + interval)
        return remaining == 0 ? timeout() : nil
    }

    mutating func pause(at now: TimeInterval) {
        guard !isPaused else { return }
        synchronize(to: now)
        isPaused = true
    }

    mutating func resume(at now: TimeInterval) {
        guard isPaused else { return }
        clock = now
        isPaused = false
    }

    private mutating func synchronize(to now: TimeInterval) {
        defer { clock = max(clock, now) }
        guard now > clock, !isPaused, let visible, !visible.hold else { return }
        elapsed = min(visibleDuration, elapsed + (now - clock))
    }

    private mutating func complete(
        feedbackID: String,
        event: FeedbackCenterWireEvent
    ) -> FeedbackCenterWireEvent? {
        guard visible?.feedbackID == feedbackID else { return nil }

        tombstones.insert(feedbackID)
        items.removeFirst()
        elapsed = 0
        return event
    }
}
