import SwiftUI

struct MetricControl: View {
    let label: String
    let value: String
    let detail: String
    let foreground: Color
    let valueColor: Color
    let accessibilityLabel: String
    let accessibilityHint: String

    var body: some View {
        VStack(alignment: .leading, spacing: 4) {
            Text(label).font(.subheadline)
            Text(value)
                .font(.title.weight(.semibold))
                .monospacedDigit()
                .foregroundStyle(valueColor)
            if !detail.isEmpty {
                Text(detail).font(.footnote)
            }
        }
        .foregroundStyle(foreground)
        .lineLimit(nil)
        .fixedSize(horizontal: false, vertical: true)
        .accessibilityElement(children: .ignore)
        .accessibilityAddTraits(.isStaticText)
        .accessibilityLabel(Text(accessibilityLabel))
        .accessibilityHint(Text(accessibilityHint))
    }
}
