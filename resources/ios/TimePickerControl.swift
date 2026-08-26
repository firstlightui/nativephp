import SwiftUI
import UIKit

struct FirstlightTimePickerControl: View {
    @Binding var state: TimePickerRendererState
    let tokens: NativeUITokens
    let onConfirm: () -> Void

    private var configuration: TimePickerRendererConfiguration { state.configuration }

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            if !configuration.label.isEmpty {
                Text(configuration.required ? "\(configuration.label) *" : configuration.label)
                    .font(.subheadline.weight(.medium))
                    .foregroundStyle(tokens.onSurface)
            }

            Button {
                state.open()
            } label: {
                HStack(spacing: 8) {
                    Text(triggerText)
                        .foregroundStyle(configuration.acceptedValue == nil ? tokens.onSurfaceVariant : tokens.onSurface)
                        .frame(maxWidth: .infinity, alignment: .leading)

                    Image(systemName: "clock")
                        .foregroundStyle(tokens.onSurfaceVariant)
                        .accessibilityHidden(true)
                }
                .padding(.horizontal, 12)
                .frame(minHeight: 44)
                .background(Color(uiColor: .secondarySystemBackground))
                .clipShape(RoundedRectangle(cornerRadius: 10, style: .continuous))
                .overlay {
                    RoundedRectangle(cornerRadius: 10, style: .continuous)
                        .stroke(configuration.error.isEmpty ? Color.clear : tokens.destructive, lineWidth: 1)
                }
                .contentShape(Rectangle())
            }
            .buttonStyle(.plain)
            .disabled(!configuration.isInteractive)
            .accessibilityLabel(Text(configuration.accessibilityLabel))
            .accessibilityHint(Text(configuration.accessibilityHint))
            .accessibilityValue(Text(configuration.acceptedValue.map {
                TimePickerClock.display($0, locale: configuration.locale, timezone: configuration.timezone)
            } ?? configuration.placeholder))
            .popover(
                isPresented: Binding(
                    get: { state.isPresented },
                    set: { if !$0 { state.cancel() } }
                ),
                attachmentAnchor: .rect(.bounds),
                arrowEdge: .bottom
            ) {
                pickerPresentation
                    .presentationCompactAdaptation(.sheet)
            }

            let supporting = configuration.error.isEmpty ? configuration.helper : configuration.error
            if !supporting.isEmpty {
                Text(supporting)
                    .font(.footnote)
                    .foregroundStyle(configuration.error.isEmpty ? tokens.onSurfaceVariant : tokens.destructive)
                    .accessibilityLabel(configuration.error.isEmpty ? supporting : "Error: \(supporting)")
            }
        }
        .opacity(configuration.disabled ? 0.6 : 1)
    }

    private var triggerText: String {
        configuration.acceptedValue.map {
            TimePickerClock.display($0, locale: configuration.locale, timezone: configuration.timezone)
        } ?? configuration.placeholder
    }

    private var draftBinding: Binding<Date> {
        Binding(
            get: {
                TimePickerClock.date(
                    from: state.draft ?? TimePickerClock.current(timezone: configuration.timezone),
                    timezone: configuration.timezone
                )
            },
            set: { date in
                state.userSelected(TimePickerClock.canonical(from: date, timezone: configuration.timezone))
            }
        )
    }

    private var pickerPresentation: some View {
        VStack(spacing: 0) {
            DatePicker("Time", selection: draftBinding, displayedComponents: .hourAndMinute)
                .datePickerStyle(.wheel)
                .labelsHidden()
                .padding()

            Divider()

            HStack {
                Button(configuration.cancelLabel) { state.cancel() }
                Spacer()
                Button(configuration.confirmLabel, action: onConfirm)
                    .buttonStyle(.borderedProminent)
            }
            .padding()
        }
        .id(state.presentationVersion)
        .environment(\.calendar, TimePickerClock.calendar(timezone: configuration.timezone))
        .environment(\.timeZone, TimeZone(identifier: configuration.timezone) ?? .current)
        .environment(\.locale, configuration.locale.isEmpty ? .current : Locale(identifier: configuration.locale))
        .frame(minWidth: 320)
    }
}
