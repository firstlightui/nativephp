import SwiftUI

struct FirstlightStepperControl: View {
    @Binding var state: StepperRendererState
    let tokens: NativeUITokens
    let onEvent: @MainActor (StepperRendererEvent) -> Void
    @Environment(\.layoutDirection) private var layoutDirection

    @State private var stepperSize: CGSize = .zero

    private var configuration: StepperRendererConfiguration { state.configuration }
    private var isInert: Bool { configuration.disabled || state.isAwaitingPublication }

    /// A tap in the 44 pt frame but outside the system stepper, which handles its own taps.
    private func stepFromBand(_ location: CGPoint) {
        guard !isInert, stepperSize.height > 0 else { return }
        let band = (max(44, stepperSize.height) - stepperSize.height) / 2
        guard location.y < band || location.y > band + stepperSize.height else { return }
        let decrements = (location.x < stepperSize.width / 2) != (layoutDirection == .rightToLeft)
        (decrements ? decrementAction : incrementAction)?()
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack(spacing: 12) {
                HStack(alignment: .firstTextBaseline, spacing: 12) {
                    if !configuration.label.isEmpty {
                        Text(configuration.label)
                            .foregroundStyle(tokens.onSurface)
                            .fixedSize(horizontal: false, vertical: true)
                            // The stepper below carries the label, value and hint for VoiceOver.
                            .accessibilityHidden(true)
                    }

                    Spacer(minLength: 12)

                    Text(configuration.displayValue)
                        .font(.body.monospacedDigit())
                        .foregroundStyle(tokens.onSurface)
                        .accessibilityHidden(true)
                }

                Stepper(
                    onIncrement: incrementAction,
                    onDecrement: decrementAction
                ) {
                    EmptyView()
                }
                .labelsHidden()
                .fixedSize()
                .onGeometryChange(for: CGSize.self) { $0.size } action: { stepperSize = $0 }
                .disabled(isInert)
                .accessibilityLabel(Text(configuration.accessibilityLabel))
                .accessibilityHint(Text(accessibilityHint))
                .accessibilityValue(Text(configuration.displayValue))
                // The system stepper is 32 pt tall at every text size. Taps in the band above and
                // below it step too, by the half they land in, so each segment is a 44 pt target.
                .frame(minHeight: 44)
                .contentShape(Rectangle())
                .gesture(SpatialTapGesture().onEnded { tap in stepFromBand(tap.location) })
            }
            .firstlightValidationFocusTarget()

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
