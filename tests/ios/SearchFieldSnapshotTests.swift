import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class SearchFieldSnapshotTests: XCTestCase {
    func testLightDarkAndAccessibilitySnapshotsWhenEvidenceCaptureIsEnabled() throws {
        guard ProcessInfo.processInfo.environment["FIRSTLIGHT_VERIFY_SEARCH_FIELD_SNAPSHOTS"] == "1" else {
            throw XCTSkip("Search Field screenshot evidence is controller-owned.")
        }

        let recordMode: SnapshotTestingConfiguration.Record = ProcessInfo.processInfo.environment[
            "FIRSTLIGHT_RECORD_SNAPSHOTS"
        ] == "1" ? .all : .never

        assertSnapshot(of: controller(style: .light, size: .large), as: .image(on: .iPhoneSe), named: "light", record: recordMode)
        assertSnapshot(of: controller(style: .dark, size: .large), as: .image(on: .iPhoneSe), named: "dark", record: recordMode)
        assertSnapshot(
            of: controller(style: .light, size: .accessibilityExtraExtraExtraLarge),
            as: .image(on: .iPhoneSe),
            named: "accessibility-extra-extra-extra-large",
            record: recordMode
        )
    }

    private func controller(style: UIUserInterfaceStyle, size: UIContentSizeCategory) -> UIViewController {
        let node = NativeUINode(
            id: 7,
            type: "firstlight_search_field",
            layout: nil,
            style: nil,
            props: GenericProps([
                "value": "Referral",
                "placeholder": "Search referrals",
                "a11y_label": "Search referrals",
            ]),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
        let host = UIHostingController(rootView: SearchFieldRenderer(node: node).padding(20))
        host.overrideUserInterfaceStyle = style
        host.view.backgroundColor = .systemBackground
        host.setOverrideTraitCollection(
            UITraitCollection(preferredContentSizeCategory: size),
            forChild: host
        )
        return host
    }
}
