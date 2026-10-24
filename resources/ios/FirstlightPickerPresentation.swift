import SwiftUI

extension View {
    /// Presents Date and Time Picker content on iPhone as a sheet sized to the content, so the
    /// calendar or wheel and its actions are not stretched into a full-height sheet. A compact
    /// popover is not used: it is anchored to the trigger, and when a trigger sits mid-screen
    /// UIKit shrinks it to the space on one side, clipping the actions. Regular width keeps
    /// the popover. The large detent applies only until the content is measured; the system caps
    /// a taller measured detent at the large height.
    func firstlightPickerPresentation() -> some View {
        modifier(FirstlightPickerPresentation())
    }
}

private struct FirstlightPickerPresentation: ViewModifier {
    @State private var height: CGFloat = 0

    func body(content: Content) -> some View {
        content
            .fixedSize(horizontal: false, vertical: true)
            .onGeometryChange(for: CGFloat.self) { $0.size.height } action: { height = $0 }
            .presentationDetents([height > 0 ? .height(height) : .large])
            .presentationCompactAdaptation(.sheet)
    }
}
