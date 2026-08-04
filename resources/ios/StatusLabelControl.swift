import SwiftUI
import UIKit

enum StatusLabelTone: String, CaseIterable {
    case neutral
    case info
    case success
    case warning
    case danger
}

struct FirstlightStatusLabelTokens {
    let background: UIColor
    let foreground: UIColor

    static func from(
        theme: NativeUITokens,
        tone: StatusLabelTone,
        traits: UITraitCollection
    ) -> Self {
        let pair: (background: Color, foreground: Color) = switch tone {
        case .neutral: (theme.surfaceVariant, theme.onSurfaceVariant)
        case .info: (theme.primary, theme.onPrimary)
        case .success: (theme.success, theme.onSuccess)
        case .warning: (theme.accent, theme.onAccent)
        case .danger: (theme.destructive, theme.onDestructive)
        }

        let surface = statusLabelOpaqueComposite(
            foreground: UIColor(theme.surface),
            over: .systemBackground,
            traits: traits
        )
        let background = statusLabelOpaqueComposite(
            foreground: UIColor(pair.background),
            over: surface,
            traits: traits
        )
        let preferred = statusLabelOpaqueComposite(
            foreground: UIColor(pair.foreground),
            over: background,
            traits: traits
        )
        let foreground = statusLabelContrastRatio(preferred, background) >= 4.5
            ? preferred
            : [UIColor.black, .white].max {
                statusLabelContrastRatio($0, background)
                    < statusLabelContrastRatio($1, background)
            }!

        return Self(background: background, foreground: foreground)
    }
}

struct FirstlightStatusLabelControl: View {
    let label: String
    let tokens: FirstlightStatusLabelTokens
    let accessibilityLabel: String
    let accessibilityHint: String

    let isInteractive = false

    var body: some View {
        Text(label)
            .font(.footnote.weight(.semibold))
            .foregroundStyle(Color(uiColor: tokens.foreground))
            .lineLimit(nil)
            .fixedSize(horizontal: false, vertical: true)
            .padding(.horizontal, 10)
            .padding(.vertical, 5)
            .background(
                Capsule().fill(Color(uiColor: tokens.background))
            )
            .accessibilityElement(children: .ignore)
            .accessibilityLabel(Text(accessibilityLabel))
            .accessibilityHint(Text(accessibilityHint))
    }
}

func statusLabelContrastRatio(_ foreground: UIColor, _ background: UIColor) -> CGFloat {
    let foregroundLuminance = statusLabelRelativeLuminance(foreground)
    let backgroundLuminance = statusLabelRelativeLuminance(background)

    return (max(foregroundLuminance, backgroundLuminance) + 0.05)
        / (min(foregroundLuminance, backgroundLuminance) + 0.05)
}

private func statusLabelOpaqueComposite(
    foreground: UIColor,
    over background: UIColor,
    traits: UITraitCollection
) -> UIColor {
    let foreground = statusLabelRGBA(foreground, traits: traits)
    let background = statusLabelRGBA(background, traits: traits)
    let alpha = foreground.alpha + background.alpha * (1 - foreground.alpha)

    guard alpha > 0 else { return .black }

    return UIColor(
        red: (foreground.red * foreground.alpha
            + background.red * background.alpha * (1 - foreground.alpha)) / alpha,
        green: (foreground.green * foreground.alpha
            + background.green * background.alpha * (1 - foreground.alpha)) / alpha,
        blue: (foreground.blue * foreground.alpha
            + background.blue * background.alpha * (1 - foreground.alpha)) / alpha,
        alpha: alpha
    )
}

private func statusLabelRGBA(
    _ color: UIColor,
    traits: UITraitCollection = .current
) -> (red: CGFloat, green: CGFloat, blue: CGFloat, alpha: CGFloat) {
    let resolved = color.resolvedColor(with: traits)
    var red: CGFloat = 0
    var green: CGFloat = 0
    var blue: CGFloat = 0
    var alpha: CGFloat = 0

    guard resolved.getRed(&red, green: &green, blue: &blue, alpha: &alpha) else {
        return (0, 0, 0, 1)
    }

    return (red, green, blue, alpha)
}

private func statusLabelRelativeLuminance(_ color: UIColor) -> CGFloat {
    let components = statusLabelRGBA(color)

    func linearized(_ channel: CGFloat) -> CGFloat {
        channel <= 0.04045
            ? channel / 12.92
            : pow((channel + 0.055) / 1.055, 2.4)
    }

    return 0.2126 * linearized(components.red)
        + 0.7152 * linearized(components.green)
        + 0.0722 * linearized(components.blue)
}
