import SwiftUI

enum ChoiceGroupRendererEvent: Equatable {
    case press(callbackId: Int, nodeId: Int)

    var wireName: String { "PRESS" }
}

struct ChoiceGroupRendererEvents {
    let send: @MainActor (ChoiceGroupRendererEvent) -> Void

    static let native = ChoiceGroupRendererEvents { event in
        switch event {
        case let .press(callbackId, nodeId):
            NativeUIBridge.sendPressEvent(callbackId, nodeId: nodeId)
        }
    }
}

struct ChoiceGroupRendererConfiguration: Equatable {
    let nodeID: Int
    let optionValues: [String]
    let optionLabels: [String]
    let optionEnabled: [Bool]
    let optionCallbacks: [Int]
    let selectedValues: [String]
    let valueType: String
    let multiple: Bool
    let disabled: Bool
    let label: String
    let helper: String
    let error: String
    let required: Bool
    let accessibilityLabel: String
    let accessibilityHint: String

    init(node: NativeUINode) {
        let props = node.props
        let values = props.getStringList("option_values")

        nodeID = node.id
        optionValues = values
        optionLabels = props.getStringList("option_labels")
        optionEnabled = props.getStringList("option_enabled").map { $0 == "1" }
        optionCallbacks = props.getStringList("option_callbacks").map { Int($0) ?? 0 }
        selectedValues = props.getStringList("selected_values")
        valueType = props.getString("value_type")
        multiple = props.getBool("multiple")
        disabled = props.getBool("disabled") || values.isEmpty
        label = props.getString("label")
        helper = props.getString("helper")
        error = props.getString("error")
        required = props.getBool("required")
        let explicitAccessibilityLabel = props.getString("a11y_label")
        accessibilityLabel = explicitAccessibilityLabel.isEmpty
            ? props.getString("label")
            : explicitAccessibilityLabel
        accessibilityHint = props.getString("a11y_hint")
    }
}

struct ChoiceGroupRendererState: Equatable {
    var configuration: ChoiceGroupRendererConfiguration
    private(set) var isAwaitingPublication = false

    init(node: NativeUINode) {
        configuration = ChoiceGroupRendererConfiguration(node: node)
    }

    mutating func userSelected(_ index: Int) -> ChoiceGroupRendererEvent? {
        guard !isAwaitingPublication,
              !configuration.disabled,
              configuration.optionValues.indices.contains(index),
              configuration.optionEnabled.indices.contains(index),
              configuration.optionEnabled[index],
              configuration.optionCallbacks.indices.contains(index),
              configuration.optionCallbacks[index] != 0
        else {
            return nil
        }

        isAwaitingPublication = true

        return .press(
            callbackId: configuration.optionCallbacks[index],
            nodeId: configuration.nodeID
        )
    }

    @discardableResult
    mutating func serverPublished(_ tree: NativeUITree) -> Bool {
        guard let node = Self.findNode(id: configuration.nodeID, in: tree.root) else {
            return false
        }

        let previousSelection = configuration.selectedValues
        configuration = ChoiceGroupRendererConfiguration(node: node)
        isAwaitingPublication = false

        return previousSelection != configuration.selectedValues
    }

    private static func findNode(id: Int, in node: NativeUINode) -> NativeUINode? {
        if node.id == id { return node }

        for child in node.children {
            if let match = findNode(id: id, in: child) { return match }
        }

        return nil
    }
}

struct ChoiceGroupRenderer: View {
    @ObservedObject private var themeStore = NativeUITheme.shared
    @ObservedObject private var bridge = NativeUIBridge.shared
    @Environment(\.colorScheme) private var colorScheme
    @State private var rendererState: ChoiceGroupRendererState

    private let events: ChoiceGroupRendererEvents

    init(node: NativeUINode, events: ChoiceGroupRendererEvents = .native) {
        _rendererState = State(initialValue: ChoiceGroupRendererState(node: node))
        self.events = events
    }

    var body: some View {
        let configuration = rendererState.configuration
        let theme = themeStore.resolve(for: colorScheme)

        FirstlightChoiceGroupField(
            label: configuration.label,
            helper: configuration.helper,
            error: configuration.error,
            required: configuration.required,
            labels: configuration.optionLabels,
            values: configuration.optionValues,
            optionEnabled: configuration.optionEnabled,
            selectedValues: configuration.selectedValues,
            multiple: configuration.multiple,
            disabled: configuration.disabled,
            awaitingPublication: rendererState.isAwaitingPublication,
            tokens: FirstlightChoiceGroupTokens.from(theme: theme),
            accessibilityLabel: configuration.accessibilityLabel,
            accessibilityHint: configuration.accessibilityHint,
            onSelection: { index in
                guard let event = rendererState.userSelected(index) else { return }
                events.send(event)
            }
        )
        .onReceive(bridge.$currentTree.compactMap { $0 }) { tree in
            rendererState.serverPublished(tree)
        }
    }
}
