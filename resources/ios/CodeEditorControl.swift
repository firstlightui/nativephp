import GameController
import SwiftUI
import UIKit

// MARK: - Field composition

struct FirstlightCodeEditorField: View {
    let configuration: CodeEditorRendererConfiguration
    @Binding var text: String
    let replacementRevision: Int
    let onFocusChanged: (Bool) -> Void
    let tokens: NativeUITokens

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            if !configuration.label.isEmpty {
                Text(configuration.required ? "\(configuration.label) *" : configuration.label)
                    .font(.subheadline.weight(.medium))
                    .foregroundStyle(tokens.onSurface)
                    .fixedSize(horizontal: false, vertical: true)
            }

            FirstlightCodeEditorControl(
                configuration: configuration,
                text: $text,
                replacementRevision: replacementRevision,
                onFocusChanged: onFocusChanged
            )
            .background(
                configuration.readOnly ? Color(uiColor: .systemBackground) : Color(uiColor: .secondarySystemBackground)
            )
            .clipShape(RoundedRectangle(cornerRadius: 10, style: .continuous))
            .overlay {
                RoundedRectangle(cornerRadius: 10, style: .continuous)
                    .stroke(
                        configuration.error.isEmpty ? Color(uiColor: .separator) : tokens.destructive,
                        lineWidth: configuration.error.isEmpty ? 0.5 : 1
                    )
            }

            let supporting = configuration.error.isEmpty ? configuration.helper : configuration.error
            if !supporting.isEmpty {
                Text(supporting)
                    .font(.footnote)
                    .foregroundStyle(configuration.error.isEmpty ? tokens.onSurfaceVariant : tokens.destructive)
                    .fixedSize(horizontal: false, vertical: true)
                    .accessibilityLabel(configuration.error.isEmpty ? supporting : "Error: \(supporting)")
            }
        }
        .opacity(configuration.disabled ? 0.6 : 1)
    }
}

struct FirstlightCodeEditorControl: UIViewRepresentable {
    let configuration: CodeEditorRendererConfiguration
    @Binding var text: String
    let replacementRevision: Int
    let onFocusChanged: (Bool) -> Void

    func makeUIView(context: Context) -> FirstlightCodeEditorView {
        let view = FirstlightCodeEditorView(
            configuration: configuration,
            text: text,
            replacementRevision: replacementRevision
        )
        bindCallbacks(view)
        return view
    }

    func updateUIView(_ view: FirstlightCodeEditorView, context: Context) {
        bindCallbacks(view)
        view.update(configuration: configuration, text: text, replacementRevision: replacementRevision)
    }

    func sizeThatFits(_ proposal: ProposedViewSize, uiView: FirstlightCodeEditorView, context: Context) -> CGSize? {
        let width = proposal.width ?? 320
        return CGSize(width: width, height: uiView.fittingHeight(forWidth: width))
    }

    private func bindCallbacks(_ view: FirstlightCodeEditorView) {
        let binding = $text
        view.onTextChange = { binding.wrappedValue = $0 }
        view.onFocusChange = onFocusChanged
    }
}

// MARK: - Editor view

/// Gutter and text view side by side, so the gutter stays put when `wrap` is off and the text
/// scrolls horizontally.
final class FirstlightCodeEditorView: UIView, UITextViewDelegate {
    let textView: FirstlightCodeTextView
    let gutter = CodeEditorGutterView()
    var onTextChange: (String) -> Void = { _ in }
    var onFocusChange: (Bool) -> Void = { _ in }
    private(set) var configuration: CodeEditorRendererConfiguration
    private var appliedRevision: Int
    private var appliedAttributes: AppliedAttributes?
    private var lineCount = 1
    private var deferredReplacement: String?
    private var needsInitialPin: Bool

    private struct ScrollPosition {
        let pinned: Bool
        let anchorLine: Int
        let anchorDelta: CGFloat
        let offset: CGPoint
    }

    private struct AppliedAttributes: Equatable {
        let pointSize: CGFloat
        let indent: Int
        let wrap: Bool
    }

    init(configuration: CodeEditorRendererConfiguration, text: String, replacementRevision: Int = 0) {
        self.configuration = configuration
        appliedRevision = replacementRevision
        needsInitialPin = configuration.follow
        textView = FirstlightCodeTextView(language: configuration.language, labels: configuration.keyLabels)
        super.init(frame: .zero)
        backgroundColor = .clear
        textView.delegate = self
        textView.onEdited = { [weak self] in self?.textDidChange() }
        textView.onResign = { [weak self] in self?.applyDeferredReplacement() }
        addSubview(gutter)
        addSubview(textView)
        apply(configuration)
        setBuffer(text)
        registerForTraitChanges([UITraitPreferredContentSizeCategory.self]) { (view: FirstlightCodeEditorView, _) in
            view.applyTextAttributes()
            view.setNeedsLayout()
        }
    }

    @available(*, unavailable)
    required init?(coder: NSCoder) {
        fatalError("init(coder:) is not supported")
    }

    func update(configuration: CodeEditorRendererConfiguration, text: String, replacementRevision: Int) {
        apply(configuration)
        guard replacementRevision != appliedRevision else { return }
        appliedRevision = replacementRevision
        replaceProgrammatically(with: text)
    }

