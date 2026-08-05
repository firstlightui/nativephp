import SwiftUI
import UIKit

enum CalloutTone: String, CaseIterable {
    case neutral
    case info
    case success
    case warning
    case danger

    var accessibilityName: String {
        switch self {
        case .neutral: "Notice"
        case .info: "Information"
        case .success: "Success"
        case .warning: "Warning"
        case .danger: "Error"
        }
    }

    var systemImageName: String {
        switch self {
        case .neutral: "bell.fill"
        case .info: "info.circle.fill"
        case .success: "checkmark.circle.fill"
        case .warning: "exclamationmark.triangle.fill"
        case .danger: "exclamationmark.octagon.fill"
        }
    }

    var statusLabelTone: StatusLabelTone {
        StatusLabelTone(rawValue: rawValue) ?? .info
    }
}

let firstlightCalloutActionMinimumHeight: CGFloat = 44

func firstlightCalloutAccessibilityLabel(
    message: String,
    tone: CalloutTone,
    explicit: String
) -> String {
    explicit.isEmpty ? "\(tone.accessibilityName): \(message)" : explicit
}

struct FirstlightCalloutControl: View {
    let configuration: CalloutRendererConfiguration
    let tokens: NativeUITokens
    let onPress: () -> Void

    @Environment(\.colorScheme) private var colorScheme

    var body: some View {
        let traits = UITraitCollection(
            userInterfaceStyle: colorScheme == .dark ? .dark : .light
        )
        let colours = FirstlightStatusLabelTokens.from(
            theme: tokens,
            tone: configuration.tone.statusLabelTone,
            traits: traits
        )
        let foreground = Color(uiColor: colours.foreground)

        HStack(alignment: .top, spacing: 12) {
            Image(systemName: configuration.tone.systemImageName)
                .font(.body.weight(.semibold))
                .foregroundStyle(foreground)
                .accessibilityHidden(true)

            VStack(alignment: .leading, spacing: 4) {
                Text(configuration.message)
                    .font(.callout)
                    .foregroundStyle(foreground)
                    .fixedSize(horizontal: false, vertical: true)
                    .accessibilityLabel(Text(configuration.resolvedAccessibilityLabel))
                    .accessibilityHint(Text(configuration.accessibilityHint))

                if configuration.hasAction {
                    Button(action: onPress) {
                        Text(configuration.actionLabel)
                            .font(.callout.weight(.semibold))
                            .frame(minHeight: firstlightCalloutActionMinimumHeight)
                            .contentShape(Rectangle())
                    }
                    .buttonStyle(.plain)
                    .foregroundStyle(foreground)
                    .accessibilityLabel(Text(configuration.actionLabel))
                }
            }
            .frame(maxWidth: .infinity, alignment: .leading)
        }
        .padding(12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(
            RoundedRectangle(cornerRadius: 12, style: .continuous)
                .fill(Color(uiColor: colours.background))
        )
        .overlay(
            RoundedRectangle(cornerRadius: 12, style: .continuous)
                .stroke(foreground.opacity(0.3), lineWidth: 1)
        )
        .accessibilityElement(children: .contain)
    }
}
