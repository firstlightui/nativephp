import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class CodeEditorSnapshotTests: XCTestCase {
    private let record: SnapshotTestingConfiguration.Record = ProcessInfo.processInfo.environment[
        "FIRSTLIGHT_RECORD_SNAPSHOTS"
    ] == "1" ? .all : .never

    func testStateSnapshots() {
        let states: [(name: String, props: [String: Any])] = [
            ("empty", ["value": "", "helper": "One KEY=value per line."]),
            ("plain", ["language": "plain", "value": Self.plain]),
            ("json", ["language": "json", "value": Self.json]),
            ("yaml", ["language": "yaml", "value": Self.yaml]),
            ("env", ["language": "env", "value": Self.env, "helper": "One KEY=value per line."]),
            ("error", ["language": "json", "value": Self.json, "error": "The payload must include an id."]),
            ("disabled", ["language": "env", "value": Self.env, "disabled": true]),
            ("read-only", ["language": "env", "value": Self.env, "read_only": true]),
            ("no-line-numbers", ["language": "yaml", "value": Self.yaml, "line_numbers": false]),
            ("no-wrap", ["language": "plain", "value": Self.plain, "wrap": false]),
        ]
        for state in states {
            assertSnapshot(
                of: controller(props: state.props),
                as: .image(on: .iPhoneSe),
                named: state.name,
                record: record
            )
        }
    }

    func testLightDarkAndAccessibilitySnapshots() {
        let props: [String: Any] = ["language": "json", "value": Self.json, "helper": "Webhook body sent to Forge."]
        assertSnapshot(of: controller(props: props, style: .light), as: .image(on: .iPhoneSe), named: "light", record: record)
        assertSnapshot(of: controller(props: props, style: .dark), as: .image(on: .iPhoneSe), named: "dark", record: record)
        assertSnapshot(
            of: controller(
                props: props.merging(["min_lines": 3, "max_lines": 4]) { _, new in new },
                contentSize: .accessibilityExtraExtraExtraLarge
            ),
            as: .image(on: .iPhoneSe),
            named: "accessibility-extra-extra-extra-large",
            record: record
        )
    }

    func testKeyRowSnapshot() {
        let row = CodeEditorKeyRow(language: .json, labels: .english)
        row.setUndoState(canUndo: true, canRedo: false)
        if #available(iOS 26.0, *) {
            XCTAssertTrue(row.backgroundView.effect is UIGlassEffect)
        } else {
            XCTAssertTrue(row.backgroundView.effect is UIBlurEffect)
        }
        // System material renders black off screen, so the snapshot covers the keys only.
        row.backgroundView.isHidden = true
        let container = UIView(frame: CGRect(x: 0, y: 0, width: 375, height: 60))
        container.backgroundColor = .systemBackground
        row.frame = container.bounds
        container.addSubview(row)
        container.layoutIfNeeded()
        assertSnapshot(of: container, as: .image, named: "json", record: record)
    }

    private static let plain = """
        Release notes for the deploy pipeline, kept short so reviewers can scan them quickly.
          - build
        \t- test with a tab
        """

    private static let json = """
        {
          "id": "deploy-42",
          "tags": ["api", "web"],
          "enabled": true
        }
        """

    private static let yaml = """
        services:
          web:
            image: nginx # pinned
            ports:
              - "80:80"
        """

    private static let env = """
        APP_ENV=local
        APP_DEBUG=true
        # Database
        DB_HOST=127.0.0.1
        """

    private func controller(
        props: [String: Any],
        style: UIUserInterfaceStyle = .light,
        contentSize: UIContentSizeCategory = .large
    ) -> UIViewController {
        var merged: [String: Any] = ["label": "Webhook payload", "required": true, "min_lines": 4, "max_lines": 8]
        merged.merge(props) { _, new in new }
        let node = NativeUINode(
            id: 7,
            type: "firstlight.code-editor",
            layout: nil,
            style: nil,
            props: GenericProps(merged),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
        let root = CodeEditorSnapshotHost(configuration: CodeEditorRendererConfiguration(node: node))
            .padding(20)
            .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .topLeading)
            .background(Color(uiColor: .systemBackground))
        let controller = UIHostingController(rootView: root)
        controller.overrideUserInterfaceStyle = style
        controller.view.frame = CGRect(x: 0, y: 0, width: 320, height: 568)
        controller.traitOverrides.preferredContentSizeCategory = contentSize
        return controller
    }
}

private struct CodeEditorSnapshotHost: View {
    let configuration: CodeEditorRendererConfiguration
    @State private var text: String

    init(configuration: CodeEditorRendererConfiguration) {
        self.configuration = configuration
        _text = State(initialValue: configuration.value)
    }

    var body: some View {
        FirstlightCodeEditorField(
            configuration: configuration,
            text: $text,
            replacementRevision: 0,
            onFocusChanged: { _ in },
            tokens: .fallback
        )
    }
}
