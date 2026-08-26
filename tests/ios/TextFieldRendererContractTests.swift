import SwiftUI
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class TextFieldRendererContractTests: XCTestCase {
    func testConfigurationDecodesNativeInputAndIconProps() {
        let configuration = TextFieldRendererConfiguration(node: makeTextFieldNode())

        XCTAssertEqual(configuration.value, "draft")
        XCTAssertEqual(configuration.keyboard, "email")
        XCTAssertEqual(configuration.contentType, "email")
        XCTAssertEqual(configuration.autocapitalize, "none")
        XCTAssertEqual(configuration.autocorrectPolicy, "disabled")
        XCTAssertEqual(configuration.submitLabel, "send")
        XCTAssertEqual(configuration.leadingIcon, "envelope")
        XCTAssertEqual(configuration.trailingIcon, "arrow.right")
        XCTAssertEqual(configuration.trailingAccessibilityLabel, "Send")
        XCTAssertEqual(configuration.clearA11yLabel, "Clear text")
        XCTAssertEqual(configuration.showPasswordA11yLabel, "Show password")
        XCTAssertEqual(configuration.hidePasswordA11yLabel, "Hide password")
        XCTAssertEqual(configuration.onPressCallback, 43)
    }

    func testFocusedAcknowledgementPreservesDraftAndDifferentPublicationWaits() {
        var state = TextFieldRendererState(node: makeTextFieldNode(value: "server"))
        _ = state.focusChanged(true)

        XCTAssertEqual(state.userChanged("local")?.wireName, "TEXT_CHANGE")
        XCTAssertEqual(state.draft, "local")

        state.serverPublished(tree(root: makeTextFieldNode(value: "local")))
        XCTAssertEqual(state.draft, "local")

        state.serverPublished(tree(root: makeTextFieldNode(value: "corrected")))
        XCTAssertEqual(state.draft, "local")
        XCTAssertEqual(state.pendingServerValue, "corrected")
    }

    func testBlurAndSubmitFlushBeforeSubmitting() {
        var state = TextFieldRendererState(node: makeTextFieldNode(syncMode: "blur"))
        _ = state.focusChanged(true)
        XCTAssertNil(state.userChanged("edited"))

        XCTAssertEqual(state.submit().map(\.wireName), ["TEXT_CHANGE", "SUBMIT"])
        XCTAssertEqual(state.draft, "edited")
    }

    func testClearCommitsImmediatelyAndRevealNeverPublishesText() {
        var clear = TextFieldRendererState(node: makeTextFieldNode(value: "query", clearable: true))
        _ = clear.focusChanged(true)
        let event = clear.clear()

        XCTAssertEqual(clear.draft, "")
        XCTAssertEqual(event, .change(callbackId: 41, nodeId: 7, value: ""))
        XCTAssertTrue(clear.isFocused)

        var reveal = TextFieldRendererState(node: makeTextFieldNode(secure: true, revealable: true))
        XCTAssertFalse(reveal.isRevealed)
        reveal.toggleReveal()
        XCTAssertTrue(reveal.isRevealed)
        XCTAssertEqual(reveal.draft, "draft")
    }

    func testDisabledAndReadOnlyFieldsRejectEditsAndClear() {
        var disabled = TextFieldRendererState(node: makeTextFieldNode(disabled: true, clearable: true))
        var readOnly = TextFieldRendererState(node: makeTextFieldNode(readOnly: true, clearable: true))

        XCTAssertNil(disabled.userChanged("changed"))
        XCTAssertNil(disabled.clear())
        XCTAssertNil(readOnly.userChanged("changed"))
        XCTAssertNil(readOnly.clear())
    }

    func testRendererCompilesAgainstNativePHPContractShims() {
        let renderer = TextFieldRenderer(
            node: makeTextFieldNode(),
            events: TextFieldRendererEvents { _ in }
        )

        XCTAssertNotNil(renderer.body)
    }

    private func makeTextFieldNode(
        value: String = "draft",
        syncMode: String = "live",
        secure: Bool = false,
        disabled: Bool = false,
        readOnly: Bool = false,
        clearable: Bool = false,
        revealable: Bool = false
    ) -> NativeUINode {
        NativeUINode(
            id: 7,
            type: "firstlight_text_field",
            layout: nil,
            style: nil,
            props: GenericProps([
                "value": value,
                "label": "Email",
                "placeholder": "you@example.com",
                "helper": "Supporting",
                "error": "",
                "keyboard": "email",
                "content_type": "email",
                "autocapitalize": "none",
                "autocorrect_policy": "disabled",
                "submit_label": "send",
                "leading_icon": "envelope",
                "trailing_icon": "arrow.right",
                "trailing_a11y_label": "Send",
                "sync_mode": syncMode,
                "debounce_ms": 300,
                "secure": secure,
                "disabled": disabled,
                "read_only": readOnly,
                "clearable": clearable,
                "revealable": revealable,
                "on_change": 41,
                "on_submit": 42,
            ]),
            onPress: 43,
            onLongPress: 0,
            children: []
        )
    }

    private func tree(root: NativeUINode) -> NativeUITree {
        NativeUITree(version: 1, callbackCount: 3, root: root)
    }
}
