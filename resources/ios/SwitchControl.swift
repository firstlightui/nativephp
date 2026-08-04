import SwiftUI

struct FirstlightSwitchControl: View {
    let value: Bool
    let label: String
    let supportingText: String
    let error: String
    let disabled: Bool
    let accessibilityLabel: String
    let accessibilityHint: String
    let tokens: NativeUITokens
    let onProposal: () -> Void

    var acceptedValueBinding: Binding<Bool> {
        Binding(
            get: { value },
            set: { _ in onProposal() }
        )
    }

    var body: some View {
        Toggle(isOn: acceptedValueBinding) {
            VStack(alignment: .leading, spacing: 2) {
                if !label.isEmpty {
                    Text(label)
                        .font(.body)
                }
                if !supportingText.isEmpty {
                    Text(supportingText)
                        .font(.footnote)
                        .foregroundStyle(error.isEmpty ? tokens.onSurfaceVariant : tokens.destructive)
                        .accessibilityLabel(error.isEmpty ? supportingText : "Error: \(error)")
                }
            }
            .fixedSize(horizontal: false, vertical: true)
        }
        .toggleStyle(.switch)
        .tint(tokens.primary)
        .disabled(disabled)
        .frame(maxWidth: .infinity, minHeight: 44)
        .modifier(SwitchAccessibilityLabelModifier(label: accessibilityLabel))
        .modifier(SwitchAccessibilityHintModifier(hint: accessibilityHint))
        .modifier(SwitchAccessibilityErrorModifier(value: value, error: error))
    }
}

enum SwitchAccessibility {
    static func errorValue(value: Bool, error: String) -> String? {
        guard !error.isEmpty else { return nil }

        return "\(value ? "On" : "Off"). Error: \(error)"
    }
}

private struct SwitchAccessibilityLabelModifier: ViewModifier {
    let label: String

    func body(content: Content) -> some View {
        if label.isEmpty {
            content
        } else {
            content.accessibilityLabel(label)
        }
    }
}

private struct SwitchAccessibilityHintModifier: ViewModifier {
    let hint: String

    func body(content: Content) -> some View {
        if hint.isEmpty {
            content
        } else {
            content.accessibilityHint(hint)
        }
    }
}

private struct SwitchAccessibilityErrorModifier: ViewModifier {
    let value: Bool
    let error: String

    func body(content: Content) -> some View {
        if let errorValue = SwitchAccessibility.errorValue(value: value, error: error) {
            content.accessibilityValue(errorValue)
        } else {
            content
        }
    }
}
