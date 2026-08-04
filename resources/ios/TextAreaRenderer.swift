import SwiftUI

struct TextAreaRendererConfiguration {
    let nodeID: Int
    let value: String
    let label: String
    let placeholder: String
    let helper: String
    let error: String
    let required: Bool
    let disabled: Bool
    let readOnly: Bool
    let minLines: Int
    let maxLines: Int
    let autocapitalize: String
    let autocorrectPolicy: String
    let syncMode: String
    let debounceMilliseconds: Int
    let accessibilityLabel: String
    let accessibilityHint: String
    let onChangeCallback: Int

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
        minLines = max(1, props.getInt("min_lines", default: 3))
        maxLines = max(minLines, props.getInt("max_lines", default: 8))
        autocapitalize = props.getString("autocapitalize")
        autocorrectPolicy = props.getString("autocorrect_policy", default: "default")
        syncMode = props.getString("sync_mode", default: "live")
        debounceMilliseconds = max(50, props.getInt("debounce_ms", default: 300))
        let explicitLabel = props.getString("a11y_label")
        accessibilityLabel = explicitLabel.isEmpty ? label : explicitLabel
        accessibilityHint = props.getString("a11y_hint")
        onChangeCallback = props.getCallbackId("on_change")
    }
}

struct TextAreaRendererEvent: Equatable {
    let callbackId: Int
    let nodeId: Int
    let value: String
    let wireName = "TEXT_CHANGE"
}

struct TextAreaRendererEvents {
    let send: @MainActor (TextAreaRendererEvent) -> Void

    static let native = TextAreaRendererEvents { event in
        NativeUIBridge.sendTextChangeEvent(event.callbackId, nodeId: event.nodeId, text: event.value)
    }
}

struct TextAreaRendererState {
    var configuration: TextAreaRendererConfiguration
    var draft: String
    var lastCommitted: String
    var pendingServerValue: String?
    var isFocused = false

    init(node: NativeUINode) {
        configuration = TextAreaRendererConfiguration(node: node)
        draft = configuration.value
        lastCommitted = configuration.value
        pendingServerValue = nil
    }

    mutating func userChanged(_ value: String) -> TextAreaRendererEvent? {
        guard !configuration.disabled, !configuration.readOnly else { return nil }
        draft = value
        return configuration.syncMode == "live" ? commitIfNeeded() : nil
    }

    mutating func focusChanged(_ focused: Bool) -> TextAreaRendererEvent? {
        if focused {
            isFocused = true
            return nil
        }

        let hadUncommittedEdit = draft != lastCommitted
        isFocused = false
        let event = commitIfNeeded()
        if !hadUncommittedEdit {
            applyPendingServerValue()
        }
        return event
    }

    mutating func flush() -> TextAreaRendererEvent? {
        commitIfNeeded()
    }

    mutating func serverPublished(_ tree: NativeUITree) {
        guard let node = Self.findNode(id: configuration.nodeID, in: tree.root) else { return }
        let published = TextAreaRendererConfiguration(node: node)
        configuration = published

        if isFocused {
            pendingServerValue = published.value == lastCommitted ? nil : published.value
        } else {
            draft = published.value
            lastCommitted = published.value
            pendingServerValue = nil
        }
    }

    private mutating func commitIfNeeded() -> TextAreaRendererEvent? {
        guard draft != lastCommitted else { return nil }
        lastCommitted = draft
        guard configuration.onChangeCallback != 0 else { return nil }
        return TextAreaRendererEvent(
            callbackId: configuration.onChangeCallback,
            nodeId: configuration.nodeID,
            value: draft,
        )
    }

    private mutating func applyPendingServerValue() {
        guard let pendingServerValue else { return }
        draft = pendingServerValue
        lastCommitted = pendingServerValue
        self.pendingServerValue = nil
    }

    private static func findNode(id: Int, in node: NativeUINode) -> NativeUINode? {
        if node.id == id { return node }
        for child in node.children {
            if let found = findNode(id: id, in: child) { return found }
        }
        return nil
    }
}

struct TextAreaRenderer: View {
    @ObservedObject private var themeStore = NativeUITheme.shared
    @ObservedObject private var bridge = NativeUIBridge.shared
    @Environment(\.colorScheme) private var colorScheme
    @FocusState private var focused: Bool
    @State private var state: TextAreaRendererState
    private let events: TextAreaRendererEvents

    init(node: NativeUINode, events: TextAreaRendererEvents = .native) {
        _state = State(initialValue: TextAreaRendererState(node: node))
        self.events = events
    }

    var body: some View {
        FirstlightTextAreaControl(
            configuration: state.configuration,
            text: $state.draft,
            isFocused: $focused,
            tokens: themeStore.resolve(for: colorScheme),
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
