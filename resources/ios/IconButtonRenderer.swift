import Combine
import SwiftUI

enum IconButtonRendererEvent: Equatable {
    case press(callbackID: Int, nodeID: Int)
}

struct IconButtonRendererConfiguration: Equatable {
    let nodeID: Int
    let icon: String
    let iconVariant: String
    let variant: FirstlightIconButtonVariant
    let size: FirstlightIconButtonSize
    let disabled: Bool
    let loading: Bool
    let accessibilityLabel: String
    let accessibilityHint: String
    let callbackID: Int

    init(node: NativeUINode) {
        nodeID = node.id
        icon = node.props.getString("icon")
        iconVariant = node.props.getString("icon_variant")
        variant = FirstlightIconButtonVariant(
            rawValue: node.props.getString("variant", default: "primary")
        ) ?? .primary
        size = FirstlightIconButtonSize(
            rawValue: node.props.getString("size", default: "md")
        ) ?? .medium
        disabled = node.props.getBool("disabled")
        loading = node.props.getBool("loading")
        accessibilityLabel = node.props.getString("a11y_label")
        accessibilityHint = node.props.getString("a11y_hint")
        callbackID = node.onPress
    }

    var isEnabled: Bool {
        !disabled && !loading && callbackID != 0
    }

    func pressEvent() -> IconButtonRendererEvent? {
        guard isEnabled else { return nil }

        return .press(callbackID: callbackID, nodeID: nodeID)
    }
}

struct IconButtonRendererState {
    private(set) var configuration: IconButtonRendererConfiguration

    init(node: NativeUINode) {
        configuration = IconButtonRendererConfiguration(node: node)
    }

    @discardableResult
    mutating func serverPublished(_ tree: NativeUITree) -> Bool {
        guard let node = iconButtonNode(id: configuration.nodeID, in: tree.root) else {
            return false
        }

        let published = IconButtonRendererConfiguration(node: node)
        guard published != configuration else { return false }

        configuration = published
        return true
    }
}

struct IconButtonRenderer: View {
    let node: NativeUINode

    @ObservedObject private var themeStore = NativeUITheme.shared
    @Environment(\.colorScheme) private var colorScheme
    @State private var state: IconButtonRendererState

    init(node: NativeUINode) {
        self.node = node
        _state = State(initialValue: IconButtonRendererState(node: node))
    }

    var body: some View {
        let configuration = state.configuration

        FirstlightIconButtonControl(
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

private func iconButtonNode(id: Int, in node: NativeUINode) -> NativeUINode? {
    if node.id == id { return node }

    for child in node.children {
        if let found = iconButtonNode(id: id, in: child) {
            return found
        }
    }

    return nil
}
