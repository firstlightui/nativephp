import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class ListItemSnapshotTests: XCTestCase {
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
        callbackID: Int = 41
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
            ]),
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
