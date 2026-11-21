import SwiftUI
import UIKit

struct FirstlightDatePickerControl: View {
    @Binding var state: DatePickerRendererState
    let tokens: NativeUITokens
    let onConfirm: () -> Void
    @Environment(\.dynamicTypeSize) private var dynamicTypeSize

    private var configuration: DatePickerRendererConfiguration { state.configuration }

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            if !configuration.label.isEmpty && configuration.variant != "chip" {
                Text(configuration.required ? "\(configuration.label) *" : configuration.label)
                    .font(.subheadline.weight(.medium))
                    .foregroundStyle(tokens.onSurface)
            }

            Button {
                state.open()
            } label: {
                let chip = configuration.variant == "chip"
                // As in Select: at accessibility sizes a chip's parts stack rather than break words.
                let stack = chip && dynamicTypeSize.isAccessibilitySize
                    ? AnyLayout(VStackLayout(alignment: .leading, spacing: 4))
                    : AnyLayout(HStackLayout(spacing: 8))
                let row = stack {
                    if chip && !configuration.label.isEmpty {
                        Text(configuration.required ? "\(configuration.label) *" : configuration.label)
                            .foregroundStyle(tokens.onSurfaceVariant)
                    }
                    Text(triggerText)
                        .foregroundStyle(configuration.acceptedValue == nil ? tokens.onSurfaceVariant : tokens.onSurface)
                        .frame(maxWidth: chip ? nil : .infinity, alignment: .leading)

                    Image(systemName: "calendar")
                        .foregroundStyle(tokens.onSurfaceVariant)
                        .accessibilityHidden(true)
                }

                if chip {
                    row.modifier(FirstlightPickerChipChrome(
                        error: configuration.error.isEmpty ? nil : tokens.destructive
                    ))
                } else {
                    row
                        .padding(.horizontal, 12)
                        .frame(minHeight: 44)
                        .background(tokens.surfaceVariant)
                        .clipShape(RoundedRectangle(cornerRadius: 10, style: .continuous))
                        .overlay {
                            RoundedRectangle(cornerRadius: 10, style: .continuous)
                                .stroke(configuration.error.isEmpty ? Color.clear : tokens.destructive, lineWidth: 1)
                        }
                        .contentShape(Rectangle())
                }
            }
            .buttonStyle(.plain)
            .disabled(!configuration.isInteractive)
            .accessibilityLabel(Text(
                configuration.required
                    ? "\(configuration.accessibilityLabel), required"
                    : configuration.accessibilityLabel
            ))
            .accessibilityHint(Text(accessibilityHint))
            .firstlightValidationFocusTarget()
            .accessibilityValue(Text(configuration.acceptedValue.map {
                DatePickerCalendar.display($0, locale: configuration.locale, timezone: configuration.timezone)
            } ?? configuration.placeholder))
            .popover(
                isPresented: Binding(
                    get: { state.isPresented },
                    set: { if !$0 { state.cancel() } }
                ),
                // No fixed arrow edge: iPad places the popover on whichever side of the field
                // has room for all of it. Forcing it above a field near the top squeezed it and
                // clipped the actions.
                attachmentAnchor: .rect(.bounds),
                arrowEdge: nil
            ) {
                pickerPresentation
                    .firstlightPickerPresentation()
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
        if configuration.variant == "chip", configuration.acceptedValue != nil, !configuration.displayText.isEmpty {
            return configuration.displayText
        }
        return configuration.acceptedValue.map {
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

    var pickerPresentation: some View {
        VStack(spacing: 0) {
            constrainedPicker
                .datePickerStyle(.graphical)
                .labelsHidden()
                .padding()

            Divider()

            HStack {
                Button(configuration.cancelLabel) { state.cancel() }
                    .firstlightGlassButtonStyle(prominent: false, bordered: false)
                Spacer()
                Button(configuration.confirmLabel, action: onConfirm)
                    .firstlightGlassButtonStyle(prominent: true)
            }
            .padding()
        }
        .tint(tokens.primary)
        .id(state.presentationVersion)
        .environment(\.calendar, DatePickerCalendar.pickerCalendar(
            timezone: configuration.timezone,
            firstDayOfWeek: configuration.firstDayOfWeek
        ))
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
