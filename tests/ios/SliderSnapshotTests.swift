import XCTest

@testable import FirstlightIOSControls

@MainActor
final class SliderSnapshotTests: XCTestCase {
    func testConfigurationDecodesFractionalFloatPropsAndMetadata() {
        let configuration = SliderRendererConfiguration(node: makeNode())

        XCTAssertEqual(configuration.value, 0.25)
        XCTAssertEqual(configuration.minimum, -1.5)
        XCTAssertEqual(configuration.maximum, 1.5)
        XCTAssertEqual(configuration.step, 0.25)
        XCTAssertEqual(configuration.intervalCount, 12)
        XCTAssertEqual(configuration.accessibilityLabel, "Medication dose")
        XCTAssertEqual(configuration.accessibilityValue, "0.25 milligrams")
    }

    func testLiveChangesSnapToTheGridAndPublishFloatEvents() {
        var state = SliderRendererState(configuration: configuration())

        XCTAssertTrue(state.beginEditing())
        XCTAssertEqual(
            state.userChanged(0.34),
            .change(callbackId: 41, nodeId: 7, value: 0.25)
        )
        XCTAssertEqual(state.draft, 0.25)
        XCTAssertNil(state.userChanged(0.26))
        XCTAssertNil(state.finishEditing())
    }

    func testBlurAndDebounceKeepDraftsLocalUntilTheirPolicyPublishes() {
        var blur = SliderRendererState(configuration: configuration(syncMode: "blur"))
        XCTAssertNil(blur.userChanged(0.74))
        XCTAssertEqual(
            blur.finishEditing(),
            .change(callbackId: 41, nodeId: 7, value: 0.75)
        )

        var debounce = SliderRendererState(configuration: configuration(syncMode: "debounce"))
        XCTAssertNil(debounce.userChanged(-0.74))
        XCTAssertEqual(
            debounce.flush(),
            .change(callbackId: 41, nodeId: 7, value: -0.75)
        )
        XCTAssertNil(debounce.flush())
        XCTAssertNil(debounce.finishEditing())
    }

    func testEveryServerPublicationIsAuthoritativeIncludingRejectedIdenticalValues() {
        let accepted = configuration(value: 0)
        var state = SliderRendererState(configuration: accepted)
        XCTAssertEqual(
            state.userChanged(0.75),
            .change(callbackId: 41, nodeId: 7, value: 0.75)
        )
        XCTAssertEqual(state.draft, 0.75)

        state.serverPublished(accepted)

        XCTAssertEqual(state.draft, 0)
        XCTAssertEqual(state.lastEmitted, 0)
        XCTAssertFalse(state.isEditing)
        XCTAssertNil(state.flush())
    }

    func testDisabledAndCallbacklessConfigurationsRejectNativeEditing() {
        var disabled = SliderRendererState(configuration: configuration(disabled: true))
        var callbackless = SliderRendererState(configuration: configuration(onChangeCallback: 0))

        XCTAssertFalse(disabled.beginEditing())
        XCTAssertNil(disabled.userChanged(1))
        XCTAssertNil(callbackless.userChanged(1))
        XCTAssertEqual(disabled.draft, 0)
        XCTAssertEqual(callbackless.draft, 0)
    }

    private func configuration(
        value: Float = 0,
        syncMode: String = "live",
        disabled: Bool = false,
        onChangeCallback: Int = 41
    ) -> SliderRendererConfiguration {
        SliderRendererConfiguration(
            nodeID: 7,
            value: value,
            minimum: -1.5,
            maximum: 1.5,
            step: 0.25,
            intervalCount: 12,
            label: "Dose",
            helper: "Choose a dose",
            error: "",
            disabled: disabled,
            syncMode: syncMode,
            debounceMilliseconds: 300,
            accessibilityLabel: "Medication dose",
            accessibilityHint: "Swipe to adjust",
            accessibilityValue: "",
            onChangeCallback: onChangeCallback
        )
    }

    private func makeNode() -> NativeUINode {
        NativeUINode(
            id: 7,
            type: "firstlight.slider",
            layout: nil,
            style: nil,
            props: GenericProps([
                "value": Float(0.25),
                "min": Float(-1.5),
                "max": Float(1.5),
                "step": Float(0.25),
                "interval_count": 12,
                "label": "Dose",
                "helper": "Choose a dose",
                "error": "",
                "disabled": false,
                "sync_mode": "live",
                "debounce_ms": 300,
                "a11y_label": "Medication dose",
                "a11y_hint": "Swipe to adjust",
                "a11y_value": "0.25 milligrams",
                "on_change": 41,
            ]),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
    }
}
