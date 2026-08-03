import SwiftUI
import UIKit

struct SegmentedRenderer: View {
    let node: NativeUINode

    @ObservedObject private var themeStore = NativeUITheme.shared
    @Environment(\.colorScheme) private var colorScheme
    @State private var selectionState: SegmentedSelectionState

    init(node: NativeUINode) {
        self.node = node

        let props = node.props
        _selectionState = State(initialValue: SegmentedSelectionState(
            selectedIndex: SegmentedSelectionState.selectedIndex(
                hasSelection: props.getBool("has_selection"),
                selectedValue: props.getString("selected_value"),
                optionValues: props.getStringList("option_values")
            )
        ))
    }

    var body: some View {
        let props = node.props
        let optionValues = props.getStringList("option_values")
        let optionLabels = props.getStringList("option_labels")
        let optionEnabled = props.getStringList("option_enabled").map { $0 == "1" }
        let optionCallbacks = props.getStringList("option_callbacks").map { Int($0) ?? 0 }
        let valueType = props.getString("value_type")
        let hasSelection = props.getBool("has_selection")
        let selectedValue = props.getString("selected_value")
        let serverSelectedIndex = SegmentedSelectionState.selectedIndex(
            hasSelection: hasSelection,
            selectedValue: selectedValue,
            optionValues: optionValues
        )
        let onChangeCallback = props.getCallbackId("on_change")
        let disabled = props.getBool("disabled") || optionValues.isEmpty
        let label = props.getString("label")
        let a11yLabel = props.getString("a11y_label")
        let theme = themeStore.resolve(for: colorScheme)

        FirstlightSegmentedField(
            label: label,
            helper: props.getString("helper"),
            error: props.getString("error"),
            required: props.getBool("required"),
            labels: optionLabels,
            optionEnabled: optionEnabled,
            disabled: disabled,
            selectionState: $selectionState,
            tokens: FirstlightSegmentedTokens(
                tintColor: UIColor(theme.primary),
                unselectedTextColor: UIColor(theme.onSurface),
                selectedTextColor: UIColor(theme.onPrimary),
                labelColor: theme.onSurface,
                helperColor: theme.onSurfaceVariant,
                errorColor: theme.destructive
            ),
            accessibilityLabel: a11yLabel.isEmpty ? label : a11yLabel,
            accessibilityHint: props.getString("a11y_hint"),
            onSelection: { index in
                guard selectionState.select(
                    index,
                    optionEnabled: optionEnabled,
                    disabled: disabled
                ) else {
                    return
                }

                if valueType == "string", onChangeCallback != 0 {
                    NativeUIBridge.sendSelectChangeEvent(
                        onChangeCallback,
                        nodeId: node.id,
                        value: optionValues[index]
                    )
                } else if valueType == "integer",
                          optionCallbacks.indices.contains(index),
                          optionCallbacks[index] != 0 {
                    NativeUIBridge.sendPressEvent(
                        optionCallbacks[index],
                        nodeId: node.id
                    )
                }
            }
        )
        .onChange(of: serverSelectedIndex) { _ in
            selectionState.reconcile(
                hasSelection: hasSelection,
                selectedValue: selectedValue,
                optionValues: optionValues
            )
        }
    }
}
