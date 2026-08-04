import SwiftUI

enum SwitchRendererEvent: Equatable {
    case toggleChange(callbackId: Int, nodeId: Int, value: Bool)
}

struct SwitchRendererConfiguration: Equatable {
    let nodeID: Int
    let value: Bool
    let label: String
    let helper: String
    let error: String
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
        disabled = props.getBool("disabled")
        accessibilityLabel = explicitAccessibilityLabel.isEmpty ? label : explicitAccessibilityLabel
        accessibilityHint = props.getString("a11y_hint")
        onChangeCallback = props.getCallbackId("on_change")
    }

    var supportingText: String {
        error.isEmpty ? helper : error
    }
}

struct SwitchRendererState: Equatable {
    var configuration: SwitchRendererConfiguration
    private var pendingProposal: Bool?

    init(node: NativeUINode) {
        configuration = SwitchRendererConfiguration(node: node)
        pendingProposal = nil
    }

    mutating func proposeChange() -> SwitchRendererEvent? {
        guard !configuration.disabled,
              configuration.onChangeCallback != 0,
              pendingProposal == nil
        else {
            return nil
        }

        let proposal = !configuration.value
        pendingProposal = proposal

        return .toggleChange(
            callbackId: configuration.onChangeCallback,
            nodeId: configuration.nodeID,
            value: proposal
        )
    }

    @discardableResult
    mutating func serverPublished(_ tree: NativeUITree) -> Bool {
        guard let node = Self.findNode(id: configuration.nodeID, in: tree.root) else {
            return false
        }

        let previousValue = configuration.value
        configuration = SwitchRendererConfiguration(node: node)
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

struct SwitchRenderer: View {
    @ObservedObject private var themeStore = NativeUITheme.shared
    @ObservedObject private var bridge = NativeUIBridge.shared
    @Environment(\.colorScheme) private var colorScheme
    @State private var rendererState: SwitchRendererState

    init(node: NativeUINode) {
        _rendererState = State(initialValue: SwitchRendererState(node: node))
    }

    var body: some View {
        let configuration = rendererState.configuration
        let tokens = themeStore.resolve(for: colorScheme)

        FirstlightSwitchControl(
            value: configuration.value,
            label: configuration.label,
            supportingText: configuration.supportingText,
            error: configuration.error,
            disabled: configuration.disabled,
            accessibilityLabel: configuration.accessibilityLabel,
            accessibilityHint: configuration.accessibilityHint,
            tokens: tokens,
            onProposal: proposeChange
        )
        .onReceive(bridge.$currentTree.compactMap { $0 }) { tree in
            // A PHP response may keep the same accepted value. Reconcile every
            // publication so that response still clears the pending proposal.
            rendererState.serverPublished(tree)
        }
    }

    private func proposeChange() {
        guard case let .toggleChange(callbackId, nodeId, value)? = rendererState.proposeChange() else {
            return
        }

        NativeElementBridge.sendToggleChangeEvent(
            callbackId,
            nodeId: nodeId,
            value: value
        )
    }
}
