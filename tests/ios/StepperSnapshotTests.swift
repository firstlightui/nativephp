import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class StepperRendererContractTests: XCTestCase {
    func testConfigurationDecodesAcceptedDisplayAndCompleteMetadata() {
        let configuration = StepperRendererConfiguration(node: makeStepperNode())

        XCTAssertEqual(configuration.nodeID, 7)
        XCTAssertEqual(configuration.displayValue, "5")
        XCTAssertEqual(configuration.label, "Quantity")
        XCTAssertEqual(configuration.helper, "Adjust one at a time")
        XCTAssertEqual(configuration.accessibilityLabel, "Medication quantity")
        XCTAssertEqual(configuration.accessibilityHint, "Use increase or decrease")
        XCTAssertTrue(configuration.canPressDecrement)
        XCTAssertTrue(configuration.canPressIncrement)
    }

    func testProposalEmitsPressWithoutOptimisticallyChangingAcceptedDisplay() {
        var state = StepperRendererState(node: makeStepperNode())

        let event = state.increment()

        XCTAssertEqual(event, .press(callbackId: 42, nodeId: 7))
        XCTAssertEqual(event?.wireName, "PRESS")
        XCTAssertEqual(state.configuration.displayValue, "5")
        XCTAssertTrue(state.isAwaitingPublication)
        XCTAssertNil(state.increment())
        XCTAssertNil(state.decrement())
    }

    func testIdenticalPublicationReleasesStaleTapSuppression() {
        var state = StepperRendererState(node: makeStepperNode())
        _ = state.increment()

        XCTAssertFalse(state.serverPublished(tree(makeStepperNode())))
        XCTAssertFalse(state.isAwaitingPublication)
        XCTAssertEqual(state.increment(), .press(callbackId: 42, nodeId: 7))
    }

    func testNestedChangedPublicationReconcilesAcceptedDisplay() {
        var state = StepperRendererState(node: makeStepperNode())
        _ = state.decrement()

        XCTAssertTrue(state.serverPublished(tree(makeStepperNode(displayValue: "4"))))
        XCTAssertEqual(state.configuration.displayValue, "4")
        XCTAssertFalse(state.isAwaitingPublication)
    }

    func testMissingStableNodeKeepsProposalPending() {
        var state = StepperRendererState(node: makeStepperNode())
        _ = state.increment()
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

        XCTAssertFalse(state.serverPublished(tree(unrelated)))
        XCTAssertTrue(state.isAwaitingPublication)
    }

    func testDisabledBoundsAndMissingCallbacksAreInert() {
        var disabled = StepperRendererState(node: makeStepperNode(disabled: true))
        var minimum = StepperRendererState(node: makeStepperNode(canDecrement: false))
        var maximum = StepperRendererState(node: makeStepperNode(canIncrement: false))
        var callbackless = StepperRendererState(node: makeStepperNode(decrementCallback: 0, incrementCallback: 0))

        XCTAssertNil(disabled.decrement())
        XCTAssertNil(disabled.increment())
        XCTAssertNil(minimum.decrement())
        XCTAssertNotNil(minimum.increment())
        XCTAssertNotNil(maximum.decrement())
        XCTAssertNil(maximum.increment())
        XCTAssertNil(callbackless.decrement())
        XCTAssertNil(callbackless.increment())
    }

    func testRendererCompilesAgainstNativePHPContractShims() {
        let renderer = StepperRenderer(node: makeStepperNode(), events: StepperRendererEvents { _ in })

        XCTAssertNotNil(renderer.body)
    }

    private func tree(_ target: NativeUINode) -> NativeUITree {
        let root = NativeUINode(
            id: 1,
            type: "column",
            layout: nil,
            style: nil,
            props: GenericProps(),
            onPress: 0,
            onLongPress: 0,
            children: [target]
        )
        return NativeUITree(version: 0, callbackCount: 0, root: root)
    }
}

@MainActor
final class StepperControlSnapshotTests: XCTestCase {
    func testLightDarkErrorDisabledAndAccessibilitySnapshotsWhenEvidenceCaptureIsEnabled() throws {
        guard ProcessInfo.processInfo.environment["FIRSTLIGHT_VERIFY_STEPPER_SNAPSHOTS"] == "1" else {
            throw XCTSkip("Stepper screenshot evidence is controller-owned.")
        }

        let recordMode: SnapshotTestingConfiguration.Record = ProcessInfo.processInfo.environment[
            "FIRSTLIGHT_RECORD_SNAPSHOTS"
        ] == "1" ? .all : .never

        assertSnapshot(of: controller(style: .light), as: .image(on: .iPhoneSe), named: "light", record: recordMode)
        assertSnapshot(of: controller(style: .dark), as: .image(on: .iPhoneSe), named: "dark", record: recordMode)
        assertSnapshot(of: controller(style: .light, error: "Quantity is unavailable"), as: .image(on: .iPhoneSe), named: "error", record: recordMode)
        assertSnapshot(of: controller(style: .light, disabled: true), as: .image(on: .iPhoneSe), named: "disabled", record: recordMode)
        assertSnapshot(
            of: controller(style: .light, size: .accessibilityExtraExtraExtraLarge),
            as: .image(on: .iPhoneSe),
            named: "accessibility-extra-extra-extra-large",
            record: recordMode
        )
    }

    private func controller(
        style: UIUserInterfaceStyle,
        disabled: Bool = false,
        error: String = "",
        size: UIContentSizeCategory = .large
    ) -> UIViewController {
        let host = UIHostingController(rootView: StepperRenderer(node: makeStepperNode(disabled: disabled, error: error)).padding(16))
        host.overrideUserInterfaceStyle = style
        host.view.backgroundColor = .systemBackground
        host.setOverrideTraitCollection(UITraitCollection(preferredContentSizeCategory: size), forChild: host)
        return host
    }
}

fileprivate func makeStepperNode(
    displayValue: String = "5",
    disabled: Bool = false,
    canDecrement: Bool = true,
    canIncrement: Bool = true,
    decrementCallback: Int = 41,
    incrementCallback: Int = 42,
    error: String = ""
) -> NativeUINode {
    NativeUINode(
        id: 7,
        type: "firstlight.stepper",
        layout: nil,
        style: nil,
        props: GenericProps([
            "display_value": displayValue,
            "label": "Quantity",
            "helper": "Adjust one at a time",
            "error": error,
            "disabled": disabled,
            "can_decrement": canDecrement,
            "can_increment": canIncrement,
            "on_decrement": decrementCallback,
            "on_increment": incrementCallback,
            "a11y_label": "Medication quantity",
            "a11y_hint": "Use increase or decrease",
        ]),
        onPress: 0,
        onLongPress: 0,
        children: []
    )
}
