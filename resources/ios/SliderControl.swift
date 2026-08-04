import Foundation
import SwiftUI

struct FirstlightSliderControl: View {
    @Binding var state: SliderRendererState
    let tokens: NativeUITokens
    let onChange: @MainActor (SliderRendererEvent) -> Void

    private var configuration: SliderRendererConfiguration { state.configuration }

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            if !configuration.label.isEmpty {
                Text(configuration.label)
                    .font(.subheadline.weight(.medium))
                    .foregroundStyle(tokens.onSurface)
                    .accessibilityHidden(true)
            }

            Slider(
                value: Binding(
                    get: { state.draft },
                    set: { value in
                        if let event = state.userChanged(value) { onChange(event) }
                    }
                ),
                in: configuration.minimum...configuration.maximum,
                step: configuration.step,
                onEditingChanged: { editing in
                    if editing {
                        state.beginEditing()
                    } else if let event = state.finishEditing() {
                        onChange(event)
                    }
                }
            )
            .tint(tokens.primary)
            .disabled(!configuration.isInteractive)
            .accessibilityLabel(Text(configuration.accessibilityLabel))
            .accessibilityHint(Text(accessibilityHint))
            .accessibilityValue(Text(accessibilityValue))

            let supporting = configuration.error.isEmpty ? configuration.helper : configuration.error
            if !supporting.isEmpty {
                Text(supporting)
                    .font(.footnote)
                    .foregroundStyle(configuration.error.isEmpty ? tokens.onSurfaceVariant : tokens.destructive)
                    .accessibilityHidden(true)
            }
        }
        .opacity(configuration.disabled ? 0.6 : 1)
    }

    private var accessibilityHint: String {
        [
            configuration.accessibilityHint,
            configuration.error.isEmpty ? configuration.helper : "Error: \(configuration.error)",
        ]
        .filter { !$0.isEmpty }
        .joined(separator: ". ")
    }

    private var accessibilityValue: String {
        configuration.accessibilityValue.isEmpty || state.isEditing
            ? String(format: "%g", state.draft)
            : configuration.accessibilityValue
    }
}
