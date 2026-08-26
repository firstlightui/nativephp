import SwiftUI
import UIKit

struct FirstlightTextFieldControl: View {
    let configuration: TextFieldRendererConfiguration
    @Binding var text: String
    @Binding var revealed: Bool
    let isFocused: FocusState<Bool>.Binding
    let tokens: NativeUITokens
    let onClear: () -> Void
    let onTrailingPress: () -> Void
    let onSubmit: () -> Void

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            if !configuration.label.isEmpty {
                Text(configuration.required ? "\(configuration.label) *" : configuration.label)
                .font(.subheadline.weight(.medium))
                .foregroundStyle(tokens.onSurface)
            }

            HStack(spacing: 8) {
                if !configuration.leadingIcon.isEmpty {
                    Image(systemName: configuration.leadingIcon)
                        .foregroundStyle(tokens.onSurfaceVariant)
                        .accessibilityHidden(true)
                }

                input
                    .focused(isFocused)
                    .disabled(configuration.disabled)

                trailing
            }
            .padding(.horizontal, 12)
            .frame(minHeight: 44)
            .background(Color(uiColor: .secondarySystemBackground))
            .clipShape(RoundedRectangle(cornerRadius: 10, style: .continuous))
            .overlay {
                RoundedRectangle(cornerRadius: 10, style: .continuous)
                    .stroke(configuration.error.isEmpty ? Color.clear : tokens.destructive, lineWidth: 1)
            }

            let supporting = configuration.error.isEmpty ? configuration.helper : configuration.error
            if !supporting.isEmpty {
                Text(supporting)
                    .font(.footnote)
                    .foregroundStyle(configuration.error.isEmpty ? tokens.onSurfaceVariant : tokens.destructive)
                    .accessibilityLabel(configuration.error.isEmpty ? supporting : "Error: \(supporting)")
            }
        }
        .opacity(configuration.disabled ? 0.6 : 1)
    }

    @ViewBuilder private var input: some View {
        if configuration.secure && !revealed {
            SecureField(
                configuration.placeholder,
                text: configuration.readOnly ? .constant(text) : $text
            )
                .textContentType(resolveContentType(configuration.contentType))
                .keyboardType(resolveKeyboard(configuration.keyboard))
                .textInputAutocapitalization(resolveCapitalization(configuration.autocapitalize))
                .autocorrectionDisabled(configuration.autocorrectPolicy == "disabled")
                .submitLabel(resolveSubmitLabel(configuration.submitLabel))
                .onSubmit(onSubmit)
        } else {
            TextField(
                configuration.placeholder,
                text: configuration.readOnly ? .constant(text) : $text
            )
                .textContentType(resolveContentType(configuration.contentType))
                .keyboardType(resolveKeyboard(configuration.keyboard))
                .textInputAutocapitalization(resolveCapitalization(configuration.autocapitalize))
                .autocorrectionDisabled(configuration.autocorrectPolicy == "disabled")
                .submitLabel(resolveSubmitLabel(configuration.submitLabel))
                .onSubmit(onSubmit)
        }
    }

    @ViewBuilder private var trailing: some View {
        if configuration.clearable && !text.isEmpty && !configuration.readOnly {
            actionButton(symbol: "xmark.circle.fill", label: configuration.clearA11yLabel, action: onClear)
        } else if configuration.revealable {
            actionButton(
                symbol: revealed ? "eye.slash" : "eye",
                label: revealed ? configuration.hidePasswordA11yLabel : configuration.showPasswordA11yLabel,
                action: { revealed.toggle() }
            )
        } else if !configuration.trailingIcon.isEmpty {
            if configuration.onPressCallback != 0 {
                actionButton(
                    symbol: configuration.trailingIcon,
                    label: configuration.trailingAccessibilityLabel,
                    action: onTrailingPress
                )
            } else {
                Image(systemName: configuration.trailingIcon)
                    .foregroundStyle(tokens.onSurfaceVariant)
                    .accessibilityHidden(true)
            }
        }
    }

    private func actionButton(symbol: String, label: String, action: @escaping () -> Void) -> some View {
        Button(action: action) {
            Image(systemName: symbol).frame(minWidth: 44, minHeight: 44)
        }
        .buttonStyle(.plain)
        .accessibilityLabel(Text(label))
        .disabled(configuration.disabled)
    }
}

private func resolveKeyboard(_ value: String) -> UIKeyboardType {
    switch value {
    case "email": .emailAddress
    case "phone": .phonePad
    case "url": .URL
    case "number": .numberPad
    case "decimal": .decimalPad
    default: .default
    }
}

private func resolveContentType(_ value: String) -> UITextContentType? {
    switch value {
    case "name": .name
    case "username": .username
    case "email": .emailAddress
    case "password": .password
    case "new-password": .newPassword
    case "one-time-code": .oneTimeCode
    default: nil
    }
}

private func resolveCapitalization(_ value: String) -> TextInputAutocapitalization? {
    switch value {
    case "none": .never
    case "sentences": .sentences
    case "words": .words
    case "characters": .characters
    default: nil
    }
}

private func resolveSubmitLabel(_ value: String) -> SubmitLabel {
    switch value {
    case "go": .go
    case "next": .next
    case "search": .search
    case "send": .send
    default: .return
    }
}
