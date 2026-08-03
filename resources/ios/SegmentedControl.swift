import SwiftUI
import UIKit

struct SegmentedSelectionState: Equatable {
    private(set) var selectedIndex: Int?

    init(selectedIndex: Int?) {
        self.selectedIndex = selectedIndex
    }

    @discardableResult
    mutating func select(
        _ index: Int,
        optionEnabled: [Bool],
        disabled: Bool
    ) -> Bool {
        guard !disabled,
              optionEnabled.indices.contains(index),
              optionEnabled[index],
              selectedIndex != index
        else {
            return false
        }

        selectedIndex = index
        return true
    }

    mutating func reconcile(
        hasSelection: Bool,
        selectedValue: String,
        optionValues: [String]
    ) {
        selectedIndex = Self.selectedIndex(
            hasSelection: hasSelection,
            selectedValue: selectedValue,
            optionValues: optionValues
        )
    }

    static func selectedIndex(
        hasSelection: Bool,
        selectedValue: String,
        optionValues: [String]
    ) -> Int? {
        guard hasSelection else { return nil }

        return optionValues.firstIndex(of: selectedValue)
    }
}

struct FirstlightSegmentedTokens {
    let tintColor: UIColor
    let unselectedTextColor: UIColor
    let selectedTextColor: UIColor
    let labelColor: Color
    let helperColor: Color
    let errorColor: Color
}

struct FirstlightSegmentedField: View {
    let label: String
    let helper: String
    let error: String
    let required: Bool
    let labels: [String]
    let optionEnabled: [Bool]
    let disabled: Bool
    @Binding var selectionState: SegmentedSelectionState
    let tokens: FirstlightSegmentedTokens
    let accessibilityLabel: String
    let accessibilityHint: String
    let onSelection: (Int) -> Void

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            if !label.isEmpty {
                HStack(spacing: 2) {
                    Text(label)
                    if required {
                        Text("*")
                            .accessibilityHidden(true)
                    }
                }
                .font(.subheadline.weight(.medium))
                .foregroundStyle(tokens.labelColor)
            }

            FirstlightSegmentedControl(
                labels: labels,
                optionEnabled: optionEnabled,
                selectedIndex: selectionState.selectedIndex,
                disabled: disabled,
                tintColor: tokens.tintColor,
                unselectedTextColor: tokens.unselectedTextColor,
                selectedTextColor: tokens.selectedTextColor,
                accessibilityLabel: accessibilityLabel,
                accessibilityHint: accessibilityHint,
                onSelection: onSelection
            )
            .frame(maxWidth: .infinity, minHeight: 44)

            if !helper.isEmpty {
                Text(helper)
                    .font(.footnote)
                    .foregroundStyle(tokens.helperColor)
            }

            if !error.isEmpty {
                Text(error)
                    .font(.footnote)
                    .foregroundStyle(tokens.errorColor)
                    .accessibilityLabel("Error: \(error)")
            }
        }
        .opacity(disabled ? 0.6 : 1)
    }
}

struct FirstlightSegmentedControl: UIViewRepresentable {
    let labels: [String]
    let optionEnabled: [Bool]
    var selectedIndex: Int?
    let disabled: Bool
    let tintColor: UIColor
    let unselectedTextColor: UIColor
    let selectedTextColor: UIColor
    let accessibilityLabel: String
    let accessibilityHint: String
    let onSelection: (Int) -> Void

    func makeCoordinator() -> Coordinator {
        Coordinator(parent: self)
    }

    func makeUIView(context: Context) -> UISegmentedControl {
        makeControl(coordinator: context.coordinator)
    }

    func updateUIView(_ control: UISegmentedControl, context: Context) {
        context.coordinator.parent = self
        updateControl(control)
    }

    func makeControl(coordinator: Coordinator? = nil) -> UISegmentedControl {
        let control = UISegmentedControl(items: labels)
        control.translatesAutoresizingMaskIntoConstraints = false
        control.apportionsSegmentWidthsByContent = false
        control.setContentCompressionResistancePriority(.defaultLow, for: .horizontal)

        let minimumHeight = control.heightAnchor.constraint(greaterThanOrEqualToConstant: 44)
        minimumHeight.identifier = "Firstlight.minimumTapTarget"
        minimumHeight.isActive = true

        if let coordinator {
            control.addTarget(
                coordinator,
                action: #selector(Coordinator.changed(_:)),
                for: .valueChanged
            )
        }

        updateControl(control)
        return control
    }

    func updateControl(_ control: UISegmentedControl) {
        synchronizeSegments(in: control)

        for index in labels.indices {
            let enabled = !disabled
                && optionEnabled.indices.contains(index)
                && optionEnabled[index]
            control.setEnabled(enabled, forSegmentAt: index)
        }

        if let selectedIndex, labels.indices.contains(selectedIndex) {
            control.selectedSegmentIndex = selectedIndex
        } else {
            control.selectedSegmentIndex = UISegmentedControl.noSegment
        }

        control.isEnabled = !disabled && !labels.isEmpty
        control.selectedSegmentTintColor = tintColor
        control.accessibilityLabel = accessibilityLabel.nilIfEmpty
        control.accessibilityHint = accessibilityHint.nilIfEmpty

        let font = UIFontMetrics(forTextStyle: .body).scaledFont(
            for: UIFont.systemFont(ofSize: 17),
            maximumPointSize: 24,
            compatibleWith: control.traitCollection
        )
        control.setTitleTextAttributes(
            [.font: font, .foregroundColor: unselectedTextColor],
            for: .normal
        )
        let effectiveSelectedTextColor: UIColor
        if #available(iOS 26.0, *) {
            // iOS 26's native glass selection capsule does not necessarily
            // paint `selectedSegmentTintColor`; keep text contrasted against
            // the system-owned capsule while still supplying the theme tint.
            effectiveSelectedTextColor = unselectedTextColor
        } else {
            effectiveSelectedTextColor = selectedTextColor
        }
        control.setTitleTextAttributes(
            [.font: font, .foregroundColor: effectiveSelectedTextColor],
            for: .selected
        )
    }

    private func synchronizeSegments(in control: UISegmentedControl) {
        if control.numberOfSegments != labels.count {
            control.removeAllSegments()
            for (index, label) in labels.enumerated() {
                control.insertSegment(withTitle: label, at: index, animated: false)
            }
            return
        }

        for (index, label) in labels.enumerated()
        where control.titleForSegment(at: index) != label {
            control.setTitle(label, forSegmentAt: index)
        }
    }

    @MainActor
    final class Coordinator: NSObject {
        var parent: FirstlightSegmentedControl

        init(parent: FirstlightSegmentedControl) {
            self.parent = parent
        }

        @objc func changed(_ sender: UISegmentedControl) {
            let index = sender.selectedSegmentIndex
            guard index != UISegmentedControl.noSegment,
                  !parent.disabled,
                  parent.optionEnabled.indices.contains(index),
                  parent.optionEnabled[index],
                  sender.isEnabledForSegment(at: index),
                  parent.selectedIndex != index
            else {
                return
            }

            parent.selectedIndex = index
            parent.onSelection(index)
        }
    }
}

private extension String {
    var nilIfEmpty: String? {
        isEmpty ? nil : self
    }
}