    /// Server-owned replacement of the buffer. Never publishes a change or posts an accessibility
    /// notification; clears undo so the person cannot undo into a value the server has superseded.
    /// A read-only selection holds the replacement until it collapses or focus leaves.
    func replaceProgrammatically(with text: String) {
        if configuration.readOnly, textView.selectedRange.length > 0 {
            deferredReplacement = text
            return
        }
        deferredReplacement = nil
        let before = textView.text ?? ""
        let position = captureScrollPosition()
        setBuffer(text)
        textView.undoManager?.removeAllActions()
        restore(position, before: before, after: text)
    }

    /// The view is pinned when its bottom edge is within one line height of the end of the content.
    var isPinnedToEnd: Bool {
        textView.layoutIfNeeded()
        let bottom = textView.contentOffset.y + textView.bounds.height - textView.adjustedContentInset.bottom
        return bottom >= textView.contentSize.height - lineHeight
    }

    /// Zero-based first visible logical line and its top in content coordinates.
    var firstVisibleLine: (line: Int, top: CGFloat) {
        let inset = textView.textContainerInset.top
        guard let layoutManager = textView.textLayoutManager,
              let contentManager = layoutManager.textContentManager,
              let fragment = layoutManager.textLayoutFragment(
                  // A line whose bottom edge sits exactly at the top edge is no longer visible.
                  for: CGPoint(x: 0, y: max(0, textView.contentOffset.y - inset + 0.5))
              )
        else { return (0, inset) }
        let offset = contentManager.offset(from: layoutManager.documentRange.location, to: fragment.rangeInElement.location)
        let line = textView.text.utf16.prefix(offset).reduce(0) { $1 == 0x0A ? $0 + 1 : $0 }
        return (line, fragment.layoutFragmentFrame.minY + inset)
    }

    /// Top of a zero-based logical line in content coordinates.
    func top(ofLine line: Int) -> CGFloat {
        let inset = textView.textContainerInset.top
        let text = textView.text.utf16
        var offset = 0
        var remaining = line
        for unit in text where remaining > 0 {
            offset += 1
            if unit == 0x0A { remaining -= 1 }
        }
        guard let layoutManager = textView.textLayoutManager,
              let contentManager = layoutManager.textContentManager,
              let location = contentManager.location(layoutManager.documentRange.location, offsetBy: offset)
        else { return inset }
        if let range = NSTextRange(location: layoutManager.documentRange.location, end: location) {
            layoutManager.ensureLayout(for: range)
        }
        guard let fragment = layoutManager.textLayoutFragment(for: location), offset < text.count else {
            return max(inset, textView.contentSize.height - textView.textContainerInset.bottom - lineHeight)
        }
        return fragment.layoutFragmentFrame.minY + inset
    }

    func height(forLines lines: Int) -> CGFloat {
        let insets = textView.textContainerInset
        return CGFloat(lines) * lineHeight + insets.top + insets.bottom
    }

    func fittingHeight(forWidth width: CGFloat) -> CGFloat {
        let minimum = height(forLines: configuration.minLines)
        let maximum = height(forLines: configuration.maxLines)
        // Every logical line takes at least one row, so long documents skip measuring.
        guard lineCount < configuration.maxLines else { return maximum }
        let textWidth = max(1, width - gutterWidth)
        let content = textView.sizeThatFits(CGSize(width: textWidth, height: .greatestFiniteMagnitude)).height
        return min(max(content, minimum), maximum)
    }

    override func layoutSubviews() {
        super.layoutSubviews()
        let width = gutterWidth
        gutter.frame = CGRect(x: 0, y: 0, width: width, height: bounds.height)
        textView.frame = CGRect(x: width, y: 0, width: max(0, bounds.width - width), height: bounds.height)
        if !configuration.wrap {
            textView.textContainer.size = CGSize(width: CodeEditorTypography.unwrappedWidth, height: .greatestFiniteMagnitude)
        }
        refreshGutter()
        if needsInitialPin, bounds.height > 0 {
            needsInitialPin = false
            scrollToEnd(keepingX: textView.contentOffset.x)
        }
    }

    // MARK: UITextViewDelegate

    func textView(_ textView: UITextView, shouldChangeTextIn range: NSRange, replacementText text: String) -> Bool {
        guard textView.markedTextRange == nil, let codeView = textView as? FirstlightCodeTextView else { return true }
        let operation: CodeEditorOperation
        if text == "\n" {
            operation = .return
        } else if configuration.language == .json, text == "}" || text == "]" {
            operation = .insert(text)
        } else {
            return true
        }
        if codeView.selectedRange != range { codeView.selectedRange = range }
        codeView.perform(operation)
        return false
    }

    func textViewDidChange(_ textView: UITextView) {
        textDidChange()
    }

    func textViewDidChangeSelection(_ textView: UITextView) {
        if textView.selectedRange.length == 0 { applyDeferredReplacement() }
    }

    func textViewDidBeginEditing(_ textView: UITextView) {
        onFocusChange(true)
    }

    func textViewDidEndEditing(_ textView: UITextView) {
        onFocusChange(false)
    }

