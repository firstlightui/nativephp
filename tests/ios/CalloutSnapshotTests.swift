import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class CalloutSnapshotTests: XCTestCase {
    func testConfigurationDecodesMessageToneActionAndAccessibility() {
        let configuration = CalloutRendererConfiguration(node: makeNode())

        XCTAssertEqual(configuration.message, "Your changes have not been submitted.")
        XCTAssertEqual(configuration.tone, .warning)
        XCTAssertEqual(configuration.actionLabel, "Review changes")
        XCTAssertEqual(configuration.accessibilityLabel, "Submission warning")
        XCTAssertEqual(configuration.accessibilityHint, "Review the form before continuing")
        XCTAssertEqual(configuration.callbackID, 41)
        XCTAssertTrue(configuration.hasAction)
    }

    func testMalformedNativeDataFallsBackAndSuppressesIncompleteActions() {
        let invalidTone = CalloutRendererConfiguration(node: makeNode(tone: "critical"))
        let labelOnly = CalloutRendererConfiguration(node: makeNode(callbackID: 0))
        let callbackOnly = CalloutRendererConfiguration(node: makeNode(actionLabel: ""))

        XCTAssertEqual(invalidTone.tone, .info)
        XCTAssertFalse(labelOnly.hasAction)
        XCTAssertNil(labelOnly.pressEvent())
        XCTAssertFalse(callbackOnly.hasAction)
        XCTAssertNil(callbackOnly.pressEvent())
    }

    func testPressEmitsTheStandardEventAndProgrammaticUpdatesDoNot() {
        var state = CalloutRendererState(node: makeNode())

        XCTAssertEqual(
            state.configuration.pressEvent(),
            .press(callbackID: 41, nodeID: 7)
        )

        let updated = makeNode(message: "The form is ready.", tone: "success", actionLabel: "", callbackID: 0)
        XCTAssertTrue(state.serverPublished(NativeUITree(
            version: 1,
            callbackCount: 0,
            root: updated
        )))
        XCTAssertEqual(state.configuration.message, "The form is ready.")
        XCTAssertEqual(state.configuration.tone, .success)
        XCTAssertNil(state.configuration.pressEvent())
    }

    func testToneOwnsDistinctVisualAndAccessibleSemantics() {
        XCTAssertEqual(Set(CalloutTone.allCases.map(\.systemImageName)).count, CalloutTone.allCases.count)
        XCTAssertEqual(
            firstlightCalloutAccessibilityLabel(message: "Check the form.", tone: .warning, explicit: ""),
            "Warning: Check the form."
        )
        XCTAssertEqual(
            firstlightCalloutAccessibilityLabel(message: "Check the form.", tone: .warning, explicit: "Custom warning"),
            "Custom warning"
        )
        XCTAssertEqual(firstlightCalloutActionMinimumHeight, 44)
    }

    func testProductionRendererConstructsAgainstTheNativePHPContract() {
        let renderer = CalloutRenderer(node: makeNode())

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
            of: makeViewController(style: .light, contentSize: .accessibilityExtraExtraExtraLarge),
            as: .image(on: .iPhoneSe),
            named: "accessibility-extra-extra-extra-large",
            record: recordMode
        )
    }

    private func makeNode(
        message: String = "Your changes have not been submitted.",
        tone: String = "warning",
        actionLabel: String = "Review changes",
        callbackID: Int = 41
    ) -> NativeUINode {
        NativeUINode(
            id: 7,
            type: "firstlight_callout",
            layout: nil,
            style: nil,
            props: GenericProps([
                "message": message,
                "tone": tone,
                "action_label": actionLabel,
                "a11y_label": "Submission warning",
                "a11y_hint": "Review the form before continuing",
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
        let view = VStack(spacing: 12) {
            ForEach(CalloutTone.allCases, id: \.self) { tone in
                FirstlightCalloutControl(
                    configuration: CalloutRendererConfiguration(node: self.makeNode(
                        message: tone == .warning
                            ? "Your changes have not been submitted. Review the form before continuing."
                            : "\(tone.accessibilityName) message",
                        tone: tone.rawValue,
                        actionLabel: tone == .warning ? "Review changes" : "",
                        callbackID: tone == .warning ? 41 : 0
                    )),
                    tokens: .calloutSnapshot,
                    onPress: {}
                )
            }
        }
        .padding(16)
        .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .top)
        .background(Color(uiColor: .systemBackground))

        return CalloutTraitHostingController(
            rootView: view,
            style: style,
            contentSize: contentSize
        )
    }
}

private extension NativeUITokens {
    static let calloutSnapshot = NativeUITokens(
        primary: Color(uiColor: .systemBlue),
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
        onAccent: Color(uiColor: .black)
    )
}

@MainActor
private final class CalloutTraitHostingController<Content: View>: UIViewController {
    private let host: UIHostingController<Content>

    init(rootView: Content, style: UIUserInterfaceStyle, contentSize: UIContentSizeCategory) {
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
