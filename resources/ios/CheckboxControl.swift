import SwiftUI

struct FirstlightCheckboxControl: View {
    let configuration: CheckboxRendererConfiguration
    let tokens: NativeUITokens
    let onProposal: () -> Void

    let glyphIsDecorative = true
    let minimumTarget: CGFloat = 44

    var body: some View {
        Button(action: onProposal) {
            HStack(alignment: .top, spacing: 12) {
                Image(systemName: configuration.value ? "checkmark.square.fill" : "square")
                    .font(.title3)
                    .foregroundStyle(configuration.value ? tokens.primary : tokens.onSurfaceVariant)
                    .frame(width: 24)
                    .frame(minHeight: minimumTarget, alignment: .top)
                    .accessibilityHidden(glyphIsDecorative)

                VStack(alignment: .leading, spacing: 2) {
                    if !configuration.label.isEmpty {
                        Text(configuration.required ? "\(configuration.label) *" : configuration.label)
                            .font(.body)
                            .foregroundStyle(tokens.onSurface)
                            .fixedSize(horizontal: false, vertical: true)
                    }

                    if !configuration.supportingText.isEmpty {
                        Text(configuration.supportingText)
                            .font(.footnote)
                            .foregroundStyle(
                                configuration.error.isEmpty
                                    ? tokens.onSurfaceVariant
                                    : tokens.destructive
                            )
                            .fixedSize(horizontal: false, vertical: true)
                    }
                }
                .frame(maxWidth: .infinity, minHeight: minimumTarget, alignment: .topLeading)
            }
            .contentShape(Rectangle())
        }
        .buttonStyle(.plain)
        .disabled(configuration.disabled)
        .opacity(configuration.disabled ? 0.38 : 1)
        .frame(maxWidth: .infinity, minHeight: minimumTarget, alignment: .leading)
        .accessibilityElement(children: .ignore)
        .modifier(CheckboxAccessibilityLabelModifier(label: configuration.accessibilityLabel))
        .modifier(CheckboxAccessibilityHintModifier(hint: configuration.accessibilityHint))
        .accessibilityValue(
            CheckboxAccessibility.value(
                value: configuration.value,
                required: configuration.required,
                error: configuration.error
            )
        )
        .accessibilityAddTraits(.isToggle)
    }
}

enum CheckboxAccessibility {
    static func value(value: Bool, required: Bool, error: String) -> String {
        var parts = [value ? "Checked" : "Not checked"]

        if required {
            parts.append("Required")
        }

        if !error.isEmpty {
            parts.append("Error: \(error)")
        }

        return parts.joined(separator: ". ")
    }
}

private struct CheckboxAccessibilityLabelModifier: ViewModifier {
    let label: String

    func body(content: Content) -> some View {
        if label.isEmpty {
            content
        } else {
            content.accessibilityLabel(label)
        }
    }
}

private struct CheckboxAccessibilityHintModifier: ViewModifier {
    let hint: String

    func body(content: Content) -> some View {
        if hint.isEmpty {
            content
        } else {
            content.accessibilityHint(hint)
        }
    }
}
