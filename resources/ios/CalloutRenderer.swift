import Combine
import SwiftUI

enum CalloutRendererEvent: Equatable {
    case press(callbackID: Int, nodeID: Int)
}

struct CalloutRendererConfiguration: Equatable {
    let nodeID: Int
    let message: String
    let tone: CalloutTone
    let actionLabel: String
    let accessibilityLabel: String
    let accessibilityHint: String
    let callbackID: Int

    init(node: NativeUINode) {
        nodeID = node.id
        message = node.props.getString("message")
        tone = CalloutTone(
            rawValue: node.props.getString("tone", default: "info")
        ) ?? .info
        actionLabel = node.props.getString("action_label")
        accessibilityLabel = node.props.getString("a11y_label")
        accessibilityHint = node.props.getString("a11y_hint")
        callbackID = node.onPress
    }

    var hasAction: Bool {
        !actionLabel.isEmpty && callbackID != 0
    }

    var resolvedAccessibilityLabel: String {
        firstlightCalloutAccessibilityLabel(
            message: message,
            tone: tone,
            explicit: accessibilityLabel
        )
    }

    func pressEvent() -> CalloutRendererEvent? {
        guard hasAction else { return nil }

        return .press(callbackID: callbackID, nodeID: nodeID)
    }
}

struct CalloutRendererState {
    private(set) var configuration: CalloutRendererConfiguration

    init(node: NativeUINode) {
        configuration = CalloutRendererConfiguration(node: node)
    }

    @discardableResult
    mutating func serverPublished(_ tree: NativeUITree) -> Bool {
        guard let node = calloutNode(id: configuration.nodeID, in: tree.root) else {
            return false
        }

        let published = CalloutRendererConfiguration(node: node)
        guard published != configuration else { return false }

        configuration = published
        return true
    }
}

struct CalloutRenderer: View {
    let node: NativeUINode

    @ObservedObject private var themeStore = NativeUITheme.shared
    @Environment(\.colorScheme) private var colorScheme
    @State private var state: CalloutRendererState

    init(node: NativeUINode) {
        self.node = node
        _state = State(initialValue: CalloutRendererState(node: node))
    }

    var body: some View {
        let configuration = state.configuration

        FirstlightCalloutControl(
            configuration: configuration,
            tokens: themeStore.resolve(for: colorScheme),
            onPress: {
                guard case let .press(callbackID, nodeID) = configuration.pressEvent() else {
                    return
                }

                NativeUIBridge.sendPressEvent(callbackID, nodeId: nodeID)
            }
        )
        .onReceive(NativeUIBridge.shared.$currentTree.compactMap { $0 }) { tree in
            state.serverPublished(tree)
        }
    }
}

private func calloutNode(id: Int, in node: NativeUINode) -> NativeUINode? {
    if node.id == id { return node }

    for child in node.children {
        if let found = calloutNode(id: id, in: child) {
            return found
        }
    }

    return nil
}
