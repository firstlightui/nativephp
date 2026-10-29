import SwiftUI

enum TextFieldRendererEvent: Equatable {
    case change(callbackId: Int, nodeId: Int, value: String)
    case submit(callbackId: Int, nodeId: Int, value: String)
    case press(callbackId: Int, nodeId: Int)

    var wireName: String {
        switch self {
        case .change: "TEXT_CHANGE"
        case .submit: "SUBMIT"
        case .press: "PRESS"
        }
    }
}

struct TextFieldRendererEvents {
    let send: @MainActor (TextFieldRendererEvent) -> Void

    static let native = TextFieldRendererEvents { event in
        switch event {
        case let .change(callbackId, nodeId, value):
            NativeUIBridge.sendTextChangeEvent(callbackId, nodeId: nodeId, text: value)
        case let .submit(callbackId, nodeId, value):
            NativeUIBridge.sendSubmitEvent(callbackId, nodeId: nodeId, text: value)
        case let .press(callbackId, nodeId):
            NativeUIBridge.sendPressEvent(callbackId, nodeId: nodeId)
        }
    }
}

struct TextFieldRendererConfiguration {
    let nodeID: Int
    let value: String
    let label: String
    let placeholder: String
    let helper: String
    let helperTone: String
    let warningColors: FirstlightWarningColors
    let error: String
    let required: Bool
    let disabled: Bool
    let readOnly: Bool
    let secure: Bool
    let mask: FirstlightTextMask?
    let keyboard: String
    let contentType: String
    let autocapitalize: String
    let autocorrectPolicy: String
    let submitLabel: String
    let doneLabel: String
    let leadingIcon: String
    let trailingIcon: String
    let trailingAccessibilityLabel: String
    let clearable: Bool
    let clearA11yLabel: String
    let revealable: Bool
    let showPasswordA11yLabel: String
    let hidePasswordA11yLabel: String
    let syncMode: String
    let debounceMilliseconds: Int
    let accessibilityLabel: String
    let accessibilityHint: String
    let onChangeCallback: Int
    let onSubmitCallback: Int
    let onPressCallback: Int

    init(node: NativeUINode) {
        let props = node.props
        nodeID = node.id
        value = props.getString("value")
        label = props.getString("label")
        placeholder = props.getString("placeholder")
        helper = props.getString("helper")
        helperTone = props.getString("helper_tone", default: "neutral")
        warningColors = FirstlightWarningColors(node: node)
        error = props.getString("error")
        required = props.getBool("required")
        disabled = props.getBool("disabled")
        readOnly = props.getBool("read_only")
        secure = props.getBool("secure")
        mask = FirstlightTextMask(pattern: props.getString("mask"))
        keyboard = props.getString("keyboard", default: "text")
        contentType = props.getString("content_type")
        autocapitalize = props.getString("autocapitalize")
        autocorrectPolicy = props.getString("autocorrect_policy", default: "default")
        submitLabel = props.getString("submit_label")
        doneLabel = props.getString("done_label", default: "Done")
        leadingIcon = props.getString("leading_icon")
        trailingIcon = props.getString("trailing_icon")
        trailingAccessibilityLabel = props.getString("trailing_a11y_label")
        clearable = props.getBool("clearable")
        clearA11yLabel = props.getString("clear_a11y_label", default: "Clear text")
        revealable = props.getBool("revealable")
        showPasswordA11yLabel = props.getString("show_password_a11y_label", default: "Show password")
        hidePasswordA11yLabel = props.getString("hide_password_a11y_label", default: "Hide password")
        syncMode = props.getString("sync_mode", default: "live")
        debounceMilliseconds = max(50, props.getInt("debounce_ms", default: 300))
        let explicitLabel = props.getString("a11y_label")
        accessibilityLabel = explicitLabel.isEmpty ? label : explicitLabel
        accessibilityHint = props.getString("a11y_hint")
        onChangeCallback = props.getCallbackId("on_change")
        onSubmitCallback = props.getCallbackId("on_submit")
        onPressCallback = node.onPress
    }
}

struct TextFieldRendererState {
    var configuration: TextFieldRendererConfiguration
    /// The value PHP receives. With a mask it holds only slot characters.
    var draft: String { didSet { if draft != oldValue { syncDisplay() } } }
    /// The text shown in the native field: `draft` formatted by the mask, or `draft` itself.
    var display: String
    var lastCommitted: String
    var pendingServerValue: String?
    var isFocused = false
    var isRevealed = false

    init(node: NativeUINode) {
        configuration = TextFieldRendererConfiguration(node: node)
        let value = configuration.mask?.conform(configuration.value) ?? configuration.value
        draft = value
        display = configuration.mask?.format(value) ?? value
        lastCommitted = value
        pendingServerValue = nil
    }

    /// Applies a native replacement of a range of the masked display text and returns the event
    /// and the display caret. Rejected characters leave the draft unchanged.
    mutating func maskedEdit(range: Range<Int>, with inserted: String) -> (event: TextFieldRendererEvent?, caret: Int?) {
        guard let mask = configuration.mask, !configuration.disabled, !configuration.readOnly else { return (nil, nil) }
        let result = mask.edit(raw: draft, displayRange: range, inserted: inserted)
        return (userChanged(result.raw), result.caret)
    }

    /// Conforms text the native field changed without reporting a range, such as committed
    /// marked text or dictation.
    mutating func maskedTextReplaced(_ text: String) -> TextFieldRendererEvent? {
        defer { syncDisplay() }
        guard let mask = configuration.mask, !configuration.disabled, !configuration.readOnly else { return nil }
        return userChanged(mask.conform(text))
    }

