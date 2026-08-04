import SwiftUI

struct FirstlightBadgeControl: View {
    let label: String
    let tokens: FirstlightStatusLabelTokens
    let accessibilityLabel: String
    let accessibilityHint: String

    var isHidden: Bool { label.isEmpty }
    let isInteractive = false

    @ViewBuilder var body: some View {
        if !isHidden {
            Text(label)
                .font(.caption2.weight(.bold))
                .monospacedDigit()
                .foregroundStyle(Color(uiColor: tokens.foreground))
                .lineLimit(1)
                .padding(.horizontal, label.count > 1 ? 6 : 5)
                .padding(.vertical, 2)
                .background(Capsule().fill(Color(uiColor: tokens.background)))
                .accessibilityElement(children: .ignore)
                .accessibilityLabel(Text(accessibilityLabel))
                .accessibilityHint(Text(accessibilityHint))
        }
    }
}
