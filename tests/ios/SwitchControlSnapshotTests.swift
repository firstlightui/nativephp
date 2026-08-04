import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class SwitchControlSnapshotTests: XCTestCase {
    func testSwitchStatesSnapshots() {
        let recordMode: SnapshotTestingConfiguration.Record = ProcessInfo.processInfo.environment[
            "FIRSTLIGHT_RECORD_SNAPSHOTS"
        ] == "1" ? .all : .never

        assertSnapshot(of: makeViewController(style: .light), as: .image(on: .iPhoneSe), named: "light", record: recordMode)
        assertSnapshot(of: makeViewController(style: .dark), as: .image(on: .iPhoneSe), named: "dark", record: recordMode)
        assertSnapshot(of: makeViewController(value: true, disabled: true), as: .image(on: .iPhoneSe), named: "disabled-on", record: recordMode)
        assertSnapshot(of: makeViewController(error: "Notifications are required for this account."), as: .image(on: .iPhoneSe), named: "error", record: recordMode)
        assertSnapshot(of: makeViewController(label: "Receive secure clinical updates and practice workflow notifications"), as: .image(on: .iPhoneSe), named: "long-label", record: recordMode)
        assertSnapshot(
            of: makeViewController(contentSize: .accessibilityExtraExtraExtraLarge),
            as: .image(on: .iPhoneSe),
            named: "accessibility-extra-extra-extra-large",
            record: recordMode
        )
    }

    func testAcceptedValueBindingProposesWithoutMutatingVisibleState() {
        var proposalCount = 0
        let control = FirstlightSwitchControl(
            value: false,
            label: "Notifications",
            supportingText: "Receive updates about new activity.",
            error: "",
            disabled: false,
            accessibilityLabel: "Receive notifications",
            accessibilityHint: "Controls notification delivery",
            tokens: .fallback,
            onProposal: { proposalCount += 1 }
        )

        XCTAssertFalse(control.acceptedValueBinding.wrappedValue)
        control.acceptedValueBinding.wrappedValue = true
        XCTAssertEqual(proposalCount, 1)
        XCTAssertFalse(control.acceptedValueBinding.wrappedValue)
    }

    func testErrorAccessibilityValuePreservesAcceptedStateAndValidationMessage() {
        XCTAssertNil(SwitchAccessibility.errorValue(value: false, error: ""))
        XCTAssertEqual(
            SwitchAccessibility.errorValue(
                value: false,
                error: "Notifications are required for this account."
            ),
            "Off. Error: Notifications are required for this account."
        )
        XCTAssertEqual(
            SwitchAccessibility.errorValue(
                value: true,
                error: "Notifications are required for this account."
            ),
            "On. Error: Notifications are required for this account."
        )
    }

    private func makeViewController(
        value: Bool = false,
        disabled: Bool = false,
        label: String = "Notifications",
        error: String = "",
        style: UIUserInterfaceStyle = .light,
        contentSize: UIContentSizeCategory = .large
    ) -> UIViewController {
        let view = FirstlightSwitchControl(
            value: value,
            label: label,
            supportingText: error.isEmpty ? "Receive updates about new activity." : error,
            error: error,
            disabled: disabled,
            accessibilityLabel: "",
            accessibilityHint: "Controls notification delivery",
            tokens: .fallback,
            onProposal: {}
        )
        .padding(20)
        .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .topLeading)
        .background(Color(uiColor: .systemBackground))

        return SwitchTraitHostingController(rootView: view, style: style, contentSize: contentSize)
    }
}

@MainActor
private final class SwitchTraitHostingController<Content: View>: UIViewController {
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
