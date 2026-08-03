import Combine
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class SegmentedRendererContractTests: XCTestCase {
    func testStringSelectionWaitsForServerAndEmitsEveryRejectedAttempt() {
        var state = SegmentedRendererState(node: makeNode(
            selectedValue: "mine",
            valueType: "string",
            onChange: 41
        ))

        let first = state.userSelected(1)
        let repeated = state.userSelected(1)

        XCTAssertEqual(state.selectionState.selectedIndex, 0)
        XCTAssertEqual(first?.wireName, "SELECT_CHANGE")
        XCTAssertEqual(
            first,
            .selectChange(callbackId: 41, nodeId: 7, value: "all")
        )
        XCTAssertEqual(
            repeated,
            .selectChange(callbackId: 41, nodeId: 7, value: "all")
        )
    }

    func testIntegerSelectionUsesExactPressWireEvent() {
        var state = SegmentedRendererState(node: makeNode(
            selectedValue: "mine",
            valueType: "integer",
            optionCallbacks: [51, 52]
        ))

        let event = state.userSelected(1)

        XCTAssertEqual(event?.wireName, "PRESS")
        XCTAssertEqual(event, .press(callbackId: 52, nodeId: 7))
    }

    func testDisabledOptionAndSameSelectionAreNoOps() {
        var state = SegmentedRendererState(node: makeNode(
            selectedValue: "mine",
            valueType: "string",
            optionEnabled: [true, false],
            onChange: 41
        ))

        XCTAssertNil(state.userSelected(0))
        XCTAssertNil(state.userSelected(1))
        XCTAssertEqual(state.selectionState.selectedIndex, 0)
    }

    func testHasSelectionDistinguishesNullFromEmptyString() {
        var state = SegmentedRendererState(node: makeNode(
            values: ["", "mine"],
            selectedValue: "",
            hasSelection: false
        ))
        XCTAssertNil(state.selectionState.selectedIndex)

        let selectedEmptyString = makeNode(
            values: ["", "mine"],
            selectedValue: "",
            hasSelection: true
        )
        state.serverPublished(tree(root: selectedEmptyString))

        XCTAssertEqual(state.selectionState.selectedIndex, 0)
    }

    func testUnchangedPreTapServerSelectionRejectsLocalSelectionWithoutEmission() {
        let unchangedServerNode = makeNode(
            selectedValue: "mine",
            valueType: "string",
            onChange: 41
        )
        var state = SegmentedRendererState(node: unchangedServerNode)
        var emitted: [SegmentedRendererEvent] = []

        if let event = state.userSelected(1) { emitted.append(event) }
        XCTAssertEqual(state.selectionState.selectedIndex, 0)

        let didReconcile = state.serverPublished(tree(root: unchangedServerNode))

        XCTAssertFalse(didReconcile)
        XCTAssertEqual(state.selectionState.selectedIndex, 0)
        XCTAssertEqual(emitted, [
            .selectChange(callbackId: 41, nodeId: 7, value: "all"),
        ])
    }

    func testServerPublicationFindsNestedNodeByStableIDAndDoesNotEmit() {
        var state = SegmentedRendererState(node: makeNode(
            selectedValue: "mine",
            valueType: "string",
            onChange: 41
        ))
        _ = state.userSelected(1)

        let corrected = makeNode(
            selectedValue: "all",
            valueType: "string",
            onChange: 41
        )
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
        XCTAssertEqual(state.selectionState.selectedIndex, 1)
    }

    func testRendererCompilesAgainstNativePHPContractShims() {
        let renderer = SegmentedRenderer(
            node: makeNode(selectedValue: "mine", valueType: "string", onChange: 41),
            events: SegmentedRendererEvents { _ in }
        )

        XCTAssertNotNil(renderer.body)
    }

    func testUnchangedBridgePublicationPreservesServerAuthoritativeSelection() {
        let serverNode = makeNode(
            selectedValue: "mine",
            valueType: "string",
            onChange: 41
        )
        let serverTree = tree(root: serverNode)
        var emitted: [SegmentedRendererEvent] = []
        var state = SegmentedRendererState(node: serverNode)
        NativeUIBridge.shared.currentTree = nil
        defer { NativeUIBridge.shared.currentTree = nil }

        let publication = NativeUIBridge.shared.$currentTree
            .dropFirst()
            .compactMap { $0 }
            .sink { tree in state.serverPublished(tree) }

        if let event = state.userSelected(1) { emitted.append(event) }
        XCTAssertEqual(state.selectionState.selectedIndex, 0)
        XCTAssertEqual(emitted, [
            .selectChange(callbackId: 41, nodeId: 7, value: "all"),
        ])

        // PHP rejected the tap while keeping the same pre-tap server value.
        // @Published still emits this tree assignment, unlike value onChange.
        NativeUIBridge.shared.currentTree = serverTree

        XCTAssertEqual(state.selectionState.selectedIndex, 0)
        XCTAssertEqual(emitted.count, 1)
        withExtendedLifetime(publication) {}
    }

    func testUIKitControlRestoresAuthoritativeSelectionBeforeSending() {
        var emitted: [Int] = []
        let controlView = FirstlightSegmentedControl(
            labels: ["Mine", "All"],
            optionEnabled: [true, true],
            selectedIndex: 0,
            disabled: false,
            tintColor: .systemBlue,
            required: false,
            accessibilityLabel: "Queue",
            accessibilityHint: "",
            onSelection: { emitted.append($0) }
        )
        let coordinator = controlView.makeCoordinator()
        let control = controlView.makeControl(coordinator: coordinator)
        control.selectedSegmentIndex = 1

        coordinator.changed(control)

        XCTAssertEqual(control.selectedSegmentIndex, 0)
        XCTAssertEqual(emitted, [1])
    }

    private func makeNode(
        values: [String] = ["mine", "all"],
        selectedValue: String,
        hasSelection: Bool = true,
        valueType: String = "string",
        optionEnabled: [Bool] = [true, true],
        optionCallbacks: [Int] = [0, 0],
        onChange: Int = 0
    ) -> NativeUINode {
        NativeUINode(
            id: 7,
            type: "firstlight_segmented",
            layout: nil,
            style: nil,
            props: GenericProps([
                "option_values": values,
                "option_labels": values.map { $0.isEmpty ? "Empty" : $0.capitalized },
                "option_enabled": optionEnabled.map { $0 ? "1" : "0" },
                "option_callbacks": optionCallbacks.map(String.init),
                "value_type": valueType,
                "has_selection": hasSelection,
                "selected_value": selectedValue,
                "on_change": onChange,
                "label": "Document queue",
                "required": true,
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
