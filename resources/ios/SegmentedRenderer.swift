import SwiftUI
import UIKit

enum SegmentedRendererEvent: Equatable {
    case selectChange(callbackId: Int, nodeId: Int, value: String)
    case press(callbackId: Int, nodeId: Int)

    var wireName: String {
        switch self {
        case .selectChange: "SELECT_CHANGE"
        case .press: "PRESS"
        }
    }
}

@MainActor
struct SegmentedRendererEvents {
    let send: @MainActor (SegmentedRendererEvent) -> Void

    static let native = SegmentedRendererEvents { event in
        switch event {
        case let .selectChange(callbackId, nodeId, value):
            NativeUIBridge.sendSelectChangeEvent(callbackId, nodeId: nodeId, value: value)
        case let .press(callbackId, nodeId):
            NativeUIBridge.sendPressEvent(callbackId, nodeId: nodeId)
        }
    }
}

struct SegmentedRendererConfiguration: Equatable {
    let nodeID: Int
    let optionValues: [String]
    let optionLabels: [String]
    let optionEnabled: [Bool]
    let optionCallbacks: [Int]
    let valueType: String
    let hasSelection: Bool
    let selectedValue: String
    let onChangeCallback: Int
    let disabled: Bool
    let label: String
    let helper: String
    let error: String
    let required: Bool
    let accessibilityLabel: String
    let accessibilityHint: String

    init(node: NativeUINode) {
        let props = node.props
        let optionValues = props.getStringList("option_values")

        self.nodeID = node.id
        self.optionValues = optionValues
        self.optionLabels = props.getStringList("option_labels")
        self.optionEnabled = props.getStringList("option_enabled").map { $0 == "1" }
        self.optionCallbacks = props.getStringList("option_callbacks").map { Int($0) ?? 0 }
        self.valueType = props.getString("value_type")
        self.hasSelection = props.getBool("has_selection")
        self.selectedValue = props.getString("selected_value")
        self.onChangeCallback = props.getCallbackId("on_change")
        self.disabled = props.getBool("disabled") || optionValues.isEmpty
        self.label = props.getString("label")
        self.helper = props.getString("helper")
        self.error = props.getString("error")
        self.required = props.getBool("required")
        let explicitAccessibilityLabel = props.getString("a11y_label")
        self.accessibilityLabel = explicitAccessibilityLabel.isEmpty
            ? props.getString("label")
            : explicitAccessibilityLabel
        self.accessibilityHint = props.getString("a11y_hint")
    }

    var serverSelectedIndex: Int? {
        SegmentedSelectionState.selectedIndex(
            hasSelection: hasSelection,
            selectedValue: selectedValue,
            optionValues: optionValues
        )
    }
}

struct SegmentedRendererState: Equatable {
    var configuration: SegmentedRendererConfiguration
    var selectionState: SegmentedSelectionState

    init(node: NativeUINode) {
        let configuration = SegmentedRendererConfiguration(node: node)
        self.configuration = configuration
        self.selectionState = SegmentedSelectionState(
            selectedIndex: configuration.serverSelectedIndex
        )
    }

    mutating func userSelected(_ index: Int) -> SegmentedRendererEvent? {
        guard selectionState.select(
            index,
            optionEnabled: configuration.optionEnabled,
            disabled: configuration.disabled
        ) else {
            return nil
        }

        if configuration.valueType == "string",
           configuration.onChangeCallback != 0,
           configuration.optionValues.indices.contains(index) {
            return .selectChange(
                callbackId: configuration.onChangeCallback,
                nodeId: configuration.nodeID,
                value: configuration.optionValues[index]
            )
        }

        if configuration.valueType == "integer",
           configuration.optionCallbacks.indices.contains(index),
           configuration.optionCallbacks[index] != 0 {
            return .press(
                callbackId: configuration.optionCallbacks[index],
                nodeId: configuration.nodeID
            )
        }

        return nil
    }

    @discardableResult
    mutating func serverPublished(_ tree: NativeUITree) -> Bool {
        guard let node = Self.findNode(id: configuration.nodeID, in: tree.root) else {
            return false
        }

        let previousSelection = selectionState.selectedIndex
        let publishedConfiguration = SegmentedRendererConfiguration(node: node)
        configuration = publishedConfiguration
        selectionState.reconcile(
            hasSelection: publishedConfiguration.hasSelection,
            selectedValue: publishedConfiguration.selectedValue,
            optionValues: publishedConfiguration.optionValues
        )

        return previousSelection != selectionState.selectedIndex
    }

    private static func findNode(id: Int, in node: NativeUINode) -> NativeUINode? {
        if node.id == id { return node }

        for child in node.children {
            if let match = findNode(id: id, in: child) { return match }
        }

        return nil
    }
}

struct SegmentedRenderer: View {
    @ObservedObject private var themeStore = NativeUITheme.shared
    @ObservedObject private var bridge = NativeUIBridge.shared
    @Environment(\.colorScheme) private var colorScheme
    @State private var rendererState: SegmentedRendererState

    private let events: SegmentedRendererEvents

    init(node: NativeUINode, events: SegmentedRendererEvents = .native) {
        _rendererState = State(initialValue: SegmentedRendererState(node: node))
        self.events = events
    }

    var body: some View {
        let configuration = rendererState.configuration
        let theme = themeStore.resolve(for: colorScheme)

        FirstlightSegmentedField(
            label: configuration.label,
            helper: configuration.helper,
            error: configuration.error,
            required: configuration.required,
            labels: configuration.optionLabels,
            optionEnabled: configuration.optionEnabled,
            disabled: configuration.disabled,
            selectionState: $rendererState.selectionState,
            tokens: FirstlightSegmentedTokens.from(
                theme: theme,
                traits: UITraitCollection(
                    userInterfaceStyle: colorScheme == .dark ? .dark : .light
                )
            ),
            accessibilityLabel: configuration.accessibilityLabel,
            accessibilityHint: configuration.accessibilityHint,
            onSelection: { index in
                guard let event = rendererState.userSelected(index) else { return }
                events.send(event)
            }
        )
        .onReceive(bridge.$currentTree.compactMap { $0 }) { tree in
            // NativeUIBridge publishes every PHP tree response. Reconcile by
            // node ID from that publication, even when the server kept the
            // pre-tap value and diffing reused an equivalent node.
            rendererState.serverPublished(tree)
        }
    }
}
