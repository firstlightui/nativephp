import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

/// Drives the production masked renderer's UITextField the way UIKit does for keyboard and paste
/// input: each replacement goes to the field's delegate, and UIKit applies it only when allowed.
@MainActor
final class TextFieldMaskEditingTests: XCTestCase {
    private var sent: [TextFieldRendererEvent] = []

    func testTypingDeletingAndPastingThroughTheNativeField() throws {
        let field = try hostMaskedField(mask: "#### #### #### ####")
        XCTAssertTrue(field.becomeFirstResponder())

        type("4111", in: field)
        type("1", in: field)
        type("x", in: field)
        XCTAssertEqual(field.text, "4111 1")
        XCTAssertEqual(caret(in: field), 6)
        XCTAssertEqual(lastValue, "41111")

        deleteBackward(in: field)
        XCTAssertEqual(field.text, "4111")
        XCTAssertEqual(lastValue, "4111")

        type("12", in: field)
        XCTAssertEqual(field.text, "4111 12")

        // Caret just after the space: backspace deletes the digit before the literal.
        place(caret: 5, in: field)
        deleteBackward(in: field)
        XCTAssertEqual(field.text, "4111 2")
        XCTAssertEqual(caret(in: field), 3)
        XCTAssertEqual(lastValue, "41112")

        // Insert in the middle; the caret stays after the typed digit.
        type("9", in: field)
        XCTAssertEqual(field.text, "4119 12")
        XCTAssertEqual(caret(in: field), 4)

        field.selectedTextRange = field.textRange(from: field.beginningOfDocument, to: field.endOfDocument)
        type("5500-0000-0000-0004-9999", in: field)
        XCTAssertEqual(field.text, "5500 0000 0000 0004")
        XCTAssertEqual(lastValue, "5500000000000004")

        type("1", in: field)
        XCTAssertEqual(field.text, "5500 0000 0000 0004", "a full mask ignores further input")

        pump()
        XCTAssertTrue(field.isFirstResponder, "formatting never moves focus")
        XCTAssertTrue(sent.allSatisfy { if case let .change(_, _, value) = $0 { !value.contains(" ") } else { true } })
    }

    func testNumericDoneCommitsPendingMaskedInputSubmitsAndDismisses() throws {
        let field = try hostMaskedField(mask: "#### ####", syncMode: "blur", doneLabel: "Terminé")
        XCTAssertTrue(field.becomeFirstResponder())
        type("12345", in: field)
        XCTAssertTrue(sent.isEmpty)

        let toolbar = try XCTUnwrap(field.inputAccessoryView as? UIToolbar)
        let done = try XCTUnwrap(toolbar.items?.last)
        XCTAssertEqual(done.title, "Terminé")
        let action = try XCTUnwrap(done.action)
        // As in FocusedFieldFlushTests, this logic-test host has no application
        // to route UIKit actions. Invoke the accessory's actual registered target.
        let target = try XCTUnwrap(done.target as? NSObject)
        XCTAssertTrue(target.responds(to: action))
        _ = target.perform(action, with: done)
        pump()

        XCTAssertEqual(sent, [
            .change(callbackId: 41, nodeId: 7, value: "12345"),
            .submit(callbackId: 42, nodeId: 7, value: "12345"),
        ])
        XCTAssertFalse(field.isFirstResponder)
        XCTAssertNil(field.inputAccessoryView, "A resigned field must not advertise an accessory to the next keyboard session")

        XCTAssertTrue(field.becomeFirstResponder())
        XCTAssertEqual((field.inputAccessoryView as? UIToolbar)?.items?.last?.title, "Terminé")
    }

    func testMaskedInputHasA44PointTargetWithoutAddingAnAccessoryToTextKeyboards() throws {
        let field = try hostMaskedField(mask: "AA-####", keyboard: "text")
        XCTAssertGreaterThanOrEqual(field.bounds.height, 44)
        XCTAssertNil(field.inputAccessoryView)
    }

    private var lastValue: String? {
        sent.reversed().lazy.compactMap { if case let .change(_, _, value) = $0 { value } else { nil } }.first
    }

    private func hostMaskedField(
        mask: String,
        keyboard: String = "number",
        syncMode: String = "live",
        doneLabel: String = "Done"
    ) throws -> UITextField {
        let node = NativeUINode(
            id: 7,
            type: "firstlight.text-field",
            layout: nil,
            style: nil,
            props: GenericProps([
                "label": "Card number", "mask": mask, "keyboard": keyboard,
                "sync_mode": syncMode, "done_label": doneLabel, "on_change": 41, "on_submit": 42,
            ]),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
        let renderer = TextFieldRenderer(
            node: node,
            events: TextFieldRendererEvents { [weak self] event in self?.sent.append(event) }
        )
        let host = UIHostingController(rootView: renderer)
        let scene = UIApplication.shared.connectedScenes.compactMap { $0 as? UIWindowScene }.first
        let window = scene.map(UIWindow.init(windowScene:)) ?? UIWindow()
        window.frame = CGRect(x: 0, y: 0, width: 390, height: 300)
        window.rootViewController = host
        window.makeKeyAndVisible()
        addTeardownBlock { window.isHidden = true }
        pump()
        return try XCTUnwrap(Self.textField(in: host.view))
    }

    private static func textField(in view: UIView) -> UITextField? {
        if let field = view as? UITextField { return field }
        for subview in view.subviews { if let field = textField(in: subview) { return field } }
        return nil
    }

    private func type(_ text: String, in field: UITextField) {
        replaceSelection(in: field, with: text)
    }

    private func deleteBackward(in field: UITextField) {
        guard let selection = field.selectedTextRange else { return }
        if selection.isEmpty, let start = field.position(from: selection.start, offset: -1) {
            field.selectedTextRange = field.textRange(from: start, to: selection.start)
        }
        replaceSelection(in: field, with: "")
    }

    private func replaceSelection(in field: UITextField, with text: String) {
        let selection = field.selectedTextRange!
        let location = field.offset(from: field.beginningOfDocument, to: selection.start)
        let length = field.offset(from: selection.start, to: selection.end)
        let range = NSRange(location: location, length: length)
        guard field.delegate?.textField?(field, shouldChangeCharactersIn: range, replacementString: text) ?? true else { return }
        field.text = ((field.text ?? "") as NSString).replacingCharacters(in: range, with: text)
        place(caret: location + (text as NSString).length, in: field)
        field.sendActions(for: .editingChanged)
    }

    private func caret(in field: UITextField) -> Int? {
        field.selectedTextRange.map { field.offset(from: field.beginningOfDocument, to: $0.start) }
    }

    private func place(caret: Int, in field: UITextField) {
        let position = field.position(from: field.beginningOfDocument, offset: caret)!
        field.selectedTextRange = field.textRange(from: position, to: position)
    }

    private func pump() {
        RunLoop.main.run(until: Date().addingTimeInterval(0.05))
    }
}
