import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class CodeEditorRendererTests: XCTestCase {
    // MARK: Decoding

    func testConfigurationDecodesEveryWireProp() {
        let configuration = CodeEditorRendererConfiguration(node: makeNode([
            "value": "A=1",
            "label": "Environment",
            "helper": "One KEY=value per line.",
            "error": "APP_KEY is missing.",
            "required": true,
            "disabled": true,
            "read_only": true,
            "language": "env",
            "line_numbers": false,
            "wrap": false,
            "indent": 4,
            "follow": true,
            "min_lines": 8,
            "max_lines": 24,
            "sync_mode": "debounce",
            "debounce_ms": 500,
            "a11y_label": "Environment file",
            "a11y_hint": "Edit environment variables",
            "on_change": 41,
        ]))

        XCTAssertEqual(configuration.nodeID, 7)
        XCTAssertEqual(configuration.value, "A=1")
        XCTAssertEqual(configuration.label, "Environment")
        XCTAssertEqual(configuration.helper, "One KEY=value per line.")
        XCTAssertEqual(configuration.error, "APP_KEY is missing.")
        XCTAssertTrue(configuration.required)
        XCTAssertTrue(configuration.disabled)
        XCTAssertTrue(configuration.readOnly)
        XCTAssertEqual(configuration.language, .env)
        XCTAssertFalse(configuration.lineNumbers)
        XCTAssertFalse(configuration.wrap)
        XCTAssertEqual(configuration.indent, 4)
        XCTAssertTrue(configuration.follow)
        XCTAssertEqual(configuration.minLines, 8)
        XCTAssertEqual(configuration.maxLines, 24)
        XCTAssertEqual(configuration.syncMode, "debounce")
        XCTAssertEqual(configuration.debounceMilliseconds, 500)
        XCTAssertEqual(configuration.accessibilityLabel, "Environment file")
        XCTAssertEqual(configuration.accessibilityHint, "Edit environment variables")
        XCTAssertEqual(configuration.onChangeCallback, 41)
    }

    func testConfigurationDefaultsMatchTheWireContract() {
        let configuration = CodeEditorRendererConfiguration(node: makeNode(["label": "Payload"]))

        XCTAssertEqual(configuration.value, "")
        XCTAssertEqual(configuration.language, .plain)
        XCTAssertTrue(configuration.lineNumbers)
        XCTAssertTrue(configuration.wrap)
        XCTAssertEqual(configuration.indent, 2)
        XCTAssertFalse(configuration.follow)
        XCTAssertEqual(configuration.minLines, 6)
        XCTAssertEqual(configuration.maxLines, 20)
        XCTAssertEqual(configuration.syncMode, "live")
        XCTAssertEqual(configuration.debounceMilliseconds, 300)
        XCTAssertEqual(configuration.accessibilityLabel, "Payload")
        XCTAssertEqual(configuration.onChangeCallback, 0)
        XCTAssertTrue(configuration.editable)
    }

    // MARK: Text behaviour

    func testFontIsMonospacedAndScalesWithDynamicType() {
        let regular = CodeEditorTypography.font(
            compatibleWith: UITraitCollection(preferredContentSizeCategory: .large)
        )
        let largest = CodeEditorTypography.font(
            compatibleWith: UITraitCollection(preferredContentSizeCategory: .accessibilityExtraExtraExtraLarge)
        )

        XCTAssertTrue(regular.fontDescriptor.symbolicTraits.contains(.traitMonoSpace))
        XCTAssertTrue(largest.fontDescriptor.symbolicTraits.contains(.traitMonoSpace))
        XCTAssertGreaterThan(largest.pointSize, regular.pointSize)
    }

    func testCorrectionSpellingAndSmartPunctuationAreOff() {
        let textView = makeEditor().textView

        XCTAssertEqual(textView.autocorrectionType, .no)
        XCTAssertEqual(textView.autocapitalizationType, .none)
        XCTAssertEqual(textView.spellCheckingType, .no)
        XCTAssertEqual(textView.smartQuotesType, .no)
        XCTAssertEqual(textView.smartDashesType, .no)
        XCTAssertEqual(textView.smartInsertDeleteType, .no)
        XCTAssertEqual(textView.inlinePredictionType, .no)
        XCTAssertNotNil(textView.textLayoutManager, "Code Editor must use TextKit 2")
    }

    func testExistingTabsDisplayAtIndentWidth() {
        let editor = makeEditor(["indent": 4, "value": "\tkey"])
        let paragraph = editor.textView.typingAttributes[.paragraphStyle] as? NSParagraphStyle
        let space = (" " as NSString).size(withAttributes: [.font: editor.textView.font!]).width

        XCTAssertEqual(paragraph?.defaultTabInterval ?? 0, space * 4, accuracy: 0.01)
        XCTAssertEqual(editor.textView.text, "\tkey")
    }

    // MARK: Key row

    func testKeyRowPolicyRequiresFocusEditabilityAndNoHardwareKeyboard() {
        XCTAssertTrue(CodeEditorKeyRowPolicy.isPresented(focused: true, editable: true, hardwareKeyboard: false))
        XCTAssertFalse(CodeEditorKeyRowPolicy.isPresented(focused: false, editable: true, hardwareKeyboard: false))
        XCTAssertFalse(CodeEditorKeyRowPolicy.isPresented(focused: true, editable: false, hardwareKeyboard: false))
        XCTAssertFalse(CodeEditorKeyRowPolicy.isPresented(focused: true, editable: true, hardwareKeyboard: true))
    }

    func testInputAccessoryViewFollowsFocusAndHardwareKeyboard() {
        let editor = makeEditor()
        var hardwareKeyboard = false
        editor.textView.hardwareKeyboardAttached = { hardwareKeyboard }
        let window = host(editor)

        XCTAssertNil(editor.textView.inputAccessoryView)
        XCTAssertTrue(editor.textView.becomeFirstResponder())
        XCTAssertTrue(editor.textView.inputAccessoryView is CodeEditorKeyRow)
        hardwareKeyboard = true
        XCTAssertNil(editor.textView.inputAccessoryView)
        hardwareKeyboard = false
        editor.textView.resignFirstResponder()
        XCTAssertNil(editor.textView.inputAccessoryView)

        let readOnly = makeEditor(["read_only": true])
        window.addSubview(readOnly)
        _ = readOnly.textView.becomeFirstResponder()
        XCTAssertNil(readOnly.textView.inputAccessoryView)
    }

    func testKeySetsPerLanguage() {
        let editing: [CodeEditorKey] = [.indent, .outdent, .undo, .redo]

        XCTAssertEqual(CodeEditorKey.keys(for: .json), editing + ["{", "}", "[", "]", "\"", ":", ","].map(CodeEditorKey.symbol))
        XCTAssertEqual(
            CodeEditorKey.keys(for: .yaml),
            editing + [":", "-", "#", "\"", "'", "|"].map(CodeEditorKey.symbol) + [.toggleComment]
        )
        XCTAssertEqual(
            CodeEditorKey.keys(for: .env),
            editing + ["=", "#", "\"", "$", "{", "}", "_"].map(CodeEditorKey.symbol) + [.toggleComment]
        )
        XCTAssertEqual(CodeEditorKey.keys(for: .plain), editing + ["(", ")", "[", "]", "{", "}", "\"", "'"].map(CodeEditorKey.symbol))
    }

    func testKeyRowButtonsHaveAccessibleNamesAndMinimumTargets() {
        let row = CodeEditorKeyRow(language: .yaml, labels: .english)
        row.frame = CGRect(x: 0, y: 0, width: 390, height: 60)
        row.layoutIfNeeded()

        let labels = row.keyButtons.map { $0.accessibilityLabel ?? "" }
        XCTAssertEqual(labels, [
            "Indent", "Outdent", "Undo", "Redo", "Insert colon", "Insert hyphen", "Insert hash",
            "Insert double quote", "Insert single quote", "Insert vertical bar", "Toggle comment",
        ])
        XCTAssertEqual(row.doneButton.accessibilityLabel, "Done")
        for button in row.keyButtons + [row.doneButton] {
            XCTAssertGreaterThanOrEqual(button.bounds.width, 44, button.accessibilityLabel ?? "")
            XCTAssertGreaterThanOrEqual(button.bounds.height, 44, button.accessibilityLabel ?? "")
        }
        let english = CodeEditorKeyLabels.english
        let expected: [String: String] = [
            "{": "Insert left brace", "}": "Insert right brace", "[": "Insert left bracket",
            "]": "Insert right bracket", "(": "Insert left parenthesis", ")": "Insert right parenthesis",
            "\"": "Insert double quote", "'": "Insert single quote", ":": "Insert colon", ",": "Insert comma",
            "-": "Insert hyphen", "#": "Insert hash", "|": "Insert vertical bar", "=": "Insert equals sign",
            "$": "Insert dollar sign", "_": "Insert underscore",
        ]
        for (symbol, label) in expected {
            XCTAssertEqual(english.label(for: .symbol(symbol)), label)
        }
    }

    func testKeyRowLabelsComeFromPublishedChromeWithEnglishFallbacks() {
        let configuration = CodeEditorRendererConfiguration(node: makeNode([
            "label": "Code",
            "language": "json",
            "indent_label": "Indenter",
            "left_brace_label": "Insérer une accolade ouvrante",
            "done_label": "OK",
        ]))
        let row = CodeEditorKeyRow(language: configuration.language, labels: configuration.keyLabels)

        XCTAssertEqual(configuration.keyLabels.label(for: .indent), "Indenter")
        XCTAssertEqual(configuration.keyLabels.label(for: .outdent), "Outdent")
        XCTAssertEqual(configuration.keyLabels.label(for: .symbol("{")), "Insérer une accolade ouvrante")
        XCTAssertEqual(configuration.keyLabels.label(for: .symbol("}")), "Insert right brace")
        XCTAssertEqual(row.keyButtons.first?.accessibilityLabel, "Indenter")
        XCTAssertEqual(row.keyButtons[4].accessibilityLabel, "Insérer une accolade ouvrante")
        XCTAssertEqual(row.keyButtons[4].configuration?.title, "{")
        XCTAssertEqual(row.doneButton.accessibilityLabel, "OK")
    }

    func testDoneDismissesTheKeyboard() {
        let editor = makeEditor()
        _ = host(editor)
        XCTAssertTrue(editor.textView.becomeFirstResponder())

        editor.textView.keyRow.doneButton.sendActions(for: .touchUpInside)

        XCTAssertFalse(editor.textView.isFirstResponder)
    }

    // MARK: Engine routing and undo

    func testReturnIndentOutdentAndCommentAreEachOneUndoableEdit() {
        let editor = makeEditor(["language": "yaml", "value": "a:\nb"])
        let textView = editor.textView
        textView.selectedRange = NSRange(location: 2, length: 0)

        XCTAssertFalse(textView.delegate!.textView!(textView, shouldChangeTextIn: textView.selectedRange, replacementText: "\n"))
        XCTAssertEqual(textView.text, "a:\n  \nb")
        XCTAssertEqual(textView.selectedRange, NSRange(location: 5, length: 0))
        endEvent()

        textView.selectedRange = NSRange(location: 0, length: textView.text.utf16.count)
        textView.handle(.indent)
        endEvent()
        XCTAssertEqual(textView.text, "  a:\n    \n  b")
        textView.handle(.outdent)
        endEvent()
        XCTAssertEqual(textView.text, "a:\n  \nb")
        textView.handle(.toggleComment)
        endEvent()
        XCTAssertEqual(textView.text, "# a:\n  \n# b")

        textView.handle(.undo)
        XCTAssertEqual(textView.text, "a:\n  \nb")
        textView.handle(.undo)
        XCTAssertEqual(textView.text, "  a:\n    \n  b")
        textView.handle(.undo)
        XCTAssertEqual(textView.text, "a:\n  \nb")
        textView.handle(.undo)
        XCTAssertEqual(textView.text, "a:\nb")
        XCTAssertEqual(textView.selectedRange, NSRange(location: 2, length: 0))
        XCTAssertFalse(textView.undoManager!.canUndo)

        textView.handle(.redo)
        XCTAssertEqual(textView.text, "a:\n  \nb")
        XCTAssertEqual(textView.selectedRange, NSRange(location: 5, length: 0))
    }

    func testKeyRowSymbolsAndTypedJSONClosersRouteThroughTheEngine() {
        var published: [String] = []
        let editor = makeEditor(["language": "json", "value": "{\n    "])
        editor.onTextChange = { published.append($0) }
        let textView = editor.textView
        textView.selectedRange = NSRange(location: 6, length: 0)

        XCTAssertFalse(textView.delegate!.textView!(textView, shouldChangeTextIn: textView.selectedRange, replacementText: "}"))
        XCTAssertEqual(textView.text, "{\n  }")

        textView.selectedRange = NSRange(location: 0, length: 1)
        textView.keyRow.press(.symbol("["))
        XCTAssertEqual(textView.text, "[\n  }")
        XCTAssertEqual(textView.selectedRange, NSRange(location: 1, length: 0))
        XCTAssertEqual(published, ["{\n  }", "[\n  }"])

        XCTAssertTrue(textView.delegate!.textView!(textView, shouldChangeTextIn: NSRange(location: 1, length: 0), replacementText: "a"))
    }

    func testTypedTextAndKeyRowEditsUndoInTheOrderTheyHappened() {
        let editor = makeEditor(["language": "json", "value": "}"])
        _ = host(editor)
        let textView = editor.textView
        XCTAssertTrue(textView.becomeFirstResponder())
        textView.selectedRange = NSRange(location: 1, length: 0)

        textView.keyRow.press(.symbol("["))
        endEvent()
        textView.insertText("a")
        endEvent()
        textView.insertText("b")
        endEvent()
        textView.keyRow.press(.symbol("]"))
        endEvent()
        textView.insertText("c")
        endEvent()
        XCTAssertEqual(textView.text, "}[ab]c")

        var undone: [String] = []
        for _ in 0..<4 {
            textView.keyRow.press(.undo)
            endEvent()
            undone.append(textView.text)
        }
        XCTAssertEqual(undone, ["}[ab]", "}[ab", "}[", "}"])
        textView.keyRow.press(.redo)
        endEvent()
        textView.keyRow.press(.redo)
        endEvent()
        XCTAssertEqual(textView.text, "}[ab")
    }

    func testMarkedTextIsCommittedBeforeKeyInsertion() {
        let editor = makeEditor(["value": ""])
        _ = host(editor)
        XCTAssertTrue(editor.textView.becomeFirstResponder())
        editor.textView.setMarkedText("か", selectedRange: NSRange(location: 1, length: 0))
        XCTAssertNotNil(editor.textView.markedTextRange)

        editor.textView.keyRow.press(.symbol("("))

        XCTAssertNil(editor.textView.markedTextRange)
        XCTAssertEqual(editor.textView.text, "か(")
        XCTAssertEqual(editor.textView.selectedRange, NSRange(location: 2, length: 0))
    }

    func testHardwareTabAndShiftTabIndentAndOutdent() {
        let editor = makeEditor(["value": "key", "indent": 4])
        let textView = editor.textView
        let commands = textView.keyCommands ?? []
        let tab = commands.first { $0.input == "\t" && $0.modifierFlags == [] }
        let shiftTab = commands.first { $0.input == "\t" && $0.modifierFlags == .shift }

        XCTAssertNotNil(tab)
        XCTAssertNotNil(shiftTab)
        XCTAssertEqual(tab?.wantsPriorityOverSystemBehavior, true)
        XCTAssertEqual(shiftTab?.wantsPriorityOverSystemBehavior, true)
        textView.perform(tab!.action, with: tab)
        XCTAssertEqual(textView.text, "    key")
        textView.perform(shiftTab!.action, with: shiftTab)
        XCTAssertEqual(textView.text, "key")

        let readOnly = makeEditor(["read_only": true])
        XCTAssertFalse((readOnly.textView.keyCommands ?? []).contains { $0.input == "\t" })
    }

    // MARK: Synchronisation

    func testFocusedAcknowledgementLeavesTextSelectionScrollAndUndoUntouched() {
        var state = CodeEditorRendererState(node: makeNode(["label": "Payload", "value": longText, "on_change": 41]))
        let editor = makeEditor(["value": longText])
        _ = host(editor)
        XCTAssertTrue(editor.textView.becomeFirstResponder())
        _ = state.focusChanged(true)

        editor.textView.selectedRange = NSRange(location: 4, length: 0)
        editor.textView.handle(.indent)
        let edited = editor.textView.text!
        XCTAssertEqual(state.userChanged(edited), CodeEditorRendererEvent(callbackId: 41, nodeId: 7, value: edited))
        editor.textView.selectedRange = NSRange(location: 3, length: 5)
        editor.textView.setContentOffset(CGPoint(x: 0, y: 40), animated: false)
        let revision = state.replacementRevision

        state.serverPublished(tree(makeNode(["label": "Payload", "value": edited, "on_change": 41])))
        editor.update(configuration: state.configuration, text: state.draft, replacementRevision: state.replacementRevision)
        state.serverPublished(tree(makeNode(["label": "Payload", "value": "corrected", "on_change": 41])))
        editor.update(configuration: state.configuration, text: state.draft, replacementRevision: state.replacementRevision)

        XCTAssertEqual(state.replacementRevision, revision)
        XCTAssertEqual(state.pendingServerValue, "corrected")
        XCTAssertEqual(editor.textView.text, edited)
        XCTAssertEqual(editor.textView.selectedRange, NSRange(location: 3, length: 5))
        XCTAssertEqual(editor.textView.contentOffset.y, 40, accuracy: 0.5)
        XCTAssertTrue(editor.textView.undoManager!.canUndo)
    }

    func testUnfocusedPublicationReplacesTextClearsUndoAndEmitsNoChange() {
        var published: [String] = []
        var state = CodeEditorRendererState(node: makeNode(["label": "Payload", "value": "a", "on_change": 41]))
        let editor = makeEditor(["value": "a"])
        editor.onTextChange = { published.append($0) }
        editor.textView.handle(.indent)
        published.removeAll()
        XCTAssertTrue(editor.textView.undoManager!.canUndo)

        state.serverPublished(tree(makeNode(["label": "Payload", "value": "server", "on_change": 41])))
        editor.update(configuration: state.configuration, text: state.draft, replacementRevision: state.replacementRevision)

        XCTAssertEqual(editor.textView.text, "server")
        XCTAssertFalse(editor.textView.undoManager!.canUndo)
        XCTAssertEqual(published, [])
        XCTAssertNil(state.flush())
    }

    func testBlurAppliesARetainedServerValueAsAProgrammaticReplacement() {
        var state = CodeEditorRendererState(node: makeNode(["label": "Payload", "value": "server", "on_change": 41]))
        _ = state.focusChanged(true)
        state.serverPublished(tree(makeNode(["label": "Payload", "value": "corrected", "on_change": 41])))
        let revision = state.replacementRevision

        XCTAssertNil(state.focusChanged(false))
        XCTAssertEqual(state.draft, "corrected")
        XCTAssertEqual(state.replacementRevision, revision + 1)
    }

    func testLiveBlurAndDebouncePublication() {
        var live = CodeEditorRendererState(node: makeNode(["label": "Payload", "on_change": 41]))
        _ = live.focusChanged(true)
        XCTAssertEqual(live.userChanged("{"), CodeEditorRendererEvent(callbackId: 41, nodeId: 7, value: "{"))

        var blur = CodeEditorRendererState(node: makeNode(["label": "Payload", "sync_mode": "blur", "on_change": 41]))
        _ = blur.focusChanged(true)
        XCTAssertNil(blur.userChanged("a: 1"))
        XCTAssertEqual(blur.focusChanged(false), CodeEditorRendererEvent(callbackId: 41, nodeId: 7, value: "a: 1"))

        var debounce = CodeEditorRendererState(node: makeNode(["label": "Payload", "sync_mode": "debounce", "on_change": 41]))
        _ = debounce.focusChanged(true)
        XCTAssertNil(debounce.userChanged("A=1"))
        XCTAssertEqual(debounce.flush(), CodeEditorRendererEvent(callbackId: 41, nodeId: 7, value: "A=1"))
        XCTAssertNil(debounce.flush())
    }

    // MARK: States

    func testDisabledAndReadOnly() {
        let disabled = makeEditor(["disabled": true, "value": "x"])
        XCTAssertFalse(disabled.textView.isEditable)
        XCTAssertFalse(disabled.textView.isSelectable)
        XCTAssertTrue(disabled.textView.accessibilityTraits.contains(.notEnabled))

        let readOnly = makeEditor(["read_only": true, "value": "x"])
        XCTAssertFalse(readOnly.textView.isEditable)
        XCTAssertTrue(readOnly.textView.isSelectable)
        XCTAssertTrue(readOnly.textView.isScrollEnabled)
        XCTAssertFalse(readOnly.textView.accessibilityTraits.contains(.notEnabled))
        readOnly.textView.handle(.indent)
        XCTAssertEqual(readOnly.textView.text, "x")

        var disabledState = CodeEditorRendererState(node: makeNode(["label": "Payload", "disabled": true, "on_change": 41]))
        var readOnlyState = CodeEditorRendererState(node: makeNode(["label": "Payload", "read_only": true, "on_change": 41]))
        XCTAssertNil(disabledState.userChanged("changed"))
        XCTAssertNil(readOnlyState.userChanged("changed"))
    }

    func testTextViewCarriesAccessibilitySemanticsAndGutterIsHidden() {
        let editor = makeEditor([
            "label": "Payload", "a11y_hint": "JSON body", "error": "Missing id", "value": "{}",
        ])

        XCTAssertEqual(editor.textView.accessibilityLabel, "Payload")
        XCTAssertEqual(editor.textView.accessibilityHint, "JSON body")
        XCTAssertEqual(editor.textView.accessibilityValue, "{}. Error: Missing id")
        XCTAssertFalse(editor.gutter.isAccessibilityElement)
        XCTAssertTrue(editor.gutter.accessibilityElementsHidden)
        XCTAssertFalse(editor.gutter.isHidden)
        XCTAssertEqual(editor.textView.semanticContentAttribute, .forceLeftToRight)

        let noNumbers = makeEditor(["line_numbers": false])
        XCTAssertTrue(noNumbers.gutter.isHidden)
    }

    func testWrapOnAndOff() {
        let wrapped = makeEditor(["wrap": true, "value": String(repeating: "x", count: 300)])
        XCTAssertTrue(wrapped.textView.textContainer.widthTracksTextView)

        let unwrapped = makeEditor(["wrap": false, "value": String(repeating: "x", count: 300)])
        _ = host(unwrapped)
        unwrapped.layoutIfNeeded()
        XCTAssertFalse(unwrapped.textView.textContainer.widthTracksTextView)
        XCTAssertGreaterThan(unwrapped.textView.contentSize.width, unwrapped.textView.bounds.width)
    }

    func testHeightStaysWithinMinimumAndMaximumLines() {
        let short = makeEditor(["min_lines": 3, "max_lines": 5, "value": "a"])
        let tall = makeEditor(["min_lines": 3, "max_lines": 5, "value": longText])
        let lineHeight = short.textView.font!.lineHeight

        let shortHeight = short.fittingHeight(forWidth: 320)
        let tallHeight = tall.fittingHeight(forWidth: 320)

        XCTAssertEqual(shortHeight, short.height(forLines: 3), accuracy: 0.5)
        XCTAssertEqual(tallHeight, tall.height(forLines: 5), accuracy: 0.5)
        XCTAssertEqual(tallHeight - shortHeight, lineHeight * 2, accuracy: 0.5)
    }

    // MARK: Follow and scroll anchoring

    func testFollowStartsPinnedAtTheEnd() {
        let editor = makeEditor(["read_only": true, "follow": true, "value": lines(1...60)])
        host(editor)
        editor.layoutIfNeeded()

        XCTAssertGreaterThan(editor.textView.contentOffset.y, 0)
        XCTAssertTrue(editor.isPinnedToEnd)
        XCTAssertLessThanOrEqual(abs(editor.firstVisibleLine.line - (60 - visibleLineCount(editor))), 1)
    }

    func testPinnedReplacementStaysAtTheEndWithAndWithoutHeadTrim() {
        let editor = makeEditor(["read_only": true, "follow": true, "value": lines(1...60)])
        host(editor)
        editor.layoutIfNeeded()

        editor.update(configuration: editor.configuration, text: lines(1...80), replacementRevision: 1)
        XCTAssertTrue(editor.isPinnedToEnd)
        XCTAssertLessThanOrEqual(abs(editor.firstVisibleLine.line - (80 - visibleLineCount(editor))), 1)

        editor.update(configuration: editor.configuration, text: lines(31...100), replacementRevision: 2)
        XCTAssertTrue(editor.isPinnedToEnd)
        XCTAssertLessThanOrEqual(abs(editor.firstVisibleLine.line - (70 - visibleLineCount(editor))), 1)

        // Same length, so the offset does not move: the gutter must still follow the new layout.
        editor.update(configuration: editor.configuration, text: lines(56...125), replacementRevision: 3)
        XCTAssertTrue(editor.isPinnedToEnd)
        XCTAssertEqual(editor.gutter.entries.first?.number, editor.firstVisibleLine.line + 1)
    }

    func testUnwrappedLogStaysPinnedWhenTheGutterWidens() {
        func log(_ range: ClosedRange<Int>) -> String {
            range.map { "[\($0)] deploy: step \($0) completed after a long build" }.joined(separator: "\n")
        }
        let editor = makeEditor(["read_only": true, "follow": true, "wrap": false, "value": log(1...60)])
        host(editor)
        editor.layoutIfNeeded()

        // Four-digit line numbers widen the gutter and narrow the text view, which re-wraps every line.
        editor.update(configuration: editor.configuration, text: log(1...1200), replacementRevision: 1)
        editor.layoutIfNeeded()

        XCTAssertTrue(editor.isPinnedToEnd)
        XCTAssertLessThanOrEqual(abs(editor.firstVisibleLine.line - (1200 - visibleLineCount(editor))), 1)
        XCTAssertNotNil(editor.textView.textLayoutManager?.textViewportLayoutController.viewportRange)
    }

    func testScrolledUpReplacementKeepsTheAnchoredLineInPlace() {
        let editor = makeEditor(["read_only": true, "follow": true, "value": lines(1...60)])
        host(editor)
        editor.layoutIfNeeded()
        scroll(editor, toLine: 20, plus: 5)
        XCTAssertFalse(editor.isPinnedToEnd)

        editor.update(configuration: editor.configuration, text: lines(11...70), replacementRevision: 1)

        XCTAssertFalse(editor.isPinnedToEnd)
        XCTAssertEqual(editor.firstVisibleLine.line, 10)
        XCTAssertEqual(editor.textView.contentOffset.y - editor.firstVisibleLine.top, 5, accuracy: 0.5)
        XCTAssertEqual(lineText(editor, editor.firstVisibleLine.line), "line 21")
    }

    func testAnchoringAppliesWithoutFollowAndFallsBackToTheClampedOffset() {
        let editor = makeEditor(["value": lines(1...60)])
        host(editor)
        editor.layoutIfNeeded()
        scroll(editor, toLine: 30, plus: 0)
        let offset = editor.textView.contentOffset.y

        editor.update(configuration: editor.configuration, text: lines(1...65), replacementRevision: 1)
        XCTAssertEqual(editor.textView.contentOffset.y, offset, accuracy: 0.5)
        XCTAssertFalse(editor.isPinnedToEnd)

        editor.update(configuration: editor.configuration, text: "unrelated\ncontent", replacementRevision: 2)
        let maximum = max(0, editor.textView.contentSize.height - editor.textView.bounds.height)
        XCTAssertEqual(editor.textView.contentOffset.y, maximum, accuracy: 0.5)
    }

    func testScrollingBackToTheEndRepins() {
        let editor = makeEditor(["read_only": true, "follow": true, "value": lines(1...60)])
        host(editor)
        editor.layoutIfNeeded()
        scroll(editor, toLine: 10, plus: 0)
        editor.update(configuration: editor.configuration, text: lines(1...70), replacementRevision: 1)
        XCTAssertEqual(editor.firstVisibleLine.line, 10)
        XCTAssertFalse(editor.isPinnedToEnd)

        let bottom = editor.textView.contentSize.height - editor.textView.bounds.height
        editor.textView.setContentOffset(CGPoint(x: 0, y: bottom), animated: false)
        editor.update(configuration: editor.configuration, text: lines(1...90), replacementRevision: 2)

        XCTAssertTrue(editor.isPinnedToEnd)
        XCTAssertLessThanOrEqual(abs(editor.firstVisibleLine.line - (90 - visibleLineCount(editor))), 1)
    }

    func testReplacementWaitsWhileAReadOnlySelectionIsHeld() {
        let editor = makeEditor(["read_only": true, "follow": true, "value": "one\ntwo"])
        host(editor)
        XCTAssertTrue(editor.textView.becomeFirstResponder())
        editor.textView.selectedRange = NSRange(location: 0, length: 3)

        editor.update(configuration: editor.configuration, text: "one\ntwo\nthree", replacementRevision: 1)
        XCTAssertEqual(editor.textView.text, "one\ntwo")
        XCTAssertEqual(editor.textView.selectedRange, NSRange(location: 0, length: 3))

        editor.textView.selectedRange = NSRange(location: 2, length: 0)
        editor.textViewDidChangeSelection(editor.textView)
        XCTAssertEqual(editor.textView.text, "one\ntwo\nthree")

        editor.textView.selectedRange = NSRange(location: 0, length: 3)
        editor.update(configuration: editor.configuration, text: "four", replacementRevision: 2)
        XCTAssertEqual(editor.textView.text, "one\ntwo\nthree")
        _ = editor.textView.resignFirstResponder()
        XCTAssertEqual(editor.textView.text, "four")
    }

    func testReplacementIsNotAnnounced() {
        var published: [String] = []
        let editor = makeEditor(["read_only": true, "follow": true, "value": lines(1...10)])
        editor.onTextChange = { published.append($0) }
        editor.update(configuration: editor.configuration, text: lines(1...12), replacementRevision: 1)

        XCTAssertFalse(editor.textView.accessibilityTraits.contains(.updatesFrequently))
        XCTAssertEqual(published, [])
    }

    func testReplacementKeepsTheHorizontalOffsetWithWrapOff() {
        let long = (1...40).map { "line \($0) " + String(repeating: "x", count: 120) }.joined(separator: "\n")
        let editor = makeEditor(["wrap": false, "value": long])
        host(editor)
        editor.layoutIfNeeded()
        editor.textView.setContentOffset(CGPoint(x: 60, y: editor.textView.contentOffset.y), animated: false)

        editor.update(configuration: editor.configuration, text: long + "\nmore", replacementRevision: 1)

        XCTAssertEqual(editor.textView.contentOffset.x, 60, accuracy: 0.5)
    }

    func testRendererCompilesAgainstNativePHPContractShims() {
        let renderer = CodeEditorRenderer(node: makeNode(["label": "Payload"]), events: CodeEditorRendererEvents { _ in })
        XCTAssertNotNil(renderer.body)
    }

    // MARK: Helpers

    private func lines(_ range: ClosedRange<Int>) -> String {
        range.map { "line \($0)" }.joined(separator: "\n")
    }

    private func lineText(_ editor: FirstlightCodeEditorView, _ line: Int) -> String {
        editor.textView.text.components(separatedBy: "\n")[line]
    }

    private func visibleLineCount(_ editor: FirstlightCodeEditorView) -> Int {
        let insets = editor.textView.textContainerInset
        return Int(((editor.textView.bounds.height - insets.top - insets.bottom) / editor.textView.font!.lineHeight).rounded())
    }

    /// Scrolls so `line` (zero-based) starts `plus` points above the top edge.
    private func scroll(_ editor: FirstlightCodeEditorView, toLine line: Int, plus: CGFloat) {
        editor.textView.setContentOffset(CGPoint(x: 0, y: editor.top(ofLine: line) + plus), animated: false)
        editor.layoutIfNeeded()
        XCTAssertEqual(editor.firstVisibleLine.line, line)
    }

    /// NSUndoManager groups registrations per run-loop event; real key presses are separate events.
    private func endEvent() {
        RunLoop.main.run(until: Date().addingTimeInterval(0.01))
    }

    private var longText: String {
        (1...40).map { "line \($0)" }.joined(separator: "\n")
    }

    private func makeEditor(_ props: [String: Any] = [:]) -> FirstlightCodeEditorView {
        var merged: [String: Any] = ["label": "Code", "value": "key"]
        merged.merge(props) { _, new in new }
        let configuration = CodeEditorRendererConfiguration(node: makeNode(merged))
        let editor = FirstlightCodeEditorView(configuration: configuration, text: configuration.value)
        editor.frame = CGRect(x: 0, y: 0, width: 320, height: 200)
        editor.layoutIfNeeded()
        return editor
    }

    @discardableResult
    private func host(_ view: UIView) -> UIWindow {
        let window = UIWindow(frame: CGRect(x: 0, y: 0, width: 320, height: 568))
        window.addSubview(view)
        window.makeKeyAndVisible()
        addTeardownBlock { window.isHidden = true }
        return window
    }

    private func makeNode(_ props: [String: Any]) -> NativeUINode {
        NativeUINode(
            id: 7,
            type: "firstlight.code-editor",
            layout: nil,
            style: nil,
            props: GenericProps(props),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
    }

    private func tree(_ root: NativeUINode) -> NativeUITree {
        NativeUITree(version: 1, callbackCount: 1, root: root)
    }
}
