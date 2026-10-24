import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class ListItemSnapshotTests: XCTestCase {
    func testIconTonesAndExplicitColourPrecedence() {
        let tokens = NativeUITokens.listItemTest
        let warning = FirstlightWarningColors()
        for (tone, expected) in [
            ("neutral", tokens.onSurfaceVariant), ("info", tokens.primary),
            ("success", tokens.success), ("danger", tokens.destructive),
            ("warning", warning.resolve(isDark: false).background), ("", Color.blue)
        ] {
            XCTAssertEqual(firstlightListItemIconColor(
                explicit: "", tone: tone, tokens: tokens, warningColors: warning,
                isDark: false, fallback: .blue
            ), expected)
            XCTAssertEqual(firstlightListItemIconColor(
                explicit: "#123ABC", tone: tone, tokens: tokens, warningColors: warning,
                isDark: false, fallback: .blue
            ), Color(.sRGB, red: 18.0 / 255, green: 58.0 / 255, blue: 188.0 / 255, opacity: 1))
        }
        XCTAssertEqual(firstlightListItemIconColor(
            explicit: "", tone: "warning", tokens: tokens, warningColors: warning,
            isDark: true, fallback: .blue
        ), warning.resolve(isDark: true).background)
        XCTAssertEqual(firstlightListItemIconColor(
            explicit: "#456", tone: "danger", tokens: tokens, warningColors: warning,
            isDark: true, fallback: .blue
        ), Color(.sRGB, red: 68.0 / 255, green: 85.0 / 255, blue: 102.0 / 255, opacity: 1))

        let configuration = ListItemRendererConfiguration(node: makeNode(iconProps: [
            "leading_icon_tone": "danger", "trailing_icon_tone": "warning",
            "leading_icon_color": "#123ABC", "trailing_icon_color": "#456"
        ]))
        XCTAssertEqual(configuration.leadingIconTone, "danger")
        XCTAssertEqual(configuration.trailingIconTone, "warning")
        XCTAssertEqual(configuration.leadingIconColor, "#123ABC")
        XCTAssertEqual(configuration.trailingIconColor, "#456")
    }

    #if SWIFT_PACKAGE
    func testPackageShimPreservesResolvedIOSSymbolNames() {
        XCTAssertEqual(getIconForName("person.crop.circle"), "person.crop.circle")
        XCTAssertEqual(getIconForName("chevron.right"), "chevron.right")
    }
    #endif

    func testConfigurationDecodesTheCompleteRowContract() {
        let configuration = ListItemRendererConfiguration(node: makeNode())

        XCTAssertEqual(configuration.nodeID, 7)
        XCTAssertEqual(configuration.headline, "Account")
        XCTAssertEqual(configuration.supporting, "Manage your profile and security")
        XCTAssertEqual(configuration.leadingType, .icon)
        XCTAssertEqual(configuration.leadingValue, "person.crop.circle")
        XCTAssertEqual(configuration.leadingIconVariant, "outlined")
        XCTAssertEqual(configuration.trailingType, .icon)
        XCTAssertEqual(configuration.trailingValue, "chevron.right")
        XCTAssertEqual(configuration.trailingIconVariant, "outlined")
        XCTAssertEqual(configuration.accessibilityLabel, "Account settings")
        XCTAssertEqual(configuration.accessibilityHint, "Opens account settings")
        XCTAssertEqual(configuration.callbackID, 41)
        XCTAssertTrue(configuration.isEnabled)
    }

    func testMalformedContentTypesDefensivelyRenderAsAbsent() {
        let configuration = ListItemRendererConfiguration(node: makeNode(
            leadingType: "control",
            trailingType: "button"
        ))

        XCTAssertEqual(configuration.leadingType, .none)
        XCTAssertEqual(configuration.trailingType, .none)
    }

    func testDisabledAndMissingCallbacksSuppressActivation() {
        XCTAssertNil(ListItemRendererConfiguration(node: makeNode(disabled: true)).pressEvent())
        XCTAssertNil(ListItemRendererConfiguration(node: makeNode(callbackID: 0)).pressEvent())
        XCTAssertEqual(
            ListItemRendererConfiguration(node: makeNode()).pressEvent(),
            .press(callbackID: 41, nodeID: 7)
        )
    }

    func testSwipeActionsDispatchOnlyPublishedEnabledCallbacks() {
        let configuration = ListItemRendererConfiguration(node: makeNode(actions: true))
        XCTAssertEqual(configuration.leadingActions.map(\.label), ["Archive"])
        XCTAssertEqual(configuration.trailingActions.map(\.label), ["Delete", "Flag"])
        XCTAssertEqual(configuration.trailingActions.first?.role, "destructive")
        XCTAssertEqual(configuration.actionEvent(callbackID: 52), .press(callbackID: 52, nodeID: 7))
        XCTAssertNil(configuration.actionEvent(callbackID: 999))
        XCTAssertNil(ListItemRendererConfiguration(node: makeNode(disabled: true, actions: true)).actionEvent(callbackID: 52))

        var state = ListItemRendererState(node: makeNode(actions: true))
        XCTAssertTrue(state.serverPublished(NativeUITree(version: 2, callbackCount: 1, root: makeNode())))
        XCTAssertNil(state.configuration.actionEvent(callbackID: 52))
    }

    func testContextMenuDispatchesOnlyEnabledPublishedItems() {
        let configuration = ListItemRendererConfiguration(node: makeNode(menu: true))
        XCTAssertEqual(configuration.contextMenu.map(\.label), ["Reply", "Forward", "Delete"])
        XCTAssertEqual(configuration.contextMenu.map(\.isEnabled), [true, false, true])
        XCTAssertEqual(configuration.contextMenu.last?.role, "destructive")
        XCTAssertTrue(configuration.hasContextMenu)
        XCTAssertEqual(configuration.actionEvent(callbackID: 61), .press(callbackID: 61, nodeID: 7))
        XCTAssertEqual(configuration.actionEvent(callbackID: 63), .press(callbackID: 63, nodeID: 7))
        XCTAssertNil(configuration.actionEvent(callbackID: 0), "disabled items publish no callback")
        XCTAssertNil(configuration.actionEvent(callbackID: 999))

        let disabledRow = ListItemRendererConfiguration(node: makeNode(disabled: true, menu: true))
        XCTAssertFalse(disabledRow.hasContextMenu)
        XCTAssertNil(disabledRow.actionEvent(callbackID: 61))

        var state = ListItemRendererState(node: makeNode(menu: true))
        XCTAssertTrue(state.serverPublished(NativeUITree(version: 2, callbackCount: 1, root: makeNode())))
        XCTAssertFalse(state.configuration.hasContextMenu)
        XCTAssertNil(state.configuration.actionEvent(callbackID: 61))
    }

    func testContextMenuExposesEnabledItemsAsVoiceOverActions() {
        let enabled = accessibilityCustomActionNames(for: makeNode(menu: true))
        XCTAssertTrue(enabled.contains("Reply"), "\(enabled)")
        XCTAssertTrue(enabled.contains("Delete"), "\(enabled)")
        XCTAssertFalse(enabled.contains("Forward"), "disabled items are not offered: \(enabled)")

        let disabledRow = accessibilityCustomActionNames(for: makeNode(disabled: true, menu: true))
        XCTAssertFalse(disabledRow.contains("Reply"), "\(disabledRow)")
    }

    private func accessibilityCustomActionNames(for node: NativeUINode) -> [String] {
        AccessibilityRuntime.enable()
        let host = UIHostingController(rootView: FirstlightListItemControl(
            configuration: ListItemRendererConfiguration(node: node),
            tokens: .listItemTest,
            onPress: {}
        ))
        let window = UIWindow(frame: CGRect(x: 0, y: 0, width: 375, height: 200))
        window.rootViewController = host
        window.makeKeyAndVisible()
        host.view.layoutIfNeeded()
        defer { window.isHidden = true }
        return customActionNames(in: host.view)
    }

    private func customActionNames(in root: NSObject) -> [String] {
        var names = (root.accessibilityCustomActions ?? []).map(\.name)
        var children = (root.accessibilityElements as? [NSObject]) ?? []
        if let view = root as? UIView { children += view.subviews }
        for child in children { names += customActionNames(in: child) }
        return names
    }

    func testServerPublicationUpdatesMetadataWithoutEmitting() {
        var state = ListItemRendererState(node: makeNode())
        let updated = makeNode(
            headline: "Billing",
            supporting: "Invoices and payment methods",
            disabled: true
        )

        XCTAssertTrue(state.serverPublished(NativeUITree(
            version: 1,
            callbackCount: 1,
            root: updated
        )))
        XCTAssertEqual(state.configuration.headline, "Billing")
        XCTAssertEqual(state.configuration.supporting, "Invoices and payment methods")
        XCTAssertTrue(state.configuration.disabled)
        XCTAssertNil(state.configuration.pressEvent())
        XCTAssertFalse(state.serverPublished(NativeUITree(
            version: 2,
            callbackCount: 1,
            root: updated
        )))
    }

    func testAccessibilityUsesExplicitOverrideOrCombinedVisibleText() {
        XCTAssertEqual(
            firstlightListItemAccessibilityLabel(
                headline: "Account",
                supporting: "Manage your profile",
                explicit: "Account settings"
            ),
            "Account settings"
        )
        XCTAssertEqual(
            firstlightListItemAccessibilityLabel(
                headline: "Account",
                supporting: "Manage your profile",
                explicit: ""
            ),
            "Account, Manage your profile"
        )
        XCTAssertEqual(
            firstlightListItemAccessibilityLabel(
                headline: "Account",
                supporting: "",
                explicit: ""
            ),
            "Account"
        )
    }

    func testControlKeepsEdgeContentDecorativeAndTargetAtLeastFortyFourPoints() {
        let control = FirstlightListItemControl(
            configuration: ListItemRendererConfiguration(node: makeNode()),
            tokens: .listItemTest,
            onPress: {}
        )

        XCTAssertTrue(control.leadingContentIsDecorative)
        XCTAssertTrue(control.trailingContentIsDecorative)
        XCTAssertGreaterThanOrEqual(control.minimumTarget, 44)
    }

    func testProductionRendererConstructsAgainstNativePHPContract() {
        let renderer = ListItemRenderer(node: makeNode())

        XCTAssertEqual(renderer.node.id, 7)
    }

    func testLightDarkAndAccessibilitySnapshots() {
        let recordMode: SnapshotTestingConfiguration.Record = ProcessInfo.processInfo.environment[
            "FIRSTLIGHT_RECORD_SNAPSHOTS"
        ] == "1" ? .all : .never

        assertSnapshot(
            of: makeViewController(style: .light, contentSize: .large),
            as: .image(on: .iPhoneSe),
            named: "light",
            record: recordMode
        )
        assertSnapshot(
            of: makeViewController(style: .dark, contentSize: .large),
            as: .image(on: .iPhoneSe),
            named: "dark",
            record: recordMode
        )
        assertSnapshot(
            of: makeViewController(
                style: .light,
                contentSize: .accessibilityExtraExtraExtraLarge
            ),
            as: .image(on: .iPhoneSe),
            named: "accessibility-extra-extra-extra-large",
            record: recordMode
        )
    }

    private func makeNode(
        headline: String = "Account",
        supporting: String = "Manage your profile and security",
        leadingType: String = "icon",
        leadingValue: String = "person.crop.circle",
        trailingType: String = "icon",
        trailingValue: String = "chevron.right",
        disabled: Bool = false,
        callbackID: Int = 41,
        actions: Bool = false,
        menu: Bool = false,
        iconProps: [String: Any] = [:]
    ) -> NativeUINode {
        NativeUINode(
            id: 7,
            type: "firstlight.list-item",
            layout: nil,
            style: nil,
            props: GenericProps([
                "headline": headline,
                "supporting": supporting,
                "leading_type": leadingType,
                "leading_value": leadingValue,
                "leading_icon_variant": "outlined",
                "trailing_type": trailingType,
                "trailing_value": trailingValue,
                "trailing_icon_variant": "outlined",
                "disabled": disabled,
                "a11y_label": "Account settings",
                "a11y_hint": "Opens account settings",
                "firstlight_leading_actions_json": actions ? """
                    [{"cb":51,"label":"Archive","icon":"archivebox","icon_variant":"","role":""}]
                    """ : "",
                "firstlight_trailing_actions_json": actions ? """
                    [{"cb":52,"label":"Delete","icon":"trash","icon_variant":"","role":"destructive"},
                     {"cb":53,"label":"Flag","icon":"flag","icon_variant":"","role":""}]
                    """ : "",
                "firstlight_context_menu_json": menu ? """
                    [{"cb":61,"label":"Reply","icon":"arrowshape.turn.up.left","icon_variant":"","role":"","disabled":false},
                     {"cb":0,"label":"Forward","icon":"","icon_variant":"","role":"","disabled":true},
                     {"cb":63,"label":"Delete","icon":"trash","icon_variant":"","role":"destructive","disabled":false}]
                    """ : "",
            ].merging(iconProps) { _, value in value }),
            onPress: callbackID,
            onLongPress: 0,
            children: []
        )
    }

    private func makeViewController(
        style: UIUserInterfaceStyle,
        contentSize: UIContentSizeCategory
    ) -> UIViewController {
        let tokens = NativeUITokens.listItemTest
        let view = VStack(spacing: 0) {
            FirstlightListItemControl(
                configuration: ListItemRendererConfiguration(node: makeNode()),
                tokens: tokens,
                onPress: {}
            )
            Divider()
            FirstlightListItemControl(
                configuration: ListItemRendererConfiguration(node: makeNode(
                    headline: "Wojt Janowski",
                    supporting: "Owner",
                    leadingType: "monogram",
                    leadingValue: "WJ",
                    trailingType: "text",
                    trailingValue: "Admin"
                )),
                tokens: tokens,
                onPress: {}
            )
            Divider()
            FirstlightListItemControl(
                configuration: ListItemRendererConfiguration(node: makeNode(
                    headline: "Unavailable account",
                    supporting: "Ask an administrator for access",
                    disabled: true
                )),
                tokens: tokens,
                onPress: {}
            )
        }
        .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .top)
        .background(Color(uiColor: .systemBackground))

        return ListItemTraitHostingController(
            rootView: view,
            style: style,
            contentSize: contentSize
        )
    }
}

