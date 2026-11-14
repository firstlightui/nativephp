import Combine
import SwiftUI

struct ToolbarRendererConfiguration: Equatable {
    let nodeID: Int
    let items: [ToolbarItemConfiguration]
    let primary: ToolbarItemConfiguration?

    init(node: NativeUINode) {
        nodeID = node.id
        let all = node.children
            .filter { $0.type == "firstlight.toolbar-item" }
            .map(ToolbarItemConfiguration.init(node:))
        items = all.filter { !$0.primary }
        primary = all.first(where: \.primary)
    }
}

/// Draws nothing in place. Its items join the bottom bar of the navigation
/// stack the screen is shown in, so the system lays them out: on iOS 26 a
/// Liquid Glass group with the primary action in its own glass, before that
/// the classic bottom toolbar.
struct ToolbarRenderer: View {
    let node: NativeUINode

    @State private var configuration: ToolbarRendererConfiguration

    init(node: NativeUINode) {
        self.node = node
        _configuration = State(initialValue: ToolbarRendererConfiguration(node: node))
    }

    var body: some View {
        Color.clear
            .frame(width: 0, height: 0)
            .accessibilityHidden(true)
            .modifier(FirstlightBottomToolbar(configuration: configuration, onPress: Self.press))
            .onReceive(NativeUIBridge.shared.$currentTree.compactMap { $0 }) { tree in
                guard let published = toolbarNode(id: configuration.nodeID, in: tree.root) else { return }
                let next = ToolbarRendererConfiguration(node: published)
                if next != configuration { configuration = next }
            }
    }

    private static func press(_ callbackID: Int, _ item: ToolbarItemConfiguration) {
        guard callbackID != 0 else { return }
        if let haptic = item.haptic { FirstlightHaptics.shared.play(haptic) }
        KeyboardFocusPolicy.dispatchPress {
            NativeUIBridge.sendPressEvent(callbackID, nodeId: item.id)
        }
    }
}

private func toolbarNode(id: Int, in node: NativeUINode) -> NativeUINode? {
    if node.id == id { return node }
    for child in node.children {
        if let found = toolbarNode(id: id, in: child) { return found }
    }
    return nil
}
