import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class StatusLabelSnapshotTests: XCTestCase {
    func testRendererConfigurationDecodesStaticTextAndAccessibility() {
        let configuration = StatusLabelRendererConfiguration(node: makeNode())

        XCTAssertEqual(configuration.label, "Awaiting review")
        XCTAssertEqual(configuration.tone, .warning)
        XCTAssertEqual(configuration.accessibilityLabel, "Referral status: awaiting review")
        XCTAssertEqual(configuration.accessibilityHint, "Updated by the referrals team")
    }

    func testMalformedNativeToneFallsBackToNeutralWithoutCrashing() {
        let configuration = StatusLabelRendererConfiguration(node: makeNode(tone: "paused"))

        XCTAssertEqual(configuration.tone, .neutral)
    }

    func testServerPublicationUpdatesDisplayMetadataByStableNodeID() {
        var state = StatusLabelRendererState(node: makeNode())
        let updated = makeNode(label: "Ready", tone: "success")

        XCTAssertTrue(state.serverPublished(NativeUITree(
            version: 1,
            callbackCount: 0,
            root: updated
        )))
        XCTAssertEqual(state.configuration.label, "Ready")
        XCTAssertEqual(state.configuration.tone, .success)
    }

    func testEveryToneResolvesToOpaqueContrastSafeColours() {
        for tone in StatusLabelTone.allCases {
            let colours = FirstlightStatusLabelTokens.from(
                theme: .statusLabelTest,
                tone: tone,
                traits: UITraitCollection(userInterfaceStyle: .light)
            )

            XCTAssertEqual(colours.background.cgColor.alpha, 1, "\(tone) background")
            XCTAssertEqual(colours.foreground.cgColor.alpha, 1, "\(tone) foreground")
            XCTAssertGreaterThanOrEqual(
                statusLabelContrastRatio(colours.foreground, colours.background),
                4.5,
                "\(tone) contrast"
            )
        }
    }

    func testControlKeepsStaticTextSemanticsAndFullAccessibleName() {
        let control = FirstlightStatusLabelControl(
            label: "Awaiting review",
            tokens: .warningTest,
            accessibilityLabel: "Referral status: awaiting review",
            accessibilityHint: "Updated by the referrals team"
        )

        XCTAssertEqual(control.accessibilityLabel, "Referral status: awaiting review")
        XCTAssertEqual(control.accessibilityHint, "Updated by the referrals team")
        XCTAssertFalse(control.isInteractive)
    }

    func testProductionRendererConstructsAgainstTheNativePHPContract() {
        let renderer = StatusLabelRenderer(node: makeNode())

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
        label: String = "Awaiting review",
        tone: String = "warning"
    ) -> NativeUINode {
        NativeUINode(
            id: 7,
            type: "firstlight_status-label",
            layout: nil,
            style: nil,
            props: GenericProps([
                "label": label,
                "tone": tone,
                "a11y_label": "Referral status: awaiting review",
                "a11y_hint": "Updated by the referrals team",
            ]),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
    }

    private func makeViewController(
        style: UIUserInterfaceStyle,
        contentSize: UIContentSizeCategory
    ) -> UIViewController {
        let view = VStack(alignment: .leading, spacing: 12) {
            ForEach(StatusLabelTone.allCases, id: \.self) { tone in
                FirstlightStatusLabelControl(
                    label: tone == .warning
                        ? "Awaiting review from the referrals team"
                        : tone.rawValue.capitalized,
                    tokens: FirstlightStatusLabelTokens.snapshot(tone: tone),
                    accessibilityLabel: "\(tone.rawValue.capitalized) status",
                    accessibilityHint: ""
                )
            }
        }
        .padding(20)
        .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .topLeading)
        .background(Color(uiColor: .systemBackground))

        return StatusLabelTraitHostingController(
            rootView: view,
            style: style,
            contentSize: contentSize
        )
    }
}

private extension NativeUITokens {
    static let statusLabelTest = NativeUITokens(
        primary: Color(uiColor: .systemTeal),
        onPrimary: Color(uiColor: .white),
        surface: Color(uiColor: .systemBackground),
        onSurface: Color(uiColor: .label),
        background: Color(uiColor: .systemBackground),
        onBackground: Color(uiColor: .label),
        surfaceVariant: Color(uiColor: .secondarySystemBackground),
        onSurfaceVariant: Color(uiColor: .secondaryLabel),
        destructive: Color(uiColor: .systemRed),
        onDestructive: Color(uiColor: .white),
        success: Color(uiColor: .systemGreen),
        onSuccess: Color(uiColor: .black),
        accent: Color(uiColor: .systemOrange),
        onAccent: Color(uiColor: .white)
    )
}

private extension FirstlightStatusLabelTokens {
    static let warningTest = FirstlightStatusLabelTokens(
        background: .systemOrange,
        foreground: .black
    )

    static func snapshot(tone: StatusLabelTone) -> FirstlightStatusLabelTokens {
        from(
            theme: .statusLabelTest,
            tone: tone,
            traits: UITraitCollection(userInterfaceStyle: .light)
        )
    }
}

@MainActor
private final class StatusLabelTraitHostingController<Content: View>: UIViewController {
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