private extension NativeUITokens {
    static let listItemTest = NativeUITokens(
        primary: Color(uiColor: .systemBlue),
        onPrimary: .white,
        surface: Color(uiColor: .systemBackground),
        onSurface: Color(uiColor: .label),
        surfaceVariant: Color(uiColor: .secondarySystemBackground),
        onSurfaceVariant: Color(uiColor: .secondaryLabel),
        destructive: Color(uiColor: .systemRed),
        onDestructive: .white,
        success: Color(uiColor: .systemGreen),
        onSuccess: .black,
        accent: Color(uiColor: .systemOrange),
        onAccent: .black
    )
}

@MainActor
private final class ListItemTraitHostingController<Content: View>: UIViewController {
    private let host: UIHostingController<Content>

    init(
        rootView: Content,
        style: UIUserInterfaceStyle,
        contentSize: UIContentSizeCategory
    ) {
        host = UIHostingController(rootView: rootView)
        host.traitOverrides.userInterfaceStyle = style
        host.traitOverrides.preferredContentSizeCategory = contentSize
        super.init(nibName: nil, bundle: nil)
    }

    @available(*, unavailable)
    required init?(coder: NSCoder) {
        fatalError("init(coder:) has not been implemented")
    }

    override func viewDidLoad() {
        super.viewDidLoad()
        addChild(host)
        host.view.translatesAutoresizingMaskIntoConstraints = false
        view.addSubview(host.view)
        NSLayoutConstraint.activate([
            host.view.leadingAnchor.constraint(equalTo: view.leadingAnchor),
            host.view.trailingAnchor.constraint(equalTo: view.trailingAnchor),
            host.view.topAnchor.constraint(equalTo: view.topAnchor),
            host.view.bottomAnchor.constraint(equalTo: view.bottomAnchor),
        ])
        host.didMove(toParent: self)
    }
}
