import SwiftUI

enum ReorderListRendererEvent: Equatable {
    case press(callbackId: Int, nodeId: Int)
}

struct ReorderListRendererEvents {
    let send: @MainActor (ReorderListRendererEvent) -> Void

    static let native = ReorderListRendererEvents { event in
        switch event {
        case let .press(callbackId, nodeId):
            NativeUIBridge.sendPressEvent(callbackId, nodeId: nodeId)
        }
    }
}

/// The published contract. Rows are indexed in published order; `moveCallbacks` holds
/// `slotCount` callback ids per row, one per destination slot, sections in order. In each
/// section a row has one slot per position it can take there.
struct ReorderListRendererConfiguration: Equatable {
    let nodeID: Int
    let sectionKeys: [String]
    let sectionLabels: [String]
    let sectionLengths: [Int]
    let rowValues: [String]
    let rowLabels: [String]
    let rowSupporting: [String]
    let rowIcons: [String]
    let rowLocked: [Bool]
    let slotCount: Int
    let moveCallbacks: [Int]
    let label: String
    let helper: String
    let error: String
    let disabled: Bool
    let accessibilityLabel: String
    let accessibilityHint: String
    let moveUpLabel: String
    let moveDownLabel: String
    let moveToLabels: [String]
    let lockedLabel: String
    let positionLabel: String

    init(node: NativeUINode) {
        let props = node.props
        nodeID = node.id
        sectionKeys = props.getStringList("section_keys")
        sectionLabels = props.getStringList("section_labels")
        sectionLengths = props.getStringList("section_lengths").map { Int($0) ?? 0 }
        rowValues = props.getStringList("row_values")
        rowLabels = props.getStringList("row_labels")
        rowSupporting = props.getStringList("row_supporting")
        rowIcons = props.getStringList("row_icons")
        rowLocked = props.getStringList("row_locked").map { $0 == "1" }
        slotCount = props.getInt("move_slot_count")
        moveCallbacks = props.getStringList("move_callbacks").map { Int($0) ?? 0 }
        label = props.getString("label")
        helper = props.getString("helper")
        error = props.getString("error")
        disabled = props.getBool("disabled") || rowValues.isEmpty
        let explicitLabel = props.getString("a11y_label")
        accessibilityLabel = explicitLabel.isEmpty ? props.getString("label") : explicitLabel
        accessibilityHint = props.getString("a11y_hint")
        moveUpLabel = props.getString("move_up_label", default: "Move up")
        moveDownLabel = props.getString("move_down_label", default: "Move down")
        moveToLabels = props.getStringList("move_to_labels")
        lockedLabel = props.getString("locked_label", default: "Locked")
        positionLabel = props.getString("position_label", default: ":position of :count")
    }

    /// Row indices per section, in published order.
    var publishedSections: [[Int]] {
        var start = 0
        return sectionLengths.map { length in
            defer { start += length }
            return Array(start..<min(start + length, rowValues.count))
        }
    }

    /// The callback for moving `row` to `position` in `section`, where `position` counts the
    /// section's rows without `row`. Zero when the move emits nothing.
    func callback(row: Int, section: Int, position: Int) -> Int {
        let sections = publishedSections
        guard sections.indices.contains(section), rowValues.indices.contains(row) else { return 0 }
        var slot = 0
        for index in 0..<section {
            slot += sections[index].filter { $0 != row }.count + 1
        }
        let length = sections[section].filter { $0 != row }.count
        guard position >= 0, position <= length else { return 0 }
        let id = row * slotCount + slot + position
        return moveCallbacks.indices.contains(id) ? moveCallbacks[id] : 0
    }

    func spokenPosition(_ position: Int, of count: Int) -> String {
        positionLabel
            .replacingOccurrences(of: ":position", with: String(position))
            .replacingOccurrences(of: ":count", with: String(count))
    }
}

