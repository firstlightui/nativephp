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
    let disabledTextColor: UIColor
    let labelColor: Color
    let helperColor: Color
    let errorColor: Color

    static func from(theme: NativeUITokens, traits: UITraitCollection) -> Self {
        let surface = opaqueComposite(
            foreground: UIColor(theme.surface),
            over: .systemBackground,
            traits: traits
        )
        let selectedFill = opaqueComposite(
            foreground: UIColor(theme.primary),
            over: surface,
            traits: traits
        )
        let selectedText = opaqueComposite(
            foreground: UIColor(theme.onPrimary),
            over: selectedFill,
            traits: traits
        )

        return Self(
            tintColor: selectedFill,
            unselectedTextColor: UIColor(theme.onSurface),
            selectedTextColor: contrastSafeSelectedTextColor(
                primary: selectedFill,
                preferred: selectedText
            ),
            disabledTextColor: UIColor(theme.onSurfaceVariant),
            labelColor: theme.onSurface,
            helperColor: theme.onSurfaceVariant,
            errorColor: theme.destructive
        )
    }

    static func contrastSafeSelectedTextColor(
        primary: UIColor,
        preferred: UIColor
    ) -> UIColor {
        let preferred = preferred.cgColor.alpha < 1
            ? opaqueComposite(foreground: preferred, over: primary, traits: .current)
            : preferred

        guard contrastRatio(foreground: preferred, background: primary) < 4.5 else {
            return preferred
        }

        let black = UIColor.black
        let white = UIColor.white

        return contrastRatio(foreground: black, background: primary)
            >= contrastRatio(foreground: white, background: primary)
            ? black
            : white
    }

    private static func contrastRatio(foreground: UIColor, background: UIColor) -> CGFloat {
        let foregroundLuminance = relativeLuminance(foreground)
        let backgroundLuminance = relativeLuminance(background)

        return (max(foregroundLuminance, backgroundLuminance) + 0.05)
            / (min(foregroundLuminance, backgroundLuminance) + 0.05)
    }

    static func opaqueComposite(
        foreground: UIColor,
        over background: UIColor,
        traits: UITraitCollection
    ) -> UIColor {
        let foreground = rgba(foreground, traits: traits)
        let background = rgba(background, traits: traits)
        let alpha = foreground.alpha + background.alpha * (1 - foreground.alpha)

        guard alpha > 0 else {
            return .black
        }

        return UIColor(
            red: (foreground.red * foreground.alpha
                + background.red * background.alpha * (1 - foreground.alpha)) / alpha,
            green: (foreground.green * foreground.alpha
                + background.green * background.alpha * (1 - foreground.alpha)) / alpha,
            blue: (foreground.blue * foreground.alpha
                + background.blue * background.alpha * (1 - foreground.alpha)) / alpha,
            alpha: alpha
        )
    }

    private static func rgba(
        _ color: UIColor,
        traits: UITraitCollection
    ) -> (red: CGFloat, green: CGFloat, blue: CGFloat, alpha: CGFloat) {
        let resolved = color.resolvedColor(with: traits)
        var red: CGFloat = 0
        var green: CGFloat = 0
        var blue: CGFloat = 0
        var alpha: CGFloat = 0

        guard resolved.getRed(&red, green: &green, blue: &blue, alpha: &alpha) else {
            return (0, 0, 0, 1)
        }

        return (red, green, blue, alpha)
    }

    private static func relativeLuminance(_ color: UIColor) -> CGFloat {
        var red: CGFloat = 0
        var green: CGFloat = 0
        var blue: CGFloat = 0
        var alpha: CGFloat = 0
        let resolved = color.resolvedColor(with: .current)

        guard resolved.getRed(&red, green: &green, blue: &blue, alpha: &alpha) else {
            return 0
        }

        func linearized(_ channel: CGFloat) -> CGFloat {
            channel <= 0.04045
                ? channel / 12.92
                : pow((channel + 0.055) / 1.055, 2.4)
        }

        return 0.2126 * linearized(red)
            + 0.7152 * linearized(green)
            + 0.0722 * linearized(blue)
    }
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
    var size: FirstlightControlSize = .md
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
                disabledTextColor: tokens.disabledTextColor,
                required: required,
                accessibilityLabel: accessibilityLabel,
                accessibilityHint: accessibilityHint,
                size: size,
                onSelection: onSelection
            )
            .frame(maxWidth: .infinity)
            .firstlightValidationFocusTarget()

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
    let disabledTextColor: UIColor
    let required: Bool
    let accessibilityLabel: String
    let accessibilityHint: String
    let size: FirstlightControlSize
    let onSelection: (Int) -> Void

    init(
        labels: [String],
        optionEnabled: [Bool],
        selectedIndex: Int?,
        disabled: Bool,
        tintColor: UIColor,
        unselectedTextColor: UIColor = .label,
        selectedTextColor: UIColor = .white,
        disabledTextColor: UIColor = .secondaryLabel,
        required: Bool,
        accessibilityLabel: String,
        accessibilityHint: String,
        size: FirstlightControlSize = .md,
        onSelection: @escaping (Int) -> Void
    ) {
        self.labels = labels
        self.optionEnabled = optionEnabled
        self.selectedIndex = selectedIndex
        self.disabled = disabled
        self.tintColor = tintColor
        self.unselectedTextColor = unselectedTextColor
        self.selectedTextColor = selectedTextColor
        self.disabledTextColor = disabledTextColor
        self.required = required
        self.accessibilityLabel = accessibilityLabel
        self.accessibilityHint = accessibilityHint
        self.size = size
        self.onSelection = onSelection
    }

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

    // SwiftUI sizes a hosted UIKit view from its intrinsic content size
    // (31pt for UISegmentedControl) and ignores Auto Layout constraints on
    // the view itself, so a height constraint or an enclosing
    // `.frame(minHeight:)` left the drawn control, and its touch area, at
    // 31pt. Report the minimum height here instead; UISegmentedControl draws
    // its track and segments to fill the bounds it is given.
    func sizeThatFits(
        _ proposal: ProposedViewSize,
        uiView control: UISegmentedControl,
        context: Context
    ) -> CGSize? {
        let intrinsic = control.intrinsicContentSize
        let traits = UITraitCollection(traitsFrom: [
            control.traitCollection,
            UITraitCollection(
                preferredContentSizeCategory: UIContentSizeCategory(context.environment.dynamicTypeSize)
            ),
        ])

        return CGSize(
            width: proposal.width ?? intrinsic.width,
            height: max(size.minimumHeight(compatibleWith: traits), intrinsic.height)
        )
    }

    func makeControl(coordinator: Coordinator? = nil) -> UISegmentedControl {
        let control = UISegmentedControl(items: labels)
        control.translatesAutoresizingMaskIntoConstraints = false
        control.apportionsSegmentWidthsByContent = false
        control.setContentCompressionResistancePriority(.defaultLow, for: .horizontal)

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

        let targetIndex = selectedIndex.flatMap {
            labels.indices.contains($0) ? $0 : nil
        } ?? UISegmentedControl.noSegment
        if control.selectedSegmentIndex != targetIndex {
            control.selectedSegmentIndex = targetIndex
            // UISegmentedControl animates a selection change in its next
            // layout pass. Inside a SwiftUI List that pass runs while the
            // List re-sizes the row with animations disabled, so the pill
            // jumped instead of sliding. Commit the layout now, while UIKit
            // animations are still enabled.
            if control.window != nil {
                control.layoutIfNeeded()
            }
        }

        control.isEnabled = !disabled && !labels.isEmpty
        // Liquid Glass draws its own thumb, which becomes a glass lens while
        // dragged; a tint replaces it with a flat fill, so it is set only before 26.
        control.selectedSegmentTintColor = firstlightUsesLiquidGlass ? nil : tintColor
        control.isAccessibilityElement = true
        control.accessibilityLabel = required
            ? "\(accessibilityLabel), required".nilIfEmpty
            : accessibilityLabel.nilIfEmpty
        control.accessibilityHint = accessibilityHint.nilIfEmpty
        control.accessibilityValue = labels.enumerated().map { index, label in
            var statuses: [String] = []
            if selectedIndex == index { statuses.append("selected") }
            let isEnabled = !disabled
                && optionEnabled.indices.contains(index)
                && optionEnabled[index]
            if !isEnabled { statuses.append("disabled") }

            return statuses.isEmpty
                ? label
                : "\(label), \(statuses.joined(separator: ", "))"
        }.joined(separator: ". ").nilIfEmpty

        let titleFont = Self.titleFont(size: size, compatibleWith: control.traitCollection)
        control.setTitleTextAttributes(
            [.font: titleFont, .foregroundColor: unselectedTextColor],
            for: .normal
        )
        control.setTitleTextAttributes(
            [.font: titleFont, .foregroundColor: firstlightUsesLiquidGlass ? unselectedTextColor : selectedTextColor],
            for: .selected
        )
        control.setTitleTextAttributes(
            [.font: titleFont, .foregroundColor: disabledTextColor],
            for: .disabled
        )
    }

    // Segmented controls use compact, single-line chrome typography: the
    // footnote text style, or subheadline for `lg`, one step larger to suit
    // Button lg's height. Each scales with Dynamic Type up to a ceiling, because the
    // single-line titles truncate rather than wrap; VoiceOver still exposes
    // every full title and state.
    static func titleFont(
        size: FirstlightControlSize = .md,
        compatibleWith traits: UITraitCollection
    ) -> UIFont {
        let (textStyle, maximumPointSize): (UIFont.TextStyle, CGFloat) = switch size {
        case .md: (.footnote, 24)
        case .lg: (.subheadline, 28)
        }
        let standardTraits = UITraitCollection(preferredContentSizeCategory: .large)
        let systemBaseline = UIFont.preferredFont(
            forTextStyle: textStyle,
            compatibleWith: standardTraits
        )

        return UIFontMetrics(forTextStyle: textStyle).scaledFont(
            for: systemBaseline,
            maximumPointSize: maximumPointSize,
            compatibleWith: traits
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

            sender.selectedSegmentIndex = parent.selectedIndex
                ?? UISegmentedControl.noSegment
            parent.onSelection(index)
        }
    }
}

private extension String {
    var nilIfEmpty: String? {
        isEmpty ? nil : self
    }
}
