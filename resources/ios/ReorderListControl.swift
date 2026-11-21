import SwiftUI

/// A SwiftUI `List` held in edit mode so rows show the system reorder handles. Section headers
/// are rows too, which lets one `onMove` carry items between sections. Every move is also an
/// accessibility action, so dragging is never the only way.
struct FirstlightReorderListField: View {
    let configuration: ReorderListRendererConfiguration
    let sections: [[Int]]
    let theme: NativeUITokens
    /// Row, destination section, position among that section's other rows. Returns whether
    /// the move was proposed.
    let onMove: (Int, Int, Int) -> Bool

    @State private var contentHeight: CGFloat = 0

    private var showsHeaders: Bool { !configuration.sectionKeys.isEmpty }

    private var entries: [ReorderListEntry] {
        ReorderListEntry.entries(sections: sections, showsHeaders: showsHeaders)
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            if !configuration.label.isEmpty {
                Text(configuration.label)
                    .font(.subheadline.weight(.medium))
                    .foregroundStyle(theme.onSurface)
                    .fixedSize(horizontal: false, vertical: true)
            }

            List {
                ForEach(entries) { entry in
                    switch entry {
                    case let .header(section):
                        header(section)
                    case let .row(row):
                        rowView(row)
                    }
                }
                .onMove { source, offset in
                    guard let from = source.first, case let .row(row) = entries[from] else { return }
                    let destination = ReorderListEntry.destination(in: entries, from: from, to: offset)
                    move(row, to: destination.section, position: destination.position)
                }
            }
            .listStyle(.insetGrouped)
            .environment(\.editMode, .constant(configuration.disabled ? .inactive : .active))
            .scrollDisabled(true)
            .scrollContentBackground(.hidden)
            .contentMargins(.horizontal, 0, for: .scrollContent)
            .contentMargins(.vertical, 0, for: .scrollContent)
            .onScrollGeometryChange(for: CGFloat.self) { $0.contentSize.height } action: { _, height in
                contentHeight = height
            }
            .frame(height: max(contentHeight, CGFloat(entries.count) * 44))
            .disabled(configuration.disabled)
            .accessibilityElement(children: .contain)
            .accessibilityLabel(configuration.accessibilityLabel)
            .accessibilityHint(configuration.accessibilityHint)

            if !configuration.error.isEmpty {
                Text(configuration.error)
                    .font(.footnote)
                    .foregroundStyle(theme.destructive)
                    .fixedSize(horizontal: false, vertical: true)
            } else if !configuration.helper.isEmpty {
                Text(configuration.helper)
                    .font(.footnote)
                    .foregroundStyle(theme.onSurfaceVariant)
                    .fixedSize(horizontal: false, vertical: true)
            }
        }
    }

    private func header(_ section: Int) -> some View {
        Text(configuration.sectionLabels.indices.contains(section) ? configuration.sectionLabels[section] : "")
            .font(.footnote)
            .textCase(.uppercase)
            .foregroundStyle(theme.onSurfaceVariant)
            .listRowBackground(Color.clear)
            .listRowSeparator(.hidden)
            .moveDisabled(true)
            .accessibilityAddTraits(.isHeader)
    }

    private func rowView(_ row: Int) -> some View {
        let locked = configuration.rowLocked.indices.contains(row) && configuration.rowLocked[row]
        let icon = configuration.rowIcons.indices.contains(row) ? configuration.rowIcons[row] : ""
        let supporting = configuration.rowSupporting.indices.contains(row) ? configuration.rowSupporting[row] : ""
        let place = location(of: row)
        let first = place?.position == 0
        let last = place.map { $0.position == sections[$0.section].count - 1 } ?? false

        return HStack(spacing: 12) {
            if !icon.isEmpty {
                NativeIconView(icon, pointSize: 20, relativeTo: .body)
                    .foregroundStyle(theme.onSurfaceVariant)
                    .accessibilityHidden(true)
            }
            VStack(alignment: .leading, spacing: 2) {
                Text(configuration.rowLabels[row])
                    .foregroundStyle(theme.onSurface)
                if !supporting.isEmpty {
                    Text(supporting)
                        .font(.footnote)
                        .foregroundStyle(theme.onSurfaceVariant)
                }
            }
            .fixedSize(horizontal: false, vertical: true)
            Spacer(minLength: 0)
            if locked {
                Image(systemName: "lock.fill")
                    .foregroundStyle(theme.onSurfaceVariant)
                    .accessibilityHidden(true)
            }
        }
        .listRowBackground(rowBackground(first: first, last: last))
        .listRowSeparator(last ? .hidden : .automatic, edges: .bottom)
        .moveDisabled(locked)
        .accessibilityElement(children: .combine)
        .accessibilityValue(spokenValue(row: row, locked: locked, place: place))
        .accessibilityActions {
            if !locked, let place {
                if place.position > 0 {
                    Button(configuration.moveUpLabel) { move(row, to: place.section, position: place.position - 1) }
                }
                if place.position < sections[place.section].count - 1 {
                    Button(configuration.moveDownLabel) { move(row, to: place.section, position: place.position + 1) }
                }
                ForEach(Array(sections.indices), id: \.self) { section in
                    if section != place.section, configuration.moveToLabels.indices.contains(section) {
                        Button(configuration.moveToLabels[section]) {
                            move(row, to: section, position: sections[section].count)
                        }
                    }
                }
            }
        }
    }

    /// Headers are rows, so the system sees one section and cannot round each card. Each row
    /// draws its own card slice instead, rounding the outer corners of its section.
    private func rowBackground(first: Bool, last: Bool) -> some View {
        let radius: CGFloat = if #available(iOS 26, *) { 26 } else { 10 }
        return UnevenRoundedRectangle(
            topLeadingRadius: first ? radius : 0,
            bottomLeadingRadius: last ? radius : 0,
            bottomTrailingRadius: last ? radius : 0,
            topTrailingRadius: first ? radius : 0,
            style: .continuous
        )
        .fill(theme.surface)
    }

    private func location(of row: Int) -> (section: Int, position: Int)? {
        for (section, rows) in sections.enumerated() {
            if let position = rows.firstIndex(of: row) { return (section, position) }
        }
        return nil
    }

    private func spokenValue(row: Int, locked: Bool, place: (section: Int, position: Int)?) -> String {
        var parts: [String] = []
        if let place {
            if showsHeaders, configuration.sectionLabels.indices.contains(place.section) {
                parts.append(configuration.sectionLabels[place.section])
            }
            parts.append(configuration.spokenPosition(place.position + 1, of: sections[place.section].count))
        }
        if locked { parts.append(configuration.lockedLabel) }
        return parts.joined(separator: ", ")
    }

    private func move(_ row: Int, to section: Int, position: Int) {
        guard onMove(row, section, position) else { return }
        let count = sections[section].count + (sections[section].contains(row) ? 0 : 1)
        var announcement = configuration.rowLabels[row]
        if showsHeaders, configuration.sectionLabels.indices.contains(section) {
            announcement += ", " + configuration.sectionLabels[section]
        }
        announcement += ", " + configuration.spokenPosition(min(position, count - 1) + 1, of: count)
        AccessibilityNotification.Announcement(announcement).post()
    }
}
