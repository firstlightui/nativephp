import SnapshotTesting
import SwiftUI
import UIKit
import Vision
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class SwitchControlSnapshotTests: XCTestCase {
    func testSwitchStatesSnapshots() throws {
        if #available(iOS 26.0, *) {
            throw XCTSkip(
                "iOS 26 Liquid Glass switch thumbs require host-app screenshot coverage; "
                    + "framework layer snapshots omit _UILiquidLensView."
            )
        }

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

    func testErrorStateVisiblyRetainsTitle() throws {
        let image = renderLayer(of: makeViewController(error: "Notifications are required for this account."))
        let request = VNRecognizeTextRequest()
        request.recognitionLevel = .accurate

        let handler = VNImageRequestHandler(cgImage: try XCTUnwrap(image.cgImage))
        try handler.perform([request])

        let recognizedLines = try XCTUnwrap(request.results).compactMap { $0.topCandidates(1).first?.string }
        XCTAssertTrue(
            recognizedLines.contains("Notifications"),
            "Expected a distinct visible Notifications title, recognized: \(recognizedLines)"
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

    func testRenderedToggleExposesOneAccessibleControlWithExplicitSemantics() throws {
        let error = "Notifications are required for this account."
        let controller = makeViewController(error: error)
        let elements = accessibilityElements(in: controller)
        let labeled = elements.filter { $0.accessibilityLabel?.isEmpty == false }

        if #unavailable(iOS 26.0) {
            // iOS 18 does not publish SwiftUI's synthesized accessibility container through
            // UIKit's in-process container APIs. Keep this path rendered and public-API-only.
            let nativeToggle = try XCTUnwrap(uiSwitches(in: controller).single)
            let control = makeControl(error: error)

            XCTAssertFalse(nativeToggle.isOn)
            XCTAssertTrue(nativeToggle.isEnabled)
            XCTAssertEqual(control.accessibilityLabel, "Receive notifications")
            XCTAssertEqual(control.accessibilityHint, "Controls notification delivery")
            XCTAssertEqual(SwitchAccessibility.value(value: control.value, error: control.error), "Off. Error: \(error)")

            let disabledController = makeViewController(value: true, disabled: true)
            let disabledToggle = try XCTUnwrap(uiSwitches(in: disabledController).single)
            XCTAssertTrue(disabledToggle.isOn)
            XCTAssertFalse(disabledToggle.isEnabled)
            XCTAssertEqual(SwitchAccessibility.value(value: true, error: ""), "On")
            return
        }

        let toggle = try XCTUnwrap(
            labeled.single,
            "Expected one labeled accessibility element, found: \(accessibilitySummary(labeled))"
        )

        XCTAssertEqual(toggle.accessibilityLabel, "Receive notifications")
        XCTAssertEqual(toggle.accessibilityHint, "Controls notification delivery")
        XCTAssertEqual(toggle.accessibilityValue, "Off. Error: \(error)")
        XCTAssertFalse(toggle.accessibilityTraits.contains(.notEnabled))
        XCTAssertTrue(toggle.accessibilityTraits.contains(.button))
        XCTAssertTrue(toggle.accessibilityActivate())
        XCTAssertFalse(labeled.contains { $0 !== toggle && $0.accessibilityLabel == "Notifications" })
        XCTAssertFalse(labeled.contains { $0 !== toggle && $0.accessibilityLabel?.contains(error) == true })

        let disabledElements = accessibilityElements(in: makeViewController(value: true, disabled: true))
        let disabledToggle = try XCTUnwrap(disabledElements.filter { $0.accessibilityLabel == "Receive notifications" }.single)
        XCTAssertEqual(disabledToggle.accessibilityValue, "On")
        XCTAssertTrue(disabledToggle.accessibilityTraits.contains(.notEnabled))
    }

    private func makeViewController(
        value: Bool = false,
        disabled: Bool = false,
        label: String = "Notifications",
        error: String = "",
        style: UIUserInterfaceStyle = .light,
        contentSize: UIContentSizeCategory = .large
    ) -> UIViewController {
        let view = makeControl(
            value: value,
            label: label,
            error: error,
            disabled: disabled
        )
        .padding(20)
        .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .topLeading)
        .background(Color(uiColor: .systemBackground))

        return SwitchTraitHostingController(rootView: view, style: style, contentSize: contentSize)
    }

    private func makeControl(
        value: Bool = false,
        label: String = "Notifications",
        error: String = "",
        disabled: Bool = false
    ) -> FirstlightSwitchControl {
        FirstlightSwitchControl(
            value: value,
            label: label,
            supportingText: error.isEmpty ? "Receive updates about new activity." : error,
            error: error,
            disabled: disabled,
            accessibilityLabel: "Receive notifications",
            accessibilityHint: "Controls notification delivery",
            tokens: .fallback,
            onProposal: {}
        )
    }

    private func renderLayer(of controller: UIViewController) -> UIImage {
        withPreparedWindow(for: controller) { window in
            let format = UIGraphicsImageRendererFormat()
            format.scale = 2
            return UIGraphicsImageRenderer(bounds: window.bounds, format: format).image { context in
                window.layer.render(in: context.cgContext)
            }
        }
    }

    private func accessibilityElements(in controller: UIViewController) -> [NSObject] {
        withPreparedWindow(for: controller) { window in
            return collectAccessibilityElements(from: window)
        }
    }

    private func uiSwitches(in controller: UIViewController) -> [UISwitch] {
        withPreparedWindow(for: controller) { window in
            uiSwitches(in: window)
        }
    }

    private func uiSwitches(in view: UIView) -> [UISwitch] {
        let current = (view as? UISwitch).map { [$0] } ?? []
        return current + view.subviews.flatMap(uiSwitches)
    }

    private func withPreparedWindow<Result>(
        for controller: UIViewController,
        perform: (UIWindow) -> Result
    ) -> Result {
        let frame = CGRect(x: 0, y: 0, width: 320, height: 568)
        let window: UIWindow
        if let scene = UIApplication.shared.connectedScenes.compactMap({ $0 as? UIWindowScene }).first {
            window = UIWindow(windowScene: scene)
            window.frame = frame
        } else {
            window = UIWindow(frame: frame)
        }
        window.rootViewController = controller
        window.makeKeyAndVisible()
        controller.beginAppearanceTransition(true, animated: false)
        controller.endAppearanceTransition()
        controller.view.setNeedsLayout()
        controller.view.layoutIfNeeded()
        RunLoop.current.run(until: Date().addingTimeInterval(0.05))
        let result = perform(window)
        controller.beginAppearanceTransition(false, animated: false)
        controller.endAppearanceTransition()
        window.isHidden = true
        window.rootViewController = nil
        return result
    }

    private func collectAccessibilityElements(from object: NSObject) -> [NSObject] {
        if object.isAccessibilityElement {
            return [object]
        }

        if let explicitElements = object.accessibilityElements, !explicitElements.isEmpty {
            return explicitElements.flatMap { element in
                guard let child = element as? NSObject else { return [NSObject]() }
                return collectAccessibilityElements(from: child)
            }
        }

        let count = object.accessibilityElementCount()
        if count != NSNotFound, count > 0 {
            return (0..<count).flatMap { index in
                guard let child = object.accessibilityElement(at: index) as? NSObject else {
                    return [NSObject]()
                }
                return collectAccessibilityElements(from: child)
            }
        }

        if let view = object as? UIView {
            return view.subviews.flatMap(collectAccessibilityElements)
        }

        return []
    }

    private func accessibilitySummary(_ elements: [NSObject]) -> [String] {
        elements.map {
            "\(type(of: $0)): label=\($0.accessibilityLabel ?? "nil"), "
                + "value=\($0.accessibilityValue ?? "nil")"
        }
    }
}

private extension Array {
    var single: Element? {
        count == 1 ? first : nil
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
