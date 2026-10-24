import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

/// A Button press goes through `KeyboardFocusPolicy.dispatchPress`, which flushes the focused
/// field first. These tests focus and type into the real renderers, then press.
@MainActor
final class FocusedFieldFlushTests: XCTestCase {
    private var window: UIWindow?
    private var log: [String] = []

    override func tearDown() {
        window?.isHidden = true
        window = nil
        log = []
        super.tearDown()
    }

    func testTextFieldBlurEditReachesPHPBeforePress() throws {
        let field = TextFieldRenderer(node: node("firstlight_text_field", syncMode: "blur"), events: .init { [self] event in
            if case let .change(_, _, value) = event { log.append("change:\(value)") }
        })
        try assertPressSeesTypedText(in: field)
    }

    func testTextAreaBlurEditReachesPHPBeforePress() throws {
        let area = TextAreaRenderer(node: node("firstlight_text_area", syncMode: "blur"), events: .init { [self] event in
            log.append("change:\(event.value)")
        })
        try assertPressSeesTypedText(in: area)
    }

    func testCodeEditorBlurEditReachesPHPBeforePress() throws {
        let editor = CodeEditorRenderer(node: node("firstlight_code_editor", syncMode: "blur"), events: .init { [self] event in
            log.append("change:\(event.value)")
        })
        try assertPressSeesTypedText(in: editor)
    }

    func testSearchFieldPendingDebounceReachesPHPBeforePress() throws {
        let search = SearchFieldRenderer(node: node("firstlight_search_field", syncMode: "debounce", debounce: 60_000), events: .init { [self] event in
            if case let .change(_, _, value) = event { log.append("change:\(value)") }
        })
        try assertPressSeesTypedText(in: search)
    }

    func testPressFlushesTheFieldThatTookFocusOver() throws {
        let events = TextFieldRendererEvents { [self] event in
            if case let .change(callbackId, _, value) = event { log.append("change\(callbackId):\(value)") }
        }
        let inputs = try host(VStack {
            TextFieldRenderer(node: node("firstlight_text_field", id: 1, syncMode: "blur", callback: 1), events: events)
            TextFieldRenderer(node: node("firstlight_text_field", id: 2, syncMode: "blur", callback: 2), events: events)
        })
        XCTAssertEqual(inputs.count, 2)

        try type("first", into: inputs[0])
        try type("second", into: inputs[1])
        press()

        XCTAssertEqual(log, ["change1:first", "change2:second", "press"])
    }

    private func assertPressSeesTypedText(in view: some View, file: StaticString = #filePath, line: UInt = #line) throws {
        let input = try XCTUnwrap(host(view).first, file: file, line: line)
        try type("typed", into: input)
        XCTAssertEqual(log, [], "the edit must still be pending before the press", file: file, line: line)

        press()
        XCTAssertEqual(log, ["change:typed", "press"], file: file, line: line)

        _ = input.resignFirstResponder()
        spin()
        XCTAssertEqual(log, ["change:typed", "press"], "blur after the press must not resend the change", file: file, line: line)
    }

    private func host(_ view: some View) throws -> [UIView] {
        let scene = UIApplication.shared.connectedScenes.compactMap { $0 as? UIWindowScene }.first
        let window = scene.map(UIWindow.init(windowScene:)) ?? UIWindow()
        window.frame = CGRect(x: 0, y: 0, width: 390, height: 600)
        window.rootViewController = UIHostingController(rootView: view.padding(16))
        window.makeKeyAndVisible()
        self.window = window
        spin()

        var inputs: [UIView] = []
        func visit(_ view: UIView) {
            if (view as? UITextField)?.isEnabled == true || (view as? UITextView)?.isEditable == true {
                inputs.append(view)
                return
            }
            view.subviews.forEach(visit)
        }
        visit(window)
        return inputs.sorted { $0.convert(CGPoint.zero, to: nil).y < $1.convert(CGPoint.zero, to: nil).y }
    }

    private func type(_ text: String, into input: UIView) throws {
        XCTAssertTrue(input.becomeFirstResponder())
        spin()
        try XCTUnwrap(input as? UIKeyInput).insertText(text)
        // Keyboard typing fires editingChanged; programmatic insertText does not, and the test
        // host has no application to route sendActions, so call the field's targets directly.
        if let field = input as? UITextField {
            for target in field.allTargets {
                for action in field.actions(forTarget: target, forControlEvent: .editingChanged) ?? [] {
                    _ = (target as NSObject).perform(NSSelectorFromString(action), with: field)
                }
            }
        }
        spin()
    }

    private func press() {
        KeyboardFocusPolicy.dispatchPress { [self] in log.append("press") }
    }

    private func spin() {
        RunLoop.main.run(until: Date().addingTimeInterval(0.2))
    }

    private func node(
        _ type: String,
        id: Int = 7,
        syncMode: String,
        debounce: Int = 300,
        callback: Int = 41
    ) -> NativeUINode {
        NativeUINode(
            id: id,
            type: type,
            layout: nil,
            style: nil,
            props: GenericProps([
                "value": "",
                "label": "Field \(id)",
                "sync_mode": syncMode,
                "debounce_ms": debounce,
                "on_change": callback,
            ]),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
    }
}
