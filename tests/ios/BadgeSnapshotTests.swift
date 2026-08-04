import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class BadgeSnapshotTests: XCTestCase {
    func testConfigurationAndStablePublication() {
        let node = makeNode(label: "3", tone: "danger")
        var state = BadgeRendererState(node: node)

        XCTAssertEqual(state.configuration.label, "3")
        XCTAssertEqual(state.configuration.tone, .danger)
        XCTAssertEqual(state.configuration.accessibilityLabel, "3 unread messages")
        XCTAssertTrue(state.serverPublished(NativeUITree(version: 1, callbackCount: 0, root: makeNode(label: "New", tone: "info"))))
        XCTAssertEqual(state.configuration.label, "New")
        XCTAssertEqual(state.configuration.tone, .info)
    }

    func testMalformedToneFallsBackAndZeroIsHidden() {
        XCTAssertEqual(BadgeRendererConfiguration(node: makeNode(label: "New", tone: "other")).tone, .neutral)
        XCTAssertTrue(FirstlightBadgeControl(
            label: "", tokens: .init(background: .systemRed, foreground: .white),
            accessibilityLabel: "", accessibilityHint: ""
        ).isHidden)
    }

    func testControlIsStaticAndKeepsAccessibilityMetadata() {
        let control = FirstlightBadgeControl(
            label: "3", tokens: .init(background: .systemRed, foreground: .white),
            accessibilityLabel: "3 unread messages", accessibilityHint: "Open inbox"
        )
        XCTAssertFalse(control.isInteractive)
        XCTAssertEqual(control.accessibilityLabel, "3 unread messages")
        XCTAssertEqual(control.accessibilityHint, "Open inbox")
    }

    func testLightDarkAndLargeTextSnapshots() {
        let record: SnapshotTestingConfiguration.Record = ProcessInfo.processInfo.environment["FIRSTLIGHT_RECORD_SNAPSHOTS"] == "1" ? .all : .never
        for style: UIUserInterfaceStyle in [.light, .dark] {
            let view = HStack(spacing: 12) {
                ForEach(StatusLabelTone.allCases, id: \.self) { tone in
                    FirstlightBadgeControl(
                        label: tone == .danger ? "99+" : "New",
                        tokens: .init(background: tone == .neutral ? .systemGray4 : .systemRed, foreground: tone == .neutral ? .label : .white),
                        accessibilityLabel: "\(tone.rawValue) badge", accessibilityHint: ""
                    )
                }
            }.padding().background(Color(uiColor: .systemBackground))
            let controller = UIHostingController(rootView: view)
            controller.overrideUserInterfaceStyle = style
            controller.view.frame = CGRect(x: 0, y: 0, width: 320, height: 120)
            assertSnapshot(of: controller, as: .image(size: CGSize(width: 320, height: 120)), named: style == .light ? "light" : "dark", record: record)
        }
    }

    private func makeNode(label: String, tone: String) -> NativeUINode {
        NativeUINode(
            id: 17, type: "firstlight_badge", layout: nil, style: nil,
            props: GenericProps(["label": label, "tone": tone, "a11y_label": "3 unread messages", "a11y_hint": "Open inbox"]),
            onPress: 0, onLongPress: 0, children: []
        )
    }
}
