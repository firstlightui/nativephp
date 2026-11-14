import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class ToolbarRendererTests: XCTestCase {
    func testConfigurationSplitsThePrimaryActionFromTheGroup() {
        let configuration = ToolbarRendererConfiguration(node: toolbarFixture())

        XCTAssertEqual(configuration.items.map(\.accessibilityLabel), ["Checklist", "Attach", "Markup"])
        XCTAssertEqual(configuration.primary?.accessibilityLabel, "New note")
        XCTAssertEqual(configuration.items[2].badge, "3")
        XCTAssertEqual(configuration.items[0].badge, "")
        XCTAssertEqual(configuration.items[0].haptic, .keypress)
    }

    /// The items must reach the navigation stack's bottom bar from deep in
    /// the screen, which is where NativePHP renders a Toolbar node.
    func testItemsJoinTheNavigationStackBottomBar() throws {
        let screen = NavigationStack {
            VStack {
                Text("Note")
                ToolbarRenderer(node: toolbarFixture())
            }
        }
        let host = UIHostingController(rootView: screen)
        let window = UIWindow(frame: CGRect(x: 0, y: 0, width: 402, height: 874))
        window.rootViewController = host
        window.makeKeyAndVisible()
        defer { window.isHidden = true }

        RunLoop.main.run(until: Date().addingTimeInterval(0.5))

        let navigation = try XCTUnwrap(firstNavigationController(in: host))
        XCTAssertFalse(navigation.isToolbarHidden, "the bottom toolbar should be shown")
        let items = navigation.topViewController?.toolbarItems ?? []
        if #available(iOS 26.0, *) {
            XCTAssertEqual(items.first { $0.title == "Markup" }?.badge?.stringValue, "3")
            XCTAssertNil(items.first { $0.title == "Checklist" }?.badge)
        }
        XCTAssertEqual(items.compactMap(\.title), ["Checklist", "Attach", "Markup", "New note"])
        XCTAssertNotNil(items.first { $0.title == "Attach" }?.menu, "Attach opens its menu")
    }

    /// PHP republishes the screen after an action; the bar must follow it,
    /// such as a badge clearing or an item turning disabled.
    func testBarItemsFollowARepublishedTree() throws {
        NativeUIBridge.shared.currentTree = nil
        defer { NativeUIBridge.shared.currentTree = nil }
        let screen = NavigationStack {
            VStack {
                Text("Note")
                ToolbarRenderer(node: toolbarFixture())
            }
        }
        let host = UIHostingController(rootView: screen)
        let window = UIWindow(frame: CGRect(x: 0, y: 0, width: 402, height: 874))
        window.rootViewController = host
        window.makeKeyAndVisible()
        defer { window.isHidden = true }
        RunLoop.main.run(until: Date().addingTimeInterval(0.5))

        let republished = toolbarFixture(markupBadge: "", checklistDisabled: true, primaryDisabled: true)
        NativeUIBridge.shared.currentTree = NativeUITree(version: 1, callbackCount: 0, root: republished)
        RunLoop.main.run(until: Date().addingTimeInterval(0.5))

        let navigation = try XCTUnwrap(firstNavigationController(in: host))
        let items = navigation.topViewController?.toolbarItems ?? []
        XCTAssertEqual(items.first { $0.title == "Checklist" }?.isEnabled, false)
        XCTAssertEqual(items.first { $0.title == "Markup" }?.isEnabled, true)
        XCTAssertEqual(items.first { $0.title == "New note" }?.isEnabled, false)
        XCTAssertEqual(items.compactMap(\.title), ["Checklist", "Attach", "Markup", "New note"])
        if #available(iOS 26.0, *) {
            XCTAssertNil(items.first { $0.title == "Markup" }?.badge)
        }
    }
}

private func firstNavigationController(in controller: UIViewController) -> UINavigationController? {
    if let navigation = controller as? UINavigationController { return navigation }
    for child in controller.children {
        if let found = firstNavigationController(in: child) { return found }
    }
    return nil
}

func toolbarFixture(markupBadge: String = "3", checklistDisabled: Bool = false, primaryDisabled: Bool = false) -> NativeUINode {
    NativeUINode(
        id: 1,
        type: "firstlight.toolbar",
        layout: nil,
        style: nil,
        props: GenericProps([:]),
        onPress: 0,
        onLongPress: 0,
        children: [
            toolbarItemFixture(2, "checklist", "Checklist", disabled: checklistDisabled, haptic: "keypress"),
            toolbarItemFixture(
                3, "paperclip", "Attach", callbackID: 0,
                menu: #"[{"cb":31,"label":"Photo","icon":"photo","icon_variant":"","role":"","disabled":false},{"cb":0,"label":"File","icon":"","icon_variant":"","role":"","disabled":true}]"#
            ),
            toolbarItemFixture(4, "pencil.tip.crop.circle", "Markup", badge: markupBadge),
            toolbarItemFixture(5, "square.and.pencil", "New note", primary: true, disabled: primaryDisabled),
        ]
    )
}

func toolbarItemFixture(
    _ id: Int,
    _ icon: String,
    _ label: String,
    callbackID: Int? = nil,
    primary: Bool = false,
    disabled: Bool = false,
    badge: String = "",
    haptic: String = "",
    menu: String = ""
) -> NativeUINode {
    NativeUINode(
        id: id,
        type: "firstlight.toolbar-item",
        layout: nil,
        style: nil,
        props: GenericProps([
            "icon": icon,
            "a11y_label": label,
            "primary": primary,
            "disabled": disabled,
            "badge": badge,
            "haptic": haptic,
            "menu_json": menu,
        ]),
        onPress: callbackID ?? id * 10,
        onLongPress: 0,
        children: []
    )
}
