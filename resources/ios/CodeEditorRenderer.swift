import SwiftUI

struct CodeEditorRendererConfiguration {
    let nodeID: Int
    let value: String
    let label: String
    let helper: String
    let error: String
    let required: Bool
    let disabled: Bool
    let readOnly: Bool
    let language: CodeEditorLanguage
    let lineNumbers: Bool
    let wrap: Bool
    let indent: Int
    let follow: Bool
    let minLines: Int
    let maxLines: Int
    let syncMode: String
    let debounceMilliseconds: Int
    let accessibilityLabel: String
    let accessibilityHint: String
    let keyLabels: CodeEditorKeyLabels
    let onChangeCallback: Int

    var editable: Bool { !disabled && !readOnly }

    init(node: NativeUINode) {
        let props = node.props
        nodeID = node.id
        value = props.getString("value")
        label = props.getString("label")
        helper = props.getString("helper")
        error = props.getString("error")
        required = props.getBool("required")
        disabled = props.getBool("disabled")
        readOnly = props.getBool("read_only")
        language = CodeEditorLanguage.from(props.getString("language", default: "plain"))
        lineNumbers = props.getBool("line_numbers", default: true)
        wrap = props.getBool("wrap", default: true)
        indent = props.getInt("indent", default: 2) == 4 ? 4 : 2
        follow = props.getBool("follow")
        minLines = max(1, props.getInt("min_lines", default: 6))
        maxLines = max(minLines, props.getInt("max_lines", default: 20))
        syncMode = props.getString("sync_mode", default: "live")
        debounceMilliseconds = max(50, props.getInt("debounce_ms", default: 300))
        let explicitLabel = props.getString("a11y_label")
        accessibilityLabel = explicitLabel.isEmpty ? label : explicitLabel
        accessibilityHint = props.getString("a11y_hint")
        keyLabels = CodeEditorKeyLabels(props: props)
        onChangeCallback = props.getCallbackId("on_change")
    }
}

struct CodeEditorRendererEvent: Equatable {
    let callbackId: Int
    let nodeId: Int
    let value: String
    let wireName = "TEXT_CHANGE"
}

struct CodeEditorRendererEvents {
    let send: @MainActor (CodeEditorRendererEvent) -> Void

    static let native = CodeEditorRendererEvents { event in
        NativeUIBridge.sendTextChangeEvent(event.callbackId, nodeId: event.nodeId, text: event.value)
    }
}

struct CodeEditorRendererState {
    var configuration: CodeEditorRendererConfiguration
    var draft: String
    var lastCommitted: String
    var pendingServerValue: String?
    var isFocused = false
    /// Increments whenever the server value replaces the draft. The control replaces its buffer and
    /// clears undo only when this changes, so focused acknowledgements never reach the text view.
    private(set) var replacementRevision = 0

    init(node: NativeUINode) {
        configuration = CodeEditorRendererConfiguration(node: node)
        draft = configuration.value
        lastCommitted = configuration.value
        pendingServerValue = nil
    }

    mutating func userChanged(_ value: String) -> CodeEditorRendererEvent? {
        guard configuration.editable else { return nil }
        draft = value
        return configuration.syncMode == "live" ? commitIfNeeded() : nil
    }

    mutating func focusChanged(_ focused: Bool) -> CodeEditorRendererEvent? {
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

    mutating func flush() -> CodeEditorRendererEvent? {
        commitIfNeeded()
    }

    mutating func serverPublished(_ tree: NativeUITree) {
        guard let node = Self.findNode(id: configuration.nodeID, in: tree.root) else { return }
        let published = CodeEditorRendererConfiguration(node: node)
        configuration = published

        if isFocused {
            pendingServerValue = published.value == lastCommitted ? nil : published.value
        } else {
            replace(with: published.value)
        }
    }

    private mutating func commitIfNeeded() -> CodeEditorRendererEvent? {
        guard draft != lastCommitted else { return nil }
        lastCommitted = draft
        guard configuration.onChangeCallback != 0 else { return nil }
        return CodeEditorRendererEvent(
            callbackId: configuration.onChangeCallback,
            nodeId: configuration.nodeID,
            value: draft
        )
    }

    private mutating func applyPendingServerValue() {
        guard let pendingServerValue else { return }
        self.pendingServerValue = nil
        replace(with: pendingServerValue)
    }

    private mutating func replace(with value: String) {
        if value != draft { replacementRevision += 1 }
        draft = value
        lastCommitted = value
        pendingServerValue = nil
    }

    private static func findNode(id: Int, in node: NativeUINode) -> NativeUINode? {
        if node.id == id { return node }
        for child in node.children {
            if let found = findNode(id: id, in: child) { return found }
        }
        return nil
    }
}

struct CodeEditorRenderer: View {
    private let validationNodeID: Int
    @ObservedObject private var themeStore = NativeUITheme.shared
    @ObservedObject private var bridge = NativeUIBridge.shared
    @Environment(\.colorScheme) private var colorScheme
    @State private var state: CodeEditorRendererState
    private let events: CodeEditorRendererEvents

    init(node: NativeUINode, events: CodeEditorRendererEvents = .native) {
        validationNodeID = node.id
        _state = State(initialValue: CodeEditorRendererState(node: node))
        self.events = events
    }

    var body: some View {
        FirstlightCodeEditorField(
            configuration: state.configuration,
            text: Binding(
                get: { state.draft },
                set: { value in
                    if let event = state.userChanged(value) { events.send(event) }
                }
            ),
            replacementRevision: state.replacementRevision,
            onFocusChanged: { focused in
                if let event = state.focusChanged(focused) { events.send(event) }
            },
            tokens: themeStore.resolve(for: colorScheme)
        )
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
}
