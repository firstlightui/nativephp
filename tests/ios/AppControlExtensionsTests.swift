import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class AppControlExtensionsTests: XCTestCase {
    func testRichTilesRetainStableSelectionAndPublicationGuard() {
        var state = ChoiceGroupRendererState(node: choiceNode())
        XCTAssertEqual(state.configuration.variant, "tiles")
        XCTAssertEqual(state.configuration.optionPrefixes, ["110", "116", "119"])
        XCTAssertEqual(state.configuration.optionSupporting, ["$90.00", "$45.00", "$65.00"])
        XCTAssertEqual(state.userSelected(1), .press(callbackId: 52, nodeId: 7))
        XCTAssertEqual(state.configuration.selectedValues, ["consult"])
        XCTAssertNil(state.userSelected(0))
        XCTAssertNil(state.userSelected(2))
        XCTAssertFalse(state.serverPublished(NativeUITree(version: 1, callbackCount: 2, root: choiceNode())))
        XCTAssertEqual(state.userSelected(1), .press(callbackId: 52, nodeId: 7))
    }

    func testPendingRowRetainsActionAndSpokenIdentity() {
        let configuration = ListItemRendererConfiguration(node: rowNode(state: "pending-removal"))
        XCTAssertEqual(configuration.resolvedAccessibilityLabel, "4B-12, Alex Morgan, Waiting to sync, Discharge")
        XCTAssertEqual(configuration.stateLabel, "Discharge waiting to sync")
        XCTAssertNotNil(configuration.pressEvent())
    }

    func testLightDarkAndLargeTextSnapshotsWhenCaptureIsEnabled() throws {
        guard ProcessInfo.processInfo.environment["FIRSTLIGHT_VERIFY_APP_EXTENSIONS_SNAPSHOTS"] == "1" else {
            throw XCTSkip("Native extension snapshots require explicit capture.")
        }
        for (name, scheme, size, height) in [
            ("light", ColorScheme.light, DynamicTypeSize.large, 1500.0),
            ("dark", .dark, .large, 1500.0),
            ("large-text", .light, .accessibility3, 2700.0),
        ] {
            let host = UIHostingController(rootView: content
                .environment(\.colorScheme, scheme)
                .environment(\.dynamicTypeSize, size))
            host.overrideUserInterfaceStyle = scheme == .dark ? .dark : .light
            assertFirstlightSnapshot(of: host, as: .image(size: CGSize(width: 402, height: height)), named: name)
        }
    }

    private var content: some View {
        VStack(alignment: .leading, spacing: 12) {
            ForEach(["normal", "provisional", "pending-removal"], id: \.self) { state in
                ListItemRenderer(node: self.rowNode(state: state))
            }
            SelectRenderer(node: node(["variant": "chip", "label": "Hospital", "option_values": ["north", "south"],
                "option_labels": ["North", "South"], "option_enabled": ["1", "1"],
                "option_callbacks": ["51", "52"], "selected_values": ["north"]]))
            DatePickerRenderer(node: node(["variant": "chip", "label": "DOS", "display_text": "Today",
                "has_value": true, "value": "2026-10-29", "locale": "en-AU", "timezone": "Australia/Sydney", "on_change": 41]))
            ChoiceGroupRenderer(node: choiceNode())
            ForEach(["success", "warning", "neutral"], id: \.self) { tone in
                TextFieldRenderer(node: self.node(["label": "Identifier", "value": "1234", "helper_tone": tone,
                    "helper": tone == "success" ? "Checksum OK" : "Check this",
                    "error": tone == "neutral" ? "Invalid identifier" : ""]))
            }
            Spacer(minLength: 0)
        }
        .padding(16)
        .background(Color(uiColor: .systemBackground))
    }

    private func choiceNode() -> NativeUINode {
        node(["variant": "tiles", "label": "Favourites", "multiple": true,
            "option_values": ["consult", "review", "disabled"], "option_labels": ["Initial consultation", "Follow-up", "Unavailable"],
            "option_prefixes": ["110", "116", "119"], "option_supporting": ["$90.00", "$45.00", "$65.00"],
            "option_enabled": ["1", "1", "0"], "option_callbacks": ["51", "52", "0"], "selected_values": ["consult"]])
    }

    private func rowNode(state: String) -> NativeUINode {
        node(["headline": "Alex Morgan", "supporting": "Discharge", "leading_type": "label", "leading_value": "4B-12",
            "trailing_type": "status", "trailing_value": state == "normal" ? "Billed today" : "Waiting to sync",
            "trailing_status_tone": state == "normal" ? "success" : "warning", "state": state,
            "state_label": "Discharge waiting to sync"])
    }

    private func node(_ props: [String: Any]) -> NativeUINode {
        NativeUINode(id: 7, type: "extension-test", layout: nil, style: nil,
            props: GenericProps(props), onPress: 11, onLongPress: 0, children: [])
    }
}
