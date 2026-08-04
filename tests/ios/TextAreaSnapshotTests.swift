import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class TextAreaSnapshotTests: XCTestCase {
    func testConfigurationDecodesMultilineMetadata() {
        let configuration = TextAreaRendererConfiguration(node: makeNode())
        XCTAssertEqual(configuration.minLines, 4)
        XCTAssertEqual(configuration.maxLines, 10)
        XCTAssertEqual(configuration.autocapitalize, "sentences")
        XCTAssertEqual(configuration.autocorrectPolicy, "disabled")
        XCTAssertEqual(configuration.accessibilityLabel, "Appointment notes")
    }

    func testFocusedAcknowledgementsPreserveTheNativeDraft() {
        var state = TextAreaRendererState(node: makeNode(value: "server"))
        _ = state.focusChanged(true)
        XCTAssertEqual(
            state.userChanged("local\nmultiline draft"),
            TextAreaRendererEvent(callbackId: 41, nodeId: 7, value: "local\nmultiline draft")
        )

        state.serverPublished(tree(makeNode(value: "local\nmultiline draft")))
        XCTAssertEqual(state.draft, "local\nmultiline draft")

        state.serverPublished(tree(makeNode(value: "corrected")))
        XCTAssertEqual(state.draft, "local\nmultiline draft")
        XCTAssertEqual(state.pendingServerValue, "corrected")
        XCTAssertNil(state.focusChanged(false))
        XCTAssertEqual(state.draft, "corrected")
    }

    func testBlurDebounceDisabledAndReadOnlyRules() {
        var blur = TextAreaRendererState(node: makeNode(syncMode: "blur"))
        _ = blur.focusChanged(true)
        XCTAssertNil(blur.userChanged("two\nlines"))
        XCTAssertEqual(
            blur.focusChanged(false),
            TextAreaRendererEvent(callbackId: 41, nodeId: 7, value: "two\nlines")
        )

        var debounce = TextAreaRendererState(node: makeNode(syncMode: "debounce"))
        _ = debounce.focusChanged(true)
        XCTAssertNil(debounce.userChanged("debounced"))
        XCTAssertEqual(
            debounce.flush(),
            TextAreaRendererEvent(callbackId: 41, nodeId: 7, value: "debounced")
        )
        XCTAssertNil(debounce.flush())

        var disabled = TextAreaRendererState(node: makeNode(disabled: true))
        var readOnly = TextAreaRendererState(node: makeNode(readOnly: true))
        XCTAssertNil(disabled.userChanged("changed"))
        XCTAssertNil(readOnly.userChanged("changed"))
        XCTAssertEqual(disabled.draft, "draft")
        XCTAssertEqual(readOnly.draft, "draft")
    }

    func testUnfocusedProgrammaticPublicationDoesNotEcho() {
        var state = TextAreaRendererState(node: makeNode())
        state.serverPublished(tree(makeNode(value: "programmatic\nupdate")))
        XCTAssertEqual(state.draft, "programmatic\nupdate")
        XCTAssertNil(state.flush())
    }

    func testControlUsesMultilineGeometryAndAccessibleErrorValue() {
        let configuration = TextAreaRendererConfiguration(node: makeNode(error: "Add one observation"))
        XCTAssertGreaterThan(configuration.maxLines, configuration.minLines)
        XCTAssertEqual(
            TextAreaAccessibility.value(text: "History", error: "Add one observation"),
            "History. Error: Add one observation"
        )
    }

    func testLightDarkAndAccessibilitySnapshots() {
        let record: SnapshotTestingConfiguration.Record = ProcessInfo.processInfo.environment[
            "FIRSTLIGHT_RECORD_SNAPSHOTS"
        ] == "1" ? .all : .never

        assertSnapshot(of: controller(style: .light), as: .image(on: .iPhoneSe), named: "light", record: record)
        assertSnapshot(of: controller(style: .dark, error: "Add one observation"), as: .image(on: .iPhoneSe), named: "dark-error", record: record)
        assertSnapshot(
            of: controller(style: .light, contentSize: .accessibilityExtraExtraExtraLarge),
            as: .image(on: .iPhoneSe),
            named: "accessibility-extra-extra-extra-large",
            record: record
        )
    }

    private func controller(
        style: UIUserInterfaceStyle,
        error: String = "",
        contentSize: UIContentSizeCategory = .large
    ) -> UIViewController {
        let root = TextAreaSnapshotHost(configuration: TextAreaRendererConfiguration(node: makeNode(error: error)))
            .padding(20)
            .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .topLeading)
            .background(Color(uiColor: .systemBackground))
        let controller = UIHostingController(rootView: root)
        controller.overrideUserInterfaceStyle = style
        controller.view.frame = CGRect(x: 0, y: 0, width: 320, height: 568)
        controller.traitOverrides.preferredContentSizeCategory = contentSize
        return controller
    }

    private func makeNode(
        value: String = "draft",
        syncMode: String = "live",
        disabled: Bool = false,
        readOnly: Bool = false,
        error: String = ""
    ) -> NativeUINode {
        NativeUINode(
            id: 7,
            type: "firstlight_text_area",
            layout: nil,
            style: nil,
            props: GenericProps([
                "value": value,
                "label": "Clinical notes",
                "placeholder": "Add relevant history and observations",
                "helper": "Relevant details only",
                "error": error,
                "required": true,
                "disabled": disabled,
                "read_only": readOnly,
                "min_lines": 4,
                "max_lines": 10,
                "autocapitalize": "sentences",
                "autocorrect_policy": "disabled",
                "sync_mode": syncMode,
                "debounce_ms": 300,
                "a11y_label": "Appointment notes",
                "a11y_hint": "Enter relevant clinical details",
                "on_change": 41,
            ]),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
    }

    private func tree(_ target: NativeUINode) -> NativeUITree {
        NativeUITree(version: 1, callbackCount: 1, root: target)
    }
}

private struct TextAreaSnapshotHost: View {
    let configuration: TextAreaRendererConfiguration
    @FocusState private var focused: Bool
    @State private var text = "History\nObservation\nPlan"

    var body: some View {
        FirstlightTextAreaControl(
            configuration: configuration,
            text: $text,
            isFocused: $focused,
            tokens: .fallback
        )
    }
}
