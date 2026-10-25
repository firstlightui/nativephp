import SwiftUI
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class TextFieldRendererContractTests: XCTestCase {
    func testAccessibilityNameFallsBackToVisibleLabelAndExplicitNameWins() {
        XCTAssertEqual(TextFieldRendererConfiguration(node: makeTextFieldNode()).accessibilityLabel, "Email")
        let configuration = TextFieldRendererConfiguration(node: makeTextFieldNode(
            accessibilityLabel: "Account email", accessibilityHint: "Used to sign in"
        ))
        XCTAssertEqual(configuration.accessibilityLabel, "Account email")
        XCTAssertEqual(configuration.accessibilityHint, "Used to sign in")
    }

    func testBlurDoesNotPublishUntilFocusLossAndDoesNotDuplicateFlush() {
        var state = TextFieldRendererState(node: makeTextFieldNode(syncMode: "blur"))
        _ = state.focusChanged(true)
        XCTAssertNil(state.userChanged("latest"))
        XCTAssertEqual(state.lastCommitted, "draft")
        XCTAssertEqual(state.focusChanged(false), .change(callbackId: 41, nodeId: 7, value: "latest"))
        XCTAssertNil(state.flush())
    }

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

    func testMaskedFieldPublishesOnlySlotCharactersAndKeepsLiteralsLocal() {
        var state = TextFieldRendererState(node: makeTextFieldNode(value: "", clearable: true, mask: "#### ####"))
        _ = state.focusChanged(true)

        XCTAssertEqual(state.maskedEdit(range: 0..<0, with: "4111").event, .change(callbackId: 41, nodeId: 7, value: "4111"))
        let typed = state.maskedEdit(range: 4..<4, with: "2")
        XCTAssertEqual(typed.event, .change(callbackId: 41, nodeId: 7, value: "41112"))
        XCTAssertEqual(state.display, "4111 2")
        XCTAssertEqual(typed.caret, 6)

        let rejected = state.maskedEdit(range: 6..<6, with: "x")
        XCTAssertNil(rejected.event, "a character that fits no slot is dropped without an event")
        XCTAssertEqual(state.display, "4111 2")
        XCTAssertEqual(rejected.caret, 6)

        XCTAssertNil(state.maskedTextReplaced("4111 2a"), "committed text that conforms to the draft is not a change")
        XCTAssertEqual(state.display, "4111 2")

        XCTAssertEqual(state.clear(), .change(callbackId: 41, nodeId: 7, value: ""))
        XCTAssertEqual(state.display, "")
    }

    func testMaskedBlurPublishesTheRawValueOnFocusLoss() {
        var state = TextFieldRendererState(node: makeTextFieldNode(value: "", syncMode: "blur", mask: "(###) ###-####"))
        _ = state.focusChanged(true)

        XCTAssertNil(state.maskedEdit(range: 0..<0, with: "(415) 555-0123").event)
        XCTAssertEqual(state.display, "(415) 555-0123")
        XCTAssertEqual(state.focusChanged(false), .change(callbackId: 41, nodeId: 7, value: "4155550123"))
    }

    func testValueThatDoesNotFitTheMaskIsShownConformedWithoutAnEvent() {
        var state = TextFieldRendererState(node: makeTextFieldNode(value: "4111-1111-99", syncMode: "blur", mask: "#### ####"))

        XCTAssertEqual(state.draft, "41111111")
        XCTAssertEqual(state.display, "4111 1111")
        _ = state.focusChanged(true)
        XCTAssertNil(state.focusChanged(false), "conforming a programmatic value is not a user change")

        state.serverPublished(tree(root: makeTextFieldNode(value: "1234 56", mask: "#### ####")))
        XCTAssertEqual(state.draft, "123456")
        XCTAssertEqual(state.display, "1234 56")
        XCTAssertNil(state.flush())
    }

    func testFocusedMaskedAcknowledgementComparesConformedValues() {
        var state = TextFieldRendererState(node: makeTextFieldNode(value: "", mask: "#### ####"))
        _ = state.focusChanged(true)
        _ = state.maskedEdit(range: 0..<0, with: "41112")

        state.serverPublished(tree(root: makeTextFieldNode(value: "4111 2", mask: "#### ####")))
        XCTAssertNil(state.pendingServerValue)
        XCTAssertEqual(state.display, "4111 2")
    }

    func testReadOnlyMaskedFieldRestoresTheFormattedDisplay() {
        var state = TextFieldRendererState(node: makeTextFieldNode(value: "41111111", readOnly: true, mask: "#### ####"))

        XCTAssertNil(state.maskedEdit(range: 9..<9, with: "2").event)
        XCTAssertEqual(state.draft, "41111111")
        XCTAssertEqual(state.display, "4111 1111")
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
        revealable: Bool = false,
        mask: String = "",
        accessibilityLabel: String = "",
        accessibilityHint: String = ""
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
                "a11y_label": accessibilityLabel,
                "a11y_hint": accessibilityHint,
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
                "mask": mask,
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
