import XCTest

@testable import FirstlightIOSControls

@MainActor
final class SwitchControlRendererContractTests: XCTestCase {
    func testConfigurationDecodesEveryPublishedFieldAndErrorReplacesHelper() {
        let configuration = SwitchRendererConfiguration(node: switchNode(
            value: true,
            disabled: true,
            helper: "Original helper",
            error: "Notifications are required"
        ))

        XCTAssertEqual(configuration.nodeID, 7)
        XCTAssertTrue(configuration.value)
        XCTAssertEqual(configuration.label, "Notifications")
        XCTAssertEqual(configuration.helper, "Original helper")
        XCTAssertEqual(configuration.error, "Notifications are required")
        XCTAssertTrue(configuration.disabled)
        XCTAssertEqual(configuration.accessibilityLabel, "Receive notifications")
        XCTAssertEqual(configuration.accessibilityHint, "Controls notification delivery")
        XCTAssertEqual(configuration.onChangeCallback, 41)
        XCTAssertEqual(configuration.supportingText, "Notifications are required")
    }

    func testVisibleLabelIsTheAccessibilityFallback() {
        let configuration = SwitchRendererConfiguration(node: switchNode(
            value: false,
            accessibilityLabel: ""
        ))

        XCTAssertEqual(configuration.accessibilityLabel, "Notifications")
    }

    func testProposalDoesNotMutateAcceptedValueAndDeduplicatesUntilPublication() {
        var state = SwitchRendererState(node: switchNode(value: false))

        XCTAssertEqual(state.proposeChange(), .toggleChange(callbackId: 41, nodeId: 7, value: true))
        XCTAssertFalse(state.configuration.value)
        XCTAssertNil(state.proposeChange())
    }

    func testRejectedIdenticalPublicationClearsPendingWithoutChangingValue() {
        var state = SwitchRendererState(node: switchNode(value: false))
        _ = state.proposeChange()

        XCTAssertFalse(state.serverPublished(tree(value: false)))
        XCTAssertFalse(state.configuration.value)
        XCTAssertEqual(state.proposeChange(), .toggleChange(callbackId: 41, nodeId: 7, value: true))
    }

    func testAcceptedAndProgrammaticPublicationsUpdateOnlyAcceptedState() {
        var state = SwitchRendererState(node: switchNode(value: false))

        XCTAssertTrue(state.serverPublished(tree(value: true)))
        XCTAssertTrue(state.configuration.value)
        XCTAssertEqual(state.proposeChange(), .toggleChange(callbackId: 41, nodeId: 7, value: false))
        XCTAssertTrue(state.configuration.value)
    }

    func testDisabledSwitchNeverProposes() {
        var state = SwitchRendererState(node: switchNode(value: false, disabled: true))

        XCTAssertNil(state.proposeChange())
        XCTAssertFalse(state.configuration.value)
    }

    func testZeroCallbackNeverProposes() {
        var state = SwitchRendererState(node: switchNode(value: false, callbackId: 0))

        XCTAssertNil(state.proposeChange())
        XCTAssertFalse(state.configuration.value)
    }

    func testEveryPublicationReplacesFieldConfigurationAndClearsPending() {
        var state = SwitchRendererState(node: switchNode(value: false, helper: "Original helper"))
        _ = state.proposeChange()

        XCTAssertFalse(state.serverPublished(tree(
            value: false,
            helper: "Rejected by your administrator",
            error: "Notifications cannot be disabled"
        )))
        XCTAssertEqual(state.configuration.helper, "Rejected by your administrator")
        XCTAssertEqual(state.configuration.error, "Notifications cannot be disabled")
        XCTAssertEqual(state.proposeChange(), .toggleChange(callbackId: 41, nodeId: 7, value: true))
    }

    func testPublicationFindsTheSwitchByStableNodeID() {
        var state = SwitchRendererState(node: switchNode(value: false))
        let root = NativeUINode(
            id: 1,
            type: "column",
            layout: nil,
            style: nil,
            props: GenericProps(),
            onPress: 0,
            onLongPress: 0,
            children: [switchNode(value: true)]
        )

        XCTAssertTrue(state.serverPublished(NativeUITree(version: 1, callbackCount: 1, root: root)))
        XCTAssertTrue(state.configuration.value)
    }

    private func switchNode(
        value: Bool,
        disabled: Bool = false,
        callbackId: Int = 41,
        helper: String = "Receive updates about new activity.",
        error: String = "",
        accessibilityLabel: String = "Receive notifications"
    ) -> NativeUINode {
        NativeUINode(
            id: 7,
            type: "firstlight.switch",
            layout: nil,
            style: nil,
            props: GenericProps([
                "value": value,
                "label": "Notifications",
                "helper": helper,
                "error": error,
                "disabled": disabled,
                "a11y_label": accessibilityLabel,
                "a11y_hint": "Controls notification delivery",
                "on_change": callbackId,
            ]),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
    }

    private func tree(value: Bool, helper: String = "Receive updates about new activity.", error: String = "") -> NativeUITree {
        NativeUITree(version: 1, callbackCount: 1, root: switchNode(value: value, helper: helper, error: error))
    }
}
