import SwiftUI

struct FirstlightStepperControl: View {
    @Binding var state: StepperRendererState
    let tokens: NativeUITokens
    let onEvent: @MainActor (StepperRendererEvent) -> Void

    private var configuration: StepperRendererConfiguration { state.configuration }

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            Stepper(
                onIncrement: incrementAction,
                onDecrement: decrementAction
            ) {
                HStack(alignment: .firstTextBaseline, spacing: 12) {
                    if !configuration.label.isEmpty {
                        Text(configuration.label)
                            .foregroundStyle(tokens.onSurface)
                            .fixedSize(horizontal: false, vertical: true)
                    }

                    Spacer(minLength: 12)

                    Text(configuration.displayValue)
                        .font(.body.monospacedDigit())
                        .foregroundStyle(tokens.onSurface)
                        .accessibilityHidden(true)
                }
            }
            .disabled(configuration.disabled || state.isAwaitingPublication)
            .accessibilityLabel(Text(configuration.accessibilityLabel))
            .accessibilityHint(Text(accessibilityHint))
            .accessibilityValue(Text(configuration.displayValue))

            let supporting = configuration.error.isEmpty ? configuration.helper : configuration.error
            if !supporting.isEmpty {
                Text(supporting)
                    .font(.footnote)
                    .foregroundStyle(configuration.error.isEmpty ? tokens.onSurfaceVariant : tokens.destructive)
                    .fixedSize(horizontal: false, vertical: true)
                    .accessibilityLabel(configuration.error.isEmpty ? supporting : "Error: \(supporting)")
            }
        }
        .opacity(configuration.disabled ? 0.6 : 1)
    }

    private var decrementAction: (() -> Void)? {
        guard configuration.canPressDecrement else { return nil }
        return {
            if let event = state.decrement() { onEvent(event) }
        }
    }

    private var incrementAction: (() -> Void)? {
        guard configuration.canPressIncrement else { return nil }
        return {
            if let event = state.increment() { onEvent(event) }
        }
    }

    private var accessibilityHint: String {
        [
            configuration.accessibilityHint,
            configuration.error.isEmpty ? configuration.helper : "Error: \(configuration.error)",
        ]
        .filter { !$0.isEmpty }
        .joined(separator: ". ")
    }
}
