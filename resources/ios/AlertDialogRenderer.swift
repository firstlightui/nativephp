import SwiftUI

enum AlertDialogRendererEvent: Equatable {
    case dismiss(callbackId: Int, nodeId: Int)

    var wireName: String { "PRESS" }
}

struct AlertDialogRendererEvents {
    let send: @MainActor (AlertDialogRendererEvent) -> Void

    static let native = AlertDialogRendererEvents { event in
        switch event {
        case let .dismiss(callbackId, nodeId):
            NativeUIBridge.sendPressEvent(callbackId, nodeId: nodeId)
        }
    }
}

struct AlertDialogRendererConfiguration: Equatable {
    let nodeID: Int
    let visible: Bool
    let title: String
    let message: String
    let actionLabel: String
    let dismissCallback: Int

    init(node: NativeUINode) {
        nodeID = node.id
        visible = node.props.getBool("visible")
        title = node.props.getString("title")
        message = node.props.getString("message")
        actionLabel = node.props.getString("action_label", default: "OK")
        dismissCallback = node.props.getCallbackId("on_dismiss")
    }

    var canPresent: Bool {
        visible && dismissCallback != 0
    }
}

struct AlertDialogRendererState {
    private(set) var configuration: AlertDialogRendererConfiguration
    private(set) var isPresented: Bool

    init(node: NativeUINode) {
        let configuration = AlertDialogRendererConfiguration(node: node)
        self.configuration = configuration
        isPresented = configuration.canPresent
    }

    mutating func dismiss() -> AlertDialogRendererEvent? {
        guard isPresented, configuration.dismissCallback != 0 else { return nil }

        isPresented = false

        return .dismiss(callbackId: configuration.dismissCallback, nodeId: configuration.nodeID)
    }

    mutating func systemDismiss() -> AlertDialogRendererEvent? {
        dismiss()
    }

    @discardableResult
    mutating func serverPublished(_ tree: NativeUITree) -> Bool {
        guard let node = Self.findNode(id: configuration.nodeID, in: tree.root) else {
            return false
        }

        return publish(AlertDialogRendererConfiguration(node: node))
    }

    @discardableResult
    mutating func serverPublished(_ node: NativeUINode) -> Bool {
        guard node.id == configuration.nodeID else { return false }

        return publish(AlertDialogRendererConfiguration(node: node))
    }

    private mutating func publish(_ published: AlertDialogRendererConfiguration) -> Bool {
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

struct AlertDialogRenderer: View {
    @ObservedObject private var bridge = NativeUIBridge.shared
    @State private var state: AlertDialogRendererState
    private let events: AlertDialogRendererEvents

    init(node: NativeUINode, events: AlertDialogRendererEvents = .native) {
        _state = State(initialValue: AlertDialogRendererState(node: node))
        self.events = events
    }

    var body: some View {
        AlertDialogControl(
            state: $state,
            onDismiss: {
                if let event = state.dismiss() { events.send(event) }
            }
        )
        .onReceive(bridge.$currentTree.compactMap { $0 }) { tree in
            state.serverPublished(tree)
        }
    }
}
