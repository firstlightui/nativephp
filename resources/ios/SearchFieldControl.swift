import SwiftUI
import UIKit

/// Bare `UISearchTextField` reports ~28pt intrinsic height outside `UISearchBar`.
/// Firstlight enforces a floor so the control remains a viable tap target on its own.
let firstlightSearchFieldMinimumHeight: CGFloat = 36

final class FirstlightSearchTextField: UISearchTextField {
    /// Set when a tap lands on the field's own clear button while it is focused. NativePHP
    /// resigns the keyboard on taps outside the first responder, and the clear button counts
    /// as outside, so the field turns down that resign until the clear has run.
    private var keepsFocusUntil: Date?

    override func hitTest(_ point: CGPoint, with event: UIEvent?) -> UIView? {
        let hit = super.hitTest(point, with: event)
        if isFirstResponder, let hit, hit !== self, hit is UIButton {
            keepsFocusUntil = Date().addingTimeInterval(1)
        }
        return hit
    }

    override func resignFirstResponder() -> Bool {
        if let keepsFocusUntil, Date() < keepsFocusUntil { return false }
        return super.resignFirstResponder()
    }

    /// Ends the hold once the clear has run, so the next tap away dismisses the keyboard.
    func releaseFocusHold() {
        DispatchQueue.main.async { self.keepsFocusUntil = nil }
    }

    override var intrinsicContentSize: CGSize {
        let size = super.intrinsicContentSize
        return CGSize(
            width: size.width,
            height: max(size.height, firstlightSearchFieldMinimumHeight)
        )
    }
}

struct FirstlightSearchFieldControl: UIViewRepresentable {
    let configuration: SearchFieldRendererConfiguration
    @Binding var text: String
    let onClear: () -> Void
    let onSubmit: () -> Void
    let onFocusChanged: (Bool) -> Void

    func makeCoordinator() -> Coordinator {
        Coordinator(parent: self)
    }

    func makeUIView(context: Context) -> FirstlightSearchTextField {
        let field = FirstlightSearchTextField(frame: .zero)
        field.delegate = context.coordinator
        field.addTarget(context.coordinator, action: #selector(Coordinator.editingChanged(_:)), for: .editingChanged)
        field.setContentHuggingPriority(.defaultLow, for: .horizontal)
        field.setContentCompressionResistancePriority(.defaultLow, for: .horizontal)
        configureSearchTextField(field, configuration: configuration)
        field.text = text
        return field
    }

    func updateUIView(_ field: FirstlightSearchTextField, context: Context) {
        context.coordinator.parent = self
        configureSearchTextField(field, configuration: configuration)

        guard field.text != text, field.markedTextRange == nil else { return }
        let selectionOffsets = field.selectedTextRange.map {
            (
                field.offset(from: field.beginningOfDocument, to: $0.start),
                field.offset(from: field.beginningOfDocument, to: $0.end)
            )
        }
        field.text = text
        if let (startOffset, endOffset) = selectionOffsets,
           let start = field.position(
               from: field.beginningOfDocument,
               offset: min(startOffset, text.utf16.count)
           ),
           let end = field.position(
               from: field.beginningOfDocument,
               offset: min(endOffset, text.utf16.count)
           ) {
            field.selectedTextRange = field.textRange(from: start, to: end)
        }
    }

    final class Coordinator: NSObject, UITextFieldDelegate {
        var parent: FirstlightSearchFieldControl

        init(parent: FirstlightSearchFieldControl) {
            self.parent = parent
        }

        @objc func editingChanged(_ sender: UISearchTextField) {
            parent.text = sender.text ?? ""
            applySearchClearButtonAccessibility(sender, label: parent.configuration.clearA11yLabel)
        }

        func textFieldDidBeginEditing(_ textField: UITextField) {
            parent.onFocusChanged(true)
            if let field = textField as? UISearchTextField {
                applySearchClearButtonAccessibility(field, label: parent.configuration.clearA11yLabel)
            }
        }

        func textFieldDidEndEditing(_ textField: UITextField) {
            parent.onFocusChanged(false)
        }

        func textFieldShouldClear(_ textField: UITextField) -> Bool {
            (textField as? FirstlightSearchTextField)?.releaseFocusHold()
            guard !parent.configuration.disabled else { return false }
            parent.text = ""
            parent.onClear()
            return false
        }

        func textFieldShouldReturn(_ textField: UITextField) -> Bool {
            guard !parent.configuration.disabled else { return false }
            parent.onSubmit()
            return true
        }
    }
}

func configureSearchTextField(
    _ field: UISearchTextField,
    configuration: SearchFieldRendererConfiguration
) {
    field.placeholder = configuration.placeholder
    field.isEnabled = !configuration.disabled
    // UISearchTextField keeps full-contrast text when disabled; dim it like other disabled fields.
    field.textColor = configuration.disabled ? .tertiaryLabel : .label
    // At the largest text sizes UIKit hides the magnifier, with alpha, once the field has text,
    // and lays the text over its place. Dim only a magnifier UIKit is showing.
    if let magnifier = field.leftView, magnifier.alpha > 0 {
        magnifier.alpha = configuration.disabled ? 0.38 : 1
    }
    field.clearButtonMode = configuration.disabled ? .never : .whileEditing
    field.returnKeyType = .search
    field.enablesReturnKeyAutomatically = false
    field.accessibilityLabel = configuration.accessibilityLabel
    field.accessibilityHint = configuration.accessibilityHint.isEmpty ? nil : configuration.accessibilityHint
    field.adjustsFontForContentSizeCategory = true
    field.autocapitalizationType = switch configuration.autocapitalize {
    case "none": .none
    case "sentences": .sentences
    case "words": .words
    case "characters": .allCharacters
    default: .none
    }
    field.autocorrectionType = switch configuration.autocorrectPolicy {
    case "enabled": .yes
    case "disabled": .no
    default: .default
    }
    applySearchClearButtonAccessibility(field, label: configuration.clearA11yLabel)
}

func searchClearButton(in field: UISearchTextField) -> UIButton? {
    var found: UIButton?
    func visit(_ view: UIView) {
        guard found == nil else { return }
        if let button = view as? UIButton,
           NSStringFromClass(type(of: button)).localizedCaseInsensitiveContains("clear") {
            found = button
            return
        }
        view.subviews.forEach(visit)
    }
    visit(field)
    return found
}

func applySearchClearButtonAccessibility(_ field: UISearchTextField, label: String) {
    searchClearButton(in: field)?.accessibilityLabel = label
}
