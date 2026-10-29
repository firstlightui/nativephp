import SwiftUI
import SnapshotTesting
import UIKit
import XCTest

@testable import FirstlightIOSControls

private nonisolated func makeNativePillGroupEvents() -> PillGroupRendererEvents {
    .native
}

@MainActor
final class PillGroupControlSnapshotTests: XCTestCase {
    func testLightDarkAndAccessibilitySnapshots() {
        assertFirstlightSnapshot(
            of: makeViewController(style: .light, contentSize: .large),
            as: .image(on: .iPhoneSe),
            named: "light"
        )
        assertFirstlightSnapshot(
            of: makeViewController(style: .dark, contentSize: .large),
            as: .image(on: .iPhoneSe),
            named: "dark"
        )
        assertFirstlightSnapshot(
            of: makeViewController(
                style: .light,
                contentSize: .accessibilityExtraExtraExtraLarge
            ),
            as: .image(on: .iPhoneSe, size: CGSize(width: 320, height: 900)),
            named: "accessibility-extra-extra-extra-large"
        )
    }

    func testCollapsedScrollableRowSnapshots() {
        for style in [UIUserInterfaceStyle.light, .dark] {
            assertFirstlightSnapshot(
                of: makeCollapsedRowViewController(style: style),
                as: .image(on: .iPhoneSe, size: CGSize(width: 320, height: 220)),
                named: style == .dark ? "collapsed-row-dark" : "collapsed-row-light"
            )
        }
    }

    private func makeCollapsedRowViewController(style: UIUserInterfaceStyle) -> UIViewController {
        let traits = UITraitCollection(userInterfaceStyle: style)
        let view = VStack(alignment: .leading, spacing: 16) {
            FirstlightPillGroupField(
                label: "Filter",
                helper: "",
                error: "",
                required: false,
                labels: ["All", "Unread", "Starred", "Attachments", "Mentions", "Archived"],
                values: ["all", "unread", "starred", "attachments", "mentions", "archived"],
                optionEnabled: [true, true, true, true, true, false],
                selectedValues: ["starred"],
                disabled: false,
                awaitingPublication: false,
                tokens: FirstlightPillGroupTokens.from(theme: .pillGroupTest, traits: traits),
                accessibilityLabel: "Filter",
                accessibilityHint: "",
                onSelection: { _ in },
                icons: ["tray", "envelope", "star", "paperclip", "at", "archivebox"],
                scrollable: true,
                collapseLabels: true
            )
            FirstlightPillGroupField(
                label: "Invoices",
                helper: "",
                error: "",
                required: false,
                labels: ["Open", "Overdue", "Paid", "Void"],
                values: ["open", "overdue", "paid", "void"],
                optionEnabled: [true, true, true, true],
                selectedValues: ["overdue"],
                disabled: false,
                awaitingPublication: false,
                tokens: FirstlightPillGroupTokens.from(theme: .pillGroupTest, traits: traits, tone: .danger),
                accessibilityLabel: "Invoices",
                accessibilityHint: "",
                onSelection: { _ in },
                icons: ["doc.text", "clock", "checkmark.circle", "nosign"],
                scrollable: true
            )
        }
        .padding(16)
        .frame(width: 320, alignment: .leading)
        .background(Color(uiColor: .systemBackground))

        return PillGroupTraitHostingController(
            rootView: view,
            style: style,
            contentSize: .large
        )
    }

    func testTranslucentThemeTokensBecomeOpaqueSelectedColors() {
        let theme = NativeUITokens(
            primary: Color(uiColor: .systemTeal),
            onPrimary: Color(uiColor: .white),
            surface: Color(uiColor: .white),
            onSurface: Color(uiColor: .label),
            surfaceVariant: Color(uiColor: .secondarySystemBackground),
            onSurfaceVariant: Color(uiColor: .secondaryLabel),
            destructive: Color(uiColor: UIColor(red: 1, green: 0, blue: 0, alpha: 0.5)),
            onDestructive: Color(uiColor: .clear)
        )

        let tokens = FirstlightPillGroupTokens.from(
            theme: theme,
            traits: UITraitCollection(userInterfaceStyle: .light),
            tone: .danger
        )
        let background = rgba(tokens.selectedBackground)
        let foreground = rgba(tokens.selectedForeground)

        // Half-opaque red over the white surface is an opaque pink fill,
        // and a clear on-destructive falls back to black on it.
        XCTAssertEqual(background.alpha, 1, accuracy: 0.001)
        XCTAssertEqual(background.red, 1, accuracy: 0.01)
        XCTAssertEqual(background.green, 0.5, accuracy: 0.01)
        XCTAssertEqual(background.blue, 0.5, accuracy: 0.01)
        XCTAssertEqual(foreground.alpha, 1, accuracy: 0.001)
        XCTAssertEqual(foreground.red, 0, accuracy: 0.001)
        XCTAssertEqual(foreground.green, 0, accuracy: 0.001)
        XCTAssertEqual(foreground.blue, 0, accuracy: 0.001)
    }

