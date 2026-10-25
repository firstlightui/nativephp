import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class ButtonGhostTests: XCTestCase {
    func testGhostKeepsA44PointTargetAtEverySize() {
        for size in ["sm", "md", "lg"] {
            let host = UIHostingController(rootView: FirstlightGhostButtonControl(
                configuration: ButtonGhostConfiguration(node: makeNode(size: size)),
                tokens: .fallback,
                onPress: {}
            ))
            let fitted = host.sizeThatFits(in: CGSize(width: 390, height: CGFloat.greatestFiniteMagnitude))

            // Mobile UI's plain ghost measured about 19 pt tall on iOS 26.
            XCTAssertGreaterThanOrEqual(fitted.height, FirstlightGhostButtonControl.minimumTarget, size)
        }
    }

    func testConfigurationDecodesTheActionContract() {
        let configuration = ButtonGhostConfiguration(node: makeNode())

        XCTAssertEqual(configuration.nodeID, 12)
        XCTAssertEqual(configuration.label, "settings recipe")
        XCTAssertEqual(configuration.trailingIcon, "chevron.right")
        XCTAssertEqual(configuration.accessibilityHint, "Opens the bundled article")
        XCTAssertEqual(configuration.callbackID, 52)
        XCTAssertTrue(configuration.isEnabled)
        XCTAssertFalse(ButtonGhostConfiguration(node: makeNode(disabled: true)).isEnabled)
        XCTAssertFalse(ButtonGhostConfiguration(node: makeNode(loading: true)).isEnabled)
    }

    private func makeNode(size: String = "md", disabled: Bool = false, loading: Bool = false) -> NativeUINode {
        NativeUINode(
            id: 12,
            type: "firstlight.button",
            layout: nil,
            style: nil,
            props: GenericProps([
                "label": "settings recipe",
                "variant": "ghost",
                "size": size,
                "trailing_icon": "chevron.right",
                "disabled": disabled,
                "loading": loading,
                "a11y_hint": "Opens the bundled article",
            ]),
            onPress: 52,
            onLongPress: 0,
            children: []
        )
    }
}
