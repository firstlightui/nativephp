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
    let error: String
    let required: Bool
    let disabled: Bool
    let readOnly: Bool
    let secure: Bool
    let keyboard: String
    let contentType: String
    let autocapitalize: String
    let autocorrectPolicy: String
    let submitLabel: String
    let leadingIcon: String
    let trailingIcon: String
    let trailingAccessibilityLabel: String
    let clearable: Bool
    let revealable: Bool
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
        error = props.getString("error")
        required = props.getBool("required")
        disabled = props.getBool("disabled")
        readOnly = props.getBool("read_only")
        secure = props.getBool("secure")
        keyboard = props.getString("keyboard", default: "text")
        contentType = props.getString("content_type")
        autocapitalize = props.getString("autocapitalize")
        autocorrectPolicy = props.getString("autocorrect_policy", default: "default")
        submitLabel = props.getString("submit_label")
        leadingIcon = props.getString("leading_icon")
        trailingIcon = props.getString("trailing_icon")
        trailingAccessibilityLabel = props.getString("trailing_a11y_label")
        clearable = props.getBool("clearable")
        revealable = props.getBool("revealable")
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
    var draft: String
    var lastCommitted: String
    var pendingServerValue: String?
    var isFocused = false
    var isRevealed = false

    init(node: NativeUINode) {
        configuration = TextFieldRendererConfiguration(node: node)
        draft = configuration.value
        lastCommitted = configuration.value
        pendingServerValue = nil
    }

    mutating func userChanged(_ value: String) -> TextFieldRendererEvent? {
        guard !configuration.disabled, !configuration.readOnly else { return nil }
        draft = value
        guard configuration.syncMode == "live" else { return nil }
        return commitIfNeeded()
    }

    mutating func focusChanged(_ focused: Bool) -> TextFieldRendererEvent? {
        isFocused = focused
        return focused ? nil : commitIfNeeded()
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
        draft = ""
        return commitIfNeeded()
    }

    mutating func toggleReveal() { if configuration.revealable { isRevealed.toggle() } }

    mutating func serverPublished(_ tree: NativeUITree) {
        guard let node = Self.findNode(id: configuration.nodeID, in: tree.root) else { return }
        let published = TextFieldRendererConfiguration(node: node)
        configuration = published
        if isFocused {
            if published.value == lastCommitted { pendingServerValue = nil }
            else { pendingServerValue = published.value }
        } else {
            draft = published.value
            lastCommitted = published.value
            pendingServerValue = nil
        }
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
    @ObservedObject private var themeStore = NativeUITheme.shared
    @ObservedObject private var bridge = NativeUIBridge.shared
    @Environment(\.colorScheme) private var colorScheme
    @FocusState private var focused: Bool
    @State private var state: TextFieldRendererState
    private let events: TextFieldRendererEvents

    init(node: NativeUINode, events: TextFieldRendererEvents = .native) {
        _state = State(initialValue: TextFieldRendererState(node: node))
        self.events = events
    }

    var body: some View {
        FirstlightTextFieldControl(
            configuration: state.configuration,
            text: $state.draft,
            revealed: $state.isRevealed,
            isFocused: $focused,
            tokens: themeStore.resolve(for: colorScheme),
            onClear: {
                if let event = state.clear() { events.send(event) }
            },
            onTrailingPress: {
                let configuration = state.configuration
                if configuration.onPressCallback != 0 {
                    events.send(.press(callbackId: configuration.onPressCallback, nodeId: configuration.nodeID))
                }
            },
            onSubmit: {
                for event in state.submit() { events.send(event) }
            }
        )
        .onChange(of: state.draft) { _, value in
            if let event = state.userChanged(value) { events.send(event) }
        }
        .onChange(of: focused) { _, value in
            if let event = state.focusChanged(value) { events.send(event) }
        }
        .onReceive(bridge.$currentTree.compactMap { $0 }) { tree in
            state.serverPublished(tree)
        }
        .task(id: state.draft) {
            guard state.configuration.syncMode == "debounce" else { return }
            try? await Task.sleep(for: .milliseconds(state.configuration.debounceMilliseconds))
            if !Task.isCancelled, let event = state.flush() { events.send(event) }
        }
    }
}
