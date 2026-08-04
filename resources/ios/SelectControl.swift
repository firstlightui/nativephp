import SwiftUI

enum SelectPresentation {
    static func filteredIndices(labels: [String], query: String) -> [Int] {
        guard !query.isEmpty else { return Array(labels.indices) }

        return labels.indices.filter { index in
            labels[index].localizedCaseInsensitiveContains(query)
        }
    }
}

struct FirstlightSelectTokens {
    let foreground: Color
    let secondary: Color
    let outline: Color
    let accent: Color
    let error: Color

    static func from(theme: NativeUITokens) -> Self {
        Self(
            foreground: theme.onSurface,
            secondary: theme.onSurfaceVariant,
            outline: theme.onSurfaceVariant.opacity(0.55),
            accent: theme.primary,
            error: theme.destructive
        )
    }
}

struct FirstlightSelectField: View {
    @State private var searchPresented = false
    @State private var searchQuery = ""

    let labels: [String]
    let values: [String]
    let optionEnabled: [Bool]
    let selectedValues: [String]
    let searchEnabled: Bool
    let disabled: Bool
    let awaitingPublication: Bool
    let label: String
    let placeholder: String
    let helper: String
    let error: String
    let required: Bool
    let tokens: FirstlightSelectTokens
    let accessibilityLabel: String
    let accessibilityHint: String
    let onSelection: (Int) -> Void

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            if !label.isEmpty {
                HStack(spacing: 2) {
                    Text(label)
                        .fixedSize(horizontal: false, vertical: true)
                    if required {
                        Text("*").accessibilityHidden(true)
                    }
                }
                .font(.subheadline.weight(.medium))
                .foregroundStyle(tokens.foreground)
            }

            trigger

            let supportingText = error.isEmpty ? helper : error
            if !supportingText.isEmpty {
                Text(supportingText)
                    .font(.footnote)
                    .foregroundStyle(error.isEmpty ? tokens.secondary : tokens.error)
                    .fixedSize(horizontal: false, vertical: true)
                    .accessibilityLabel(error.isEmpty ? supportingText : "Error: \(supportingText)")
            }
        }
        .opacity(disabled ? 0.6 : 1)
        .sheet(isPresented: $searchPresented, onDismiss: { searchQuery = "" }) {
            searchableSheet
        }
    }

    @ViewBuilder
    private var trigger: some View {
        if searchEnabled {
            Button {
                searchPresented = true
            } label: {
                triggerLabel
            }
            .buttonStyle(.plain)
            .disabled(disabled)
            .accessibilityLabel(groupAccessibilityLabel)
            .accessibilityValue(selectedLabel ?? placeholder)
            .accessibilityHint(accessibilityHint)
        } else {
            Menu {
                ForEach(labels.indices, id: \.self) { index in
                    Button {
                        select(index)
                    } label: {
                        if isSelected(index) {
                            Label(labels[index], systemImage: "checkmark")
                        } else {
                            Text(labels[index])
                        }
                    }
                    .disabled(!isOptionEnabled(index))
                }
            } label: {
                triggerLabel
            }
            .buttonStyle(.plain)
            .disabled(disabled)
            .accessibilityLabel(groupAccessibilityLabel)
            .accessibilityValue(selectedLabel ?? placeholder)
            .accessibilityHint(accessibilityHint)
        }
    }

    private var triggerLabel: some View {
        HStack(spacing: 12) {
            Text(selectedLabel ?? placeholder)
                .font(.body)
                .foregroundStyle(selectedLabel == nil ? tokens.secondary : tokens.foreground)
                .multilineTextAlignment(.leading)
                .fixedSize(horizontal: false, vertical: true)

            Spacer(minLength: 8)

            Image(systemName: searchEnabled ? "magnifyingglass" : "chevron.up.chevron.down")
                .foregroundStyle(tokens.secondary)
                .accessibilityHidden(true)
        }
        .padding(.horizontal, 12)
        .padding(.vertical, 10)
        .frame(maxWidth: .infinity, minHeight: 44, alignment: .leading)
        .background(
            RoundedRectangle(cornerRadius: 10, style: .continuous)
                .stroke(error.isEmpty ? tokens.outline : tokens.error, lineWidth: 1)
        )
        .contentShape(Rectangle())
    }

    private var searchableSheet: some View {
        NavigationStack {
            List {
                ForEach(
                    SelectPresentation.filteredIndices(labels: labels, query: searchQuery),
                    id: \.self
                ) { index in
                    Button {
                        select(index)
                        searchPresented = false
                    } label: {
                        HStack(spacing: 12) {
                            Text(labels[index])
                                .foregroundStyle(tokens.foreground)
                                .multilineTextAlignment(.leading)
                                .fixedSize(horizontal: false, vertical: true)
                            Spacer(minLength: 8)
                            if isSelected(index) {
                                Image(systemName: "checkmark")
                                    .foregroundStyle(tokens.accent)
                                    .accessibilityHidden(true)
                            }
                        }
                        .frame(minHeight: 44)
                    }
                    .disabled(!isOptionEnabled(index))
                    .accessibilityLabel(labels[index])
                    .accessibilityValue(isSelected(index) ? "Selected" : "Not selected")
                    .accessibilityAddTraits(isSelected(index) ? .isSelected : [])
                }
            }
            .navigationTitle(label.isEmpty ? accessibilityLabel : label)
            .navigationBarTitleDisplayMode(.inline)
            .searchable(
                text: $searchQuery,
                prompt: placeholder.isEmpty ? "Search options" : placeholder
            )
            .toolbar {
                ToolbarItem(placement: .confirmationAction) {
                    Button("Done") { searchPresented = false }
                }
            }
        }
    }

    private var selectedLabel: String? {
        guard let selectedValue = selectedValues.first,
              let index = values.firstIndex(of: selectedValue),
              labels.indices.contains(index)
        else {
            return nil
        }

        return labels[index]
    }

    private func isSelected(_ index: Int) -> Bool {
        guard let value = values.indices.contains(index) ? values[index] : nil else {
            return false
        }

        return selectedValues.contains(value)
    }

    private func isOptionEnabled(_ index: Int) -> Bool {
        !disabled && optionEnabled.indices.contains(index) && optionEnabled[index]
    }

    private func select(_ index: Int) {
        guard !awaitingPublication else { return }
        onSelection(index)
    }

    private var groupAccessibilityLabel: String {
        let base = accessibilityLabel.isEmpty ? label : accessibilityLabel
        return required && !base.isEmpty ? "\(base), required" : base
    }
}
