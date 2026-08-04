import Combine
import SwiftUI
import UIKit

struct BadgeRendererConfiguration: Equatable {
    let label: String
    let tone: StatusLabelTone
    let accessibilityLabel: String
    let accessibilityHint: String

    init(node: NativeUINode) {
        label = node.props.getString("label")
        tone = StatusLabelTone(rawValue: node.props.getString("tone", default: "neutral")) ?? .neutral
        accessibilityLabel = node.props.getString("a11y_label").isEmpty ? label : node.props.getString("a11y_label")
        accessibilityHint = node.props.getString("a11y_hint")
    }
}

struct BadgeRendererState {
    private(set) var configuration: BadgeRendererConfiguration
    private let nodeID: Int

    init(node: NativeUINode) {
        nodeID = node.id
        configuration = BadgeRendererConfiguration(node: node)
    }

    @discardableResult mutating func serverPublished(_ tree: NativeUITree) -> Bool {
        guard let node = badgeNode(id: nodeID, in: tree.root) else { return false }
        let published = BadgeRendererConfiguration(node: node)
        guard published != configuration else { return false }
        configuration = published
        return true
    }
}

struct BadgeRenderer: View {
    let node: NativeUINode
    @ObservedObject private var themeStore = NativeUITheme.shared
    @Environment(\.colorScheme) private var colorScheme
    @State private var state: BadgeRendererState

    init(node: NativeUINode) {
        self.node = node
        _state = State(initialValue: BadgeRendererState(node: node))
    }

    var body: some View {
        let configuration = state.configuration
        FirstlightBadgeControl(
            label: configuration.label,
            tokens: FirstlightStatusLabelTokens.from(
                theme: themeStore.resolve(for: colorScheme), tone: configuration.tone,
                traits: UITraitCollection(userInterfaceStyle: colorScheme == .dark ? .dark : .light)
            ),
            accessibilityLabel: configuration.accessibilityLabel,
            accessibilityHint: configuration.accessibilityHint
        )
        .onReceive(NativeUIBridge.shared.$currentTree.compactMap { $0 }) { tree in state.serverPublished(tree) }
    }
}

private func badgeNode(id: Int, in node: NativeUINode) -> NativeUINode? {
    if node.id == id { return node }
    for child in node.children { if let found = badgeNode(id: id, in: child) { return found } }
    return nil
}
