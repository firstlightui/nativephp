import SwiftUI

/// Chip-variant pickers draw their own capsule instead of relying on
/// `.buttonStyle(.bordered)`: a `Menu` trigger does not reliably adopt that
/// style, so select chips rendered without the fill that date chips had.
/// Chips keep their intrinsic width so a squeezed row cannot break their text
/// letter by letter. Material chip geometry is not copied.
struct FirstlightPickerChipChrome: ViewModifier {
    let error: Color?

    func body(content: Content) -> some View {
        content
            .lineLimit(1)
            .padding(.horizontal, 14)
            .frame(minHeight: 44)
            .background(Capsule(style: .continuous).fill(Color(uiColor: .tertiarySystemFill)))
            .overlay {
                if let error {
                    Capsule(style: .continuous).stroke(error, lineWidth: 1)
                }
            }
            .contentShape(Capsule(style: .continuous))
            .fixedSize()
    }
}