    func scrollViewDidScroll(_ scrollView: UIScrollView) {
        refreshGutter()
    }

    // MARK: Private

    private var lineHeight: CGFloat {
        (textView.font ?? CodeEditorTypography.font(compatibleWith: traitCollection)).lineHeight
    }

    private var gutterWidth: CGFloat {
        guard configuration.lineNumbers else { return 0 }
        return CodeEditorGutterView.width(forLineCount: lineCount, font: gutter.font)
    }

    private func apply(_ configuration: CodeEditorRendererConfiguration) {
        let layoutChanged = configuration.lineNumbers != self.configuration.lineNumbers
            || configuration.wrap != self.configuration.wrap
        self.configuration = configuration
        textView.apply(configuration)
        gutter.isHidden = !configuration.lineNumbers
        applyTextAttributes()
        if layoutChanged { setNeedsLayout() }
    }

    private func applyTextAttributes() {
        let font = CodeEditorTypography.font(compatibleWith: traitCollection)
        let key = AppliedAttributes(pointSize: font.pointSize, indent: configuration.indent, wrap: configuration.wrap)
        guard key != appliedAttributes else { return }
        appliedAttributes = key
        let attributes = CodeEditorTypography.attributes(font: font, indent: configuration.indent, wrap: configuration.wrap)
        textView.textAttributes = attributes
        let storage = textView.textStorage
        storage.beginEditing()
        storage.setAttributes(attributes, range: NSRange(location: 0, length: storage.length))
        storage.endEditing()
        textView.textContainer.widthTracksTextView = configuration.wrap
        if !configuration.wrap {
            textView.textContainer.size = CGSize(width: CodeEditorTypography.unwrappedWidth, height: .greatestFiniteMagnitude)
        }
        gutter.font = font
        setNeedsLayout()
    }

    private func applyDeferredReplacement() {
        guard let deferredReplacement else { return }
        textView.selectedRange = NSRange(location: textView.selectedRange.location, length: 0)
        replaceProgrammatically(with: deferredReplacement)
    }

    private func captureScrollPosition() -> ScrollPosition {
        let offset = textView.contentOffset
        let anchor = firstVisibleLine
        return ScrollPosition(
            pinned: configuration.follow && isPinnedToEnd,
            anchorLine: anchor.line,
            anchorDelta: offset.y - anchor.top,
            offset: offset
        )
    }

    /// Follow keeps a pinned view at the end; otherwise the first visible line stays put (append and
    /// head-trim cases) or the previous offset is kept, clamped. Never jumps to the top.
    private func restore(_ position: ScrollPosition, before: String, after: String) {
        textView.layoutIfNeeded()
        if position.pinned {
            scrollToEnd(keepingX: position.offset.x)
            return
        }
        var y = position.offset.y
        if let line = CodeEditorScrollAnchor.anchorLine(before: before, after: after, anchorLine: position.anchorLine) {
            y = top(ofLine: line) + position.anchorDelta
        }
        textView.layoutIfNeeded()
        textView.setContentOffset(clamped(CGPoint(x: position.offset.x, y: y)), animated: false)
    }

    private func scrollToEnd(keepingX x: CGFloat) {
        let length = textView.textStorage.length
        if let layoutManager = textView.textLayoutManager {
            layoutManager.ensureLayout(for: layoutManager.documentRange)
        }
        textView.layoutIfNeeded()
        textView.setContentOffset(clamped(CGPoint(x: x, y: .greatestFiniteMagnitude)), animated: false)
        if length > 0, !isPinnedToEnd {
            textView.scrollRangeToVisible(NSRange(location: length, length: 0))
            textView.setContentOffset(clamped(CGPoint(x: x, y: .greatestFiniteMagnitude)), animated: false)
        }
    }

    private func clamped(_ point: CGPoint) -> CGPoint {
        let insets = textView.adjustedContentInset
        let maxX = max(-insets.left, textView.contentSize.width - textView.bounds.width + insets.right)
        let maxY = max(-insets.top, textView.contentSize.height - textView.bounds.height + insets.bottom)
        return CGPoint(x: min(max(point.x, -insets.left), maxX), y: min(max(point.y, -insets.top), maxY))
    }

    private func setBuffer(_ text: String) {
        textView.textStorage.setAttributedString(NSAttributedString(string: text, attributes: textView.textAttributes))
        recountLines()
    }

    private func textDidChange() {
        recountLines()
        onTextChange(textView.text)
    }

    private func recountLines() {
        let count = textView.text.utf16.reduce(1) { $1 == 0x0A ? $0 + 1 : $0 }
        let widthChanged = configuration.lineNumbers
            && CodeEditorGutterView.width(forLineCount: count, font: gutter.font)
            != CodeEditorGutterView.width(forLineCount: lineCount, font: gutter.font)
        lineCount = count
        if widthChanged { setNeedsLayout() }
        refreshGutter()
    }

