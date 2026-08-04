import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class IconButtonSnapshotTests: XCTestCase {
    #if SWIFT_PACKAGE
    func testPackageShimPreservesResolvedIOSSymbolNames() {
        XCTAssertEqual(getIconForName("plus.circle"), "plus.circle")
    }
    #endif

    func testConfigurationDecodesTheCompleteActionContract() {
        let configuration = IconButtonRendererConfiguration(node: makeNode())

        XCTAssertEqual(configuration.nodeID, 7)
        XCTAssertEqual(configuration.icon, "plus.circle")
        XCTAssertEqual(configuration.iconVariant, "outlined")
        XCTAssertEqual(configuration.variant, .success)
        XCTAssertEqual(configuration.size, .large)
        XCTAssertEqual(configuration.accessibilityLabel, "Add item")
        XCTAssertEqual(configuration.accessibilityHint, "Adds a blank item")
        XCTAssertEqual(configuration.callbackID, 41)
        XCTAssertTrue(configuration.isEnabled)
    }

    func testMalformedVariantAndSizeDefensivelyUseStableDefaults() {
        let configuration = IconButtonRendererConfiguration(node: makeNode(
            variant: "accent",
            size: "xl"
        ))

        XCTAssertEqual(configuration.variant, .primary)
        XCTAssertEqual(configuration.size, .medium)
    }

    func testDisabledLoadingAndMissingCallbacksSuppressActivation() {
        XCTAssertNil(IconButtonRendererConfiguration(node: makeNode(disabled: true)).pressEvent())
        XCTAssertNil(IconButtonRendererConfiguration(node: makeNode(loading: true)).pressEvent())
        XCTAssertNil(IconButtonRendererConfiguration(node: makeNode(callbackID: 0)).pressEvent())
        XCTAssertEqual(
            IconButtonRendererConfiguration(node: makeNode()).pressEvent(),
            .press(callbackID: 41, nodeID: 7)
        )
    }

    func testServerPublicationUpdatesStateWithoutEmitting() {
        var state = IconButtonRendererState(node: makeNode())
        let updated = makeNode(icon: "trash", variant: "destructive", loading: true)

        XCTAssertTrue(state.serverPublished(NativeUITree(
            version: 1,
            callbackCount: 1,
            root: updated
        )))
        XCTAssertEqual(state.configuration.icon, "trash")
        XCTAssertEqual(state.configuration.variant, .destructive)
        XCTAssertTrue(state.configuration.loading)
        XCTAssertNil(state.configuration.pressEvent())
    }

    func testControlKeepsIconDecorativeAndTargetAtLeastFortyFourPoints() {
        for size in FirstlightIconButtonSize.allCases {
            let control = FirstlightIconButtonControl(
                configuration: IconButtonRendererConfiguration(node: makeNode(size: size.rawValue)),
                tokens: .iconButtonTest,
                onPress: {}
            )

            XCTAssertTrue(control.iconIsDecorative)
            XCTAssertGreaterThanOrEqual(control.minimumTarget, 44)
            XCTAssertGreaterThanOrEqual(control.metrics.minimumTarget, 44)
        }
    }

    func testProductionRendererConstructsAgainstNativePHPContract() {
        let renderer = IconButtonRenderer(node: makeNode())

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
        icon: String = "plus.circle",
        variant: String = "success",
        size: String = "lg",
        disabled: Bool = false,
        loading: Bool = false,
        callbackID: Int = 41
    ) -> NativeUINode {
        NativeUINode(
            id: 7,
            type: "firstlight.icon-button",
            layout: nil,
            style: nil,
            props: GenericProps([
                "icon": icon,
                "icon_variant": "outlined",
                "variant": variant,
                "size": size,
                "disabled": disabled,
                "loading": loading,
                "a11y_label": "Add item",
                "a11y_hint": "Adds a blank item",
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
        let tokens = NativeUITokens.iconButtonTest
        let view = VStack(alignment: .leading, spacing: 12) {
            ForEach(FirstlightIconButtonVariant.allCases, id: \.self) { variant in
                HStack(spacing: 12) {
                    ForEach(FirstlightIconButtonSize.allCases, id: \.self) { size in
                        FirstlightIconButtonControl(
                            configuration: IconButtonRendererConfiguration(node: self.makeNode(
                                variant: variant.rawValue,
                                size: size.rawValue
                            )),
                            tokens: tokens,
                            onPress: {}
                        )
                    }
                }
            }
            FirstlightIconButtonControl(
                configuration: IconButtonRendererConfiguration(node: makeNode(disabled: true)),
                tokens: tokens,
                onPress: {}
            )
            FirstlightIconButtonControl(
                configuration: IconButtonRendererConfiguration(node: makeNode(loading: true)),
                tokens: tokens,
                onPress: {}
            )
        }
        .padding(20)
        .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .topLeading)
        .background(Color(uiColor: .systemBackground))

        return IconButtonTraitHostingController(
            rootView: view,
            style: style,
            contentSize: contentSize
        )
    }
}

private extension NativeUITokens {
    static let iconButtonTest = NativeUITokens(
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
private final class IconButtonTraitHostingController<Content: View>: UIViewController {
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
