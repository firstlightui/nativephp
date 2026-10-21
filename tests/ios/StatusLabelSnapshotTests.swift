import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class StatusLabelSnapshotTests: XCTestCase {
    func testWarningPairUsesPublishedCustomTokensAndIndependentFallbacks() {
        let node = NativeUINode(
            id: 1, type: "firstlight.status-label", layout: nil, style: nil,
            props: GenericProps([
                "warning_color": "#92400E", "on_warning_color": "#FFFFFF",
                "dark_warning_color": "#FDE68A", "dark_on_warning_color": "#292524",
            ]), onPress: 0, onLongPress: 0, children: []
        )
        let palette = StatusLabelRendererConfiguration(node: node).warningColors
        XCTAssertEqual(palette, CalloutRendererConfiguration(node: node).warningColors)
        XCTAssertEqual(palette, FeedbackCenterItemConfiguration(node: node).warningColors)
        for dark in [false, true] {
            let pair = palette.resolve(isDark: dark)
            let colours = FirstlightStatusLabelTokens.from(
                theme: .statusLabelTest, tone: .warning,
                traits: UITraitCollection(userInterfaceStyle: dark ? .dark : .light),
                warningColors: palette
            )
            XCTAssertEqual(statusLabelContrastRatio(colours.background, UIColor(pair.background)), 1, accuracy: 0.0001)
            XCTAssertEqual(statusLabelContrastRatio(colours.foreground, UIColor(pair.foreground)), 1, accuracy: 0.0001)
        }
        XCTAssertEqual(FirstlightWarningColors().light, "#B45309")
        XCTAssertEqual(FirstlightWarningColors().dark, "#FBBF24")
        XCTAssertEqual(FirstlightWarningColors().onLight, "#FFFFFF")
        XCTAssertEqual(FirstlightWarningColors().onDark, "#1C1917")
    }

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

    func testWrappedTextRemainsOnItsBackgroundAtLargestAccessibilitySize() throws {
        // Render the same label with and without ink: every opaque white text pixel
        // must have an opaque coloured background beneath it, including line ends.
        func pixels(foreground: UIColor) throws -> [UInt8] {
            let renderer = ImageRenderer(content: FirstlightStatusLabelControl(
                label: "Awaiting review from the referrals team",
                tokens: .init(background: .blue, foreground: foreground),
                accessibilityLabel: "Awaiting review from the referrals team",
                accessibilityHint: ""
            ).frame(width: 280).environment(\.dynamicTypeSize, .accessibility5))
            renderer.scale = 1
            let image = try XCTUnwrap(renderer.uiImage?.cgImage)
            var pixels = [UInt8](repeating: 0, count: image.width * image.height * 4)
            try pixels.withUnsafeMutableBytes { buffer in
                let context = try XCTUnwrap(CGContext(
                    data: buffer.baseAddress, width: image.width, height: image.height,
                    bitsPerComponent: 8, bytesPerRow: image.width * 4,
                    space: CGColorSpaceCreateDeviceRGB(),
                    bitmapInfo: CGImageAlphaInfo.premultipliedLast.rawValue
                ))
                context.draw(image, in: CGRect(x: 0, y: 0, width: image.width, height: image.height))
            }
            return pixels
        }
        let text = try pixels(foreground: .white)
        let background = try pixels(foreground: .clear)
        XCTAssertEqual(text.count, background.count)
        var textPixels = 0
        var escapedPixels = 0
        for index in stride(from: 0, to: min(text.count, background.count), by: 4) {
            if text[index] > 250 && text[index + 1] > 250 && text[index + 2] > 250 && text[index + 3] > 250 {
                textPixels += 1
                if background[index + 3] < 250 { escapedPixels += 1 }
            }
        }
        XCTAssertGreaterThan(textPixels, 100, "The control must actually render the long label")
        XCTAssertEqual(escapedPixels, 0, "Wrapped text must not escape the contrast-safe background")
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
                    tokens: FirstlightStatusLabelTokens.snapshot(tone: tone, style: style),
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

    static func snapshot(tone: StatusLabelTone, style: UIUserInterfaceStyle) -> FirstlightStatusLabelTokens {
        from(
            theme: .statusLabelTest,
            tone: tone,
            traits: UITraitCollection(userInterfaceStyle: style)
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
