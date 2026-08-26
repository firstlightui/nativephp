import SwiftUI

enum DatePickerRendererEvent: Equatable {
    case change(callbackId: Int, nodeId: Int, value: String)

    var wireName: String { "SELECT_CHANGE" }
}

struct DatePickerRendererEvents {
    let send: @MainActor (DatePickerRendererEvent) -> Void

    static let native = DatePickerRendererEvents { event in
        switch event {
        case let .change(callbackId, nodeId, value):
            NativeUIBridge.sendSelectChangeEvent(callbackId, nodeId: nodeId, value: value)
        }
    }
}

struct DatePickerRendererConfiguration: Equatable {
    let nodeID: Int
    let hasValue: Bool
    let value: String
    let minimum: String
    let maximum: String
    let label: String
    let placeholder: String
    let helper: String
    let error: String
    let required: Bool
    let disabled: Bool
    let locale: String
    let timezone: String
    let confirmLabel: String
    let cancelLabel: String
    let accessibilityLabel: String
    let accessibilityHint: String
    let onChangeCallback: Int

    init(node: NativeUINode) {
        let props = node.props
        nodeID = node.id
        hasValue = props.getBool("has_value")
        value = props.getString("value")
        minimum = props.getString("min")
        maximum = props.getString("max")
        label = props.getString("label")
        placeholder = props.getString("placeholder")
        helper = props.getString("helper")
        error = props.getString("error")
        required = props.getBool("required")
        disabled = props.getBool("disabled")
        locale = props.getString("locale")
        timezone = props.getString("timezone")
        confirmLabel = props.getString("confirm_label", default: "Confirm")
        cancelLabel = props.getString("cancel_label", default: "Cancel")
        let explicitLabel = props.getString("a11y_label")
        accessibilityLabel = explicitLabel.isEmpty ? label : explicitLabel
        accessibilityHint = props.getString("a11y_hint")
        onChangeCallback = props.getCallbackId("on_change")
    }

    var acceptedValue: String? { hasValue ? value : nil }
    var isInteractive: Bool { !disabled && onChangeCallback != 0 }

    fileprivate var presentationFingerprint: String {
        [hasValue ? "1" : "0", value, minimum, maximum, locale, timezone, disabled ? "1" : "0"]
            .joined(separator: "\u{1F}")
    }
}

struct DatePickerRendererState {
    var configuration: DatePickerRendererConfiguration
    var draft: String?
    var isPresented = false
    var presentationVersion = 0

    init(node: NativeUINode) {
        configuration = DatePickerRendererConfiguration(node: node)
        draft = nil
    }

    @discardableResult
    mutating func open(today: String? = nil) -> Bool {
        guard configuration.isInteractive else { return false }
        let seed = configuration.acceptedValue
            ?? today
            ?? DatePickerCalendar.today(timezone: configuration.timezone)
        draft = clamp(seed)
        presentationVersion += 1
        isPresented = true
        return true
    }

    mutating func userSelected(_ value: String) {
        guard isPresented else { return }
        draft = clamp(value)
    }

    mutating func cancel() {
        draft = nil
        isPresented = false
    }

    mutating func confirm() -> DatePickerRendererEvent? {
        let selected = draft
        let published = configuration
        cancel()

        guard let selected, published.isInteractive, selected != published.acceptedValue else {
            return nil
        }

        return .change(
            callbackId: published.onChangeCallback,
            nodeId: published.nodeID,
            value: selected
        )
    }

    mutating func serverPublished(_ tree: NativeUITree) {
        guard let node = Self.findNode(id: configuration.nodeID, in: tree.root) else { return }
        publish(DatePickerRendererConfiguration(node: node))
    }

    private mutating func publish(_ published: DatePickerRendererConfiguration) {
        let presentationChanged = published.presentationFingerprint != configuration.presentationFingerprint
        configuration = published
        if isPresented && presentationChanged { cancel() }
    }

    private func clamp(_ value: String) -> String {
        let aboveMinimum = configuration.minimum.isEmpty ? value : Swift.max(value, configuration.minimum)
        return configuration.maximum.isEmpty ? aboveMinimum : Swift.min(aboveMinimum, configuration.maximum)
    }

    private static func findNode(id: Int, in node: NativeUINode) -> NativeUINode? {
        if node.id == id { return node }
        for child in node.children {
            if let found = findNode(id: id, in: child) { return found }
        }
        return nil
    }
}

enum DatePickerCalendar {
    static func calendar(timezone: String) -> Calendar {
        var calendar = Calendar(identifier: .gregorian)
        calendar.locale = Locale(identifier: "en_US_POSIX")
        calendar.timeZone = TimeZone(identifier: timezone) ?? .current
        return calendar
    }

    static func date(from canonical: String, timezone: String) -> Date {
        let parts = canonical.split(separator: "-").compactMap { Int($0) }
        precondition(parts.count == 3, "Date Picker received a noncanonical date")
        let value = calendar(timezone: timezone).date(
            from: DateComponents(year: parts[0], month: parts[1], day: parts[2])
        )
        return value ?? Date(timeIntervalSince1970: 0)
    }

    static func canonical(from date: Date, timezone: String) -> String {
        let components = calendar(timezone: timezone).dateComponents([.year, .month, .day], from: date)
        return String(format: "%04d-%02d-%02d", components.year!, components.month!, components.day!)
    }

    static func today(timezone: String, now: Date = Date()) -> String {
        canonical(from: now, timezone: timezone)
    }

    static func display(_ canonical: String, locale: String, timezone: String) -> String {
        let formatter = DateFormatter()
        formatter.calendar = calendar(timezone: timezone)
        formatter.timeZone = formatter.calendar.timeZone
        formatter.locale = locale.isEmpty ? .current : Locale(identifier: locale)
        formatter.dateStyle = .medium
        formatter.timeStyle = .none
        return formatter.string(from: date(from: canonical, timezone: timezone))
    }
}

struct DatePickerRenderer: View {
    @ObservedObject private var themeStore = NativeUITheme.shared
    @ObservedObject private var bridge = NativeUIBridge.shared
    @Environment(\.colorScheme) private var colorScheme
    @State private var state: DatePickerRendererState
    private let events: DatePickerRendererEvents

    init(node: NativeUINode, events: DatePickerRendererEvents = .native) {
        _state = State(initialValue: DatePickerRendererState(node: node))
        self.events = events
    }

    var body: some View {
        FirstlightDatePickerControl(
            state: $state,
            tokens: themeStore.resolve(for: colorScheme),
            onConfirm: {
                if let event = state.confirm() { events.send(event) }
            }
        )
        .onReceive(bridge.$currentTree.compactMap { $0 }) { tree in
            state.serverPublished(tree)
        }
    }
}
