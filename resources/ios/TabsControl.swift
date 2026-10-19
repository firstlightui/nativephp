import SwiftUI
import UIKit

/// Visible segment title. iOS segments show a title or an image, not a
/// badge, so a visible count follows the label inside the native title.
func firstlightTabTitle(label: String, badge: String) -> String {
    badge.isEmpty ? label : "\(label) (\(badge))"
}

/// Tabs on iOS: a native `UISegmentedControl`, which Apple's Human Interface
/// Guidelines permit for switching between views, exposed to VoiceOver as a
/// tab bar whose segments are individually selectable tabs.
struct FirstlightTabsControl: View {
    let labels: [String]
    let badges: [String]
    let accessibilityLabels: [String]
    let tabEnabled: [Bool]
    let selectedIndex: Int?
    let disabled: Bool
    let tokens: FirstlightSegmentedTokens
    let groupAccessibilityLabel: String
    let groupAccessibilityHint: String
    let onSelection: (Int) -> Void

    var body: some View {
        FirstlightTabsSegmentedControl(
            titles: labels.indices.map { index in
                firstlightTabTitle(
                    label: labels[index],
                    badge: badges.indices.contains(index) ? badges[index] : ""
                )
            },
            tabEnabled: tabEnabled,
            selectedIndex: selectedIndex,
            disabled: disabled,
            tokens: tokens,
            onSelection: onSelection
        )
        .frame(maxWidth: .infinity, minHeight: 44)
        .accessibilityRepresentation {
            // UISegmentedControl reads visible titles only. The representation
            // keeps the native control on screen while VoiceOver receives one
            // tab per segment with badge text, selected state, and position.
            HStack(spacing: 0) {
                ForEach(labels.indices, id: \.self) { index in
                    Button(accessibilityLabel(at: index)) {
                        onSelection(index)
                    }
                    .disabled(!isEnabled(index))
                    .accessibilityAddTraits(selectedIndex == index ? .isSelected : [])
                }
            }
            .accessibilityElement(children: .contain)
            .accessibilityAddTraits(.isTabBar)
            .accessibilityLabel(groupAccessibilityLabel)
            .accessibilityHint(groupAccessibilityHint)
        }
    }

    func accessibilityLabel(at index: Int) -> String {
        let explicit = accessibilityLabels.indices.contains(index) ? accessibilityLabels[index] : ""

        return explicit.isEmpty ? labels[index] : explicit
    }

    func isEnabled(_ index: Int) -> Bool {
        !disabled && tabEnabled.indices.contains(index) && tabEnabled[index]
    }
}

struct FirstlightTabsSegmentedControl: UIViewRepresentable {
    let titles: [String]
    let tabEnabled: [Bool]
    var selectedIndex: Int?
    let disabled: Bool
    let tokens: FirstlightSegmentedTokens
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
        let control = UISegmentedControl(items: titles)
        control.translatesAutoresizingMaskIntoConstraints = false
        // Section names differ in length ("Sites", "Deployments (2)"), so
        // segments take their content width instead of truncating equally.
        control.apportionsSegmentWidthsByContent = true
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
        if control.numberOfSegments != titles.count {
            control.removeAllSegments()
            for (index, title) in titles.enumerated() {
                control.insertSegment(withTitle: title, at: index, animated: false)
            }
        } else {
            for (index, title) in titles.enumerated()
            where control.titleForSegment(at: index) != title {
                control.setTitle(title, forSegmentAt: index)
            }
        }

        for index in titles.indices {
            control.setEnabled(
                !disabled && tabEnabled.indices.contains(index) && tabEnabled[index],
                forSegmentAt: index
            )
        }

        if let selectedIndex, titles.indices.contains(selectedIndex) {
            control.selectedSegmentIndex = selectedIndex
        } else {
            control.selectedSegmentIndex = UISegmentedControl.noSegment
        }

        control.isEnabled = !disabled && !titles.isEmpty
        control.selectedSegmentTintColor = tokens.tintColor

        let titleFont = FirstlightSegmentedControl.titleFont(compatibleWith: control.traitCollection)
        control.setTitleTextAttributes(
            [.font: titleFont, .foregroundColor: tokens.unselectedTextColor],
            for: .normal
        )
        control.setTitleTextAttributes(
            [.font: titleFont, .foregroundColor: tokens.selectedTextColor],
            for: .selected
        )
        control.setTitleTextAttributes(
            [.font: titleFont, .foregroundColor: tokens.disabledTextColor],
            for: .disabled
        )
    }

    @MainActor
    final class Coordinator: NSObject {
        var parent: FirstlightTabsSegmentedControl

        init(parent: FirstlightTabsSegmentedControl) {
            self.parent = parent
        }

        @objc func changed(_ sender: UISegmentedControl) {
            let index = sender.selectedSegmentIndex

            // UIKit has already moved the selection. Restore PHP's accepted
            // tab first; the next publication decides what becomes selected.
            sender.selectedSegmentIndex = parent.selectedIndex ?? UISegmentedControl.noSegment

            guard index != UISegmentedControl.noSegment,
                  index != parent.selectedIndex,
                  !parent.disabled,
                  parent.tabEnabled.indices.contains(index),
                  parent.tabEnabled[index]
            else {
                return
            }

            parent.onSelection(index)
        }
    }
}
