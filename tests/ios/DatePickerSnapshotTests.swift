import SwiftUI
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class DatePickerSnapshotTests: XCTestCase {
    func testConfigurationDecodesExplicitNullAndDisplayContext() {
        let configuration = DatePickerRendererConfiguration(node: node(hasValue: false, value: ""))

        XCTAssertFalse(configuration.hasValue)
        XCTAssertNil(configuration.acceptedValue)
        XCTAssertEqual(configuration.locale, "en-AU")
        XCTAssertEqual(configuration.timezone, "Australia/Sydney")
        XCTAssertEqual(configuration.confirmLabel, "Confirm")
        XCTAssertEqual(configuration.cancelLabel, "Cancel")
        XCTAssertEqual(configuration.accessibilityLabel, "Appointment date")
    }

    func testNullSeedIsTodayClampedToNearestInclusiveBound() {
        var below = DatePickerRendererState(node: node(hasValue: false, value: "", minimum: "2026-08-10"))
        var above = DatePickerRendererState(node: node(hasValue: false, value: "", maximum: "2026-08-01"))

        XCTAssertTrue(below.open(today: "2026-08-04"))
        XCTAssertEqual(below.draft, "2026-08-10")
        XCTAssertTrue(above.open(today: "2026-08-04"))
        XCTAssertEqual(above.draft, "2026-08-01")
    }

    func testConfirmEmitsWithoutOptimisticallyAccepting() {
        var state = DatePickerRendererState(node: node(value: "2026-08-04"))
        state.open(today: "2026-08-04")
        state.userSelected("2026-08-05")

        XCTAssertEqual(state.confirm(), .change(callbackId: 41, nodeId: 7, value: "2026-08-05"))
        XCTAssertEqual(state.configuration.acceptedValue, "2026-08-04")
        XCTAssertFalse(state.isPresented)

        XCTAssertTrue(state.open(today: "2026-08-06"))
        XCTAssertEqual(state.draft, "2026-08-04")
    }

    func testCancelAndAcceptedConfirmPublishNothing() {
        var state = DatePickerRendererState(node: node(value: "2026-08-04"))
        state.open()
        state.cancel()
        XCTAssertNil(state.confirm())

        state.open()
        XCTAssertNil(state.confirm())
    }

    func testDisabledAndCallbacklessTriggersAreInert() {
        var disabled = DatePickerRendererState(node: node(disabled: true))
        var callbackless = DatePickerRendererState(node: node(callback: 0))
        XCTAssertFalse(disabled.open())
        XCTAssertFalse(callbackless.open())
    }

    func testAcceptedPresentationChangesDismissAndDiscardDraft() {
        var state = DatePickerRendererState(node: node(value: "2026-08-04"))
        state.open()
        state.userSelected("2026-08-05")

        state.serverPublished(tree(root: node(value: "2026-08-06")))

        XCTAssertFalse(state.isPresented)
        XCTAssertNil(state.draft)
        XCTAssertEqual(state.configuration.acceptedValue, "2026-08-06")
    }

    func testCalendarMappingPreservesCanonicalWireDates() {
        for value in ["0001-01-01", "2024-02-29", "9999-12-31"] {
            let date = DatePickerCalendar.date(from: value, timezone: "Pacific/Auckland")
            XCTAssertEqual(DatePickerCalendar.canonical(from: date, timezone: "Pacific/Auckland"), value)
        }
    }

    func testRendererCompilesAgainstNativePHPContractShims() {
        let renderer = DatePickerRenderer(node: node(), events: DatePickerRendererEvents { _ in })
        XCTAssertNotNil(renderer.body)
    }

    private func node(
        hasValue: Bool = true,
        value: String = "2026-08-04",
        minimum: String = "0001-01-01",
        maximum: String = "9999-12-31",
        disabled: Bool = false,
        callback: Int = 41
    ) -> NativeUINode {
        NativeUINode(
            id: 7,
            type: "firstlight_date_picker",
            layout: nil,
            style: nil,
            props: GenericProps([
                "has_value": hasValue,
                "value": value,
                "min": minimum,
                "max": maximum,
                "label": "Appointment date",
                "placeholder": "Choose a date",
                "helper": "Local clinic date",
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
