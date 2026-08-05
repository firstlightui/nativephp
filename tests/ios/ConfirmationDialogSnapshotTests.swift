import SwiftUI
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class ConfirmationDialogSnapshotTests: XCTestCase {
    func testConfigurationDecodesPresentationAndActionRoles() {
        let configuration = ConfirmationDialogRendererConfiguration(node: node())

        XCTAssertTrue(configuration.visible)
        XCTAssertEqual(configuration.title, "Delete appointment?")
        XCTAssertEqual(configuration.message, "This action cannot be undone.")
        XCTAssertEqual(configuration.confirmLabel, "Delete")
        XCTAssertEqual(configuration.cancelLabel, "Keep appointment")
        XCTAssertEqual(configuration.tone, .destructive)
        XCTAssertEqual(configuration.confirmCallback, 41)
        XCTAssertEqual(configuration.dismissCallback, 42)
        XCTAssertTrue(configuration.canPresent)
    }

    func testMalformedToneDefensivelyUsesDefaultRole() {
        XCTAssertEqual(
            ConfirmationDialogRendererConfiguration(node: node(tone: "warning")).tone,
            .default
        )
    }

    func testConfirmDismissesAndEmitsOnlyOnce() {
        var state = ConfirmationDialogRendererState(node: node())

        XCTAssertEqual(state.confirm(), .press(callbackId: 41, nodeId: 7))
        XCTAssertFalse(state.isPresented)
        XCTAssertNil(state.confirm())
        XCTAssertNil(state.dismiss())
    }

    func testCancelAndSystemDismissUseTheDismissCallbackOnlyOnce() {
        var cancelState = ConfirmationDialogRendererState(node: node())
        XCTAssertEqual(cancelState.dismiss(), .dismiss(callbackId: 42, nodeId: 7))
        XCTAssertNil(cancelState.systemDismiss())

        var systemState = ConfirmationDialogRendererState(node: node())
        XCTAssertEqual(systemState.systemDismiss(), .dismiss(callbackId: 42, nodeId: 7))
        XCTAssertNil(systemState.dismiss())
    }

    func testMissingCallbacksPreventPresentation() {
        XCTAssertFalse(ConfirmationDialogRendererState(node: node(confirmCallback: 0)).isPresented)
        XCTAssertFalse(ConfirmationDialogRendererState(node: node(dismissCallback: 0)).isPresented)
    }

    func testServerVisibilityReconcilesWithoutEmittingOrReopeningOnCopyOnlyChanges() {
        var state = ConfirmationDialogRendererState(node: node())
        XCTAssertNotNil(state.dismiss())

        XCTAssertTrue(state.serverPublished(tree(root: node(message: "Updated details."))))
        XCTAssertFalse(state.isPresented)

        XCTAssertTrue(state.serverPublished(tree(root: node(visible: false))))
        XCTAssertFalse(state.isPresented)

        XCTAssertTrue(state.serverPublished(tree(root: node(visible: true))))
        XCTAssertTrue(state.isPresented)
    }

    func testProgrammaticClosureEmitsNothing() {
        var state = ConfirmationDialogRendererState(node: node())

        XCTAssertTrue(state.serverPublished(node(visible: false)))
        XCTAssertFalse(state.isPresented)
    }

    func testRendererCompilesAgainstNativePHPContractShims() {
        let renderer = ConfirmationDialogRenderer(
            node: node(),
            events: ConfirmationDialogRendererEvents { _ in }
        )

        XCTAssertNotNil(renderer.body)
    }

    private func node(
        visible: Bool = true,
        message: String = "This action cannot be undone.",
        tone: String = "destructive",
        confirmCallback: Int = 41,
        dismissCallback: Int = 42
    ) -> NativeUINode {
        NativeUINode(
            id: 7,
            type: "firstlight_confirmation_dialog",
            layout: nil,
            style: nil,
            props: GenericProps([
                "visible": visible,
                "title": "Delete appointment?",
                "message": message,
                "confirm_label": "Delete",
                "cancel_label": "Keep appointment",
                "tone": tone,
                "on_dismiss": dismissCallback,
            ]),
            onPress: confirmCallback,
            onLongPress: 0,
            children: []
        )
    }

    private func tree(root: NativeUINode) -> NativeUITree {
        NativeUITree(version: 1, callbackCount: 2, root: root)
    }
}