    /// Numbers each logical (`\n`-separated) line at its first visual row, matching the engine's
    /// notion of a line.
    private func refreshGutter() {
        guard configuration.lineNumbers,
              let layoutManager = textView.textLayoutManager,
              let contentManager = layoutManager.textContentManager
        else { return }
        let font = gutter.font
        let top = textView.textContainerInset.top - textView.contentOffset.y
        let visibleMinY = textView.contentOffset.y - textView.textContainerInset.top
        let visibleMaxY = visibleMinY + textView.bounds.height
        let text = textView.text ?? ""
        guard !text.isEmpty else {
            gutter.entries = [.init(number: 1, y: top, height: font.lineHeight)]
            return
        }

        let documentStart = layoutManager.documentRange.location
        let startLocation = layoutManager.textLayoutFragment(for: CGPoint(x: 0, y: max(0, visibleMinY)))?
            .rangeInElement.location ?? documentStart
        let startOffset = contentManager.offset(from: documentStart, to: startLocation)
        var number = 1 + text.utf16.prefix(startOffset).reduce(0) { $1 == 0x0A ? $0 + 1 : $0 }
        var entries: [CodeEditorGutterView.Entry] = []
        layoutManager.enumerateTextLayoutFragments(
            from: startLocation,
            options: [.ensuresLayout, .ensuresExtraLineFragment]
        ) { fragment in
            let frame = fragment.layoutFragmentFrame
            guard frame.minY <= visibleMaxY else { return false }
            let lines = fragment.textLineFragments
            if let first = lines.first {
                entries.append(.init(
                    number: number,
                    y: top + frame.minY + first.typographicBounds.minY,
                    height: first.typographicBounds.height
                ))
            }
            if lines.count > 1, let extra = lines.last, extra.characterRange.length == 0 {
                entries.append(.init(
                    number: number + 1,
                    y: top + frame.minY + extra.typographicBounds.minY,
                    height: extra.typographicBounds.height
                ))
            }
            number += 1
            return true
        }
        gutter.entries = entries
    }
}

// MARK: - Text view

final class FirstlightCodeTextView: UITextView {
    let keyRow: CodeEditorKeyRow
    var hardwareKeyboardAttached: () -> Bool = { GCKeyboard.coalesced != nil }
    var onEdited: () -> Void = {}
    var onResign: () -> Void = {}
    var textAttributes: [NSAttributedString.Key: Any] = [:] {
        didSet { typingAttributes = textAttributes }
    }
    private(set) var language: CodeEditorLanguage
    private(set) var indentWidth = 2
    private var accessibilityError = ""
    private var isInEditingSession = false
    private let ownedUndoManager = UndoManager()
    private var baseAccessibilityTraits: UIAccessibilityTraits = []