    private func rgba(_ color: Color) -> (red: CGFloat, green: CGFloat, blue: CGFloat, alpha: CGFloat) {
        var red: CGFloat = 0
        var green: CGFloat = 0
        var blue: CGFloat = 0
        var alpha: CGFloat = 0
        XCTAssertTrue(UIColor(color).getRed(&red, green: &green, blue: &blue, alpha: &alpha))

        return (red, green, blue, alpha)
    }

    private func makeViewController(
        style: UIUserInterfaceStyle,
        contentSize: UIContentSizeCategory
    ) -> UIViewController {
        let theme = NativeUITokens(
            primary: Color(uiColor: UIColor(red: 174 / 255, green: 21 / 255, blue: 21 / 255, alpha: 1)),
            onPrimary: Color(uiColor: UIColor(red: 1, green: 245 / 255, blue: 234 / 255, alpha: 1)),
            surface: Color(uiColor: .systemBackground),
            onSurface: Color(uiColor: .label),
            surfaceVariant: Color(uiColor: .secondarySystemBackground),
            onSurfaceVariant: Color(uiColor: .secondaryLabel),
            destructive: Color(uiColor: .systemRed)
        )
        let traits = UITraitCollection(userInterfaceStyle: style)
        let view = FirstlightPillGroupField(
            label: "Referral queues",
            helper: "Choose any that apply.",
            error: "",
            required: true,
            labels: ["Mine", "All referrals", "Needs follow-up", "Archived"],
            values: ["mine", "all", "follow-up", "archived"],
            optionEnabled: [true, true, true, false],
            selectedValues: ["mine", "follow-up"],
            disabled: false,
            awaitingPublication: false,
            tokens: FirstlightPillGroupTokens.from(theme: theme, traits: traits),
            accessibilityLabel: "Referral queues",
            accessibilityHint: "Toggle queue filters",
            onSelection: { _ in }
        )
        .padding(16)
        .frame(width: 320, alignment: .leading)
        .background(Color(uiColor: .systemBackground))

        return PillGroupTraitHostingController(
            rootView: view,
            style: style,
            contentSize: contentSize
        )
    }
}

@MainActor
private final class PillGroupTraitHostingController<Content: View>: UIViewController {
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

@MainActor
final class PillGroupRendererContractTests: XCTestCase {
    func testNativeEventTransportCanBeResolvedFromANonisolatedContext() {
        XCTAssertNotNil(makeNativePillGroupEvents())
    }

    func testConfigurationDecodesCompleteFieldAndSelectionContract() {
        let configuration = PillGroupRendererConfiguration(node: makeNode(
            selectedValues: ["mine", "urgent"],
            multiple: true,
            helper: "Choose any that apply",
            error: "Review the selection",
            a11yLabel: "Queue filters",
            a11yHint: "Double tap to toggle"
        ))

        XCTAssertEqual(configuration.optionValues, ["mine", "all", "urgent"])
        XCTAssertEqual(configuration.optionLabels, ["Mine", "All", "Urgent"])
        XCTAssertEqual(configuration.optionEnabled, [true, true, false])
        XCTAssertEqual(configuration.optionCallbacks, [51, 52, 0])
        XCTAssertEqual(configuration.selectedValues, ["mine", "urgent"])
        XCTAssertTrue(configuration.multiple)
        XCTAssertEqual(configuration.helper, "Choose any that apply")
        XCTAssertEqual(configuration.error, "Review the selection")
        XCTAssertTrue(configuration.required)
        XCTAssertEqual(configuration.accessibilityLabel, "Queue filters")
        XCTAssertEqual(configuration.accessibilityHint, "Double tap to toggle")
    }

