import SwiftUI
import UIKit

struct FirstlightDatePickerControl: View {
    @Binding var state: DatePickerRendererState
    let tokens: NativeUITokens
    let onConfirm: () -> Void

    private var configuration: DatePickerRendererConfiguration { state.configuration }

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

                    Image(systemName: "calendar")
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
            .accessibilityLabel(Text(
                configuration.required
                    ? "\(configuration.accessibilityLabel), required"
                    : configuration.accessibilityLabel
            ))
            .accessibilityHint(Text(accessibilityHint))
            .accessibilityValue(Text(configuration.acceptedValue.map {
                DatePickerCalendar.display($0, locale: configuration.locale, timezone: configuration.timezone)
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
            DatePickerCalendar.display($0, locale: configuration.locale, timezone: configuration.timezone)
        } ?? configuration.placeholder
    }

    private var accessibilityHint: String {
        [
            configuration.accessibilityHint,
            configuration.error.isEmpty ? configuration.helper : "Error: \(configuration.error)",
        ]
        .filter { !$0.isEmpty }
        .joined(separator: ". ")
    }

    private var draftBinding: Binding<Date> {
        Binding(
            get: {
                DatePickerCalendar.date(
                    from: state.draft ?? DatePickerCalendar.today(timezone: configuration.timezone),
                    timezone: configuration.timezone
                )
            },
            set: { date in
                state.userSelected(DatePickerCalendar.canonical(from: date, timezone: configuration.timezone))
            }
        )
    }

    private var pickerPresentation: some View {
        VStack(spacing: 0) {
            constrainedPicker
                .datePickerStyle(.graphical)
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
        .environment(\.calendar, DatePickerCalendar.calendar(timezone: configuration.timezone))
        .environment(\.timeZone, TimeZone(identifier: configuration.timezone) ?? .current)
        .environment(\.locale, configuration.locale.isEmpty ? .current : Locale(identifier: configuration.locale))
        .frame(minWidth: 320)
    }

    @ViewBuilder private var constrainedPicker: some View {
        let minimum = configuration.minimum.isEmpty ? nil : DatePickerCalendar.date(
            from: configuration.minimum,
            timezone: configuration.timezone
        )
        let maximum = configuration.maximum.isEmpty ? nil : DatePickerCalendar.date(
            from: configuration.maximum,
            timezone: configuration.timezone
        )

        if let minimum, let maximum {
            DatePicker("Date", selection: draftBinding, in: minimum...maximum, displayedComponents: .date)
        } else if let minimum {
            DatePicker("Date", selection: draftBinding, in: minimum..., displayedComponents: .date)
        } else if let maximum {
            DatePicker("Date", selection: draftBinding, in: ...maximum, displayedComponents: .date)
        } else {
            DatePicker("Date", selection: draftBinding, displayedComponents: .date)
        }
    }
}
