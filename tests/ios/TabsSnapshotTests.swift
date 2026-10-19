import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

private nonisolated func makeNativeTabsEvents() -> TabsRendererEvents {
    .native
}

@MainActor
final class TabsRendererContractTests: XCTestCase {
    func testNativeEventTransportCanBeResolvedFromANonisolatedContext() {
        XCTAssertNotNil(makeNativeTabsEvents())
    }

    func testConfigurationDecodesValuesBadgesAccessibilityAndSelection() {
        let configuration = TabsRendererConfiguration(node: makeNode(selectedValue: "deploys"))

        XCTAssertEqual(configuration.values, ["overview", "sites", "deploys", "manage"])
        XCTAssertEqual(configuration.badges, ["", "", "2", ""])
        XCTAssertEqual(configuration.accessibilityLabels[2], "Deployments, 2 failed")
        XCTAssertEqual(configuration.enabled, [true, true, true, false])
        XCTAssertEqual(configuration.selectedIndex, 2)
        XCTAssertEqual(configuration.groupAccessibilityLabel, "Server sections")
    }

    func testNullAndUnknownValuesLeaveEveryTabUnselected() {
        XCTAssertNil(TabsRendererConfiguration(node: makeNode(selectedValue: nil)).selectedIndex)
        XCTAssertNil(TabsRendererConfiguration(node: makeNode(selectedValue: "archived")).selectedIndex)
    }

    func testTapProposesStableValueWithoutMovingSelection() {
        let state = TabsRendererState(node: makeNode(selectedValue: "overview"))

        let event = state.userSelected(2)

        XCTAssertEqual(event, TabsRendererEvent(callbackId: 41, nodeId: 7, value: "deploys"))
        XCTAssertEqual(event?.wireName, "SELECT_CHANGE")
        XCTAssertEqual(state.configuration.selectedIndex, 0)
    }

    func testSelectedDisabledOutOfRangeAndUnboundTapsEmitNothing() {
        let state = TabsRendererState(node: makeNode(selectedValue: "overview"))

        XCTAssertNil(state.userSelected(0))
        XCTAssertNil(state.userSelected(3))
        XCTAssertNil(state.userSelected(9))
        XCTAssertNil(TabsRendererState(node: makeNode(selectedValue: "overview", disabled: true)).userSelected(1))
        XCTAssertNil(TabsRendererState(node: makeNode(selectedValue: "overview", onChange: 0)).userSelected(1))
    }

    func testServerPublicationDecidesSelectionIncludingRejection() {
        var state = TabsRendererState(node: makeNode(selectedValue: "overview"))
        _ = state.userSelected(1)

        XCTAssertFalse(state.serverPublished(tree(makeNode(selectedValue: "overview"))))
        XCTAssertEqual(state.configuration.selectedIndex, 0)

        let nested = NativeUINode(
            id: 1,
            type: "column",
            layout: nil,
            style: nil,
            props: GenericProps(),
            onPress: 0,
            onLongPress: 0,
            children: [makeNode(selectedValue: "sites")]
        )
        XCTAssertTrue(state.serverPublished(tree(nested)))
        XCTAssertEqual(state.configuration.selectedIndex, 1)
    }

    func testUIKitControlRestoresAcceptedTabBeforeProposing() {
        var proposed: [Int] = []
        let controlView = segmentedControl(selectedIndex: 0) { proposed.append($0) }
        let coordinator = controlView.makeCoordinator()
        let control = controlView.makeControl(coordinator: coordinator)

        control.selectedSegmentIndex = 2
        coordinator.changed(control)
        control.selectedSegmentIndex = 3
        coordinator.changed(control)

        XCTAssertEqual(control.selectedSegmentIndex, 0)
        XCTAssertEqual(proposed, [2])
    }

    func testUIKitControlShowsBadgesInTitlesAndSizesSegmentsByContent() {
        let control = segmentedControl(selectedIndex: nil) { _ in }.makeControl()

        XCTAssertEqual(
            (0..<control.numberOfSegments).compactMap { control.titleForSegment(at: $0) },
            ["Overview", "Sites", "Deployments (2)", "Manage"]
        )
        XCTAssertTrue(control.apportionsSegmentWidthsByContent)
        XCTAssertEqual(control.selectedSegmentIndex, UISegmentedControl.noSegment)
        XCTAssertFalse(control.isEnabledForSegment(at: 3))
        XCTAssertTrue(control.constraints.contains {
            $0.identifier == "Firstlight.minimumTapTarget" && $0.constant == 44
        })
    }

    func testAccessibilityNamesIncludeBadgeText() {
        let control = tabsControl(selectedIndex: 2, disabled: false)

        XCTAssertEqual(control.accessibilityLabel(at: 2), "Deployments, 2 failed")
        XCTAssertEqual(control.accessibilityLabel(at: 0), "Overview")
        XCTAssertTrue(control.isEnabled(1))
        XCTAssertFalse(control.isEnabled(3))
        XCTAssertFalse(tabsControl(selectedIndex: 2, disabled: true).isEnabled(1))
    }

