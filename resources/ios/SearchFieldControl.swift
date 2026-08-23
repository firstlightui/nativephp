import SwiftUI
import UIKit

/// Bare `UISearchTextField` reports ~28pt intrinsic height outside `UISearchBar`.
/// Firstlight enforces a floor so the control remains a viable tap target on its own.
let firstlightSearchFieldMinimumHeight: CGFloat = 36

final class FirstlightSearchTextField: UISearchTextField {
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
        }

        func textFieldDidBeginEditing(_ textField: UITextField) {
            parent.onFocusChanged(true)
        }

        func textFieldDidEndEditing(_ textField: UITextField) {
            parent.onFocusChanged(false)
        }

        func textFieldShouldClear(_ textField: UITextField) -> Bool {
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
    default: .sentences
    }
    field.autocorrectionType = switch configuration.autocorrectPolicy {
    case "enabled": .yes
    case "disabled": .no
    default: .default
    }
}
