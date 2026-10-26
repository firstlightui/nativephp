import SwiftUI
import UIKit

struct FirstlightTextFieldControl: View {
    let configuration: TextFieldRendererConfiguration
    @Binding var text: String
    /// The formatted text of a masked field, which the renderer owns.
    let maskedText: String
    /// Changes when a masked field should keep the focus it had before a SwiftUI action, such as clear.
    var maskedRefocus = 0
    @Binding var revealed: Bool
    let isFocused: FocusState<Bool>.Binding
    let tokens: NativeUITokens
    let onClear: () -> Void
    let onTrailingPress: () -> Void
    let onSubmit: () -> Void
    /// Applies a masked edit of a display character range and returns the new display and caret.
    let onMaskedEdit: (Range<Int>, String) -> (text: String, caret: Int?)
    let onMaskedTextReplaced: (String) -> String
    let onMaskedFocusChanged: (Bool) -> Void
    @State private var maskedFocusRequest = 0

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
                    .disabled(configuration.disabled)
                    .accessibilityLabel(Text(configuration.accessibilityLabel))
                    .accessibilityHint(Text(configuration.accessibilityHint))
                    .frame(minHeight: 44)
                    .firstlightValidationFocusTarget()

                trailing
            }
            .padding(.horizontal, 12)
            .frame(minHeight: 44)
            .background {
                tokens.surfaceVariant
                    .contentShape(Rectangle())
                    .onTapGesture {
                        guard !configuration.disabled else { return }
                        if configuration.mask != nil { maskedFocusRequest += 1 }
                        else { isFocused.wrappedValue = true }
                    }
            }
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
        .toolbar {
            if configuration.mask == nil && isFocused.wrappedValue && needsDoneAccessory(configuration.keyboard) {
                ToolbarItemGroup(placement: .keyboard) {
                    Spacer()
                    Button(configuration.doneLabel) {
                        onSubmit()
                        isFocused.wrappedValue = false
                    }
                    .disabled(configuration.disabled)
                }
            }
        }
    }

    @ViewBuilder private var input: some View {
        if configuration.mask != nil {
            // UIKit reports each edit's exact range before applying it, so the renderer can
            // insert literals, drop rejected characters, and place the caret in one step.
            FirstlightMaskedTextInput(
                configuration: configuration,
                text: maskedText,
                refocus: maskedRefocus,
                focusRequest: maskedFocusRequest,
                onEdit: onMaskedEdit,
                onTextReplaced: onMaskedTextReplaced,
                onFocusChanged: onMaskedFocusChanged,
                onSubmit: onSubmit
            )
        } else if configuration.secure && !revealed {
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
                .focused(isFocused)
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
                .focused(isFocused)
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

struct FirstlightMaskedTextInput: UIViewRepresentable {
    let configuration: TextFieldRendererConfiguration
    let text: String
    let refocus: Int
    var focusRequest = 0
    let onEdit: (Range<Int>, String) -> (text: String, caret: Int?)
    let onTextReplaced: (String) -> String
    let onFocusChanged: (Bool) -> Void
    let onSubmit: () -> Void

    func makeCoordinator() -> Coordinator { Coordinator() }

    func makeUIView(context: Context) -> FirstlightMaskedUITextField {
        let field = FirstlightMaskedUITextField()
        field.font = .preferredFont(forTextStyle: .body)
        field.adjustsFontForContentSizeCategory = true
        field.borderStyle = .none
        field.setContentHuggingPriority(.defaultLow, for: .horizontal)
        field.setContentCompressionResistancePriority(.defaultLow, for: .horizontal)
        field.delegate = context.coordinator
        field.addTarget(context.coordinator, action: #selector(Coordinator.editingChanged(_:)), for: .editingChanged)
        return field
    }

    func updateUIView(_ field: FirstlightMaskedUITextField, context: Context) {
        context.coordinator.input = self
        if context.coordinator.focusRequest != focusRequest {
            context.coordinator.focusRequest = focusRequest
            DispatchQueue.main.async { field.becomeFirstResponder() }
        }
        if context.coordinator.refocus != refocus {
            context.coordinator.refocus = refocus
            // SwiftUI does not track UIKit focus, so its button tap can resign the field just
            // before the action runs. Take focus back only when it was lost that moment.
            if !field.isFirstResponder, let ended = context.coordinator.endedEditing, Date().timeIntervalSince(ended) < 0.5 {
                DispatchQueue.main.async { field.becomeFirstResponder() }
            }
        }
        if field.text != text, field.markedTextRange == nil { field.text = text }
        field.placeholder = configuration.placeholder
        field.isEnabled = !configuration.disabled
        field.keyboardType = resolveKeyboard(configuration.keyboard)
        field.textContentType = resolveContentType(configuration.contentType)
        if needsDoneAccessory(configuration.keyboard) {
            if field.doneAccessory == nil {
                let toolbar = UIToolbar()
                toolbar.items = [
                    UIBarButtonItem(systemItem: .flexibleSpace),
                    UIBarButtonItem(title: configuration.doneLabel, style: .done, target: context.coordinator, action: #selector(Coordinator.done)),
                ]
                toolbar.sizeToFit()
                field.doneAccessory = toolbar
            }
            field.doneAccessory?.items?.last?.title = configuration.doneLabel
        } else {
            field.doneAccessory = nil
        }
        field.autocapitalizationType = switch configuration.autocapitalize {
        case "none": .none
        case "words": .words
        case "characters": .allCharacters
        default: .sentences
        }
        field.autocorrectionType = switch configuration.autocorrectPolicy {
        case "enabled": .yes
        case "disabled": .no
        default: .default
        }
        field.returnKeyType = switch configuration.submitLabel {
        case "done": .done
        case "go": .go
        case "next": .next
        case "search": .search
        case "send": .send
        default: .default
        }
    }

    func sizeThatFits(_ proposal: ProposedViewSize, uiView: FirstlightMaskedUITextField, context: Context) -> CGSize? {
        CGSize(width: proposal.width ?? uiView.intrinsicContentSize.width, height: max(44, uiView.intrinsicContentSize.height))
    }

    final class Coordinator: NSObject, UITextFieldDelegate {
        var input: FirstlightMaskedTextInput?
        var refocus = 0
        var focusRequest = 0
        var endedEditing: Date?
        private weak var field: UITextField?

        func textField(_ field: UITextField, shouldChangeCharactersIn range: NSRange, replacementString string: String) -> Bool {
            // Marked text is composed natively and conformed when it is committed.
            guard let input, field.markedTextRange == nil,
                  let current = field.text, let bounds = Range(range, in: current)
            else { return true }
            let lower = current.distance(from: current.startIndex, to: bounds.lowerBound)
            let upper = current.distance(from: current.startIndex, to: bounds.upperBound)
            let result = input.onEdit(lower..<upper, string)
            field.text = result.text
            place(caret: result.caret ?? result.text.count, in: field)
            return false
        }

        @objc func editingChanged(_ field: UITextField) {
            guard let input, field.markedTextRange == nil, let text = field.text else { return }
            let display = input.onTextReplaced(text)
            if display != text {
                field.text = display
                place(caret: display.count, in: field)
            }
        }

        func textFieldDidBeginEditing(_ field: UITextField) {
            self.field = field
            input?.onFocusChanged(true)
        }

        func textFieldDidEndEditing(_ field: UITextField) {
            endedEditing = Date()
            input?.onFocusChanged(false)
        }

        @objc func done() {
            guard let input, !input.configuration.disabled else { return }
            input.onSubmit()
            field?.resignFirstResponder()
        }

        func textFieldShouldReturn(_ field: UITextField) -> Bool {
            input?.onSubmit()
            field.resignFirstResponder()
            return false
        }

        private func place(caret: Int, in field: UITextField) {
            let text = field.text ?? ""
            let index = text.index(text.startIndex, offsetBy: min(caret, text.count))
            let offset = text.utf16.distance(from: text.utf16.startIndex, to: index)
            if let position = field.position(from: field.beginningOfDocument, offset: offset) {
                field.selectedTextRange = field.textRange(from: position, to: position)
            }
        }
    }
}

/// Keep the masked field's accessory within its UIKit editing session.
final class FirstlightMaskedUITextField: UITextField {
    var doneAccessory: UIToolbar?
    private var isInEditingSession = false

    override var inputAccessoryView: UIView? {
        get { isInEditingSession ? doneAccessory : nil }
        set {}
    }

    override func becomeFirstResponder() -> Bool {
        isInEditingSession = true
        let became = super.becomeFirstResponder()
        if !became { isInEditingSession = false }
        return became
    }

    override func resignFirstResponder() -> Bool {
        let wasEditing = isInEditingSession
        isInEditingSession = false
        // Refresh while this field still owns the keyboard. Clearing only after
        // resignation leaves the next SwiftUI keyboard inset short by the accessory height.
        if isFirstResponder { reloadInputViews() }
        let resigned = super.resignFirstResponder()
        if !resigned {
            isInEditingSession = wasEditing
            if isFirstResponder { reloadInputViews() }
        }
        return resigned
    }
}

private func needsDoneAccessory(_ keyboard: String) -> Bool {
    ["number", "decimal", "phone"].contains(keyboard)
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
    case "done": .done
    case "go": .go
    case "next": .next
    case "search": .search
    case "send": .send
    default: .return
    }
}
