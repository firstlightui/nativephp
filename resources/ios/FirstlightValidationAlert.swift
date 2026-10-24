import SwiftUI

/// One-time screen-reader alert PHP attaches to the first error-bound
/// element of the frame published after a failed interaction.
struct FirstlightValidationAlert: Equatable {
    let revision: Int
    let announcement: String
    let movesFocus: Bool

    init(revision: Int, announcement: String, movesFocus: Bool) {
        self.revision = revision
        self.announcement = announcement
        self.movesFocus = movesFocus
    }

    init(node: NativeUINode) {
        revision = node.props.getInt("validation_alert", default: 0)
        announcement = node.props.getString("validation_announcement")
        movesFocus = node.props.getBool("validation_focus")
    }

    var isEmpty: Bool { revision == 0 || (announcement.isEmpty && !movesFocus) }
}

/// Acts on each revision once, including on the frame that creates the node,
/// so republishing the same tree never repeats speech or steals focus.
struct FirstlightValidationAlertState {
    private var handledRevision = 0

    mutating func consume(_ alert: FirstlightValidationAlert) -> FirstlightValidationAlert? {
        guard !alert.isEmpty, alert.revision != handledRevision else { return nil }

        handledRevision = alert.revision
        return alert
    }
}

private struct FirstlightValidationFocusKey: EnvironmentKey {
    static var defaultValue: AccessibilityFocusState<Bool>.Binding? { nil }
}

extension EnvironmentValues {
    var firstlightValidationFocus: AccessibilityFocusState<Bool>.Binding? {
        get { self[FirstlightValidationFocusKey.self] }
        set { self[FirstlightValidationFocusKey.self] = newValue }
    }
}

/// Applied by a renderer around its control. Speaks the announcement after
/// current VoiceOver speech and moves VoiceOver focus to the control's
/// `firstlightValidationFocusTarget()` element. Keyboard focus is unchanged.
struct FirstlightValidationAlertModifier: ViewModifier {
    let nodeID: Int

    @State private var state = FirstlightValidationAlertState()
    @AccessibilityFocusState private var focused: Bool

    func body(content: Content) -> some View {
        // `$currentTree` replays the current tree on subscription, so the
        // frame that creates this node is handled as well as later ones.
        content
            .environment(\.firstlightValidationFocus, $focused)
            .onReceive(NativeUIBridge.shared.$currentTree.compactMap { $0 }) { tree in
                guard let node = firstlightValidationNode(id: nodeID, in: tree.root) else { return }
                handle(FirstlightValidationAlert(node: node))
            }
    }

    private func handle(_ alert: FirstlightValidationAlert) {
        guard let alert = state.consume(alert) else { return }

        // Wait for the published frame's layout: an accessibility focus
        // change made in the transaction that creates the element is dropped.
        Task { @MainActor in
            try? await Task.sleep(for: .milliseconds(firstlightValidationAlertDelayMilliseconds))

            if alert.movesFocus {
                focused = true
            }

            if !alert.announcement.isEmpty {
                AccessibilityNotification.Announcement(firstlightValidationAnnouncement(alert.announcement)).post()
            }
        }
    }
}

let firstlightValidationAlertDelayMilliseconds = 150

/// Queued behind the focus change so VoiceOver reads the focused field first.
func firstlightValidationAnnouncement(_ text: String) -> AttributedString {
    var announcement = AttributedString(text)
    announcement.accessibilitySpeechAnnouncementPriority = .low
    return announcement
}

private struct FirstlightValidationFocusTargetModifier: ViewModifier {
    let enabled: Bool

    @Environment(\.firstlightValidationFocus) private var focus

    func body(content: Content) -> some View {
        if enabled, let focus {
            content.accessibilityFocused(focus)
        } else {
            content
        }
    }
}

extension View {
    func firstlightValidationAlert(nodeID: Int) -> some View {
        modifier(FirstlightValidationAlertModifier(nodeID: nodeID))
    }

    /// Marks the element VoiceOver focuses when this field is the first invalid one.
    func firstlightValidationFocusTarget(_ enabled: Bool = true) -> some View {
        modifier(FirstlightValidationFocusTargetModifier(enabled: enabled))
    }
}

private func firstlightValidationNode(id: Int, in node: NativeUINode) -> NativeUINode? {
    if node.id == id { return node }

    for child in node.children {
        if let found = firstlightValidationNode(id: id, in: child) {
            return found
        }
    }

    return nil
}
