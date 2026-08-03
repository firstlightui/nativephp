import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class SegmentedControlSnapshotTests: XCTestCase {
    func testNewSelectionWaitsForServerAndAllowsRepeatedAttempts() {
        var state = SegmentedSelectionState(selectedIndex: 0)
        var stateObservedAtEmission: [Int?] = []

        func select(_ index: Int) {
            if state.select(index, optionEnabled: [true, true], disabled: false) {
                stateObservedAtEmission.append(state.selectedIndex)
            }
        }

        select(1)
        select(1)

        XCTAssertEqual(state.selectedIndex, 0)
        XCTAssertEqual(stateObservedAtEmission, [0, 0])
    }

    func testDisabledSelectionsDoNotEmit() {
        var state = SegmentedSelectionState(selectedIndex: 0)
        var changes: [Int] = []

        if state.select(1, optionEnabled: [true, false], disabled: false) {
            changes.append(1)
        }
        if state.select(1, optionEnabled: [true, true], disabled: true) {
            changes.append(1)
        }

        XCTAssertEqual(state.selectedIndex, 0)
        XCTAssertEqual(changes, [])
    }

    func testServerEchoAndCorrectionNeverEmit() {
        var state = SegmentedSelectionState(selectedIndex: 0)
        var changes: [Int] = []

        if state.select(1, optionEnabled: [true, true], disabled: false) {
            changes.append(1)
        }

        state.reconcile(hasSelection: true, selectedValue: "all", optionValues: ["mine", "all"])
        XCTAssertEqual(state.selectedIndex, 1)
        XCTAssertEqual(changes, [1])

        state.reconcile(hasSelection: true, selectedValue: "mine", optionValues: ["mine", "all"])
        XCTAssertEqual(state.selectedIndex, 0)
        XCTAssertEqual(changes, [1])
    }

    func testNullAndEmptyStringSelectionsRemainDistinct() {
        var state = SegmentedSelectionState(selectedIndex: 1)

        state.reconcile(hasSelection: false, selectedValue: "", optionValues: ["", "mine"])
        XCTAssertNil(state.selectedIndex)

        state.reconcile(hasSelection: true, selectedValue: "", optionValues: ["", "mine"])
        XCTAssertEqual(state.selectedIndex, 0)
    }

    func testSelectionAndDisabledSegment() {
        let control = makeControl(
            values: ["mine", "all"],
            enabled: [true, false],
            selected: "mine"
        )

        XCTAssertEqual(control.selectedSegmentIndex, 0)
        XCTAssertTrue(control.isEnabledForSegment(at: 0))
        XCTAssertFalse(control.isEnabledForSegment(at: 1))
    }

    func testProgrammaticReconciliationDoesNotEmitChange() {
        var changes: [Int] = []
        var representable = makeRepresentable(onSelection: { changes.append($0) })
        let control = representable.makeControl()

        representable.selectedIndex = 1
        representable.updateControl(control)

        XCTAssertEqual(control.selectedSegmentIndex, 1)
        XCTAssertEqual(changes, [])
    }

    func testCoordinatorRejectsRepeatedAndDisabledControlEvents() {
        var changes: [Int] = []
        let representable = makeRepresentable(onSelection: { changes.append($0) })
        let coordinator = representable.makeCoordinator()
        let control = representable.makeControl(coordinator: coordinator)

        XCTAssertTrue(control.allTargets.contains(coordinator))
        XCTAssertEqual(
            control.actions(forTarget: coordinator, forControlEvent: .valueChanged),
            ["changed:"]
        )

        control.selectedSegmentIndex = 1
        coordinator.changed(control)
        coordinator.changed(control)
        control.setEnabled(false, forSegmentAt: 0)
        control.selectedSegmentIndex = 0
        coordinator.changed(control)

        XCTAssertEqual(changes, [1])
    }

    func testMinimumTargetAndAccessibilityMetadata() {
        let representable = makeRepresentable(onSelection: { _ in })
        let control = representable.makeControl()
        let fittingSize = control.systemLayoutSizeFitting(
            CGSize(width: 320, height: UIView.layoutFittingCompressedSize.height),
            withHorizontalFittingPriority: .required,
            verticalFittingPriority: .fittingSizeLevel
        )

        XCTAssertGreaterThanOrEqual(fittingSize.height, 44)
        XCTAssertEqual(control.accessibilityLabel, "Document queue, required")
        XCTAssertEqual(control.accessibilityHint, "Changes the active queue")
        XCTAssertEqual(control.accessibilityValue, "Mine, selected. All")
        XCTAssertEqual(control.selectedSegmentTintColor, .systemTeal)
        XCTAssertNotNil(control.titleTextAttributes(for: .normal)?[.font])
        XCTAssertNotNil(control.titleTextAttributes(for: .selected)?[.font])
    }

    func testSemanticTitleColorsApplyToNormalSelectedAndDisabledStates() {
        let representable = makeRepresentable(onSelection: { _ in })
        let control = representable.makeControl()

        XCTAssertEqual(titleColor(named: "unselectedTextColor", in: representable), .label)
        XCTAssertEqual(titleColor(named: "selectedTextColor", in: representable), .white)
        XCTAssertEqual(titleColor(named: "disabledTextColor", in: representable), .secondaryLabel)
        XCTAssertEqual(control.titleTextAttributes(for: .normal)?[.foregroundColor] as? UIColor, .label)
        XCTAssertEqual(control.titleTextAttributes(for: .selected)?[.foregroundColor] as? UIColor, .white)
        XCTAssertEqual(control.titleTextAttributes(for: .disabled)?[.foregroundColor] as? UIColor, .secondaryLabel)
    }

    func testSelectedSemanticTitleColorIsPreservedOnIOS26() {
        guard #available(iOS 26.0, *) else {
            return XCTFail("This contrast regression test requires iOS 26")
        }

        let control = makeRepresentable(onSelection: { _ in }).makeControl()

        XCTAssertEqual(control.titleTextAttributes(for: .selected)?[.foregroundColor] as? UIColor, .white)
    }

    func testDefaultThemeSelectedForegroundsMeetNormalTextContrast() {
        let lightPrimary = UIColor(red: 15 / 255, green: 118 / 255, blue: 110 / 255, alpha: 1)
        let darkPrimary = UIColor(red: 20 / 255, green: 184 / 255, blue: 166 / 255, alpha: 1)

        let lightForeground = FirstlightSegmentedTokens.contrastSafeSelectedTextColor(
            primary: lightPrimary,
            preferred: .white
        )
        let darkForeground = FirstlightSegmentedTokens.contrastSafeSelectedTextColor(
            primary: darkPrimary,
            preferred: .white
        )

        XCTAssertEqual(lightForeground, .white)
        XCTAssertEqual(darkForeground, .black)
        XCTAssertGreaterThanOrEqual(contrastRatio(lightForeground, lightPrimary), 4.5)
        XCTAssertGreaterThanOrEqual(contrastRatio(darkForeground, darkPrimary), 4.5)
    }

    func testSelectedTokenRetainsThemeOnPrimaryWhenItIsContrastSafe() {
        let preferredOnPrimary = UIColor(red: 1, green: 0.8, blue: 0, alpha: 1)
        let theme = NativeUITokens(
            primary: Color(uiColor: .black),
            onPrimary: Color(uiColor: preferredOnPrimary),
            surface: Color(uiColor: .white),
            onSurface: Color(uiColor: .label),
            onSurfaceVariant: Color(uiColor: .secondaryLabel),
            destructive: Color(uiColor: .systemRed)
        )

        let tokens = FirstlightSegmentedTokens.from(theme: theme, traits: lightTraits)

        XCTAssertEqual(tokens.selectedTextColor, preferredOnPrimary)
        XCTAssertEqual(tokens.unselectedTextColor, .label)
        XCTAssertEqual(tokens.disabledTextColor, .secondaryLabel)
    }

    func testTranslucentThemeTokensBecomeOpaqueControlColorsWithSafeContrast() {
        let theme = NativeUITokens(
            primary: Color(uiColor: UIColor(red: 1, green: 1, blue: 1, alpha: 0.5)),
            onPrimary: Color(uiColor: UIColor(white: 0, alpha: 0.5)),
            surface: Color(uiColor: UIColor(red: 0, green: 0, blue: 1, alpha: 0.2)),
            onSurface: Color(uiColor: .label),
            onSurfaceVariant: Color(uiColor: .secondaryLabel),
            destructive: Color(uiColor: .systemRed)
        )

        let tokens = FirstlightSegmentedTokens.from(theme: theme, traits: lightTraits)
        let control = FirstlightSegmentedControl(
            labels: ["Mine", "All"],
            optionEnabled: [true, true],
            selectedIndex: 0,
            disabled: false,
            tintColor: tokens.tintColor,
            unselectedTextColor: tokens.unselectedTextColor,
            selectedTextColor: tokens.selectedTextColor,
            disabledTextColor: tokens.disabledTextColor,
            required: false,
            accessibilityLabel: "Document queue",
            accessibilityHint: "Changes the active queue",
            onSelection: { _ in }
        ).makeControl()

        XCTAssertEqual(alpha(of: tokens.tintColor), 1)
        XCTAssertEqual(alpha(of: tokens.selectedTextColor), 1)
        XCTAssertEqual(alpha(of: control.selectedSegmentTintColor!), 1)
        XCTAssertEqual(
            control.titleTextAttributes(for: .selected)?[.foregroundColor] as? UIColor,
            tokens.selectedTextColor
        )
        XCTAssertGreaterThanOrEqual(contrastRatio(tokens.selectedTextColor, tokens.tintColor), 4.5)
    }

    func testTranslucentOnPrimaryIsRetainedAfterCompositingWhenItIsContrastSafe() {
        let preferredOnPrimary = UIColor(white: 1, alpha: 0.5)
        let theme = NativeUITokens(
            primary: Color(uiColor: UIColor(white: 0, alpha: 0.9)),
            onPrimary: Color(uiColor: preferredOnPrimary),
            surface: Color(uiColor: UIColor(white: 1, alpha: 0.5)),
            onSurface: Color(uiColor: .label),
            onSurfaceVariant: Color(uiColor: .secondaryLabel),
            destructive: Color(uiColor: .systemRed)
        )

        let tokens = FirstlightSegmentedTokens.from(theme: theme, traits: lightTraits)

        XCTAssertEqual(alpha(of: tokens.tintColor), 1)
        XCTAssertEqual(alpha(of: tokens.selectedTextColor), 1)
        XCTAssertNotEqual(tokens.selectedTextColor, .white)
        XCTAssertGreaterThanOrEqual(contrastRatio(tokens.selectedTextColor, tokens.tintColor), 4.5)
    }

    func testAccessibilityExposesEveryFullTitleAndState() {
        let representable = FirstlightSegmentedControl(
            labels: ["Mine", "All referrals", "Unassigned"],
            optionEnabled: [true, true, false],
            selectedIndex: 1,
            disabled: false,
            tintColor: .systemTeal,
            required: true,
            accessibilityLabel: "Document queue",
            accessibilityHint: "Changes the active queue",
            onSelection: { _ in }
        )
        let control = representable.makeControl()

        XCTAssertTrue(control.isAccessibilityElement)
        XCTAssertEqual(control.accessibilityLabel, "Document queue, required")
        XCTAssertEqual(
            control.accessibilityValue,
            "Mine. All referrals, selected. Unassigned, disabled"
        )
    }

    func testSelectionSemanticsDoNotDependOnUIViewAnimations() {
        let animationsWereEnabled = UIView.areAnimationsEnabled
        UIView.setAnimationsEnabled(false)
        defer { UIView.setAnimationsEnabled(animationsWereEnabled) }

        var changes: [Int] = []
        let representable = makeRepresentable(onSelection: { changes.append($0) })
        let coordinator = representable.makeCoordinator()
        let control = representable.makeControl(coordinator: coordinator)

        control.selectedSegmentIndex = 1
        coordinator.changed(control)

        XCTAssertEqual(changes, [1])
        XCTAssertEqual(control.selectedSegmentIndex, 0)
    }

    func testCompactSystemTitlesGrowWithDynamicTypeAndFitAtStandardSize() {
        let standardTraits = UITraitCollection(preferredContentSizeCategory: .large)
        let accessibilityTraits = UITraitCollection(
            preferredContentSizeCategory: .accessibilityExtraExtraExtraLarge
        )
        let standardFont = FirstlightSegmentedControl.titleFont(
            compatibleWith: standardTraits
        )
        let accessibilityFont = FirstlightSegmentedControl.titleFont(
            compatibleWith: accessibilityTraits
        )

        XCTAssertEqual(
            standardFont.fontDescriptor.object(forKey: .textStyle) as? String,
            UIFont.TextStyle.footnote.rawValue
        )
        XCTAssertGreaterThan(accessibilityFont.pointSize, standardFont.pointSize)
        XCTAssertLessThanOrEqual(accessibilityFont.pointSize, 24)

        let titleWidth = ("Unassigned" as NSString).size(
            withAttributes: [.font: standardFont]
        ).width
        let standardSegmentWidth = CGFloat(320 / 3)
        XCTAssertLessThanOrEqual(titleWidth + 16, standardSegmentWidth)
    }

    func testLightDarkAndAccessibilitySnapshots() {
        let recordMode: SnapshotTestingConfiguration.Record = ProcessInfo.processInfo.environment[
            "FIRSTLIGHT_RECORD_SNAPSHOTS"
        ] == "1" ? .all : .never

        assertSnapshot(
            of: makeViewController(style: .light, contentSize: .large),
            as: .image(on: .iPhoneSe),
            named: "light",
            record: recordMode
        )
        assertSnapshot(
            of: makeViewController(style: .dark, contentSize: .large),
            as: .image(on: .iPhoneSe),
            named: "dark",
            record: recordMode
        )
        assertSnapshot(
            of: makeViewController(
                style: .light,
                contentSize: .accessibilityExtraExtraExtraLarge
            ),
            as: .image(on: .iPhoneSe),
            named: "accessibility-extra-extra-extra-large",
            record: recordMode
        )
    }

    private func makeControl(
        values: [String],
        enabled: [Bool],
        selected: String?
    ) -> UISegmentedControl {
        let selectedIndex = selected.flatMap(values.firstIndex(of:))
        let representable = FirstlightSegmentedControl(
            labels: values.map { $0.capitalized },
            optionEnabled: enabled,
            selectedIndex: selectedIndex,
            disabled: false,
            tintColor: .systemTeal,
            required: false,
            accessibilityLabel: "Document queue",
            accessibilityHint: "Changes the active queue",
            onSelection: { _ in }
        )

        return representable.makeControl()
    }

    private func makeRepresentable(
        onSelection: @escaping (Int) -> Void
    ) -> FirstlightSegmentedControl {
        FirstlightSegmentedControl(
            labels: ["Mine", "All"],
            optionEnabled: [true, true],
            selectedIndex: 0,
            disabled: false,
            tintColor: .systemTeal,
            required: true,
            accessibilityLabel: "Document queue",
            accessibilityHint: "Changes the active queue",
            onSelection: onSelection
        )
    }

    private func makeViewController(
        style: UIUserInterfaceStyle,
        contentSize: UIContentSizeCategory
    ) -> UIViewController {
        let state = Binding.constant(SegmentedSelectionState(selectedIndex: nil))
        let view = FirstlightSegmentedField(
            label: "Queue",
            helper: "Choose the referrals shown in this list.",
            error: "The selected queue is unavailable.",
            required: true,
            labels: ["Mine", "All", "Unassigned"],
            optionEnabled: [true, true, false],
            disabled: false,
            selectionState: state,
            tokens: .snapshot,
            accessibilityLabel: "Referral queue",
            accessibilityHint: "Changes the active referral queue",
            onSelection: { _ in }
        )
        .padding(20)
        .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .topLeading)
        .background(Color(uiColor: .systemBackground))

        return TraitHostingController(
            rootView: view,
            style: style,
            contentSize: contentSize
        )
    }

    private func titleColor(
        named name: String,
        in representable: FirstlightSegmentedControl
    ) -> UIColor? {
        Mirror(reflecting: representable).children.first { $0.label == name }?.value as? UIColor
    }

    private func contrastRatio(_ foreground: UIColor, _ background: UIColor) -> CGFloat {
        let foregroundLuminance = relativeLuminance(foreground)
        let backgroundLuminance = relativeLuminance(background)

        return (max(foregroundLuminance, backgroundLuminance) + 0.05)
            / (min(foregroundLuminance, backgroundLuminance) + 0.05)
    }

    private var lightTraits: UITraitCollection {
        UITraitCollection(userInterfaceStyle: .light)
    }

    private func alpha(of color: UIColor) -> CGFloat {
        color.resolvedColor(with: lightTraits).cgColor.alpha
    }

    private func relativeLuminance(_ color: UIColor) -> CGFloat {
        var red: CGFloat = 0
        var green: CGFloat = 0
        var blue: CGFloat = 0
        var alpha: CGFloat = 0
        XCTAssertTrue(color.getRed(&red, green: &green, blue: &blue, alpha: &alpha))

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

private extension FirstlightSegmentedTokens {
    static let snapshot = FirstlightSegmentedTokens(
        tintColor: .firstlightSemantic(light: 0x0F766E, dark: 0x14B8A6),
        unselectedTextColor: .firstlightSemantic(light: 0x0F172A, dark: 0xF8FAFC),
        selectedTextColor: .firstlightSemantic(light: 0xFFFFFF, dark: 0x000000),
        disabledTextColor: .firstlightSemantic(light: 0x475569, dark: 0x94A3B8),
        labelColor: Color(uiColor: .firstlightSemantic(light: 0x0F172A, dark: 0xF8FAFC)),
        helperColor: Color(uiColor: .firstlightSemantic(light: 0x475569, dark: 0x94A3B8)),
        errorColor: Color(uiColor: .firstlightSemantic(light: 0xB91C1C, dark: 0xF87171))
    )
}

private extension UIColor {
    static func firstlightSemantic(light: Int, dark: Int) -> UIColor {
        UIColor { traits in
            let rgb = traits.userInterfaceStyle == .dark ? dark : light
            return UIColor(
                red: CGFloat((rgb >> 16) & 0xFF) / 255,
                green: CGFloat((rgb >> 8) & 0xFF) / 255,
                blue: CGFloat(rgb & 0xFF) / 255,
                alpha: 1
            )
        }
    }
}

@MainActor
private final class TraitHostingController<Content: View>: UIViewController {
    private let host: UIHostingController<Content>
    private let traits: UITraitCollection

    init(
        rootView: Content,
        style: UIUserInterfaceStyle,
        contentSize: UIContentSizeCategory
    ) {
        host = UIHostingController(rootView: rootView)
        traits = UITraitCollection(traitsFrom: [
            UITraitCollection(userInterfaceStyle: style),
            UITraitCollection(preferredContentSizeCategory: contentSize),
        ])
        super.init(nibName: nil, bundle: nil)
    }

    @available(*, unavailable)
    required init?(coder: NSCoder) {
        fatalError("init(coder:) has not been implemented")
    }

    override func viewDidLoad() {
        super.viewDidLoad()
        addChild(host)
        host.view.translatesAutoresizingMaskIntoConstraints = false
        view.addSubview(host.view)
        NSLayoutConstraint.activate([
            host.view.leadingAnchor.constraint(equalTo: view.leadingAnchor),
            host.view.trailingAnchor.constraint(equalTo: view.trailingAnchor),
            host.view.topAnchor.constraint(equalTo: view.topAnchor),
            host.view.bottomAnchor.constraint(equalTo: view.bottomAnchor),
        ])
        host.didMove(toParent: self)
        setOverrideTraitCollection(traits, forChild: host)
    }
}
