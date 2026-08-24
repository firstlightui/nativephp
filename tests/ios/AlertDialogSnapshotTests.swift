import SwiftUI
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class AlertDialogSnapshotTests: XCTestCase {
    func testConfigurationDecodesPresentationAndActionCopy() {
        let configuration = AlertDialogRendererConfiguration(node: node())

        XCTAssertTrue(configuration.visible)
        XCTAssertEqual(configuration.title, "Changes saved")
        XCTAssertEqual(configuration.message, "Your profile was updated.")
        XCTAssertEqual(configuration.actionLabel, "OK")
        XCTAssertEqual(configuration.dismissCallback, 42)
        XCTAssertTrue(configuration.canPresent)
    }

    func testAcknowledgeAndSystemDismissEmitOnlyOnce() {
        var acknowledgeState = AlertDialogRendererState(node: node())
        XCTAssertEqual(acknowledgeState.dismiss(), .dismiss(callbackId: 42, nodeId: 7))
        XCTAssertFalse(acknowledgeState.isPresented)
        XCTAssertNil(acknowledgeState.systemDismiss())

        var systemState = AlertDialogRendererState(node: node())
        XCTAssertEqual(systemState.systemDismiss(), .dismiss(callbackId: 42, nodeId: 7))
        XCTAssertNil(systemState.dismiss())
    }

    func testMissingCallbackPreventsPresentation() {
        XCTAssertFalse(AlertDialogRendererState(node: node(dismissCallback: 0)).isPresented)
    }

    func testServerVisibilityReconcilesWithoutEmittingOrReopeningOnCopyOnlyChanges() {
        var state = AlertDialogRendererState(node: node())
        XCTAssertNotNil(state.dismiss())

        XCTAssertTrue(state.serverPublished(tree(root: node(message: "Updated copy."))))
        XCTAssertFalse(state.isPresented)

        XCTAssertTrue(state.serverPublished(tree(root: node(visible: false))))
        XCTAssertFalse(state.isPresented)

        XCTAssertTrue(state.serverPublished(tree(root: node(visible: true))))
        XCTAssertTrue(state.isPresented)
    }

    func testProgrammaticClosureEmitsNothing() {
        var state = AlertDialogRendererState(node: node())

        XCTAssertTrue(state.serverPublished(node(visible: false)))
        XCTAssertFalse(state.isPresented)
    }

    func testRendererCompilesAgainstNativePHPContractShims() {
        let renderer = AlertDialogRenderer(
            node: node(),
            events: AlertDialogRendererEvents { _ in }
        )

        XCTAssertNotNil(renderer.body)
    }

    private func node(
        visible: Bool = true,
        message: String = "Your profile was updated.",
        dismissCallback: Int = 42
    ) -> NativeUINode {
        NativeUINode(
            id: 7,
            type: "firstlight_alert_dialog",
            layout: nil,
            style: nil,
            props: GenericProps([
                "visible": visible,
                "title": "Changes saved",
                "message": message,
                "action_label": "OK",
                "on_dismiss": dismissCallback,
            ]),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
    }

    private func tree(root: NativeUINode) -> NativeUITree {
        NativeUITree(version: 1, callbackCount: 1, root: root)
    }
}
