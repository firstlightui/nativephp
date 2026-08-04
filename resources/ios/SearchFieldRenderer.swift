import SwiftUI

enum SearchFieldRendererEvent: Equatable {
    case change(callbackId: Int, nodeId: Int, value: String)
    case submit(callbackId: Int, nodeId: Int, value: String)

    var wireName: String {
        switch self {
        case .change: "TEXT_CHANGE"
        case .submit: "SUBMIT"
        }
    }
}

struct SearchFieldRendererEvents {
    let send: @MainActor (SearchFieldRendererEvent) -> Void

    static let native = SearchFieldRendererEvents { event in
        switch event {
        case let .change(callbackId, nodeId, value):
            NativeUIBridge.sendTextChangeEvent(callbackId, nodeId: nodeId, text: value)
        case let .submit(callbackId, nodeId, value):
            NativeUIBridge.sendSubmitEvent(callbackId, nodeId: nodeId, text: value)
        }
    }
}

struct SearchFieldRendererConfiguration {
    let nodeID: Int
    let value: String
    let placeholder: String
    let disabled: Bool
    let autocapitalize: String
    let autocorrectPolicy: String
    let syncMode: String
    let debounceMilliseconds: Int
    let accessibilityLabel: String
    let accessibilityHint: String
    let onChangeCallback: Int
    let onSubmitCallback: Int

    init(node: NativeUINode) {
        let props = node.props
        nodeID = node.id
        value = props.getString("value")
        placeholder = props.getString("placeholder")
        disabled = props.getBool("disabled")
        autocapitalize = props.getString("autocapitalize")
        autocorrectPolicy = props.getString("autocorrect_policy", default: "default")
        syncMode = props.getString("sync_mode", default: "live")
        debounceMilliseconds = max(50, props.getInt("debounce_ms", default: 300))
        accessibilityLabel = props.getString("a11y_label")
        accessibilityHint = props.getString("a11y_hint")
        onChangeCallback = props.getCallbackId("on_change")
        onSubmitCallback = props.getCallbackId("on_submit")
    }
}

struct SearchFieldRendererState {
    var configuration: SearchFieldRendererConfiguration
    var draft: String
    var lastCommitted: String
    var pendingServerValue: String?
    var isFocused = false

    init(node: NativeUINode) {
        configuration = SearchFieldRendererConfiguration(node: node)
        draft = configuration.value
        lastCommitted = configuration.value
        pendingServerValue = nil
    }

    mutating func userChanged(_ value: String) -> SearchFieldRendererEvent? {
        guard !configuration.disabled else { return nil }
        draft = value
        return configuration.syncMode == "live" ? commitIfNeeded() : nil
    }

    mutating func focusChanged(_ focused: Bool) -> SearchFieldRendererEvent? {
        isFocused = focused
        return focused ? nil : commitIfNeeded()
    }

    mutating func flush() -> SearchFieldRendererEvent? {
        commitIfNeeded()
    }

    mutating func submit() -> [SearchFieldRendererEvent] {
        guard !configuration.disabled else { return [] }
        var events: [SearchFieldRendererEvent] = []
        if let change = commitIfNeeded() { events.append(change) }
        if configuration.onSubmitCallback != 0 {
            events.append(.submit(
                callbackId: configuration.onSubmitCallback,
                nodeId: configuration.nodeID,
                value: draft
            ))
        }
        return events
    }

    mutating func clear() -> SearchFieldRendererEvent? {
        guard !configuration.disabled else { return nil }
        draft = ""
        return commitIfNeeded()
    }

    mutating func serverPublished(_ tree: NativeUITree) {
        guard let node = Self.findNode(id: configuration.nodeID, in: tree.root) else { return }
        let published = SearchFieldRendererConfiguration(node: node)
        configuration = published
        if isFocused {
            pendingServerValue = published.value == lastCommitted ? nil : published.value
        } else {
            draft = published.value
            lastCommitted = published.value
            pendingServerValue = nil
        }
    }

    private mutating func commitIfNeeded() -> SearchFieldRendererEvent? {
        guard draft != lastCommitted else { return nil }
        lastCommitted = draft
        guard configuration.onChangeCallback != 0 else { return nil }
        return .change(
            callbackId: configuration.onChangeCallback,
            nodeId: configuration.nodeID,
            value: draft
        )
    }

    private static func findNode(id: Int, in node: NativeUINode) -> NativeUINode? {
        if node.id == id { return node }
        for child in node.children {
            if let found = findNode(id: id, in: child) { return found }
        }
        return nil
    }
}

struct SearchFieldRenderer: View {
    @ObservedObject private var bridge = NativeUIBridge.shared
    @State private var state: SearchFieldRendererState
    private let events: SearchFieldRendererEvents

    init(node: NativeUINode, events: SearchFieldRendererEvents = .native) {
        _state = State(initialValue: SearchFieldRendererState(node: node))
        self.events = events
    }

    var body: some View {
        FirstlightSearchFieldControl(
            configuration: state.configuration,
            text: Binding(
                get: { state.draft },
                set: { value in
                    if let event = state.userChanged(value) { events.send(event) }
                }
            ),
            onClear: {
                if let event = state.clear() { events.send(event) }
            },
            onSubmit: {
                for event in state.submit() { events.send(event) }
            },
            onFocusChanged: { focused in
                if let event = state.focusChanged(focused) { events.send(event) }
            }
        )
        .frame(minHeight: 44)
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
