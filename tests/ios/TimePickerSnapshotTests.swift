import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class TimePickerSnapshotTests: XCTestCase {
    func testConfigurationDecodesExplicitNullAndDisplayContext() {
        let configuration = TimePickerRendererConfiguration(node: node(hasValue: false, value: ""))

        XCTAssertFalse(configuration.hasValue)
        XCTAssertNil(configuration.acceptedValue)
        XCTAssertEqual(configuration.locale, "en-AU")
        XCTAssertEqual(configuration.timezone, "Australia/Sydney")
        XCTAssertEqual(configuration.confirmLabel, "Confirm")
        XCTAssertEqual(configuration.cancelLabel, "Cancel")
        XCTAssertEqual(configuration.accessibilityLabel, "Appointment time")
    }

    func testNullSeedUsesSuppliedCurrentMinute() {
        var state = TimePickerRendererState(node: node(hasValue: false, value: ""))

        XCTAssertTrue(state.open(currentTime: "07:05"))
        XCTAssertEqual(state.draft, "07:05")
    }

    func testConfirmEmitsWithoutOptimisticallyAccepting() {
        var state = TimePickerRendererState(node: node(value: "14:30"))
        state.open(currentTime: "07:05")
        state.userSelected("14:45")

        XCTAssertEqual(state.confirm(), .change(callbackId: 41, nodeId: 7, value: "14:45"))
        XCTAssertEqual(state.configuration.acceptedValue, "14:30")
        XCTAssertFalse(state.isPresented)

        XCTAssertTrue(state.open(currentTime: "07:06"))
        XCTAssertEqual(state.draft, "14:30")
    }

    func testCancelAndAcceptedConfirmPublishNothing() {
        var state = TimePickerRendererState(node: node(value: "14:30"))
        state.open()
        state.cancel()
        XCTAssertNil(state.confirm())

        state.open()
        XCTAssertNil(state.confirm())
    }

    func testDisabledAndCallbacklessTriggersAreInert() {
        var disabled = TimePickerRendererState(node: node(disabled: true))
        var callbackless = TimePickerRendererState(node: node(callback: 0))
        XCTAssertFalse(disabled.open())
        XCTAssertFalse(callbackless.open())
    }

    func testAcceptedPresentationChangesDismissAndDiscardDraft() {
        var state = TimePickerRendererState(node: node(value: "14:30"))
        state.open()
        state.userSelected("14:45")

        state.serverPublished(tree(root: node(value: "15:00")))

        XCTAssertFalse(state.isPresented)
        XCTAssertNil(state.draft)
        XCTAssertEqual(state.configuration.acceptedValue, "15:00")
    }

    func testClockMappingPreservesCanonicalWireTimes() {
        for value in ["00:00", "07:05", "14:30", "23:59"] {
            let date = TimePickerClock.date(from: value, timezone: "Pacific/Auckland")
            XCTAssertEqual(TimePickerClock.canonical(from: date, timezone: "Pacific/Auckland"), value)
        }
    }

    func testRendererCompilesAgainstNativePHPContractShims() {
        let renderer = TimePickerRenderer(node: node(), events: TimePickerRendererEvents { _ in })
        XCTAssertNotNil(renderer.body)
    }

    func testPresentedPickerIsSizedToItsContentOnIPhone() {
        var heights: [String: CGFloat] = [:]
        for (name, scheme, size) in [
            ("light", ColorScheme.light, DynamicTypeSize.large),
            ("dark", .dark, .large),
            ("accessibility-extra-extra-extra-large", .light, .accessibility5),
        ] {
            var state = TimePickerRendererState(node: node())
            state.open(currentTime: "14:30")
            let host = UIHostingController(rootView: PresentedTimePickerContent(state: state)
                .environment(\.colorScheme, scheme)
                .environment(\.dynamicTypeSize, size))
            let fitted = settledSize(of: host)
            heights[name] = fitted.height

            // The wheel is not snapshotted: UIPickerView's 3D layer transforms do not rasterise
            // offscreen, so a reference image would not show what the device shows.
        }

        // The iPhone sheet detent is the content height: well short of a full-height sheet at the
        // default size, and growing with Dynamic Type rather than clipping.
        XCTAssertLessThan(heights["light", default: .infinity], 874 * 0.7)
        XCTAssertGreaterThan(heights["accessibility-extra-extra-extra-large", default: 0], heights["light", default: .infinity])
    }

    /// UIKit-backed pickers grow after their first layout pass, so measure after layout settles,
    /// as the presented sheet's geometry observer does.
    private func settledSize(of host: UIViewController) -> CGSize {
        host.view.frame = CGRect(x: 0, y: 0, width: 402, height: 2000)
        host.view.layoutIfNeeded()
        RunLoop.main.run(until: Date().addingTimeInterval(0.3))
        host.view.layoutIfNeeded()
        return host.view.sizeThatFits( CGSize(width: 402, height: CGFloat.infinity))
    }

    private func node(
        hasValue: Bool = true,
        value: String = "14:30",
        disabled: Bool = false,
        callback: Int = 41
    ) -> NativeUINode {
        NativeUINode(
            id: 7,
            type: "firstlight_time_picker",
            layout: nil,
            style: nil,
            props: GenericProps([
                "has_value": hasValue,
                "value": value,
                "label": "Appointment time",
                "placeholder": "Choose a time",
                "helper": "Clinic local time",
                "required": true,
                "locale": "en-AU",
                "timezone": "Australia/Sydney",
                "on_change": callback,
                "disabled": disabled,
            ]),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
    }

    private func tree(root: NativeUINode) -> NativeUITree {
        NativeUITree(version: 1, callbackCount: 1, root: root)
    }
}

/// The picker content exactly as the control presents it, without the trigger.
private struct PresentedTimePickerContent: View {
    @State var state: TimePickerRendererState

    var body: some View {
        FirstlightTimePickerControl(state: $state, tokens: .fallback, onConfirm: {})
            .pickerPresentation
            .background(Color(uiColor: .systemBackground))
    }
}
