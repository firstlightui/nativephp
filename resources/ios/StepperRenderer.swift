import SwiftUI

enum StepperRendererEvent: Equatable {
    case press(callbackId: Int, nodeId: Int)

    var wireName: String { "PRESS" }
}

struct StepperRendererEvents {
    let send: @MainActor (StepperRendererEvent) -> Void

    static let native = StepperRendererEvents { event in
        switch event {
        case let .press(callbackId, nodeId):
            NativeUIBridge.sendPressEvent(callbackId, nodeId: nodeId)
        }
    }
}

struct StepperRendererConfiguration: Equatable {
    let nodeID: Int
    let displayValue: String
    let label: String
    let helper: String
    let error: String
    let disabled: Bool
    let canDecrement: Bool
    let canIncrement: Bool
    let decrementCallback: Int
    let incrementCallback: Int
    let accessibilityLabel: String
    let accessibilityHint: String

    init(node: NativeUINode) {
        let props = node.props
        nodeID = node.id
        displayValue = props.getString("display_value")
        label = props.getString("label")
        helper = props.getString("helper")
        error = props.getString("error")
        disabled = props.getBool("disabled")
        canDecrement = props.getBool("can_decrement")
        canIncrement = props.getBool("can_increment")
        decrementCallback = props.getCallbackId("on_decrement")
        incrementCallback = props.getCallbackId("on_increment")
        let explicitLabel = props.getString("a11y_label")
        accessibilityLabel = explicitLabel.isEmpty ? label : explicitLabel
        accessibilityHint = props.getString("a11y_hint")
    }

    init(
        nodeID: Int,
        displayValue: String,
        label: String,
        helper: String,
        error: String,
        disabled: Bool,
        canDecrement: Bool,
        canIncrement: Bool,
        decrementCallback: Int,
        incrementCallback: Int,
        accessibilityLabel: String,
        accessibilityHint: String
    ) {
        self.nodeID = nodeID
        self.displayValue = displayValue
        self.label = label
        self.helper = helper
        self.error = error
        self.disabled = disabled
        self.canDecrement = canDecrement
        self.canIncrement = canIncrement
        self.decrementCallback = decrementCallback
        self.incrementCallback = incrementCallback
        self.accessibilityLabel = accessibilityLabel
        self.accessibilityHint = accessibilityHint
    }

    var canPressDecrement: Bool { !disabled && canDecrement && decrementCallback != 0 }
    var canPressIncrement: Bool { !disabled && canIncrement && incrementCallback != 0 }
}

struct StepperRendererState: Equatable {
    var configuration: StepperRendererConfiguration
    private(set) var isAwaitingPublication = false

    init(node: NativeUINode) {
        configuration = StepperRendererConfiguration(node: node)
    }

    init(configuration: StepperRendererConfiguration) {
        self.configuration = configuration
    }

    mutating func decrement() -> StepperRendererEvent? {
        propose(callbackId: configuration.decrementCallback, enabled: configuration.canPressDecrement)
    }

    mutating func increment() -> StepperRendererEvent? {
        propose(callbackId: configuration.incrementCallback, enabled: configuration.canPressIncrement)
    }

    @discardableResult
    mutating func serverPublished(_ tree: NativeUITree) -> Bool {
        guard let node = Self.findNode(id: configuration.nodeID, in: tree.root) else { return false }

        let previousDisplayValue = configuration.displayValue
        configuration = StepperRendererConfiguration(node: node)
        isAwaitingPublication = false

        return previousDisplayValue != configuration.displayValue
    }

    mutating func serverPublished(_ published: StepperRendererConfiguration) {
        configuration = published
        isAwaitingPublication = false
    }

    private mutating func propose(callbackId: Int, enabled: Bool) -> StepperRendererEvent? {
        guard !isAwaitingPublication, enabled else { return nil }
        isAwaitingPublication = true

        return .press(callbackId: callbackId, nodeId: configuration.nodeID)
    }

    private static func findNode(id: Int, in node: NativeUINode) -> NativeUINode? {
        if node.id == id { return node }
        for child in node.children {
            if let found = findNode(id: id, in: child) { return found }
        }
        return nil
    }
}

struct StepperRenderer: View {
    @ObservedObject private var themeStore = NativeUITheme.shared
    @ObservedObject private var bridge = NativeUIBridge.shared
    @Environment(\.colorScheme) private var colorScheme
    @State private var state: StepperRendererState
    private let events: StepperRendererEvents

    init(node: NativeUINode, events: StepperRendererEvents = .native) {
        _state = State(initialValue: StepperRendererState(node: node))
        self.events = events
    }

    var body: some View {
        FirstlightStepperControl(
            state: $state,
            tokens: themeStore.resolve(for: colorScheme),
            onEvent: events.send
        )
        .onReceive(bridge.$currentTree.compactMap { $0 }) { tree in
            state.serverPublished(tree)
        }
    }
}
