import Combine
import SwiftUI

enum ListItemRendererEvent: Equatable {
    case press(callbackID: Int, nodeID: Int)
}

struct ListItemSwipeAction: Decodable, Equatable {
    let cb: Int
    let label: String
    let icon: String
    let icon_variant: String
    let role: String

    static func decode(_ json: String) -> [Self] {
        guard let data = json.data(using: .utf8),
              let actions = try? JSONDecoder().decode([Self].self, from: data) else { return [] }
        return actions.filter { $0.cb != 0 && !$0.label.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty }
    }
}

struct ListItemRendererConfiguration: Equatable {
    let nodeID: Int
    let headline: String
    let supporting: String
    let leadingType: FirstlightListItemLeadingType
    let leadingValue: String
    let leadingIconVariant: String
    let trailingType: FirstlightListItemTrailingType
    let trailingValue: String
    let trailingIconVariant: String
    let disabled: Bool
    let accessibilityLabel: String
    let accessibilityHint: String
    let callbackID: Int
    let leadingActions: [ListItemSwipeAction]
    let trailingActions: [ListItemSwipeAction]

    init(node: NativeUINode) {
        nodeID = node.id
        headline = node.props.getString("headline")
        supporting = node.props.getString("supporting")
        leadingType = FirstlightListItemLeadingType(
            wireValue: node.props.getString("leading_type")
        )
        leadingValue = node.props.getString("leading_value")
        leadingIconVariant = node.props.getString("leading_icon_variant")
        trailingType = FirstlightListItemTrailingType(
            wireValue: node.props.getString("trailing_type")
        )
        trailingValue = node.props.getString("trailing_value")
        trailingIconVariant = node.props.getString("trailing_icon_variant")
        disabled = node.props.getBool("disabled")
        accessibilityLabel = node.props.getString("a11y_label")
        accessibilityHint = node.props.getString("a11y_hint")
        callbackID = node.onPress
        leadingActions = ListItemSwipeAction.decode(node.props.getString("firstlight_leading_actions_json"))
        trailingActions = ListItemSwipeAction.decode(node.props.getString("firstlight_trailing_actions_json"))
    }

    var isEnabled: Bool {
        !disabled && callbackID != 0
    }

    var resolvedAccessibilityLabel: String {
        firstlightListItemAccessibilityLabel(
            headline: headline,
            supporting: supporting,
            explicit: accessibilityLabel
        )
    }

    func pressEvent() -> ListItemRendererEvent? {
        guard isEnabled else { return nil }

        return .press(callbackID: callbackID, nodeID: nodeID)
    }

    func actionEvent(callbackID: Int) -> ListItemRendererEvent? {
        guard isEnabled, (leadingActions + trailingActions).contains(where: { $0.cb == callbackID }) else { return nil }
        return .press(callbackID: callbackID, nodeID: nodeID)
    }
}

struct ListItemRendererState {
    private(set) var configuration: ListItemRendererConfiguration

    init(node: NativeUINode) {
        configuration = ListItemRendererConfiguration(node: node)
    }

    @discardableResult
    mutating func serverPublished(_ tree: NativeUITree) -> Bool {
        guard let node = listItemNode(id: configuration.nodeID, in: tree.root) else {
            return false
        }

        let published = ListItemRendererConfiguration(node: node)
        guard published != configuration else { return false }

        configuration = published
        return true
    }
}

struct ListItemRenderer: View {
    let node: NativeUINode

    @ObservedObject private var themeStore = NativeUITheme.shared
    @Environment(\.colorScheme) private var colorScheme
    @State private var state: ListItemRendererState

    init(node: NativeUINode) {
        self.node = node
        _state = State(initialValue: ListItemRendererState(node: node))
    }

    var body: some View {
        let configuration = state.configuration

        FirstlightListItemControl(
            configuration: configuration,
            tokens: themeStore.resolve(for: colorScheme),
            onPress: {
                guard case let .press(callbackID, nodeID) = configuration.pressEvent() else {
                    return
                }

                NativeUIBridge.sendPressEvent(callbackID, nodeId: nodeID)
            },
            onAction: { callback in
                guard case let .press(callbackID, nodeID) = state.configuration.actionEvent(callbackID: callback) else { return }
                NativeUIBridge.sendPressEvent(callbackID, nodeId: nodeID)
            }
        )
        .onReceive(NativeUIBridge.shared.$currentTree.compactMap { $0 }) { tree in
            state.serverPublished(tree)
        }
    }
}

private func listItemNode(id: Int, in node: NativeUINode) -> NativeUINode? {
    if node.id == id { return node }

    for child in node.children {
        if let found = listItemNode(id: id, in: child) {
            return found
        }
    }

    return nil
}
