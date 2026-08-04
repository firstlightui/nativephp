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
        a11yHint: String = ""
    ) -> NativeUINode {
        NativeUINode(
            id: 7,
            type: "firstlight.pill-group",
            layout: nil,
            style: nil,
            props: GenericProps([
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
            ]),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
    }

    private func tree(root: NativeUINode) -> NativeUITree {
        NativeUITree(version: 0, callbackCount: 0, root: root)
    }
}
