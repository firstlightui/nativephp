import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class ActivityIndicatorSnapshotTests: XCTestCase {
    func testConfigurationDecodesSemanticSizeAndRequiredName() {
        let configuration = ActivityIndicatorRendererConfiguration(
            node: makeNode(size: "lg", label: "Loading appointments")
        )

        XCTAssertEqual(configuration.size, .large)
        XCTAssertEqual(configuration.accessibilityLabel, "Loading appointments")
    }

    func testMalformedNativeSizeFallsBackToMediumWithoutCrashing() {
        let configuration = ActivityIndicatorRendererConfiguration(
            node: makeNode(size: "oversized")
        )

        XCTAssertEqual(configuration.size, .medium)
    }

    func testAnnouncementIsConsumedOnlyOncePerMountedState() {
        var state = ActivityIndicatorAnnouncementState()

        XCTAssertEqual(
            state.consume(label: "Loading appointments"),
            "Loading appointments"
        )
        XCTAssertNil(state.consume(label: "Loading appointments"))
        XCTAssertNil(state.consume(label: "Loading updated appointments"))
    }

    func testReconciliationUpdatesPresentationWithoutResettingAnnouncement() {
        var state = ActivityIndicatorRendererState(
            node: makeNode(size: "sm", label: "Loading appointments")
        )
        XCTAssertEqual(state.consumeAnnouncement(), "Loading appointments")

        let published = makeNode(size: "lg", label: "Loading updated appointments")
        XCTAssertTrue(state.serverPublished(tree(node: published)))
        XCTAssertEqual(state.configuration.size, .large)
        XCTAssertEqual(
            state.configuration.accessibilityLabel,
            "Loading updated appointments"
        )
        XCTAssertNil(state.consumeAnnouncement())
        XCTAssertFalse(state.serverPublished(tree(node: published)))
    }

    func testStableNodeCanBeReconciledFromANestedPublishedTree() {
        var state = ActivityIndicatorRendererState(node: makeNode(size: "sm"))
        let nested = NativeUINode(
            id: 2,
            type: "column",
            layout: nil,
            style: nil,
            props: GenericProps(),
            onPress: 0,
            onLongPress: 0,
            children: [makeNode(size: "lg")]
        )

        XCTAssertTrue(state.serverPublished(tree(node: nested)))
        XCTAssertEqual(state.configuration.size, .large)
    }

    func testControlUsesNativeSizingAndKeepsStaticAccessibilityMetadata() {
        let control = FirstlightActivityIndicatorControl(
            size: .small,
            accessibilityLabel: "Loading appointments",
            tint: .blue
        )

        XCTAssertEqual(control.size.controlSize, .small)
        XCTAssertEqual(control.accessibilityLabel, "Loading appointments")
        XCTAssertFalse(control.isInteractive)
    }

    func testProductionRendererConstructsAgainstTheNativePHPContract() {
        let renderer = ActivityIndicatorRenderer(node: makeNode())

        XCTAssertEqual(renderer.node.id, 7)
    }

    func testLightAndDarkSnapshotsContainEverySemanticSize() {
        let record: SnapshotTestingConfiguration.Record = ProcessInfo.processInfo.environment[
            "FIRSTLIGHT_RECORD_SNAPSHOTS"
        ] == "1" ? .all : .never

        for style: UIUserInterfaceStyle in [.light, .dark] {
            let view = HStack(spacing: 32) {
                ForEach(ActivityIndicatorSize.allCases, id: \.self) { size in
                    FirstlightActivityIndicatorControl(
                        size: size,
                        accessibilityLabel: "Loading \(size.rawValue)",
                        tint: Color(uiColor: .systemTeal)
                    )
                }
            }
            .padding(24)
            .frame(maxWidth: .infinity, maxHeight: .infinity)
            .background(Color(uiColor: .systemBackground))

            let controller = UIHostingController(rootView: view)
            controller.overrideUserInterfaceStyle = style

            assertSnapshot(
                of: controller,
                as: .image(size: CGSize(width: 320, height: 140)),
                named: style == .light ? "light" : "dark",
                record: record
            )
        }
    }

    private func makeNode(
        size: String = "md",
        label: String = "Loading appointments"
    ) -> NativeUINode {
        NativeUINode(
            id: 7,
            type: "firstlight_activity-indicator",
            layout: nil,
            style: nil,
            props: GenericProps([
                "size": size,
                "a11y_label": label,
            ]),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
    }

    private func tree(node: NativeUINode) -> NativeUITree {
        NativeUITree(version: 1, callbackCount: 0, root: node)
    }
}