    mutating func userChanged(_ value: String) -> TextFieldRendererEvent? {
        guard !configuration.disabled, !configuration.readOnly else { return nil }
        if value != draft { pendingServerValue = nil }
        draft = value
        guard configuration.syncMode == "live" else { return nil }
        return commitIfNeeded()
    }

    mutating func focusChanged(_ focused: Bool) -> TextFieldRendererEvent? {
        isFocused = focused
        guard !focused else { return nil }
        let edited = draft != lastCommitted
        let event = commitIfNeeded()
        if !edited, let pendingServerValue {
            draft = pendingServerValue
            lastCommitted = pendingServerValue
            syncDisplay()
        }
        pendingServerValue = nil
        return event
    }

    mutating func flush() -> TextFieldRendererEvent? { commitIfNeeded() }

    mutating func submit() -> [TextFieldRendererEvent] {
        var events: [TextFieldRendererEvent] = []
        if let change = commitIfNeeded() { events.append(change) }
        if configuration.onSubmitCallback != 0 {
            events.append(.submit(callbackId: configuration.onSubmitCallback, nodeId: configuration.nodeID, value: draft))
        }
        return events
    }

    mutating func clear() -> TextFieldRendererEvent? {
        guard configuration.clearable, !configuration.disabled, !configuration.readOnly else { return nil }
        pendingServerValue = nil
        draft = ""
        return commitIfNeeded()
    }

    mutating func toggleReveal() { if configuration.revealable { isRevealed.toggle() } }

    mutating func serverPublished(_ tree: NativeUITree) {
        guard let node = Self.findNode(id: configuration.nodeID, in: tree.root) else { return }
        let published = TextFieldRendererConfiguration(node: node)
        let maskChanged = published.mask != configuration.mask
        configuration = published
        // A value that does not fit the mask is shown conformed; the conformed value is
        // published only with the person's next edit.
        let value = published.mask?.conform(published.value) ?? published.value
        if isFocused {
            if value == lastCommitted { pendingServerValue = nil }
            else { pendingServerValue = value }
            if maskChanged { draft = published.mask?.conform(draft) ?? draft; syncDisplay() }
        } else {
            draft = value
            lastCommitted = value
            pendingServerValue = nil
            syncDisplay()
        }
    }

    private mutating func syncDisplay() {
        display = configuration.mask?.format(draft) ?? draft
    }

    private mutating func commitIfNeeded() -> TextFieldRendererEvent? {
        guard draft != lastCommitted else { return nil }
        lastCommitted = draft
        guard configuration.onChangeCallback != 0 else { return nil }
        return .change(callbackId: configuration.onChangeCallback, nodeId: configuration.nodeID, value: draft)
    }

    private static func findNode(id: Int, in node: NativeUINode) -> NativeUINode? {
        if node.id == id { return node }
        for child in node.children {
            if let found = findNode(id: id, in: child) { return found }
        }
        return nil
    }
}

struct TextFieldRenderer: View {
    private let validationNodeID: Int
    @ObservedObject private var themeStore = NativeUITheme.shared
    @ObservedObject private var bridge = NativeUIBridge.shared
    @Environment(\.colorScheme) private var colorScheme
    @FocusState private var focused: Bool
    @State private var state: TextFieldRendererState
    @State private var focusToken = UUID()
    @State private var maskedRefocus = 0
    private let events: TextFieldRendererEvents

    init(node: NativeUINode, events: TextFieldRendererEvents = .native) {
        validationNodeID = node.id
        _state = State(initialValue: TextFieldRendererState(node: node))
        self.events = events
    }

    var body: some View {
        FirstlightTextFieldControl(
            configuration: state.configuration,
            text: Binding(
                get: { state.draft },
                set: { value in
                    if let event = state.userChanged(value) { events.send(event) }
                }
            ),
            maskedText: state.display,
            maskedRefocus: maskedRefocus,
            revealed: $state.isRevealed,
            isFocused: $focused,
            tokens: themeStore.resolve(for: colorScheme),
            onClear: {
                if let event = state.clear() { events.send(event) }
                if state.configuration.mask != nil { maskedRefocus += 1 }
            },
            onTrailingPress: {
                let configuration = state.configuration
                if configuration.onPressCallback != 0 {
                    events.send(.press(callbackId: configuration.onPressCallback, nodeId: configuration.nodeID))
                }
            },
            onSubmit: {
                for event in state.submit() { events.send(event) }
            },
            onMaskedEdit: { range, inserted in
                let result = state.maskedEdit(range: range, with: inserted)
                if let event = result.event { events.send(event) }
                return (state.display, result.caret)
            },
            onMaskedTextReplaced: { text in
                if let event = state.maskedTextReplaced(text) { events.send(event) }
                return state.display
            },
            onMaskedFocusChanged: focusChanged
        )
        .modifier(FirstlightFocusedFieldScroll(nodeID: validationNodeID, focused: state.isFocused))
        .onChange(of: focused) { _, value in focusChanged(value) }
        .onDisappear { KeyboardFocusPolicy.fieldBlurred(focusToken) }
        .firstlightValidationAlert(nodeID: validationNodeID)
        .onReceive(bridge.$currentTree.compactMap { $0 }) { tree in
            state.serverPublished(tree)
        }
        .task(id: state.draft) {
            guard state.configuration.syncMode == "debounce" else { return }
            try? await Task.sleep(for: .milliseconds(state.configuration.debounceMilliseconds))
            if !Task.isCancelled, let event = state.flush() { events.send(event) }
        }
    }

    private func focusChanged(_ value: Bool) {
        KeyboardFocusPolicy.firstlightFieldFocusChanged(value, token: focusToken) {
            if let event = state.flush() { events.send(event) }
        }
        if let event = state.focusChanged(value) { events.send(event) }
    }
}
