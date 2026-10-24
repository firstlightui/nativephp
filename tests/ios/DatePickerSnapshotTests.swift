import SnapshotTesting
import SwiftUI
import UIKit
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

    func testPresentedPickerIsSizedToItsContentOnIPhone() {
        var heights: [String: CGFloat] = [:]
        for (name, scheme, size) in [
            ("light", ColorScheme.light, DynamicTypeSize.large),
            ("dark", .dark, .large),
            ("accessibility-extra-extra-extra-large", .light, .accessibility5),
        ] {
            var state = DatePickerRendererState(node: node())
            state.open(today: "2026-08-04")
            let host = UIHostingController(rootView: PresentedDatePickerContent(state: state)
                .environment(\.colorScheme, scheme)
                .environment(\.dynamicTypeSize, size))
            let fitted = settledSize(of: host)
            heights[name] = fitted.height

            // Rendered top-aligned on an iPhone 17 Pro-sized canvas: the space left below is the
            // screen a full-height sheet used to waste.
            let screen = UIHostingController(rootView: PresentedDatePickerContent(state: state)
                .frame(maxHeight: .infinity, alignment: .top)
                .background(Color(uiColor: .systemBackground))
                .environment(\.colorScheme, scheme)
                .environment(\.dynamicTypeSize, size))
            assertFirstlightSnapshot(
                of: screen,
                as: .image(on: ViewImageConfig(safeArea: .zero, size: CGSize(width: 402, height: 874), traits: UITraitCollection())),
                named: name
            )
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

/// The picker content exactly as the control presents it, without the trigger.
private struct PresentedDatePickerContent: View {
    @State var state: DatePickerRendererState

    var body: some View {
        FirstlightDatePickerControl(state: $state, tokens: .fallback, onConfirm: {})
            .pickerPresentation
            .background(Color(uiColor: .systemBackground))
    }
}
