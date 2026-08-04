import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

private nonisolated func makeNativeChoiceGroupEvents() -> ChoiceGroupRendererEvents {
    .native
}

@MainActor
final class ChoiceGroupRendererContractTests: XCTestCase {
    func testNativeEventTransportCanBeResolvedFromANonisolatedContext() {
        XCTAssertNotNil(makeNativeChoiceGroupEvents())
    }

    func testConfigurationDecodesCompleteFieldAndSelectionContract() {
        let configuration = ChoiceGroupRendererConfiguration(node: makeNode(
            selectedValues: ["mine", "urgent"],
            multiple: true,
            helper: "Choose any that apply",
            error: "Review the selection",
            a11yLabel: "Queue choices",
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
        XCTAssertEqual(configuration.accessibilityLabel, "Queue choices")
        XCTAssertEqual(configuration.accessibilityHint, "Double tap to toggle")
    }

    func testTapEmitsPressWithoutOptimisticallyChangingSelectionAndSuppressesStaleTaps() {
        var state = ChoiceGroupRendererState(node: makeNode(selectedValues: ["mine"]))

        let event = state.userSelected(1)

        XCTAssertEqual(event, .press(callbackId: 52, nodeId: 7))
        XCTAssertEqual(event?.wireName, "PRESS")
        XCTAssertEqual(state.configuration.selectedValues, ["mine"])
        XCTAssertTrue(state.isAwaitingPublication)
        XCTAssertNil(state.userSelected(0))
    }

    func testEqualPublicationReleasesTheStaleTapGuard() {
        var state = ChoiceGroupRendererState(node: makeNode(selectedValues: ["mine"]))
        _ = state.userSelected(1)

        XCTAssertFalse(state.serverPublished(tree(root: makeNode(selectedValues: ["mine"]))))
        XCTAssertFalse(state.isAwaitingPublication)
        XCTAssertEqual(state.userSelected(1), .press(callbackId: 52, nodeId: 7))
    }

    func testDisabledGroupOptionSelectedRadioAndMissingCallbackAreNoOps() {
        var disabledGroup = ChoiceGroupRendererState(node: makeNode(disabled: true))
        var disabledOption = ChoiceGroupRendererState(node: makeNode())
        var selectedRadio = ChoiceGroupRendererState(node: makeNode(
            selectedValues: ["mine"],
            optionCallbacks: [0, 52, 0]
        ))
        var missingCallback = ChoiceGroupRendererState(node: makeNode(
            optionEnabled: [true, true, true],
            optionCallbacks: [51, 0, 53]
        ))

        XCTAssertNil(disabledGroup.userSelected(0))
        XCTAssertNil(disabledOption.userSelected(2))
        XCTAssertNil(selectedRadio.userSelected(0))
        XCTAssertNil(missingCallback.userSelected(1))
    }

    func testNestedStableNodePublicationReconcilesServerSelection() {
        var state = ChoiceGroupRendererState(node: makeNode(selectedValues: ["mine"]))
        _ = state.userSelected(1)
        let root = NativeUINode(
            id: 1,
            type: "column",
            layout: nil,
            style: nil,
            props: GenericProps(),
            onPress: 0,
            onLongPress: 0,
            children: [makeNode(selectedValues: ["all"])]
        )

        XCTAssertTrue(state.serverPublished(tree(root: root)))
        XCTAssertEqual(state.configuration.selectedValues, ["all"])
        XCTAssertFalse(state.isAwaitingPublication)
    }

    func testMissingStableNodeKeepsProposalPending() {
        var state = ChoiceGroupRendererState(node: makeNode(selectedValues: ["mine"]))
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
        let renderer = ChoiceGroupRenderer(
            node: makeNode(selectedValues: ["mine"]),
            events: ChoiceGroupRendererEvents { _ in }
        )

        XCTAssertNotNil(renderer.body)
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
            type: "firstlight.choice-group",
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

@MainActor
final class ChoiceGroupControlSnapshotTests: XCTestCase {
    func testLightDarkAndAccessibilitySnapshotsWhenEvidenceCaptureIsEnabled() throws {
        guard ProcessInfo.processInfo.environment["FIRSTLIGHT_VERIFY_CHOICE_GROUP_SNAPSHOTS"] == "1" else {
            throw XCTSkip("Choice Group screenshot evidence is controller-owned.")
        }

        let recordMode: SnapshotTestingConfiguration.Record = ProcessInfo.processInfo.environment[
            "FIRSTLIGHT_RECORD_SNAPSHOTS"
        ] == "1" ? .all : .never

        assertSnapshot(of: controller(style: .light, size: .large), as: .image(on: .iPhoneSe), named: "light", record: recordMode)
        assertSnapshot(of: controller(style: .dark, size: .large), as: .image(on: .iPhoneSe), named: "dark", record: recordMode)
        assertSnapshot(
            of: controller(style: .light, size: .accessibilityExtraExtraExtraLarge),
            as: .image(on: .iPhoneSe),
            named: "accessibility-extra-extra-extra-large",
            record: recordMode
        )
    }

    private func controller(style: UIUserInterfaceStyle, size: UIContentSizeCategory) -> UIViewController {
        let node = ChoiceGroupRendererContractTests.makeSnapshotNode()
        let host = UIHostingController(rootView: ChoiceGroupRenderer(node: node).padding(16))
        host.overrideUserInterfaceStyle = style
        host.view.backgroundColor = .systemBackground
        host.setOverrideTraitCollection(
            UITraitCollection(preferredContentSizeCategory: size),
            forChild: host
        )
        return host
    }
}

fileprivate extension ChoiceGroupRendererContractTests {
    static func makeSnapshotNode() -> NativeUINode {
        NativeUINode(
            id: 7,
            type: "firstlight.choice-group",
            layout: nil,
            style: nil,
            props: GenericProps([
                "option_values": ["routine", "urgent", "critical"],
                "option_labels": ["Routine", "Urgent", "Critical review required"],
                "option_enabled": ["1", "1", "0"],
                "option_callbacks": ["51", "0", "0"],
                "selected_values": ["urgent"],
                "value_type": "string",
                "multiple": false,
                "disabled": false,
                "label": "Priority",
                "helper": "Choose one priority.",
                "error": "",
                "required": true,
                "a11y_label": "Priority",
                "a11y_hint": "Select a priority",
            ]),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
    }
}
