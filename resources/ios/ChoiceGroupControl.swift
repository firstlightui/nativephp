import SwiftUI

struct FirstlightChoiceGroupTokens {
    let foreground: Color
    let secondary: Color
    let divider: Color
    let selected: Color
    let error: Color

    static func from(theme: NativeUITokens) -> Self {
        Self(
            foreground: theme.onSurface,
            secondary: theme.onSurfaceVariant,
            divider: theme.onSurfaceVariant.opacity(0.28),
            selected: theme.primary,
            error: theme.destructive
        )
    }
}

struct FirstlightChoiceGroupField: View {
    let label: String
    let helper: String
    let error: String
    let required: Bool
    let labels: [String]
    let values: [String]
    let optionEnabled: [Bool]
    let selectedValues: [String]
    let multiple: Bool
    let disabled: Bool
    let awaitingPublication: Bool
    let tokens: FirstlightChoiceGroupTokens
    let accessibilityLabel: String
    let accessibilityHint: String
    let onSelection: (Int) -> Void
    var tiles = false
    var prefixes: [String] = []
    var supporting: [String] = []
    @Environment(\.dynamicTypeSize) private var dynamicTypeSize

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

            Group {
                if tiles {
                    LazyVGrid(columns: [GridItem(.adaptive(minimum: dynamicTypeSize.isAccessibilitySize ? 280 : 150))], spacing: 8) {
                        ForEach(labels.indices, id: \.self) { index in
                            choiceRow(index: index)
                                .firstlightValidationFocusTarget(index == labels.indices.first)
                        }
                    }
                } else {
                    VStack(spacing: 0) {
                        ForEach(labels.indices, id: \.self) { index in
                            choiceRow(index: index)
                                .firstlightValidationFocusTarget(index == labels.indices.first)

                            if index != labels.indices.last {
                                Divider().overlay(tokens.divider)
                            }
                        }
                    }
                }
            }
            .accessibilityElement(children: .contain)
            .accessibilityLabel(groupAccessibilityLabel)
            .accessibilityHint(accessibilityHint)

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
    }

    private func choiceRow(index: Int) -> some View {
        let value = values.indices.contains(index) ? values[index] : ""
        let selected = selectedValues.contains(value)
        let enabled = !disabled
            && optionEnabled.indices.contains(index)
            && optionEnabled[index]

        return Button {
            if !awaitingPublication {
                onSelection(index)
            }
        } label: {
            HStack(alignment: .center, spacing: 12) {
                VStack(alignment: .leading, spacing: 4) {
                    if prefixes.indices.contains(index), !prefixes[index].isEmpty {
                        Text(prefixes[index]).font(.subheadline.weight(.semibold)).foregroundStyle(tokens.secondary)
                    }
                    Text(labels[index])
                        .font(.body)
                        .foregroundStyle(tokens.foreground)
                        .multilineTextAlignment(.leading)
                        .fixedSize(horizontal: false, vertical: true)
                    if supporting.indices.contains(index), !supporting[index].isEmpty {
                        Text(supporting[index]).font(.subheadline).foregroundStyle(tokens.secondary)
                            .fixedSize(horizontal: false, vertical: true)
                    }
                }

                Spacer(minLength: 12)

                Image(systemName: "checkmark")
                    .font(.body.weight(.semibold))
                    .foregroundStyle(tokens.selected)
                    .opacity(selected ? 1 : 0)
                    .accessibilityHidden(true)
            }
            .padding(.horizontal, 12)
            .padding(.vertical, 10)
            .frame(maxWidth: .infinity, minHeight: 44, alignment: .leading)
            .background {
                if tiles {
                    RoundedRectangle(cornerRadius: 12, style: .continuous)
                        .fill(selected ? tokens.selected.opacity(0.12) : tokens.divider.opacity(0.25))
                }
            }
            .overlay {
                if tiles {
                    RoundedRectangle(cornerRadius: 12, style: .continuous)
                        .stroke(selected ? tokens.selected : tokens.divider, lineWidth: 1)
                }
            }
            .contentShape(Rectangle())
        }
        .buttonStyle(.plain)
        .disabled(!enabled)
        .accessibilityLabel([
            prefixes.indices.contains(index) ? prefixes[index] : "",
            labels[index], supporting.indices.contains(index) ? supporting[index] : "",
        ].filter { !$0.isEmpty }.joined(separator: ", "))
        .accessibilityValue(multiple
            ? (selected ? "Checked" : "Not checked")
            : (selected ? "Selected" : "Not selected"))
        .accessibilityAddTraits(selected ? .isSelected : [])
        .accessibilityHint(accessibilityHint)
    }

    private var groupAccessibilityLabel: String {
        let base = accessibilityLabel.isEmpty ? label : accessibilityLabel
        return required && !base.isEmpty ? "\(base), required" : base
    }
}
