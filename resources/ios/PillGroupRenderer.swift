import SwiftUI
import UIKit

enum PillGroupRendererEvent: Equatable {
    case press(callbackId: Int, nodeId: Int)

    var wireName: String { "PRESS" }
}

struct PillGroupRendererEvents {
    let send: @MainActor (PillGroupRendererEvent) -> Void

    static let native = PillGroupRendererEvents { event in
        switch event {
        case let .press(callbackId, nodeId):
            NativeUIBridge.sendPressEvent(callbackId, nodeId: nodeId)
        }
    }
}

struct PillGroupRendererConfiguration: Equatable {
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

        self.nodeID = node.id
        self.optionValues = values
        self.optionLabels = props.getStringList("option_labels")
        self.optionEnabled = props.getStringList("option_enabled").map { $0 == "1" }
        self.optionCallbacks = props.getStringList("option_callbacks").map { Int($0) ?? 0 }
        self.selectedValues = props.getStringList("selected_values")
        self.valueType = props.getString("value_type")
        self.multiple = props.getBool("multiple")
        self.disabled = props.getBool("disabled") || values.isEmpty
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
}

struct PillGroupRendererState: Equatable {
    var configuration: PillGroupRendererConfiguration
    private(set) var isAwaitingPublication = false

    init(node: NativeUINode) {
        configuration = PillGroupRendererConfiguration(node: node)
    }

    mutating func userSelected(_ index: Int) -> PillGroupRendererEvent? {
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
        configuration = PillGroupRendererConfiguration(node: node)
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

struct PillGroupRenderer: View {
    @ObservedObject private var themeStore = NativeUITheme.shared
    @ObservedObject private var bridge = NativeUIBridge.shared
    @Environment(\.colorScheme) private var colorScheme
    @State private var rendererState: PillGroupRendererState

    private let events: PillGroupRendererEvents

    init(node: NativeUINode, events: PillGroupRendererEvents = .native) {
        _rendererState = State(initialValue: PillGroupRendererState(node: node))
        self.events = events
    }

    var body: some View {
        let configuration = rendererState.configuration
        let theme = themeStore.resolve(for: colorScheme)

        FirstlightPillGroupField(
            label: configuration.label,
            helper: configuration.helper,
            error: configuration.error,
            required: configuration.required,
            labels: configuration.optionLabels,
            values: configuration.optionValues,
            optionEnabled: configuration.optionEnabled,
            selectedValues: configuration.selectedValues,
            disabled: configuration.disabled,
            awaitingPublication: rendererState.isAwaitingPublication,
            tokens: FirstlightPillGroupTokens.from(
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
            rendererState.serverPublished(tree)
        }
    }
}