    func testConfigurationDecodesIconsRowPresentationAndTone() {
        let configuration = PillGroupRendererConfiguration(node: makeNode(extra: [
            "option_icons": ["tray", "envelope", "star.fill"],
            "option_icon_variants": ["", "", ""],
            "scrollable": true,
            "collapse_labels": true,
            "tone": "danger",
        ]))

        XCTAssertEqual(configuration.optionIcons, ["tray", "envelope", "star.fill"])
        XCTAssertTrue(configuration.scrollable)
        XCTAssertTrue(configuration.collapseLabels)
        XCTAssertEqual(configuration.tone, .danger)
    }

    func testConfigurationDefaultsToWrappingPrimaryGroupWithoutIcons() {
        let configuration = PillGroupRendererConfiguration(node: makeNode(extra: ["tone": "unknown"]))

        XCTAssertEqual(configuration.optionIcons, [])
        XCTAssertFalse(configuration.scrollable)
        XCTAssertFalse(configuration.collapseLabels)
        XCTAssertEqual(configuration.tone, .primary)
    }

    func testEveryToneTintsOnlyTheSelectedPillAndStaysContrastSafe() {
        for style in [UIUserInterfaceStyle.light, .dark] {
            let traits = UITraitCollection(userInterfaceStyle: style)
            let primary = FirstlightPillGroupTokens.from(theme: .pillGroupTest, traits: traits)

            for tone in PillGroupTone.allCases {
                let tokens = FirstlightPillGroupTokens.from(theme: .pillGroupTest, traits: traits, tone: tone)
                let background = UIColor(tokens.selectedBackground).resolvedColor(with: traits)
                let foreground = UIColor(tokens.selectedForeground).resolvedColor(with: traits)

                XCTAssertGreaterThanOrEqual(
                    statusLabelContrastRatio(foreground, background), 4.5, "\(tone) in \(style.rawValue)"
                )
                XCTAssertEqual(tokens.unselectedBackground, primary.unselectedBackground)
                XCTAssertEqual(tokens.unselectedForeground, primary.unselectedForeground)
            }
        }
    }

    func testTapEmitsPressWithoutOptimisticallyChangingSelection() {
        var state = PillGroupRendererState(node: makeNode(selectedValues: ["mine"]))

        let event = state.userSelected(1)

        XCTAssertEqual(event, .press(callbackId: 52, nodeId: 7))
        XCTAssertEqual(event?.wireName, "PRESS")
        XCTAssertEqual(state.configuration.selectedValues, ["mine"])
        XCTAssertTrue(state.isAwaitingPublication)
    }

    func testOnlyOneProposalIsAllowedBeforeTheServerPublishes() {
        var state = PillGroupRendererState(node: makeNode(selectedValues: ["mine"]))

        XCTAssertNotNil(state.userSelected(1))
        XCTAssertNil(state.userSelected(0))
        XCTAssertTrue(state.isAwaitingPublication)

        XCTAssertFalse(state.serverPublished(tree(root: makeNode(selectedValues: ["mine"]))))
        XCTAssertFalse(state.isAwaitingPublication)
        XCTAssertEqual(
            state.userSelected(0),
            .press(callbackId: 51, nodeId: 7)
        )
    }

    func testDisabledGroupOptionAndMissingCallbackAreNoOps() {
        var disabledGroup = PillGroupRendererState(node: makeNode(disabled: true))
        var disabledOption = PillGroupRendererState(node: makeNode())
        var missingCallback = PillGroupRendererState(node: makeNode(
            optionEnabled: [true, true, true],
            optionCallbacks: [51, 0, 53]
        ))

        XCTAssertNil(disabledGroup.userSelected(0))
        XCTAssertNil(disabledOption.userSelected(2))
        XCTAssertNil(missingCallback.userSelected(1))
    }

    func testServerPublicationFindsNestedStableNodeAndReconcilesSelection() {
        var state = PillGroupRendererState(node: makeNode(selectedValues: ["mine"]))
        _ = state.userSelected(1)
        let corrected = makeNode(selectedValues: ["mine", "all"])
        let root = NativeUINode(
            id: 1,
            type: "column",
            layout: nil,
            style: nil,
            props: GenericProps(),
            onPress: 0,
            onLongPress: 0,
            children: [corrected]
        )

        XCTAssertTrue(state.serverPublished(tree(root: root)))
        XCTAssertEqual(state.configuration.selectedValues, ["mine", "all"])
        XCTAssertFalse(state.isAwaitingPublication)
    }

