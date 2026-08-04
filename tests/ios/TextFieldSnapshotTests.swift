import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class TextFieldSnapshotTests: XCTestCase {
    func testLightDarkAndAccessibilitySnapshots() {
        let recordMode: SnapshotTestingConfiguration.Record = ProcessInfo.processInfo.environment[
            "FIRSTLIGHT_RECORD_SNAPSHOTS"
        ] == "1" ? .all : .never

        assertSnapshot(
            of: makeController(style: .light, size: .large, error: ""),
            as: .image(on: .iPhoneSe),
            named: "light-clearable",
            record: recordMode
        )
        assertSnapshot(
            of: makeController(style: .dark, size: .large, error: "Enter a valid email"),
            as: .image(on: .iPhoneSe),
            named: "dark-error",
            record: recordMode
        )
        assertSnapshot(
            of: makeController(
                style: .light,
                size: .accessibilityExtraExtraExtraLarge,
                error: ""
            ),
            as: .image(on: .iPhoneSe),
            named: "accessibility-extra-extra-extra-large",
            record: recordMode
        )
    }

    private func makeController(
        style: UIUserInterfaceStyle,
        size: UIContentSizeCategory,
        error: String
    ) -> UIViewController {
        TextFieldTraitHostingController(
            rootView: TextFieldSnapshotHost(configuration: configuration(error: error)),
            style: style,
            contentSize: size
        )
    }

    private func configuration(error: String) -> TextFieldRendererConfiguration {
        TextFieldRendererConfiguration(node: NativeUINode(
            id: 7,
            type: "firstlight_text_field",
            layout: nil,
            style: nil,
            props: GenericProps([
                "value": "person@example.com",
                "label": "Contact email",
                "placeholder": "you@example.com",
                "helper": "Used for appointment updates",
                "error": error,
                "required": true,
                "keyboard": "email",
                "content_type": "email",
                "leading_icon": "envelope",
                "clearable": true,
            ]),
            onPress: 0,
            onLongPress: 0,
            children: []
        ))
    }
}

private struct TextFieldSnapshotHost: View {
    let configuration: TextFieldRendererConfiguration
    @FocusState private var focused: Bool
    @State private var text = "person@example.com"
    @State private var revealed = false

    var body: some View {
        FirstlightTextFieldControl(
            configuration: configuration,
            text: $text,
            revealed: $revealed,
            isFocused: $focused,
            tokens: .fallback,
            onClear: { text = "" },
            onTrailingPress: {},
            onSubmit: {}
        )
        .padding(20)
        .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .topLeading)
        .background(Color(uiColor: .systemBackground))
    }
}

@MainActor
private final class TextFieldTraitHostingController<Content: View>: UIViewController {
    private let host: UIHostingController<Content>
    private let traits: UITraitCollection

    init(rootView: Content, style: UIUserInterfaceStyle, contentSize: UIContentSizeCategory) {
        host = UIHostingController(rootView: rootView)
        traits = UITraitCollection(traitsFrom: [
            UITraitCollection(userInterfaceStyle: style),
            UITraitCollection(preferredContentSizeCategory: contentSize),
        ])
        super.init(nibName: nil, bundle: nil)
    }

    @available(*, unavailable)
    required init?(coder: NSCoder) { fatalError("init(coder:) has not been implemented") }

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
        setOverrideTraitCollection(traits, forChild: host)
    }
}
