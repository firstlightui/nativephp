import SwiftUI
import UIKit

struct TabsRendererEvent: Equatable {
    let callbackId: Int
    let nodeId: Int
    let value: String

    var wireName: String { "SELECT_CHANGE" }
}

struct TabsRendererEvents {
    let send: @MainActor (TabsRendererEvent) -> Void

    static let native = TabsRendererEvents { event in
        NativeUIBridge.sendSelectChangeEvent(event.callbackId, nodeId: event.nodeId, value: event.value)
    }
}

struct TabsRendererConfiguration: Equatable {
    let nodeID: Int
    let values: [String]
    let labels: [String]
    let badges: [String]
    let accessibilityLabels: [String]
    let enabled: [Bool]
    let hasSelection: Bool
    let selectedValue: String
    let onChangeCallback: Int
    let disabled: Bool
    let groupAccessibilityLabel: String
    let groupAccessibilityHint: String

    init(node: NativeUINode) {
        let props = node.props
        let values = props.getStringList("tab_values")

        self.nodeID = node.id
        self.values = values
        self.labels = props.getStringList("tab_labels")
        self.badges = props.getStringList("tab_badges")
        self.accessibilityLabels = props.getStringList("tab_a11y_labels")
        self.enabled = props.getStringList("tab_enabled").map { $0 == "1" }
        self.hasSelection = props.getBool("has_selection")
        self.selectedValue = props.getString("selected_value")
        self.onChangeCallback = props.getCallbackId("on_change")
        self.disabled = props.getBool("disabled") || values.isEmpty
        self.groupAccessibilityLabel = props.getString("a11y_label")
        self.groupAccessibilityHint = props.getString("a11y_hint")
    }

    /// Index of PHP's accepted value, or nil for no selection or an unknown value.
    var selectedIndex: Int? {
        hasSelection ? values.firstIndex(of: selectedValue) : nil
    }
}

struct TabsRendererState: Equatable {
    var configuration: TabsRendererConfiguration

    init(node: NativeUINode) {
        configuration = TabsRendererConfiguration(node: node)
    }

    /// A tap proposes a value without moving the selection.
    func userSelected(_ index: Int) -> TabsRendererEvent? {
        guard !configuration.disabled,
              configuration.values.indices.contains(index),
              configuration.enabled.indices.contains(index),
              configuration.enabled[index],
              configuration.selectedIndex != index,
              configuration.onChangeCallback != 0
        else {
            return nil
        }

        return TabsRendererEvent(
            callbackId: configuration.onChangeCallback,
            nodeId: configuration.nodeID,
            value: configuration.values[index]
        )
    }

    @discardableResult
    mutating func serverPublished(_ tree: NativeUITree) -> Bool {
        guard let node = Self.findNode(id: configuration.nodeID, in: tree.root) else {
            return false
        }

        let previous = configuration.selectedIndex
        configuration = TabsRendererConfiguration(node: node)

        return previous != configuration.selectedIndex
    }

    private static func findNode(id: Int, in node: NativeUINode) -> NativeUINode? {
        if node.id == id { return node }

        for child in node.children {
            if let match = findNode(id: id, in: child) { return match }
        }

        return nil
    }
}

struct TabsRenderer: View {
    @ObservedObject private var themeStore = NativeUITheme.shared
    @ObservedObject private var bridge = NativeUIBridge.shared
    @Environment(\.colorScheme) private var colorScheme
    @State private var rendererState: TabsRendererState

    private let events: TabsRendererEvents

    init(node: NativeUINode, events: TabsRendererEvents = .native) {
        _rendererState = State(initialValue: TabsRendererState(node: node))
        self.events = events
    }

    var body: some View {
        let configuration = rendererState.configuration
        let theme = themeStore.resolve(for: colorScheme)

        FirstlightTabsControl(
            labels: configuration.labels,
            badges: configuration.badges,
            accessibilityLabels: configuration.accessibilityLabels,
            tabEnabled: configuration.enabled,
            selectedIndex: configuration.selectedIndex,
            disabled: configuration.disabled,
            tokens: FirstlightSegmentedTokens.from(
                theme: theme,
                traits: UITraitCollection(userInterfaceStyle: colorScheme == .dark ? .dark : .light)
            ),
            groupAccessibilityLabel: configuration.groupAccessibilityLabel,
            groupAccessibilityHint: configuration.groupAccessibilityHint,
            onSelection: { index in
                guard let event = rendererState.userSelected(index) else { return }
                events.send(event)
            }
        )
        .onReceive(bridge.$currentTree.compactMap { $0 }) { tree in
            // Reconcile from every PHP publication, including a rejection that
            // republishes the pre-tap value.
            rendererState.serverPublished(tree)
        }
    }
}
