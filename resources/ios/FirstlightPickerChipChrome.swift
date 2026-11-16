import SwiftUI

/// Chip-variant pickers draw their own capsule instead of relying on
/// `.buttonStyle(.bordered)`: a `Menu` trigger does not reliably adopt that
/// style, so select chips rendered without the fill that date chips had.
/// Chips keep their intrinsic width so a squeezed row cannot break their text
/// letter by letter. At accessibility text sizes, where the chip's parts stack,
/// or when even the capsule is wider than the row, the text wraps inside a
/// rounded rectangle instead of running off the screen. Material chip geometry
/// is not copied.
struct FirstlightPickerChipChrome: ViewModifier {
    let error: Color?
    @Environment(\.dynamicTypeSize) private var dynamicTypeSize

    @ViewBuilder
    func body(content: Content) -> some View {
        let wrapping = chrome(content, shape: RoundedRectangle(cornerRadius: 22, style: .continuous))
            .fixedSize(horizontal: false, vertical: true)

        if dynamicTypeSize.isAccessibilitySize {
            wrapping
        } else {
            ViewThatFits(in: .horizontal) {
                chrome(content.lineLimit(1), shape: Capsule(style: .continuous))
                    .fixedSize()
                wrapping
            }
        }
    }

    private func chrome(_ content: some View, shape: some Shape) -> some View {
        content
            .padding(.horizontal, 14)
            .padding(.vertical, 4)
            .frame(minHeight: 44)
            .background(shape.fill(Color(uiColor: .tertiarySystemFill)))
            .overlay {
                if let error {
                    shape.stroke(error, lineWidth: 1)
                }
            }
            .contentShape(shape)
    }
}
