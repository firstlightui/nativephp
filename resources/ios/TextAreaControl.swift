import SwiftUI
import UIKit

struct FirstlightTextAreaControl: View {
    let configuration: TextAreaRendererConfiguration
    @Binding var text: String
    let isFocused: FocusState<Bool>.Binding
    let tokens: NativeUITokens

    var minimumHeight: CGFloat {
        CGFloat(configuration.minLines) * 22 + 18
    }

    var maximumHeight: CGFloat {
        CGFloat(configuration.maxLines) * 22 + 18
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            if !configuration.label.isEmpty {
                Text(configuration.required ? "\(configuration.label) *" : configuration.label)
                    .font(.subheadline.weight(.medium))
                    .foregroundStyle(tokens.onSurface)
                    .fixedSize(horizontal: false, vertical: true)
            }

            ZStack(alignment: .topLeading) {
                if text.isEmpty && !configuration.placeholder.isEmpty {
                    Text(configuration.placeholder)
                        .foregroundStyle(Color(uiColor: .placeholderText))
                        .padding(.horizontal, 5)
                        .padding(.vertical, 8)
                        .allowsHitTesting(false)
                        .accessibilityHidden(true)
                }

                TextEditor(text: configuration.readOnly ? .constant(text) : $text)
                    .scrollContentBackground(.hidden)
                    .focused(isFocused)
                    .disabled(configuration.disabled)
                    .modifier(TextAreaCapitalizationModifier(policy: configuration.autocapitalize))
                    .modifier(TextAreaAutocorrectionModifier(policy: configuration.autocorrectPolicy))
                    .modifier(TextAreaAccessibilityLabelModifier(label: configuration.accessibilityLabel))
                    .modifier(TextAreaAccessibilityHintModifier(hint: configuration.accessibilityHint))
                    .accessibilityValue(TextAreaAccessibility.value(text: text, error: configuration.error))
            }
            .frame(minHeight: minimumHeight, maxHeight: maximumHeight, alignment: .topLeading)
            .padding(.horizontal, 7)
            .background(Color(uiColor: .secondarySystemBackground))
            .clipShape(RoundedRectangle(cornerRadius: 10, style: .continuous))
            .overlay {
                RoundedRectangle(cornerRadius: 10, style: .continuous)
                    .stroke(
                        configuration.error.isEmpty ? Color(uiColor: .separator) : tokens.destructive,
                        lineWidth: configuration.error.isEmpty ? 0.5 : 1,
                    )
            }

            let supporting = configuration.error.isEmpty ? configuration.helper : configuration.error
            if !supporting.isEmpty {
                Text(supporting)
                    .font(.footnote)
                    .foregroundStyle(configuration.error.isEmpty ? tokens.onSurfaceVariant : tokens.destructive)
                    .fixedSize(horizontal: false, vertical: true)
                    .accessibilityLabel(configuration.error.isEmpty ? supporting : "Error: \(supporting)")
            }
        }
        .opacity(configuration.disabled ? 0.6 : 1)
    }
}

enum TextAreaAccessibility {
    static func value(text: String, error: String) -> String {
        guard !error.isEmpty else { return text }
        guard !text.isEmpty else { return "Error: \(error)" }
        return "\(text). Error: \(error)"
    }
}

private struct TextAreaCapitalizationModifier: ViewModifier {
    let policy: String

    @ViewBuilder
    func body(content: Content) -> some View {
        switch policy {
        case "none": content.textInputAutocapitalization(.never)
        case "sentences": content.textInputAutocapitalization(.sentences)
        case "words": content.textInputAutocapitalization(.words)
        case "characters": content.textInputAutocapitalization(.characters)
        default: content
        }
    }
}

private struct TextAreaAutocorrectionModifier: ViewModifier {
    let policy: String

    @ViewBuilder
    func body(content: Content) -> some View {
        switch policy {
        case "enabled": content.autocorrectionDisabled(false)
        case "disabled": content.autocorrectionDisabled(true)
        default: content
        }
    }
}

private struct TextAreaAccessibilityLabelModifier: ViewModifier {
    let label: String

    @ViewBuilder
    func body(content: Content) -> some View {
        if label.isEmpty { content } else { content.accessibilityLabel(label) }
    }
}

private struct TextAreaAccessibilityHintModifier: ViewModifier {
    let hint: String

    @ViewBuilder
    func body(content: Content) -> some View {
        if hint.isEmpty { content } else { content.accessibilityHint(hint) }
    }
}
