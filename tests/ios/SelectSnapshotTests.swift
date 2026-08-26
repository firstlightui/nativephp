import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

private nonisolated func makeNativeSelectEvents() -> SelectRendererEvents {
    .native
}

@MainActor
final class SelectRendererContractTests: XCTestCase {
    func testNativeEventTransportCanBeResolvedFromANonisolatedContext() {
        XCTAssertNotNil(makeNativeSelectEvents())
    }

    func testConfigurationDecodesCompleteFieldAndOptionContract() {
        let configuration = SelectRendererConfiguration(node: makeNode(
            selectedValues: ["urgent"],
            searchEnabled: true,
            helper: "Choose one priority",
            error: "Review the priority",
            a11yLabel: "Document priority",
            a11yHint: "Opens priority options"
        ))

        XCTAssertEqual(configuration.optionValues, ["routine", "urgent", "critical"])
        XCTAssertEqual(configuration.optionLabels, ["Routine", "Urgent", "Critical"])
        XCTAssertEqual(configuration.optionEnabled, [true, true, false])
        XCTAssertEqual(configuration.optionCallbacks, [51, 52, 0])
        XCTAssertEqual(configuration.selectedValues, ["urgent"])
        XCTAssertEqual(configuration.valueType, "string")
        XCTAssertTrue(configuration.searchEnabled)
        XCTAssertEqual(configuration.placeholder, "Select a priority")
        XCTAssertEqual(configuration.helper, "Choose one priority")
        XCTAssertEqual(configuration.error, "Review the priority")
        XCTAssertTrue(configuration.required)
        XCTAssertEqual(configuration.accessibilityLabel, "Document priority")
        XCTAssertEqual(configuration.accessibilityHint, "Opens priority options")
        XCTAssertEqual(configuration.doneLabel, "Done")
    }

    func testSelectionEmitsPressWithoutOptimisticallyChangingTheAcceptedValue() {
        var state = SelectRendererState(node: makeNode(selectedValues: ["routine"]))

        let event = state.userSelected(1)

        XCTAssertEqual(event, .press(callbackId: 52, nodeId: 7))
        XCTAssertEqual(event?.wireName, "PRESS")
        XCTAssertEqual(state.configuration.selectedValues, ["routine"])
        XCTAssertTrue(state.isAwaitingPublication)
    }

    func testOnlyOneSelectionIsAcceptedUntilAnEqualServerPublicationArrives() {
        var state = SelectRendererState(node: makeNode(selectedValues: ["routine"]))

        XCTAssertNotNil(state.userSelected(1))
        XCTAssertNil(state.userSelected(2))
        XCTAssertTrue(state.isAwaitingPublication)

        XCTAssertFalse(state.serverPublished(tree(root: makeNode(selectedValues: ["routine"]))))
        XCTAssertFalse(state.isAwaitingPublication)
        XCTAssertEqual(state.userSelected(1), .press(callbackId: 52, nodeId: 7))
    }

    func testDisabledGroupOptionSelectedValueAndMissingCallbackAreNoOps() {
        var disabledGroup = SelectRendererState(node: makeNode(disabled: true))
        var disabledOption = SelectRendererState(node: makeNode())
        var selected = SelectRendererState(node: makeNode(
            selectedValues: ["routine"],
            optionCallbacks: [51, 52, 0]
        ))
        var missingCallback = SelectRendererState(node: makeNode(
            optionEnabled: [true, true, true],
            optionCallbacks: [51, 0, 53]
        ))

        XCTAssertNil(disabledGroup.userSelected(0))
        XCTAssertNil(disabledOption.userSelected(2))
        XCTAssertNil(selected.userSelected(0))
        XCTAssertNil(missingCallback.userSelected(1))
        XCTAssertNil(missingCallback.userSelected(8))
    }

