import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class CheckboxSnapshotTests: XCTestCase {
    func testConfigurationDecodesTheCompleteBooleanFieldContract() {
        let configuration = CheckboxRendererConfiguration(node: makeNode(
            value: true,
            required: true,
            disabled: true,
            helper: "Read the terms first.",
            error: "Agreement is required."
        ))

        XCTAssertEqual(configuration.nodeID, 7)
        XCTAssertTrue(configuration.value)
        XCTAssertEqual(configuration.label, "I agree to the terms")
        XCTAssertEqual(configuration.helper, "Read the terms first.")
        XCTAssertEqual(configuration.error, "Agreement is required.")
        XCTAssertTrue(configuration.required)
        XCTAssertTrue(configuration.disabled)
        XCTAssertEqual(configuration.accessibilityLabel, "Accept the terms")
        XCTAssertEqual(configuration.accessibilityHint, "Required before continuing")
        XCTAssertEqual(configuration.onChangeCallback, 41)
        XCTAssertEqual(configuration.supportingText, "Agreement is required.")
    }

    func testVisibleLabelIsTheAccessibilityFallback() {
        let configuration = CheckboxRendererConfiguration(node: makeNode(
            accessibilityLabel: ""
        ))

        XCTAssertEqual(configuration.accessibilityLabel, "I agree to the terms")
    }

    func testProposalDoesNotMutateAcceptedValueAndDeduplicatesUntilPublication() {
        var state = CheckboxRendererState(node: makeNode(value: false))

        XCTAssertEqual(
            state.proposeChange(),
            .checkboxChange(callbackID: 41, nodeID: 7, value: true)
        )
        XCTAssertFalse(state.configuration.value)
        XCTAssertNil(state.proposeChange())
    }

    func testRejectedIdenticalPublicationClearsPendingWithoutChangingValue() {
        var state = CheckboxRendererState(node: makeNode(value: false))
        _ = state.proposeChange()

        XCTAssertFalse(state.serverPublished(tree(node: makeNode(value: false))))
        XCTAssertFalse(state.configuration.value)
        XCTAssertEqual(
            state.proposeChange(),
            .checkboxChange(callbackID: 41, nodeID: 7, value: true)
        )
    }

    func testAcceptedAndProgrammaticPublicationsUpdateOnlyAcceptedState() {
        var state = CheckboxRendererState(node: makeNode(value: false))

        XCTAssertTrue(state.serverPublished(tree(node: makeNode(value: true))))
        XCTAssertTrue(state.configuration.value)
        XCTAssertEqual(
            state.proposeChange(),
            .checkboxChange(callbackID: 41, nodeID: 7, value: false)
        )
        XCTAssertTrue(state.configuration.value)
    }

    func testDisabledAndMissingCallbackNeverPropose() {
        var disabled = CheckboxRendererState(node: makeNode(disabled: true))
        var missingCallback = CheckboxRendererState(node: makeNode(callbackID: 0))

        XCTAssertNil(disabled.proposeChange())
        XCTAssertNil(missingCallback.proposeChange())
    }

    func testEveryPublicationReplacesMetadataAndClearsPendingByStableNodeID() {
        var state = CheckboxRendererState(node: makeNode(helper: "Original helper"))
        _ = state.proposeChange()
        let published = makeNode(
            helper: "Rejected by policy",
            error: "Agreement is still required."
        )
        let nested = NativeUINode(
            id: 1,
            type: "column",
            layout: nil,
            style: nil,
            props: GenericProps(),
            onPress: 0,
            onLongPress: 0,
            children: [published]
        )

        XCTAssertFalse(state.serverPublished(tree(node: nested)))
        XCTAssertEqual(state.configuration.helper, "Rejected by policy")
        XCTAssertEqual(state.configuration.error, "Agreement is still required.")
        XCTAssertNotNil(state.proposeChange())
    }

    func testAccessibilityValueCombinesAcceptedRequiredAndErrorState() {
        XCTAssertEqual(
            CheckboxAccessibility.value(value: false, required: true, error: "Agreement is required."),
            "Not checked. Required. Error: Agreement is required."
        )
        XCTAssertEqual(
            CheckboxAccessibility.value(value: true, required: false, error: ""),
            "Checked"
        )
    }

    func testControlOwnsOneDecorativeGlyphAndMinimumTarget() {
        let control = FirstlightCheckboxControl(
            configuration: CheckboxRendererConfiguration(node: makeNode()),
            tokens: .checkboxTest,
            onProposal: {}
        )

        XCTAssertTrue(control.glyphIsDecorative)
        XCTAssertGreaterThanOrEqual(control.minimumTarget, 44)
    }

    func testProductionRendererConstructsAgainstTheNativePHPContract() {
        let renderer = CheckboxRenderer(node: makeNode())

        XCTAssertEqual(renderer.node.id, 7)
    }

    func testLightDarkDisabledErrorAndLargeTextSnapshots() throws {
        let environment = ProcessInfo.processInfo.environment
        let shouldRecord = environment["FIRSTLIGHT_RECORD_SNAPSHOTS"] == "1"
        let shouldVerify = environment["FIRSTLIGHT_VERIFY_CHECKBOX_SNAPSHOTS"] == "1"

        try XCTSkipUnless(
            shouldRecord || shouldVerify,
            "Checkbox snapshots require the controller-owned capture gate."
        )

        let record: SnapshotTestingConfiguration.Record = shouldRecord ? .all : .never

        assertSnapshot(
            of: makeViewController(style: .light, contentSize: .large),
            as: .image(on: .iPhoneSe),
            named: "light",
            record: record
        )
        assertSnapshot(
            of: makeViewController(style: .dark, contentSize: .large),
            as: .image(on: .iPhoneSe),
            named: "dark",
            record: record
        )
        assertSnapshot(
            of: makeViewController(
                style: .light,
                contentSize: .accessibilityExtraExtraExtraLarge
            ),
            as: .image(on: .iPhoneSe),
            named: "accessibility-extra-extra-extra-large",
            record: record
        )
    }

    private func makeNode(
        value: Bool = false,
        required: Bool = false,
        disabled: Bool = false,
        helper: String = "Required before continuing.",
        error: String = "",
        accessibilityLabel: String = "Accept the terms",
        callbackID: Int = 41
    ) -> NativeUINode {
        NativeUINode(
            id: 7,
            type: "firstlight.checkbox",
            layout: nil,
            style: nil,
            props: GenericProps([
                "value": value,
                "label": "I agree to the terms",
                "helper": helper,
                "error": error,
                "required": required,
                "disabled": disabled,
                "a11y_label": accessibilityLabel,
                "a11y_hint": "Required before continuing",
                "on_change": callbackID,
            ]),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
    }

    private func tree(node: NativeUINode) -> NativeUITree {
        NativeUITree(version: 1, callbackCount: 1, root: node)
    }

    private func makeViewController(
        style: UIUserInterfaceStyle,
        contentSize: UIContentSizeCategory
    ) -> UIViewController {
        let view = VStack(spacing: 16) {
            FirstlightCheckboxControl(
                configuration: CheckboxRendererConfiguration(node: self.makeNode()),
                tokens: .checkboxTest,
                onProposal: {}
            )
            FirstlightCheckboxControl(
                configuration: CheckboxRendererConfiguration(node: self.makeNode(value: true, required: true)),
                tokens: .checkboxTest,
                onProposal: {}
            )
            FirstlightCheckboxControl(
                configuration: CheckboxRendererConfiguration(node: self.makeNode(
                    value: true,
                    disabled: true,
                    helper: ""
                )),
                tokens: .checkboxTest,
                onProposal: {}
            )
            FirstlightCheckboxControl(
                configuration: CheckboxRendererConfiguration(node: self.makeNode(error: "Agreement is required.")),
                tokens: .checkboxTest,
                onProposal: {}
            )
        }
        .padding(20)
        .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .topLeading)
        .background(Color(uiColor: .systemBackground))

        let controller = UIHostingController(rootView: view)
        controller.overrideUserInterfaceStyle = style
        controller.traitOverrides.preferredContentSizeCategory = contentSize

        return controller
    }
}

private extension NativeUITokens {
    static let checkboxTest = NativeUITokens(
        primary: Color(uiColor: .systemBlue),
        onPrimary: .white,
        surface: Color(uiColor: .systemBackground),
        onSurface: Color(uiColor: .label),
        surfaceVariant: Color(uiColor: .secondarySystemBackground),
        onSurfaceVariant: Color(uiColor: .secondaryLabel),
        destructive: Color(uiColor: .systemRed),
        onDestructive: .white
    )
}
