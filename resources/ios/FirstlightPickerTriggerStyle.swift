import SwiftUI

/// A compact picker keeps Apple's bordered button/menu treatment; full fields
/// retain their authored field chrome. Material chip geometry is not copied.
struct FirstlightPickerTriggerStyle: ViewModifier {
    let compact: Bool

    @ViewBuilder
    func body(content: Content) -> some View {
        if compact {
            content.buttonStyle(.bordered).buttonBorderShape(.capsule)
        } else {
            content.buttonStyle(.plain)
        }
    }
}
