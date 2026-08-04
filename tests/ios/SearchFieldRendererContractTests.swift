import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class SearchFieldRendererContractTests: XCTestCase {
    func testConfigurationDecodesFocusedSearchProps() {
        let configuration = SearchFieldRendererConfiguration(node: node())

        XCTAssertEqual(configuration.value, "cardiology")
        XCTAssertEqual(configuration.placeholder, "Search specialties")
        XCTAssertEqual(configuration.autocapitalize, "words")
        XCTAssertEqual(configuration.autocorrectPolicy, "disabled")
        XCTAssertEqual(configuration.accessibilityLabel, "Search specialties")
        XCTAssertEqual(configuration.onChangeCallback, 41)
        XCTAssertEqual(configuration.onSubmitCallback, 42)
    }

    func testFocusedAcknowledgementPreservesDraftAndCorrectionWaits() {
        var state = SearchFieldRendererState(node: node(value: "server"))
        _ = state.focusChanged(true)

        XCTAssertEqual(state.userChanged("local"), .change(callbackId: 41, nodeId: 7, value: "local"))
        state.serverPublished(tree(node(value: "local")))
        XCTAssertEqual(state.draft, "local")

        state.serverPublished(tree(node(value: "corrected")))
        XCTAssertEqual(state.draft, "local")
        XCTAssertEqual(state.pendingServerValue, "corrected")
    }

    func testBlurFlushesBeforeSubmitAndEmptyQueryStillSubmits() {
        var state = SearchFieldRendererState(node: node(value: "", syncMode: "blur"))
        _ = state.focusChanged(true)
        XCTAssertNil(state.userChanged("referral"))
        XCTAssertEqual(state.submit().map(\.wireName), ["TEXT_CHANGE", "SUBMIT"])

        var empty = SearchFieldRendererState(node: node(value: ""))
        XCTAssertEqual(empty.submit(), [.submit(callbackId: 42, nodeId: 7, value: "")])
    }

    func testClearCommitsImmediatelyAndRetainsFocus() {
        var state = SearchFieldRendererState(node: node(value: "query", syncMode: "debounce"))
        _ = state.focusChanged(true)

        XCTAssertEqual(state.clear(), .change(callbackId: 41, nodeId: 7, value: ""))
        XCTAssertEqual(state.draft, "")
        XCTAssertTrue(state.isFocused)
    }

    func testDisabledSearchRejectsEditingClearAndSubmit() {
        var state = SearchFieldRendererState(node: node(disabled: true))

        XCTAssertNil(state.userChanged("changed"))
        XCTAssertNil(state.clear())
        XCTAssertTrue(state.submit().isEmpty)
    }

    func testUIKitConfigurationUsesNativeSearchAndAccessibilitySemantics() {
        let field = UISearchTextField()
        configureSearchTextField(field, configuration: SearchFieldRendererConfiguration(node: node()))

        XCTAssertEqual(field.placeholder, "Search specialties")
        XCTAssertEqual(field.accessibilityLabel, "Search specialties")
        XCTAssertEqual(field.accessibilityHint, "Enter a specialty name")
        XCTAssertEqual(field.returnKeyType, .search)
        XCTAssertEqual(field.clearButtonMode, .whileEditing)
        XCTAssertEqual(field.autocapitalizationType, .words)
        XCTAssertEqual(field.autocorrectionType, .no)
        XCTAssertGreaterThanOrEqual(field.intrinsicContentSize.height, 36)
    }

    func testRendererCompilesAgainstNativePHPContractShims() {
        let renderer = SearchFieldRenderer(node: node(), events: SearchFieldRendererEvents { _ in })
        XCTAssertNotNil(renderer.body)
    }

    private func node(
        value: String = "cardiology",
        syncMode: String = "live",
        disabled: Bool = false
    ) -> NativeUINode {
        NativeUINode(
            id: 7,
            type: "firstlight_search_field",
            layout: nil,
            style: nil,
            props: GenericProps([
                "value": value,
                "placeholder": "Search specialties",
                "disabled": disabled,
                "autocapitalize": "words",
                "autocorrect_policy": "disabled",
                "sync_mode": syncMode,
                "debounce_ms": 300,
                "a11y_label": "Search specialties",
                "a11y_hint": "Enter a specialty name",
                "on_change": 41,
                "on_submit": 42,
            ]),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
    }

    private func tree(_ root: NativeUINode) -> NativeUITree {
        NativeUITree(version: 1, callbackCount: 2, root: root)
    }
}
