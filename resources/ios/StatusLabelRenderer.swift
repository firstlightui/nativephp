import Combine
import SwiftUI
import UIKit

struct StatusLabelRendererConfiguration: Equatable {
    let label: String
    let tone: StatusLabelTone
    let accessibilityLabel: String
    let accessibilityHint: String

    init(node: NativeUINode) {
        let label = node.props.getString("label")

        self.label = label
        self.tone = StatusLabelTone(
            rawValue: node.props.getString("tone", default: "neutral")
        ) ?? .neutral
        self.accessibilityLabel = node.props.getString("a11y_label").isEmpty
            ? label
            : node.props.getString("a11y_label")
        self.accessibilityHint = node.props.getString("a11y_hint")
    }
}

struct StatusLabelRendererState {
    private(set) var configuration: StatusLabelRendererConfiguration
    private let nodeID: Int

    init(node: NativeUINode) {
        nodeID = node.id
        configuration = StatusLabelRendererConfiguration(node: node)
    }

    @discardableResult
    mutating func serverPublished(_ tree: NativeUITree) -> Bool {
        guard let node = statusLabelNode(id: nodeID, in: tree.root) else {
            return false
        }

        let published = StatusLabelRendererConfiguration(node: node)
        guard published != configuration else { return false }

        configuration = published
        return true
    }
}

struct StatusLabelRenderer: View {
    let node: NativeUINode

    @ObservedObject private var themeStore = NativeUITheme.shared
    @Environment(\.colorScheme) private var colorScheme
    @State private var state: StatusLabelRendererState

    init(node: NativeUINode) {
        self.node = node
        _state = State(initialValue: StatusLabelRendererState(node: node))
    }

    var body: some View {
        let configuration = state.configuration
        let traits = UITraitCollection(
            userInterfaceStyle: colorScheme == .dark ? .dark : .light
        )

        FirstlightStatusLabelControl(
            label: configuration.label,
            tokens: FirstlightStatusLabelTokens.from(
                theme: themeStore.resolve(for: colorScheme),
                tone: configuration.tone,
                traits: traits
            ),
            accessibilityLabel: configuration.accessibilityLabel,
            accessibilityHint: configuration.accessibilityHint
        )
        .onReceive(NativeUIBridge.shared.$currentTree.compactMap { $0 }) { tree in
            state.serverPublished(tree)
        }
    }
}

private func statusLabelNode(id: Int, in node: NativeUINode) -> NativeUINode? {
    if node.id == id { return node }

    for child in node.children {
        if let found = statusLabelNode(id: id, in: child) {
            return found
        }
    }

    return nil
}