    func testNestedPublicationReconcilesWithoutEchoAndMissingNodeKeepsProposalPending() {
        var state = SelectRendererState(node: makeNode(selectedValues: ["routine"]))
        _ = state.userSelected(1)
        let root = NativeUINode(
            id: 1,
            type: "column",
            layout: nil,
            style: nil,
            props: GenericProps(),
            onPress: 0,
            onLongPress: 0,
            children: [makeNode(selectedValues: ["urgent"])]
        )

        XCTAssertTrue(state.serverPublished(tree(root: root)))
        XCTAssertEqual(state.configuration.selectedValues, ["urgent"])
        XCTAssertFalse(state.isAwaitingPublication)

        _ = state.userSelected(0)
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

    func testSearchFiltersLabelsCaseInsensitivelyAndKeepsAuthoredOrder() {
        XCTAssertEqual(
            SelectPresentation.filteredIndices(
                labels: ["Routine", "Urgent clinical", "Critical"],
                query: "CLIN"
            ),
            [1]
        )
        XCTAssertEqual(
            SelectPresentation.filteredIndices(
                labels: ["Routine", "Urgent", "Critical"],
                query: ""
            ),
            [0, 1, 2]
        )
    }

    func testRendererCompilesAgainstNativePHPContractShims() {
        let renderer = SelectRenderer(
            node: makeNode(selectedValues: ["routine"]),
            events: SelectRendererEvents { _ in }
        )

        XCTAssertNotNil(renderer.body)
    }

    fileprivate func makeNode(
        selectedValues: [String] = [],
        searchEnabled: Bool = false,
        optionEnabled: [Bool] = [true, true, false],
        optionCallbacks: [Int] = [51, 52, 0],
        disabled: Bool = false,
        helper: String = "",
        error: String = "",
        a11yLabel: String = "",
        a11yHint: String = ""
    ) -> NativeUINode {
        Self.node(
            selectedValues: selectedValues,
            searchEnabled: searchEnabled,
            optionEnabled: optionEnabled,
            optionCallbacks: optionCallbacks,
            disabled: disabled,
            helper: helper,
            error: error,
            a11yLabel: a11yLabel,
            a11yHint: a11yHint
        )
    }

    fileprivate static func node(
        selectedValues: [String] = [],
        searchEnabled: Bool = false,
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
            type: "firstlight.select",
            layout: nil,
            style: nil,
            props: GenericProps([
                "option_values": ["routine", "urgent", "critical"],
                "option_labels": ["Routine", "Urgent", "Critical"],
                "option_enabled": optionEnabled.map { $0 ? "1" : "0" },
                "option_callbacks": optionCallbacks.map(String.init),
                "selected_values": selectedValues,
                "value_type": "string",
                "search_enabled": searchEnabled,
                "disabled": disabled,
                "label": "Priority",
                "placeholder": "Select a priority",
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

    fileprivate func tree(root: NativeUINode) -> NativeUITree {
        NativeUITree(version: 0, callbackCount: 0, root: root)
    }
}

@MainActor
final class SelectControlSnapshotTests: XCTestCase {
    func testCompactSearchableAndAccessibilitySnapshotsWhenEvidenceCaptureIsEnabled() throws {
        guard ProcessInfo.processInfo.environment["FIRSTLIGHT_VERIFY_SELECT_SNAPSHOTS"] == "1" else {
            throw XCTSkip("Select screenshot evidence is controller-owned.")
        }

        let recordMode: SnapshotTestingConfiguration.Record = ProcessInfo.processInfo.environment[
            "FIRSTLIGHT_RECORD_SNAPSHOTS"
        ] == "1" ? .all : .never

        assertSnapshot(of: controller(searchEnabled: false, style: .light, size: .large), as: .image(on: .iPhoneSe), named: "compact-light", record: recordMode)
        assertSnapshot(of: controller(searchEnabled: true, style: .dark, size: .large), as: .image(on: .iPhoneSe), named: "searchable-dark", record: recordMode)
        assertSnapshot(
            of: controller(searchEnabled: false, style: .light, size: .accessibilityExtraExtraExtraLarge),
            as: .image(on: .iPhoneSe),
            named: "accessibility-extra-extra-extra-large",
            record: recordMode
        )
    }

    private func controller(
        searchEnabled: Bool,
        style: UIUserInterfaceStyle,
        size: UIContentSizeCategory
    ) -> UIViewController {
        let node = SelectRendererContractTests.node(
            selectedValues: ["urgent"],
            searchEnabled: searchEnabled,
            helper: "Choose one priority"
        )
        let host = UIHostingController(rootView: SelectRenderer(node: node).padding(16))
        host.overrideUserInterfaceStyle = style
        host.view.backgroundColor = .systemBackground
        host.setOverrideTraitCollection(
            UITraitCollection(preferredContentSizeCategory: size),
            forChild: host
        )
        return host
    }
}
