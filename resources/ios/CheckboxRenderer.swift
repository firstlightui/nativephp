import SwiftUI

enum CheckboxRendererEvent: Equatable {
    case checkboxChange(callbackID: Int, nodeID: Int, value: Bool)
}

struct CheckboxRendererConfiguration: Equatable {
    let nodeID: Int
    let value: Bool
    let label: String
    let helper: String
    let error: String
    let required: Bool
    let disabled: Bool
    let accessibilityLabel: String
    let accessibilityHint: String
    let onChangeCallback: Int

    init(node: NativeUINode) {
        let props = node.props
        let explicitAccessibilityLabel = props.getString("a11y_label")

        nodeID = node.id
        value = props.getBool("value")
        label = props.getString("label")
        helper = props.getString("helper")
        error = props.getString("error")
        required = props.getBool("required")
        disabled = props.getBool("disabled")
        accessibilityLabel = explicitAccessibilityLabel.isEmpty ? label : explicitAccessibilityLabel
        accessibilityHint = props.getString("a11y_hint")
        onChangeCallback = props.getCallbackId("on_change")
    }

    var supportingText: String {
        error.isEmpty ? helper : error
    }
}

struct CheckboxRendererState: Equatable {
    var configuration: CheckboxRendererConfiguration
    private var pendingProposal: Bool?

    init(node: NativeUINode) {
        configuration = CheckboxRendererConfiguration(node: node)
        pendingProposal = nil
    }

    mutating func proposeChange() -> CheckboxRendererEvent? {
        guard !configuration.disabled,
              configuration.onChangeCallback != 0,
              pendingProposal == nil
        else {
            return nil
        }

        let proposal = !configuration.value
        pendingProposal = proposal

        return .checkboxChange(
            callbackID: configuration.onChangeCallback,
            nodeID: configuration.nodeID,
            value: proposal
        )
    }

    @discardableResult
    mutating func serverPublished(_ tree: NativeUITree) -> Bool {
        guard let node = Self.findNode(id: configuration.nodeID, in: tree.root) else {
            return false
        }

        let previousValue = configuration.value
        configuration = CheckboxRendererConfiguration(node: node)
        pendingProposal = nil

        return previousValue != configuration.value
    }

    private static func findNode(id: Int, in node: NativeUINode) -> NativeUINode? {
        if node.id == id {
            return node
        }

        for child in node.children {
            if let match = findNode(id: id, in: child) {
                return match
            }
        }

        return nil
    }
}

struct CheckboxRenderer: View {
    let node: NativeUINode

    @ObservedObject private var themeStore = NativeUITheme.shared
    @ObservedObject private var bridge = NativeUIBridge.shared
    @Environment(\.colorScheme) private var colorScheme
    @State private var rendererState: CheckboxRendererState

    init(node: NativeUINode) {
        self.node = node
        _rendererState = State(initialValue: CheckboxRendererState(node: node))
    }

    var body: some View {
        FirstlightCheckboxControl(
            configuration: rendererState.configuration,
            tokens: themeStore.resolve(for: colorScheme),
            onProposal: proposeChange
        )
        .onReceive(bridge.$currentTree.compactMap { $0 }) { tree in
            // Reconcile every publication: an unchanged value can still be a
            // server rejection and must release the pending proposal guard.
            rendererState.serverPublished(tree)
        }
    }

    private func proposeChange() {
        guard case let .checkboxChange(callbackID, nodeID, value)? = rendererState.proposeChange() else {
            return
        }

        NativeElementBridge.sendCheckboxChangeEvent(
            callbackID,
            nodeId: nodeID,
            value: value
        )
    }
}