    func testRendererCompilesAgainstNativePHPContractShims() {
        let renderer = TabsRenderer(node: makeNode(selectedValue: "overview"), events: TabsRendererEvents { _ in })

        XCTAssertNotNil(renderer.body)
    }

    private func segmentedControl(
        selectedIndex: Int?,
        onSelection: @escaping (Int) -> Void
    ) -> FirstlightTabsSegmentedControl {
        FirstlightTabsSegmentedControl(
            titles: ["Overview", "Sites", "Deployments (2)", "Manage"],
            tabEnabled: [true, true, true, false],
            selectedIndex: selectedIndex,
            disabled: false,
            tokens: tabsTestTokens(style: .light),
            onSelection: onSelection
        )
    }

    private func tree(_ root: NativeUINode) -> NativeUITree {
        NativeUITree(version: 0, callbackCount: 0, root: root)
    }
}

@MainActor
final class TabsControlSnapshotTests: XCTestCase {
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
            of: makeViewController(style: .light, contentSize: .accessibilityExtraExtraExtraLarge),
            as: .image(on: .iPhoneSe),
            named: "accessibility-extra-extra-extra-large",
            record: recordMode
        )
    }

    private func makeViewController(
        style: UIUserInterfaceStyle,
        contentSize: UIContentSizeCategory
    ) -> UIViewController {
        let view = VStack(alignment: .leading, spacing: 16) {
            tabsControl(selectedIndex: 2, disabled: false, style: style)
            Text("No selection").padding(.horizontal)
            tabsControl(selectedIndex: nil, disabled: false, style: style)
            Text("Disabled group").padding(.horizontal)
            tabsControl(selectedIndex: 1, disabled: true, style: style)
            Spacer()
        }
        // Full width, as documented: the control supplies its own iOS margin.
        .padding(.vertical, 16)
        .background(Color(uiColor: .systemBackground))

        return TabsTraitHostingController(rootView: view, style: style, contentSize: contentSize)
    }
}

@MainActor
private final class TabsTraitHostingController<Content: View>: UIViewController {
    private let host: UIHostingController<Content>
    private let traits: UITraitCollection

    init(rootView: Content, style: UIUserInterfaceStyle, contentSize: UIContentSizeCategory) {
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

private func tabsTestTokens(style: UIUserInterfaceStyle) -> FirstlightSegmentedTokens {
    let theme = NativeUITokens(
        primary: Color(uiColor: UIColor(red: 15 / 255, green: 118 / 255, blue: 110 / 255, alpha: 1)),
        onPrimary: Color(uiColor: .white),
        surface: Color(uiColor: .systemBackground),
        onSurface: Color(uiColor: .label),
        surfaceVariant: Color(uiColor: .secondarySystemBackground),
        onSurfaceVariant: Color(uiColor: .secondaryLabel),
        destructive: Color(uiColor: .systemRed)
    )

    return FirstlightSegmentedTokens.from(theme: theme, traits: UITraitCollection(userInterfaceStyle: style))
}

@MainActor
private func tabsControl(
    selectedIndex: Int?,
    disabled: Bool,
    style: UIUserInterfaceStyle = .light
) -> FirstlightTabsControl {
    FirstlightTabsControl(
        labels: ["Overview", "Sites", "Deployments", "Manage"],
        badges: ["", "", "2", ""],
        accessibilityLabels: ["Overview", "Sites", "Deployments, 2 failed", "Manage"],
        tabEnabled: [true, true, true, false],
        selectedIndex: selectedIndex,
        disabled: disabled,
        tokens: tabsTestTokens(style: style),
        groupAccessibilityLabel: "Server sections",
        groupAccessibilityHint: "",
        onSelection: { _ in }
    )
}

private func makeNode(
    selectedValue: String?,
    disabled: Bool = false,
    onChange: Int = 41
) -> NativeUINode {
    NativeUINode(
        id: 7,
        type: "firstlight.tabs",
        layout: nil,
        style: nil,
        props: GenericProps([
            "tab_values": ["overview", "sites", "deploys", "manage"],
            "tab_labels": ["Overview", "Sites", "Deployments", "Manage"],
            "tab_badges": ["", "", "2", ""],
            "tab_a11y_labels": ["Overview", "Sites", "Deployments, 2 failed", "Manage"],
            "tab_enabled": ["1", "1", "1", "0"],
            "has_selection": selectedValue != nil,
            "selected_value": selectedValue ?? "",
            "on_change": onChange,
            "disabled": disabled,
            "a11y_label": "Server sections",
        ]),
        onPress: 0,
        onLongPress: 0,
        children: []
    )
}