/// A flat entry of the drawn list: section headers are rows too, so items can be dragged
/// across them with one `onMove`.
enum ReorderListEntry: Hashable, Identifiable {
    case header(Int)
    case row(Int)

    var id: String {
        switch self {
        case let .header(section): "h\(section)"
        case let .row(row): "r\(row)"
        }
    }

    static func entries(sections: [[Int]], showsHeaders: Bool) -> [ReorderListEntry] {
        sections.enumerated().flatMap { index, rows in
            (showsHeaders ? [.header(index)] : []) + rows.map(ReorderListEntry.row)
        }
    }

    /// Where an `onMove` drop lands: the section of the nearest header above the drop, and
    /// the position among that section's other rows.
    static func destination(in entries: [ReorderListEntry], from source: Int, to offset: Int) -> (section: Int, position: Int) {
        var moved = entries
        let entry = moved.remove(at: source)
        moved.insert(entry, at: offset > source ? offset - 1 : offset)
        let landed = moved.firstIndex(of: entry) ?? 0

        var section = 0
        var position = 0
        for item in moved[..<landed] {
            switch item {
            case let .header(index):
                section = index
                position = 0
            case .row:
                position += 1
            }
        }
        return (section, position)
    }
}

struct ReorderListRendererState: Equatable {
    var configuration: ReorderListRendererConfiguration
    /// Row indices per section as drawn: the published order, or the dropped draft while a
    /// proposal awaits publication.
    private(set) var sections: [[Int]]
    private(set) var isAwaitingPublication = false

    init(node: NativeUINode) {
        configuration = ReorderListRendererConfiguration(node: node)
        sections = configuration.publishedSections
    }

    mutating func userMoved(row: Int, toSection section: Int, position: Int) -> ReorderListRendererEvent? {
        guard !isAwaitingPublication, !configuration.disabled else { return nil }
        let callbackId = configuration.callback(row: row, section: section, position: position)
        guard callbackId != 0 else { return nil }

        sections = sections.map { $0.filter { $0 != row } }
        sections[section].insert(row, at: min(position, sections[section].count))
        isAwaitingPublication = true

        return .press(callbackId: callbackId, nodeId: configuration.nodeID)
    }

    @discardableResult
    mutating func serverPublished(_ tree: NativeUITree) -> Bool {
        guard let node = Self.findNode(id: configuration.nodeID, in: tree.root) else { return false }
        let previous = sections
        configuration = ReorderListRendererConfiguration(node: node)
        sections = configuration.publishedSections
        isAwaitingPublication = false
        return previous != sections
    }

    private static func findNode(id: Int, in node: NativeUINode) -> NativeUINode? {
        if node.id == id { return node }
        for child in node.children {
            if let match = findNode(id: id, in: child) { return match }
        }
        return nil
    }
}

struct ReorderListRenderer: View {
    private let validationNodeID: Int
    @ObservedObject private var themeStore = NativeUITheme.shared
    @ObservedObject private var bridge = NativeUIBridge.shared
    @Environment(\.colorScheme) private var colorScheme
    @State private var rendererState: ReorderListRendererState

    private let events: ReorderListRendererEvents

    init(node: NativeUINode, events: ReorderListRendererEvents = .native) {
        validationNodeID = node.id
        _rendererState = State(initialValue: ReorderListRendererState(node: node))
        self.events = events
    }

    var body: some View {
        FirstlightReorderListField(
            configuration: rendererState.configuration,
            sections: rendererState.sections,
            theme: themeStore.resolve(for: colorScheme),
            onMove: { row, section, position in
                guard let event = rendererState.userMoved(row: row, toSection: section, position: position) else {
                    return false
                }
                events.send(event)
                return true
            }
        )
        .firstlightValidationAlert(nodeID: validationNodeID)
        .onReceive(bridge.$currentTree.compactMap { $0 }) { tree in
            rendererState.serverPublished(tree)
        }
    }
}
