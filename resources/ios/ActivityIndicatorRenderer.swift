import Combine
import SwiftUI

struct ActivityIndicatorRendererConfiguration: Equatable {
    let size: ActivityIndicatorSize
    let accessibilityLabel: String

    init(node: NativeUINode) {
        size = ActivityIndicatorSize(
            rawValue: node.props.getString("size", default: "md")
        ) ?? .medium
        accessibilityLabel = node.props.getString("a11y_label")
    }
}

struct ActivityIndicatorAnnouncementState {
    private var hasAnnounced = false

    mutating func consume(label: String) -> String? {
        guard !hasAnnounced, !label.isEmpty else { return nil }

        hasAnnounced = true
        return label
    }
}

struct ActivityIndicatorRendererState {
    private(set) var configuration: ActivityIndicatorRendererConfiguration
    private let nodeID: Int
    private var announcement = ActivityIndicatorAnnouncementState()

    init(node: NativeUINode) {
        nodeID = node.id
        configuration = ActivityIndicatorRendererConfiguration(node: node)
    }

    mutating func consumeAnnouncement() -> String? {
        announcement.consume(label: configuration.accessibilityLabel)
    }

    @discardableResult
    mutating func serverPublished(_ tree: NativeUITree) -> Bool {
        guard let node = activityIndicatorNode(id: nodeID, in: tree.root) else {
            return false
        }

        let published = ActivityIndicatorRendererConfiguration(node: node)
        guard published != configuration else { return false }

        configuration = published
        return true
    }
}

struct ActivityIndicatorRenderer: View {
    let node: NativeUINode

    @ObservedObject private var themeStore = NativeUITheme.shared
    @Environment(\.colorScheme) private var colorScheme
    @State private var state: ActivityIndicatorRendererState

    init(node: NativeUINode) {
        self.node = node
        _state = State(initialValue: ActivityIndicatorRendererState(node: node))
    }

    var body: some View {
        let configuration = state.configuration

        FirstlightActivityIndicatorControl(
            size: configuration.size,
            accessibilityLabel: configuration.accessibilityLabel,
            tint: themeStore.resolve(for: colorScheme).primary
        )
        .onAppear {
            if let label = state.consumeAnnouncement() {
                AccessibilityNotification.Announcement(label).post()
            }
        }
        .onReceive(NativeUIBridge.shared.$currentTree.compactMap { $0 }) { tree in
            state.serverPublished(tree)
        }
    }
}

private func activityIndicatorNode(id: Int, in node: NativeUINode) -> NativeUINode? {
    if node.id == id { return node }

    for child in node.children {
        if let found = activityIndicatorNode(id: id, in: child) {
            return found
        }
    }

    return nil
}
