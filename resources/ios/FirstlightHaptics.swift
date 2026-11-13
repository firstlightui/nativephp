import SwiftUI
import UIKit

/// Firstlight's haptic vocabulary (spec/reference/haptics.md). Each meaning
/// plays iOS's canonical pattern: a UIKit feedback generator where one
/// exists, otherwise SwiftUI sensory feedback. Both follow the System Haptics
/// setting.
enum FirstlightHaptic: String, CaseIterable {
    case success
    case warning
    case error
    case selection
    case tick
    case toggleOn = "toggle-on"
    case toggleOff = "toggle-off"
    case press
    case keypress
    case longPress = "long-press"
    case thresholdActivate = "threshold-activate"
    case thresholdDeactivate = "threshold-deactivate"
    case dragStart = "drag-start"
    case alignment
    case increase
    case decrease
    case levelChange = "level-change"
    case start
    case stop
    case pathComplete = "path-complete"
    case none

    /// How the haptic plays on iOS.
    enum Pattern: Equatable {
        case notification(UINotificationFeedbackGenerator.FeedbackType)
        case selection
        case impact(UIImpactFeedbackGenerator.FeedbackStyle)
        /// SwiftUI-only kinds, played through the root host.
        case sensory(String)
        case silent
    }

    var pattern: Pattern {
        switch self {
        case .success: .notification(.success)
        case .warning: .notification(.warning)
        case .error: .notification(.error)
        // iOS uses the selection pattern for every detent and toggle.
        case .selection, .tick, .toggleOn, .toggleOff: .selection
        case .press, .keypress, .thresholdActivate: .impact(.light)
        case .thresholdDeactivate: .impact(.soft)
        case .longPress, .dragStart: .impact(.medium)
        case .alignment: .sensory("alignment")
        case .increase: .sensory("increase")
        case .decrease: .sensory("decrease")
        case .levelChange: .sensory("levelChange")
        case .start: .sensory("start")
        case .stop: .sensory("stop")
        case .pathComplete: .sensory("pathComplete")
        case .none: .silent
        }
    }
}

@MainActor
final class FirstlightHaptics: ObservableObject {
    static let shared = FirstlightHaptics()

    nonisolated static let enabledKey = "firstlight.haptics.enabled"
    nonisolated static let impactWeights: [String: UIImpactFeedbackGenerator.FeedbackStyle] = [
        "light": .light, "medium": .medium, "heavy": .heavy, "soft": .soft, "rigid": .rigid,
    ]

    /// The last SwiftUI-only haptic asked for; the root host plays each new one.
    @Published private(set) var sensoryRequest = FirstlightSensoryRequest(id: 0, kind: "")

    /// Records instead of playing, for tests.
    var recorder: ((FirstlightHaptic) -> Void)?

    nonisolated static var isEnabled: Bool {
        UserDefaults.standard.object(forKey: enabledKey) as? Bool ?? true
    }

    nonisolated static func setEnabled(_ enabled: Bool) {
        UserDefaults.standard.set(enabled, forKey: enabledKey)
    }

    func play(_ haptic: FirstlightHaptic) {
        if let recorder {
            recorder(haptic)
            return
        }
        guard Self.isEnabled else { return }

        switch haptic.pattern {
        case let .notification(type):
            UINotificationFeedbackGenerator().notificationOccurred(type)
        case .selection:
            UISelectionFeedbackGenerator().selectionChanged()
        case let .impact(style):
            UIImpactFeedbackGenerator(style: style).impactOccurred()
        case let .sensory(kind):
            sensoryRequest = FirstlightSensoryRequest(id: sensoryRequest.id + 1, kind: kind)
        case .silent:
            break
        }
    }

    func impact(_ style: UIImpactFeedbackGenerator.FeedbackStyle, intensity: CGFloat) {
        guard Self.isEnabled, intensity > 0 else { return }
        UIImpactFeedbackGenerator(style: style).impactOccurred(intensity: min(intensity, 1))
    }
}

struct FirstlightSensoryRequest: Equatable {
    let id: Int
    let kind: String

    var feedback: SensoryFeedback? {
        switch kind {
        case "alignment": .alignment
        case "increase": .increase
        case "decrease": .decrease
        case "levelChange": .levelChange
        case "start": .start
        case "stop": .stop
        case "pathComplete": .pathComplete
        default: nil
        }
    }
}

/// Plays the SwiftUI-only haptics PHP asks for. Registered as a root host.
struct FirstlightSensoryFeedbackHost<Content: View>: View {
    @ObservedObject private var haptics = FirstlightHaptics.shared
    let content: Content

    var body: some View {
        content.sensoryFeedback(trigger: haptics.sensoryRequest) { _, request in
            request.feedback
        }
    }
}

extension View {
    /// Plays a button's `haptic` natively as the tap fires, alongside the
    /// button's own action, without waiting for PHP.
    @ViewBuilder
    func firstlightPressHaptic(_ node: NativeUINode, enabled: Bool) -> some View {
        if enabled,
           let haptic = FirstlightHaptic(rawValue: node.props.getString("haptic")),
           haptic != .none {
            simultaneousGesture(TapGesture().onEnded { FirstlightHaptics.shared.play(haptic) })
        } else {
            self
        }
    }
}

// MARK: - Firstlight.Haptics.* bridge functions

enum FirstlightHapticsFunctions {
    /// `{"haptic": "success"}` or `{"impact": "medium", "intensity": 0.7}`.
    class Play: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            if let weight = parameters["impact"] as? String {
                guard let style = FirstlightHaptics.impactWeights[weight] else {
                    throw BridgeError.invalidParameters("unknown impact weight '\(weight)'")
                }
                let intensity = CGFloat((parameters["intensity"] as? NSNumber)?.doubleValue ?? 1)
                DispatchQueue.main.async {
                    MainActor.assumeIsolated { FirstlightHaptics.shared.impact(style, intensity: intensity) }
                }
                return [:]
            }

            let name = parameters["haptic"] as? String ?? ""
            guard let haptic = FirstlightHaptic(rawValue: name) else {
                throw BridgeError.invalidParameters("unknown haptic '\(name)'")
            }
            DispatchQueue.main.async {
                MainActor.assumeIsolated { FirstlightHaptics.shared.play(haptic) }
            }
            return [:]
        }
    }

    /// `{"enabled": false}` turns every Firstlight haptic off for this install.
    class SetEnabled: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let enabled = parameters["enabled"] as? Bool else {
                throw BridgeError.invalidParameters("enabled must be a boolean")
            }
            FirstlightHaptics.setEnabled(enabled)
            return [:]
        }
    }
}