    init(language: CodeEditorLanguage, labels: CodeEditorKeyLabels) {
        self.language = language
        keyRow = CodeEditorKeyRow(language: language, labels: labels)
        // On iOS 16+, UITextView uses TextKit 2 unless `layoutManager` is touched; never touch it.
        super.init(frame: .zero, textContainer: nil)
        baseAccessibilityTraits = accessibilityTraits
        backgroundColor = .clear
        textContainerInset = CodeEditorTypography.insets
        // Embedded in a field: the screen's safe area is not this scroll view's concern.
        contentInsetAdjustmentBehavior = .never
        textContainer.lineFragmentPadding = 0
        adjustsFontForContentSizeCategory = false
        autocorrectionType = .no
        autocapitalizationType = .none
        spellCheckingType = .no
        smartQuotesType = .no
        smartDashesType = .no
        smartInsertDeleteType = .no
        inlinePredictionType = .no
        writingToolsBehavior = .none
        dataDetectorTypes = []
        allowsEditingTextAttributes = false
        semanticContentAttribute = .forceLeftToRight
        textAlignment = .left
        keyRow.onKey = { [weak self] key in self?.handle(key) }
        keyRow.onDone = { [weak self] in _ = self?.resignFirstResponder() }
        let center = NotificationCenter.default
        for name in [Notification.Name.GCKeyboardDidConnect, .GCKeyboardDidDisconnect] {
            center.addObserver(self, selector: #selector(hardwareKeyboardChanged), name: name, object: nil)
        }
        for name in [Notification.Name.NSUndoManagerDidCloseUndoGroup, .NSUndoManagerDidUndoChange, .NSUndoManagerDidRedoChange] {
            center.addObserver(self, selector: #selector(undoStateChanged), name: name, object: ownedUndoManager)
        }
        undoStateChanged()
    }

    @available(*, unavailable)
    required init?(coder: NSCoder) {
        fatalError("init(coder:) is not supported")
    }

    override var undoManager: UndoManager? { ownedUndoManager }

    /// With `wrap` off TextKit 2 keeps the content as wide as the view, so the measured width of the
    /// laid-out lines widens it for horizontal scrolling.
    var wrapsLines = true {
        didSet { if wrapsLines != oldValue { measuredLineWidth = 0; setNeedsLayout() } }
    }

    private var measuredLineWidth: CGFloat = 0

    override var contentSize: CGSize {
        get { super.contentSize }
        set {
            super.contentSize = wrapsLines
                ? newValue
                : CGSize(width: max(newValue.width, measuredLineWidth), height: newValue.height)
        }
    }

    override func layoutSubviews() {
        super.layoutSubviews()
        guard !wrapsLines else { return }
        let width = ceil(visibleLineWidth() + textContainerInset.left + textContainerInset.right)
        guard width != measuredLineWidth else { return }
        measuredLineWidth = width
        contentSize = super.contentSize
    }

    private func visibleLineWidth() -> CGFloat {
        guard let layoutManager = textLayoutManager else { return 0 }
        let visibleMinY = contentOffset.y - textContainerInset.top
        let visibleMaxY = visibleMinY + bounds.height
        let start = layoutManager.textLayoutFragment(for: CGPoint(x: 0, y: max(0, visibleMinY)))?
            .rangeInElement.location ?? layoutManager.documentRange.location
        var width: CGFloat = 0
        layoutManager.enumerateTextLayoutFragments(from: start, options: [.ensuresLayout]) { fragment in
            let frame = fragment.layoutFragmentFrame
            guard frame.minY <= visibleMaxY else { return false }
            for line in fragment.textLineFragments {
                width = max(width, frame.minX + line.typographicBounds.maxX)
            }
            return true
        }
        return width
    }

    override var inputAccessoryView: UIView? {
        get {
            CodeEditorKeyRowPolicy.isPresented(
                focused: isInEditingSession,
                editable: isEditable,
                hardwareKeyboard: hardwareKeyboardAttached()
            ) ? keyRow : nil
        }
        set {}
    }

    override var keyCommands: [UIKeyCommand]? {
        guard isEditable else { return super.keyCommands }
        let indent = UIKeyCommand(title: keyRow.labels.indent, action: #selector(indentFromKeyboard), input: "\t")
        let outdent = UIKeyCommand(
            title: keyRow.labels.outdent,
            action: #selector(outdentFromKeyboard),
            input: "\t",
            modifierFlags: .shift
        )
        indent.wantsPriorityOverSystemBehavior = true
        outdent.wantsPriorityOverSystemBehavior = true
        return (super.keyCommands ?? []) + [indent, outdent]
    }

    override var accessibilityValue: String? {
        get {
            accessibilityError.isEmpty
                ? super.accessibilityValue
                : TextAreaAccessibility.value(text: text ?? "", error: accessibilityError)
        }
        set { super.accessibilityValue = newValue }
    }

    override func becomeFirstResponder() -> Bool {
        isInEditingSession = true
        let became = super.becomeFirstResponder()
        if !became { isInEditingSession = false }
        return became
    }

    override func resignFirstResponder() -> Bool {
        let resigned = super.resignFirstResponder()
        if resigned {
            isInEditingSession = false
            onResign()
        }
        return resigned
    }

    func apply(_ configuration: CodeEditorRendererConfiguration) {
        language = configuration.language
        indentWidth = configuration.indent
        wrapsLines = configuration.wrap
        keyRow.configure(language: configuration.language, labels: configuration.keyLabels)
        if isEditable != configuration.editable {
            isEditable = configuration.editable
            if isFirstResponder { reloadInputViews() }
        }
        isSelectable = !configuration.disabled
        accessibilityLabel = configuration.accessibilityLabel
        accessibilityHint = configuration.accessibilityHint.isEmpty ? nil : configuration.accessibilityHint
        accessibilityError = configuration.error
        accessibilityTraits = configuration.disabled ? baseAccessibilityTraits.union(.notEnabled) : baseAccessibilityTraits
    }

    func handle(_ key: CodeEditorKey) {
        switch key {
        case .indent: perform(.indent)
        case .outdent: perform(.outdent)
        case .toggleComment: perform(.toggleComment)
        case .symbol(let symbol): perform(.insert(symbol))
        case .undo:
            guard isEditable else { return }
            commitMarkedText()
            if ownedUndoManager.canUndo { ownedUndoManager.undo() }
        case .redo:
            guard isEditable else { return }
            commitMarkedText()
            if ownedUndoManager.canRedo { ownedUndoManager.redo() }
        }
    }

    /// Applies one engine operation as a single undoable edit.
    func perform(_ operation: CodeEditorOperation) {
        guard isEditable else { return }
        commitMarkedText()
        let current = text ?? ""
        let edit = CodeEditorEditing.apply(
            text: current,
            selection: CodeEditorSelection(selectedRange),
            operation: operation,
            language: language,
            indent: indentWidth
        )
        let change = CodeEditorTextChange(from: current, to: edit.text)
        guard !change.isEmpty || edit.selection.range != selectedRange else { return }
        replace(change.range, with: change.replacement, selecting: edit.selection.range, undoSelection: selectedRange)
    }

    /// Undo restores `undoSelection`; the matching redo restores `selection` again.
    private func replace(
        _ range: NSRange,
        with replacement: String,
        selecting selection: NSRange,
        undoSelection: NSRange
    ) {
        let removed = (textStorage.string as NSString).substring(with: range)
        textStorage.beginEditing()
        textStorage.replaceCharacters(in: range, with: NSAttributedString(string: replacement, attributes: textAttributes))
        textStorage.endEditing()
        selectedRange = selection
        let inverse = NSRange(location: range.location, length: replacement.utf16.count)
        ownedUndoManager.beginUndoGrouping()
        ownedUndoManager.registerUndo(withTarget: self) { textView in
            textView.replace(inverse, with: removed, selecting: undoSelection, undoSelection: selection)
        }
        ownedUndoManager.endUndoGrouping()
        scrollRangeToVisible(selection)
        onEdited()
    }

    private func commitMarkedText() {
        if markedTextRange != nil { unmarkText() }
    }

    @objc private func indentFromKeyboard(_ sender: Any?) {
        handle(.indent)
    }

    @objc private func outdentFromKeyboard(_ sender: Any?) {
        handle(.outdent)
    }

    @objc private func hardwareKeyboardChanged() {
        if isFirstResponder { reloadInputViews() }
    }

    @objc private func undoStateChanged() {
        keyRow.setUndoState(canUndo: ownedUndoManager.canUndo, canRedo: ownedUndoManager.canRedo)
    }
}

/// The smallest UTF-16 replacement turning one string into another, never splitting a surrogate pair.
struct CodeEditorTextChange: Equatable {
    let range: NSRange
    let replacement: String

    var isEmpty: Bool { range.length == 0 && replacement.isEmpty }

    init(from old: String, to new: String) {
        let before = Array(old.utf16)
        let after = Array(new.utf16)
        var prefix = 0
        while prefix < before.count, prefix < after.count, before[prefix] == after[prefix] { prefix += 1 }
        if prefix > 0, UTF16.isLeadSurrogate(before[prefix - 1]) { prefix -= 1 }
        var suffix = 0
        while suffix < before.count - prefix, suffix < after.count - prefix,
              before[before.count - 1 - suffix] == after[after.count - 1 - suffix]
        {
            suffix += 1
        }
        if suffix > 0, UTF16.isTrailSurrogate(before[before.count - suffix]) { suffix -= 1 }
        range = NSRange(location: prefix, length: before.count - prefix - suffix)
        replacement = String(decoding: after[prefix..<(after.count - suffix)], as: UTF16.self)
    }
}

// MARK: - Typography

enum CodeEditorTypography {
    static let insets = UIEdgeInsets(top: 8, left: 6, bottom: 8, right: 8)
    /// Container width used when `wrap` is off; TextKit 2 sizes content to the used width.
    static let unwrappedWidth: CGFloat = 100_000

    static func font(compatibleWith traits: UITraitCollection?) -> UIFont {
        UIFontMetrics(forTextStyle: .body).scaledFont(
            for: .monospacedSystemFont(ofSize: 15, weight: .regular),
            compatibleWith: traits
        )
    }

    static func attributes(font: UIFont, indent: Int, wrap: Bool) -> [NSAttributedString.Key: Any] {
        let paragraph = NSMutableParagraphStyle()
        paragraph.tabStops = []
        paragraph.defaultTabInterval = (" " as NSString).size(withAttributes: [.font: font]).width * CGFloat(indent)
        paragraph.baseWritingDirection = .leftToRight
        paragraph.alignment = .left
        paragraph.lineBreakMode = wrap ? .byWordWrapping : .byClipping
        return [.font: font, .foregroundColor: UIColor.label, .paragraphStyle: paragraph]
    }
}

// MARK: - Gutter

final class CodeEditorGutterView: UIView {
    struct Entry: Equatable {
        let number: Int
        let y: CGFloat
        let height: CGFloat
    }

    static let horizontalPadding: CGFloat = 8

    var entries: [Entry] = [] {
        didSet { if entries != oldValue { setNeedsDisplay() } }
    }

    var font: UIFont = CodeEditorTypography.font(compatibleWith: nil) {
        didSet { setNeedsDisplay() }
    }

    override init(frame: CGRect) {
        super.init(frame: frame)
        isOpaque = false
        backgroundColor = .clear
        contentMode = .redraw
        isUserInteractionEnabled = false
        // Decorative: VoiceOver reads lines from the text view itself.
        isAccessibilityElement = false
        accessibilityElementsHidden = true
    }

    @available(*, unavailable)
    required init?(coder: NSCoder) {
        fatalError("init(coder:) is not supported")
    }

    static func width(forLineCount lineCount: Int, font: UIFont) -> CGFloat {
        let digits = max(2, String(max(1, lineCount)).count)
        let digitWidth = ("0" as NSString).size(withAttributes: [.font: font]).width
        return ceil(CGFloat(digits) * digitWidth + horizontalPadding * 2)
    }

    override func draw(_ rect: CGRect) {
        let paragraph = NSMutableParagraphStyle()
        paragraph.alignment = .right
        let attributes: [NSAttributedString.Key: Any] = [
            .font: font,
            .foregroundColor: UIColor.secondaryLabel,
            .paragraphStyle: paragraph,
        ]
        for entry in entries where entry.y + entry.height >= rect.minY && entry.y <= rect.maxY {
            ("\(entry.number)" as NSString).draw(
                in: CGRect(x: 0, y: entry.y, width: bounds.width - Self.horizontalPadding, height: entry.height),
                withAttributes: attributes
            )
        }
        UIColor.separator.setFill()
        let scale = window?.screen.scale ?? traitCollection.displayScale
        UIRectFill(CGRect(x: bounds.width - 1 / max(scale, 1), y: 0, width: 1 / max(scale, 1), height: bounds.height))
    }
}

// MARK: - Key row

enum CodeEditorKey: Equatable {
    case indent
    case outdent
    case undo
    case redo
    case toggleComment
    case symbol(String)

    static func keys(for language: CodeEditorLanguage) -> [CodeEditorKey] {
        let editing: [CodeEditorKey] = [.indent, .outdent, .undo, .redo]
        switch language {
        case .json: return editing + ["{", "}", "[", "]", "\"", ":", ","].map(CodeEditorKey.symbol)
        case .yaml: return editing + [":", "-", "#", "\"", "'", "|"].map(CodeEditorKey.symbol) + [.toggleComment]
        case .env: return editing + ["=", "#", "\"", "$", "{", "}", "_"].map(CodeEditorKey.symbol) + [.toggleComment]
        case .plain: return editing + ["(", ")", "[", "]", "{", "}", "\"", "'"].map(CodeEditorKey.symbol)
        }
    }

    var systemImage: String? {
        switch self {
        case .indent: "increase.indent"
        case .outdent: "decrease.indent"
        case .undo: "arrow.uturn.backward"
        case .redo: "arrow.uturn.forward"
        case .toggleComment, .symbol: nil
        }
    }
}

/// Key-row chrome published by PHP (`*_label` props), with English fallbacks.
struct CodeEditorKeyLabels: Equatable {
    let done: String
    let indent: String
    let outdent: String
    let undo: String
    let redo: String
    let toggleComment: String
    let symbols: [String: String]

    private static let actions: [(prop: String, fallback: String)] = [
        ("done_label", "Done"), ("indent_label", "Indent"), ("outdent_label", "Outdent"),
        ("undo_label", "Undo"), ("redo_label", "Redo"), ("toggle_comment_label", "Toggle comment"),
    ]

    private static let symbolLabels: [(symbol: String, prop: String, fallback: String)] = [
        ("{", "left_brace_label", "Insert left brace"),
        ("}", "right_brace_label", "Insert right brace"),
        ("[", "left_bracket_label", "Insert left bracket"),
        ("]", "right_bracket_label", "Insert right bracket"),
        ("(", "left_parenthesis_label", "Insert left parenthesis"),
        (")", "right_parenthesis_label", "Insert right parenthesis"),
        ("\"", "double_quote_label", "Insert double quote"),
        ("'", "single_quote_label", "Insert single quote"),
        (":", "colon_label", "Insert colon"),
        (",", "comma_label", "Insert comma"),
        ("-", "hyphen_label", "Insert hyphen"),
        ("#", "hash_label", "Insert hash"),
        ("|", "vertical_bar_label", "Insert vertical bar"),
        ("=", "equals_label", "Insert equals sign"),
        ("$", "dollar_sign_label", "Insert dollar sign"),
        ("_", "underscore_label", "Insert underscore"),
    ]

    static let english = CodeEditorKeyLabels { _, fallback in fallback }

    init(props: GenericProps) {
        self.init { prop, fallback in
            let value = props.getString(prop)
            return value.isEmpty ? fallback : value
        }
    }

    private init(resolve: (String, String) -> String) {
        let actions = Self.actions.map { resolve($0.prop, $0.fallback) }
        done = actions[0]
        indent = actions[1]
        outdent = actions[2]
        undo = actions[3]
        redo = actions[4]
        toggleComment = actions[5]
        symbols = Dictionary(uniqueKeysWithValues: Self.symbolLabels.map { ($0.symbol, resolve($0.prop, $0.fallback)) })
    }

    func label(for key: CodeEditorKey) -> String {
        switch key {
        case .indent: indent
        case .outdent: outdent
        case .undo: undo
        case .redo: redo
        case .toggleComment: toggleComment
        case .symbol(let symbol): symbols[symbol] ?? symbol
        }
    }
}

enum CodeEditorKeyRowPolicy {
    static func isPresented(focused: Bool, editable: Bool, hardwareKeyboard: Bool) -> Bool {
        focused && editable && !hardwareKeyboard
    }
}

/// Input accessory above the software keyboard: a horizontally scrolling row of keys and a
/// trailing Done button.
final class CodeEditorKeyRow: UIInputView {
    static let minimumTarget: CGFloat = 44

    var onKey: (CodeEditorKey) -> Void = { _ in }
    var onDone: () -> Void = {}
    let doneButton = UIButton(configuration: .plain())
    private(set) var keyButtons: [UIButton] = []
    private(set) var labels: CodeEditorKeyLabels
    private(set) var language: CodeEditorLanguage
    private let scrollView = UIScrollView()
    private let stack = UIStackView()
    private var canUndo = false
    private var canRedo = false

    init(language: CodeEditorLanguage, labels: CodeEditorKeyLabels) {
        self.language = language
        self.labels = labels
        super.init(frame: CGRect(x: 0, y: 0, width: 0, height: Self.minimumTarget + 8), inputViewStyle: .keyboard)
        allowsSelfSizing = true
        scrollView.showsHorizontalScrollIndicator = false
        scrollView.alwaysBounceHorizontal = true
        scrollView.translatesAutoresizingMaskIntoConstraints = false
        stack.axis = .horizontal
        stack.spacing = 6
        stack.alignment = .center
        stack.translatesAutoresizingMaskIntoConstraints = false
        doneButton.translatesAutoresizingMaskIntoConstraints = false
        doneButton.addAction(UIAction { [weak self] _ in self?.onDone() }, for: .touchUpInside)
        addSubview(scrollView)
        scrollView.addSubview(stack)
        addSubview(doneButton)

        NSLayoutConstraint.activate([
            scrollView.leadingAnchor.constraint(equalTo: leadingAnchor),
            scrollView.topAnchor.constraint(equalTo: topAnchor, constant: 4),
            scrollView.bottomAnchor.constraint(equalTo: bottomAnchor, constant: -4),
            scrollView.trailingAnchor.constraint(equalTo: doneButton.leadingAnchor, constant: -4),
            stack.leadingAnchor.constraint(equalTo: scrollView.contentLayoutGuide.leadingAnchor, constant: 8),
            stack.trailingAnchor.constraint(equalTo: scrollView.contentLayoutGuide.trailingAnchor, constant: -8),
            stack.topAnchor.constraint(equalTo: scrollView.contentLayoutGuide.topAnchor),
            stack.bottomAnchor.constraint(equalTo: scrollView.contentLayoutGuide.bottomAnchor),
            stack.heightAnchor.constraint(equalTo: scrollView.frameLayoutGuide.heightAnchor),
            doneButton.trailingAnchor.constraint(equalTo: safeAreaLayoutGuide.trailingAnchor, constant: -8),
            doneButton.centerYAnchor.constraint(equalTo: scrollView.centerYAnchor),
            doneButton.widthAnchor.constraint(greaterThanOrEqualToConstant: Self.minimumTarget),
            doneButton.heightAnchor.constraint(greaterThanOrEqualToConstant: Self.minimumTarget),
            scrollView.heightAnchor.constraint(greaterThanOrEqualTo: doneButton.heightAnchor),
        ])
        doneButton.setContentCompressionResistancePriority(.required, for: .horizontal)
        rebuild()
    }

    @available(*, unavailable)
    required init?(coder: NSCoder) {
        fatalError("init(coder:) is not supported")
    }

    func configure(language: CodeEditorLanguage, labels: CodeEditorKeyLabels) {
        guard language != self.language || labels != self.labels else { return }
        self.language = language
        self.labels = labels
        rebuild()
    }

    func press(_ key: CodeEditorKey) {
        onKey(key)
    }

    func setUndoState(canUndo: Bool, canRedo: Bool) {
        self.canUndo = canUndo
        self.canRedo = canRedo
        for (key, button) in zip(CodeEditorKey.keys(for: language), keyButtons) {
            switch key {
            case .undo: button.isEnabled = canUndo
            case .redo: button.isEnabled = canRedo
            default: break
            }
        }
    }

    private func rebuild() {
        keyButtons.forEach { $0.removeFromSuperview() }
        keyButtons = CodeEditorKey.keys(for: language).map(makeButton)
        keyButtons.forEach(stack.addArrangedSubview)

        var done = UIButton.Configuration.plain()
        done.title = labels.done
        done.titleTextAttributesTransformer = UIConfigurationTextAttributesTransformer { attributes in
            var attributes = attributes
            attributes.font = UIFont.preferredFont(forTextStyle: .headline)
            return attributes
        }
        doneButton.configuration = done
        doneButton.accessibilityLabel = labels.done
        setUndoState(canUndo: canUndo, canRedo: canRedo)
    }

    private func makeButton(for key: CodeEditorKey) -> UIButton {
        var configuration = UIButton.Configuration.gray()
        configuration.cornerStyle = .medium
        configuration.baseForegroundColor = .label
        configuration.contentInsets = NSDirectionalEdgeInsets(top: 6, leading: 10, bottom: 6, trailing: 10)
        if let systemImage = key.systemImage {
            configuration.image = UIImage(systemName: systemImage)
            configuration.preferredSymbolConfigurationForImage = UIImage.SymbolConfiguration(textStyle: .body)
        } else {
            if case .symbol(let symbol) = key {
                configuration.title = symbol
            } else {
                configuration.title = labels.toggleComment
            }
            let monospaced = key != .toggleComment
            configuration.titleTextAttributesTransformer = UIConfigurationTextAttributesTransformer { attributes in
                var attributes = attributes
                attributes.font = monospaced
                    ? UIFontMetrics(forTextStyle: .body).scaledFont(for: .monospacedSystemFont(ofSize: 17, weight: .medium))
                    : UIFont.preferredFont(forTextStyle: .body)
                return attributes
            }
        }
        let button = UIButton(configuration: configuration)
        button.accessibilityLabel = labels.label(for: key)
        button.addAction(UIAction { [weak self] _ in self?.press(key) }, for: .touchUpInside)
        button.translatesAutoresizingMaskIntoConstraints = false
        NSLayoutConstraint.activate([
            button.widthAnchor.constraint(greaterThanOrEqualToConstant: Self.minimumTarget),
            button.heightAnchor.constraint(greaterThanOrEqualToConstant: Self.minimumTarget),
        ])
        return button
    }
}