    func testMissingStableNodeDoesNotClearPendingProposal() {
        var state = PillGroupRendererState(node: makeNode(selectedValues: ["mine"]))
        _ = state.userSelected(1)

        let unrelated = NativeUINode(
            id: 99,
            type: "column",
            layout: nil,
            style: nil,
            props: GenericProps(),
            onPress: 0,
            onLongPress: 0,
            children: []
        )

        XCTAssertFalse(state.serverPublished(tree(root: unrelated)))
        XCTAssertTrue(state.isAwaitingPublication)
    }

    func testRendererCompilesAgainstNativePHPContractShims() {
        let renderer = PillGroupRenderer(
            node: makeNode(selectedValues: ["mine"]),
            events: PillGroupRendererEvents { _ in }
        )

        XCTAssertNotNil(renderer.body)
    }

    func testFlowLayoutWrapsAndMirrorsForRightToLeft() {
        let sizes = [
            CGSize(width: 70, height: 44),
            CGSize(width: 60, height: 44),
            CGSize(width: 80, height: 44),
        ]

        let leftToRight = PillFlowLayout.frames(
            containerWidth: 150,
            sizes: sizes,
            horizontalSpacing: 8,
            verticalSpacing: 8,
            layoutDirection: .leftToRight
        )
        let rightToLeft = PillFlowLayout.frames(
            containerWidth: 150,
            sizes: sizes,
            horizontalSpacing: 8,
            verticalSpacing: 8,
            layoutDirection: .rightToLeft
        )

        XCTAssertEqual(leftToRight[0].origin, CGPoint(x: 0, y: 0))
        XCTAssertEqual(leftToRight[1].origin, CGPoint(x: 78, y: 0))
        XCTAssertEqual(leftToRight[2].origin, CGPoint(x: 0, y: 52))
        XCTAssertEqual(rightToLeft[0].origin, CGPoint(x: 80, y: 0))
        XCTAssertEqual(rightToLeft[1].origin, CGPoint(x: 12, y: 0))
        XCTAssertEqual(rightToLeft[2].origin, CGPoint(x: 70, y: 52))
    }

    private func makeNode(
        selectedValues: [String] = [],
        multiple: Bool = false,
        optionEnabled: [Bool] = [true, true, false],
        optionCallbacks: [Int] = [51, 52, 0],
        disabled: Bool = false,
        helper: String = "",
        error: String = "",
        a11yLabel: String = "",
        a11yHint: String = "",
        extra: [String: Any] = [:]
    ) -> NativeUINode {
        NativeUINode(
            id: 7,
            type: "firstlight.pill-group",
            layout: nil,
            style: nil,
            props: GenericProps(([
                "option_values": ["mine", "all", "urgent"],
                "option_labels": ["Mine", "All", "Urgent"],
                "option_enabled": optionEnabled.map { $0 ? "1" : "0" },
                "option_callbacks": optionCallbacks.map(String.init),
                "selected_values": selectedValues,
                "value_type": "string",
                "multiple": multiple,
                "disabled": disabled,
                "label": "Document queue",
                "helper": helper,
                "error": error,
                "required": true,
                "a11y_label": a11yLabel,
                "a11y_hint": a11yHint,
            ] as [String: Any]).merging(extra) { _, new in new }),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
    }

    private func tree(root: NativeUINode) -> NativeUITree {
        NativeUITree(version: 0, callbackCount: 0, root: root)
    }
}

// The shim's `.fallback` leaves on-* tone colours clear; Mobile UI's real
// fallback pairs each tone with an opaque foreground, so tone tests use this.
private extension NativeUITokens {
    static let pillGroupTest = NativeUITokens(
        primary: Color(uiColor: .systemTeal),
        onPrimary: Color(uiColor: .white),
        surface: Color(uiColor: .systemBackground),
        onSurface: Color(uiColor: .label),
        background: Color(uiColor: .systemBackground),
        onBackground: Color(uiColor: .label),
        surfaceVariant: Color(uiColor: .secondarySystemBackground),
        onSurfaceVariant: Color(uiColor: .secondaryLabel),
        destructive: Color(uiColor: .systemRed),
        onDestructive: Color(uiColor: .white),
        success: Color(uiColor: .systemGreen),
        onSuccess: Color(uiColor: .black),
        accent: Color(uiColor: .systemOrange),
        onAccent: Color(uiColor: .white)
    )
}
