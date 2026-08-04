import Foundation
import SwiftUI

enum SliderRendererEvent: Equatable {
    case change(callbackId: Int, nodeId: Int, value: Float)

    var wireName: String { "SLIDER_CHANGE" }
}

struct SliderRendererEvents {
    let send: @MainActor (SliderRendererEvent) -> Void

    static let native = SliderRendererEvents { event in
#if SWIFT_PACKAGE
        // State/event behavior is injected in package tests; NativePHP owns the runtime bridge.
        _ = event
#else
        switch event {
        case let .change(callbackId, nodeId, value):
            NativeUIBridge.sendSliderChangeEvent(callbackId, nodeId: nodeId, value: value)
        }
#endif
    }
}

struct SliderRendererConfiguration: Equatable {
    let nodeID: Int
    let value: Float
    let minimum: Float
    let maximum: Float
    let step: Float
    let intervalCount: Int
    let label: String
    let helper: String
    let error: String
    let disabled: Bool
    let syncMode: String
    let debounceMilliseconds: Int
    let accessibilityLabel: String
    let accessibilityHint: String
    let accessibilityValue: String
    let onChangeCallback: Int

    init(node: NativeUINode) {
        let props = node.props
        nodeID = node.id
        value = sliderFloat(props, "value")
        minimum = sliderFloat(props, "min")
        maximum = sliderFloat(props, "max")
        step = sliderFloat(props, "step", default: 1)
        intervalCount = props.getInt("interval_count")
        label = props.getString("label")
        helper = props.getString("helper")
        error = props.getString("error")
        disabled = props.getBool("disabled")
        syncMode = props.getString("sync_mode", default: "live")
        debounceMilliseconds = max(50, props.getInt("debounce_ms", default: 300))
        let explicitLabel = props.getString("a11y_label")
        accessibilityLabel = explicitLabel.isEmpty ? label : explicitLabel
        accessibilityHint = props.getString("a11y_hint")
        accessibilityValue = props.getString("a11y_value")
        onChangeCallback = props.getCallbackId("on_change")
    }

    init(
        nodeID: Int,
        value: Float,
        minimum: Float,
        maximum: Float,
        step: Float,
        intervalCount: Int,
        label: String,
        helper: String,
        error: String,
        disabled: Bool,
        syncMode: String,
        debounceMilliseconds: Int,
        accessibilityLabel: String,
        accessibilityHint: String,
        accessibilityValue: String,
        onChangeCallback: Int
    ) {
        self.nodeID = nodeID
        self.value = value
        self.minimum = minimum
        self.maximum = maximum
        self.step = step
        self.intervalCount = intervalCount
        self.label = label
        self.helper = helper
        self.error = error
        self.disabled = disabled
        self.syncMode = syncMode
        self.debounceMilliseconds = debounceMilliseconds
        self.accessibilityLabel = accessibilityLabel
        self.accessibilityHint = accessibilityHint
        self.accessibilityValue = accessibilityValue
        self.onChangeCallback = onChangeCallback
    }

    var isInteractive: Bool { !disabled && onChangeCallback != 0 }
}

struct SliderRendererState {
    var configuration: SliderRendererConfiguration
    var draft: Float
    var lastEmitted: Float
    var isEditing = false

    init(node: NativeUINode) {
        self.init(configuration: SliderRendererConfiguration(node: node))
    }

    init(configuration: SliderRendererConfiguration) {
        self.configuration = configuration
        draft = configuration.value
        lastEmitted = configuration.value
    }

    @discardableResult
    mutating func beginEditing() -> Bool {
        guard configuration.isInteractive else { return false }
        isEditing = true
        return true
    }

    mutating func userChanged(_ value: Float) -> SliderRendererEvent? {
        guard configuration.isInteractive else { return nil }
        if !isEditing { isEditing = true }
        draft = snapped(value)
        return configuration.syncMode == "live" ? emitIfNeeded() : nil
    }

    mutating func finishEditing() -> SliderRendererEvent? {
        guard isEditing else { return nil }
        isEditing = false
        return configuration.syncMode == "live" ? nil : emitIfNeeded()
    }

    mutating func flush() -> SliderRendererEvent? {
        guard configuration.syncMode == "debounce" else { return nil }
        return emitIfNeeded()
    }

    mutating func serverPublished(_ tree: NativeUITree) {
        guard let node = Self.findNode(id: configuration.nodeID, in: tree.root) else { return }
        publish(SliderRendererConfiguration(node: node))
    }

    mutating func serverPublished(_ published: SliderRendererConfiguration) {
        publish(published)
    }

    private mutating func publish(_ published: SliderRendererConfiguration) {
        configuration = published
        draft = published.value
        lastEmitted = published.value
        isEditing = false
    }

    private mutating func emitIfNeeded() -> SliderRendererEvent? {
        guard !approximatelyEqual(draft, lastEmitted) else { return nil }
        lastEmitted = draft
        return .change(
            callbackId: configuration.onChangeCallback,
            nodeId: configuration.nodeID,
            value: draft
        )
    }

    private func snapped(_ value: Float) -> Float {
        let index = ((value - configuration.minimum) / configuration.step)
            .rounded()
            .clamped(to: 0...Float(configuration.intervalCount))
        return configuration.minimum + (index * configuration.step)
    }

    private func approximatelyEqual(_ lhs: Float, _ rhs: Float) -> Bool {
        abs(lhs - rhs) <= max(abs(configuration.step) * 0.000001, 0.000001)
    }

    private static func findNode(id: Int, in node: NativeUINode) -> NativeUINode? {
        if node.id == id { return node }
        for child in node.children {
            if let found = findNode(id: id, in: child) { return found }
        }
        return nil
    }
}

struct SliderRenderer: View {
    @ObservedObject private var themeStore = NativeUITheme.shared
    @ObservedObject private var bridge = NativeUIBridge.shared
    @Environment(\.colorScheme) private var colorScheme
    @State private var state: SliderRendererState
    private let events: SliderRendererEvents

    init(node: NativeUINode, events: SliderRendererEvents = .native) {
        _state = State(initialValue: SliderRendererState(node: node))
        self.events = events
    }

    var body: some View {
        FirstlightSliderControl(
            state: $state,
            tokens: themeStore.resolve(for: colorScheme),
            onChange: events.send
        )
        .onReceive(bridge.$currentTree.compactMap { $0 }) { tree in
            state.serverPublished(tree)
        }
        .task(id: state.draft) {
            guard state.configuration.syncMode == "debounce", state.isEditing else { return }
            try? await Task.sleep(for: .milliseconds(state.configuration.debounceMilliseconds))
            if !Task.isCancelled, let event = state.flush() { events.send(event) }
        }
    }
}

private func sliderFloat(_ props: GenericProps, _ key: String, default defaultValue: Float = 0) -> Float {
#if SWIFT_PACKAGE
    // The package's narrow NativePHP test shim predates GenericProps.getFloat.
    let mirror = Mirror(reflecting: props)
    guard let values = mirror.children.first(where: { $0.label == "map" })?.value as? [String: Any],
          let number = values[key] as? NSNumber else {
        return defaultValue
    }
    return number.floatValue
#else
    return props.getFloat(key, default: defaultValue)
#endif
}

private extension Float {
    func clamped(to range: ClosedRange<Float>) -> Float {
        Swift.min(Swift.max(self, range.lowerBound), range.upperBound)
    }
}
