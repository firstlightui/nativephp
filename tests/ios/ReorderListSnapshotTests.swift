import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class ReorderListRendererContractTests: XCTestCase {
    // Published: Tab bar = [home, inbox, reports], More = [files]. Five slots per row.
    // Callbacks match tests/Feature/ReorderListElementTest.php: row r, slot s -> 100 + 10r + s,
    // except Home (locked) and each row's current place, which publish 0.
    func testCallbackLooksUpTheSlotForEachSectionAndPosition() {
        let configuration = ReorderListRendererConfiguration(node: makeNode())

        XCTAssertEqual(configuration.publishedSections, [[0, 1, 2], [3]])
        XCTAssertEqual(configuration.callback(row: 1, section: 0, position: 0), 110)
        XCTAssertEqual(configuration.callback(row: 1, section: 0, position: 1), 0)
        XCTAssertEqual(configuration.callback(row: 1, section: 1, position: 1), 114)
        XCTAssertEqual(configuration.callback(row: 3, section: 0, position: 3), 133)
        XCTAssertEqual(configuration.callback(row: 1, section: 1, position: 2), 0, "beyond the end")
        XCTAssertEqual(configuration.callback(row: 0, section: 1, position: 0), 0, "locked")
    }

    func testDropAcrossAHeaderLandsInThatSection() {
        let entries = ReorderListEntry.entries(sections: [[0, 1, 2], [3]], showsHeaders: true)
        // [h0, r0, r1, r2, h1, r3]: drag Inbox (index 2) below Files (offset 6).
        let below = ReorderListEntry.destination(in: entries, from: 2, to: 6)
        XCTAssertEqual(below.section, 1)
        XCTAssertEqual(below.position, 1)

        // Drag Files (index 5) to just after Home (offset 2).
        let up = ReorderListEntry.destination(in: entries, from: 5, to: 2)
        XCTAssertEqual(up.section, 0)
        XCTAssertEqual(up.position, 1)
    }

    func testMoveShowsTheDraftAndIgnoresMovesUntilPHPPublishes() {
        var state = ReorderListRendererState(node: makeNode())

        XCTAssertEqual(state.userMoved(row: 1, toSection: 1, position: 0), .press(callbackId: 113, nodeId: 9))
        XCTAssertEqual(state.sections, [[0, 2], [1, 3]])
        XCTAssertNil(state.userMoved(row: 2, toSection: 0, position: 0))

        XCTAssertTrue(state.serverPublished(NativeUITree(version: 0, callbackCount: 0, root: makeNode())))
        XCTAssertEqual(state.sections, [[0, 1, 2], [3]])
        XCTAssertFalse(state.isAwaitingPublication)
    }

    func testLockedAndUnchangedMovesEmitNothing() {
        var state = ReorderListRendererState(node: makeNode())

        XCTAssertNil(state.userMoved(row: 0, toSection: 1, position: 0))
        XCTAssertNil(state.userMoved(row: 2, toSection: 0, position: 2))
        XCTAssertFalse(state.isAwaitingPublication)
    }

    func testRendererCompilesAgainstNativePHPContractShims() {
        XCTAssertNotNil(ReorderListRenderer(node: makeNode(), events: ReorderListRendererEvents { _ in }).body)
    }

    static func makeNode() -> NativeUINode {
        let callbacks = (0..<4).flatMap { row in
            (0..<5).map { slot -> String in
                let current = [[0, 1, 2], [1], [2], [4]][row]
                return row == 0 || current.contains(slot) ? "0" : String(100 + 10 * row + slot)
            }
        }
        return NativeUINode(
            id: 9,
            type: "firstlight.reorder-list",
            layout: nil,
            style: nil,
            props: GenericProps([
                "section_keys": ["bar", "more"],
                "section_labels": ["Tab bar", "More"],
                "section_lengths": ["3", "1"],
                "row_values": ["home", "inbox", "reports", "files"],
                "row_labels": ["Home", "Inbox", "Reports", "Files"],
                "row_supporting": ["", "", "Weekly", ""],
                "row_icons": ["house", "tray", "", "folder"],
                "row_locked": ["1", "0", "0", "0"],
                "move_slot_count": 5,
                "move_callbacks": callbacks,
                "label": "Tabs",
                "helper": "Up to 5 tabs. Home stays first.",
                "error": "",
                "disabled": false,
                "move_to_labels": ["Move to Tab bar", "Move to More"],
            ]),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
    }

    private func makeNode() -> NativeUINode { Self.makeNode() }
}

@MainActor
final class ReorderListControlSnapshotTests: XCTestCase {
    func testLightDarkAndAccessibilitySnapshotsWhenEvidenceCaptureIsEnabled() throws {
        guard ProcessInfo.processInfo.environment["FIRSTLIGHT_VERIFY_REORDER_LIST_SNAPSHOTS"] == "1" else {
            throw XCTSkip("Reorder List screenshot evidence is controller-owned.")
        }

        assertFirstlightSnapshot(of: controller(style: .light, size: .large), as: .image(on: .iPhoneSe), named: "light")
        assertFirstlightSnapshot(of: controller(style: .dark, size: .large), as: .image(on: .iPhoneSe), named: "dark")
    }

    private func controller(style: UIUserInterfaceStyle, size: UIContentSizeCategory) -> UIViewController {
        let host = UIHostingController(rootView: ReorderListRenderer(node: ReorderListRendererContractTests.makeNode()).padding(16))
        host.overrideUserInterfaceStyle = style
        host.view.backgroundColor = .systemGroupedBackground
        host.setOverrideTraitCollection(UITraitCollection(preferredContentSizeCategory: size), forChild: host)
        return host
    }
}
