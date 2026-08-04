import SwiftUI
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
