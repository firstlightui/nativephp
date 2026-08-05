import SwiftUI

enum ConfirmationDialogRendererEvent: Equatable {
    case press(callbackId: Int, nodeId: Int)
    case dismiss(callbackId: Int, nodeId: Int)

    var wireName: String { "PRESS" }
}

struct ConfirmationDialogRendererEvents {
    let send: @MainActor (ConfirmationDialogRendererEvent) -> Void

    static let native = ConfirmationDialogRendererEvents { event in
        switch event {
        case let .press(callbackId, nodeId), let .dismiss(callbackId, nodeId):
            NativeUIBridge.sendPressEvent(callbackId, nodeId: nodeId)
        }
    }
}

struct ConfirmationDialogRendererConfiguration: Equatable {
    let nodeID: Int
    let visible: Bool
    let title: String
    let message: String
    let confirmLabel: String
    let cancelLabel: String
    let tone: FirstlightConfirmationDialogTone
    let confirmCallback: Int
    let dismissCallback: Int

    init(node: NativeUINode) {
        nodeID = node.id
        visible = node.props.getBool("visible")
        title = node.props.getString("title")
        message = node.props.getString("message")
        confirmLabel = node.props.getString("confirm_label", default: "Confirm")
        cancelLabel = node.props.getString("cancel_label", default: "Cancel")
        tone = FirstlightConfirmationDialogTone(
            rawValue: node.props.getString("tone", default: "default")
        ) ?? .default
        confirmCallback = node.onPress
        dismissCallback = node.props.getCallbackId("on_dismiss")
    }

    var canPresent: Bool {
        visible && confirmCallback != 0 && dismissCallback != 0
    }
}

struct ConfirmationDialogRendererState {
    private(set) var configuration: ConfirmationDialogRendererConfiguration
    private(set) var isPresented: Bool

    init(node: NativeUINode) {
        let configuration = ConfirmationDialogRendererConfiguration(node: node)
        self.configuration = configuration
        isPresented = configuration.canPresent
    }

    mutating func confirm() -> ConfirmationDialogRendererEvent? {
        guard isPresented, configuration.confirmCallback != 0 else { return nil }

        isPresented = false

        return .press(callbackId: configuration.confirmCallback, nodeId: configuration.nodeID)
    }

    mutating func dismiss() -> ConfirmationDialogRendererEvent? {
        guard isPresented, configuration.dismissCallback != 0 else { return nil }

        isPresented = false

        return .dismiss(callbackId: configuration.dismissCallback, nodeId: configuration.nodeID)
    }

    mutating func systemDismiss() -> ConfirmationDialogRendererEvent? {
        dismiss()
    }

    @discardableResult
    mutating func serverPublished(_ tree: NativeUITree) -> Bool {
        guard let node = Self.findNode(id: configuration.nodeID, in: tree.root) else {
            return false
        }

        return publish(ConfirmationDialogRendererConfiguration(node: node))
    }

    @discardableResult
    mutating func serverPublished(_ node: NativeUINode) -> Bool {
        guard node.id == configuration.nodeID else { return false }

        return publish(ConfirmationDialogRendererConfiguration(node: node))
    }

    private mutating func publish(_ published: ConfirmationDialogRendererConfiguration) -> Bool {
        guard published != configuration else { return false }

        let visibilityChanged = published.visible != configuration.visible
        configuration = published

        if !published.canPresent {
            isPresented = false
        } else if visibilityChanged {
            isPresented = true
        }

        return true
    }

    private static func findNode(id: Int, in node: NativeUINode) -> NativeUINode? {
        if node.id == id { return node }

        for child in node.children {
            if let found = findNode(id: id, in: child) { return found }
        }

        return nil
    }
}

struct ConfirmationDialogRenderer: View {
    @ObservedObject private var bridge = NativeUIBridge.shared
    @State private var state: ConfirmationDialogRendererState
    private let events: ConfirmationDialogRendererEvents

    init(node: NativeUINode, events: ConfirmationDialogRendererEvents = .native) {
        _state = State(initialValue: ConfirmationDialogRendererState(node: node))
        self.events = events
    }

    var body: some View {
        ConfirmationDialogControl(
            state: $state,
            onConfirm: {
                if let event = state.confirm() { events.send(event) }
            },
            onDismiss: {
                if let event = state.dismiss() { events.send(event) }
            }
        )
        .onReceive(bridge.$currentTree.compactMap { $0 }) { tree in
            state.serverPublished(tree)
        }
    }
}
