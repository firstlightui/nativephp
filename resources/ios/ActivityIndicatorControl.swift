import SwiftUI

enum ActivityIndicatorSize: String, CaseIterable {
    case small = "sm"
    case medium = "md"
    case large = "lg"

    var controlSize: ControlSize {
        switch self {
        case .small: .small
        case .medium: .regular
        case .large: .large
        }
    }
}

struct FirstlightActivityIndicatorControl: View {
    let size: ActivityIndicatorSize
    let accessibilityLabel: String
    let tint: Color

    let isInteractive = false

    var body: some View {
        ProgressView()
            .progressViewStyle(.circular)
            .controlSize(size.controlSize)
            .tint(tint)
            .accessibilityLabel(Text(accessibilityLabel))
    }
}
