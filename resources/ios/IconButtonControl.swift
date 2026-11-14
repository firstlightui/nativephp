import SwiftUI

enum FirstlightIconButtonVariant: String, CaseIterable {
    case primary
    case secondary
    case destructive
    case success
    case ghost
}

enum FirstlightIconButtonSize: String, CaseIterable {
    case small = "sm"
    case medium = "md"
    case large = "lg"

    var metrics: FirstlightIconButtonMetrics {
        switch self {
        case .small:
            FirstlightIconButtonMetrics(
                controlSize: .small,
                visualSize: 32,
                iconSize: 16,
                minimumTarget: 44
            )
        case .medium:
            FirstlightIconButtonMetrics(
                controlSize: .regular,
                visualSize: 40,
                iconSize: 20,
                minimumTarget: 44
            )
        case .large:
            FirstlightIconButtonMetrics(
                controlSize: .large,
                visualSize: 48,
                iconSize: 24,
                minimumTarget: 48
            )
        }
    }
}

struct FirstlightIconButtonMetrics: Equatable {
    let controlSize: ControlSize
    let visualSize: CGFloat
    let iconSize: CGFloat
    let minimumTarget: CGFloat
}

struct FirstlightIconButtonControl: View {
    let configuration: IconButtonRendererConfiguration
    let tokens: NativeUITokens
    /// Set only where Liquid Glass draws; nil keeps the variant's look.
    var glass: FirstlightGlassStyle? = nil
    let onPress: () -> Void

    let iconIsDecorative = true
    let minimumTarget: CGFloat = 44

    var metrics: FirstlightIconButtonMetrics { configuration.size.metrics }

    var body: some View {
        if let glass {
            glassButton(glass)
        } else {
            variantButton
        }
    }

    /// The variant's fill and the label drawn on it.
    private var variantColors: (fill: Color, onFill: Color) {
        switch configuration.variant {
        case .primary, .ghost: (tokens.primary, tokens.onPrimary)
        case .secondary: (tokens.surfaceVariant, tokens.onSurfaceVariant)
        case .destructive: (tokens.destructive, tokens.onDestructive)
        case .success: (tokens.success, tokens.onSuccess)
        }
    }

    /// Prominent glass fills with the variant's colour. Regular glass draws
    /// the icon in the system's primary style, which the glass keeps legible
    /// over bright or dark content (see FirstlightRegularGlassLabel). Disabled
    /// uses the same token pair as the other paths.
    private func glassButton(_ glass: FirstlightGlassStyle) -> some View {
        let colors = variantColors
        let prominent = glass == .prominent
        let foreground: Color = if configuration.disabled {
            tokens.onSurfaceVariant
        } else if prominent {
            colors.onFill
        } else {
            .primary
        }

        return button
            .firstlightGlassButtonStyle(prominent: prominent)
            .tint(configuration.disabled ? tokens.surfaceVariant : colors.fill)
            .foregroundStyle(foreground)
    }

    @ViewBuilder
    private var variantButton: some View {
        switch configuration.variant {
        case .ghost:
            button
                .buttonStyle(.plain)
                .foregroundStyle(configuration.disabled ? tokens.onSurfaceVariant : tokens.primary)
        case .secondary:
            button
                .buttonStyle(.borderedProminent)
                .tint(tokens.surfaceVariant)
                .foregroundStyle(tokens.onSurfaceVariant)
        case .destructive:
            prominentButton(tint: tokens.destructive, foreground: tokens.onDestructive)
        case .success:
            prominentButton(tint: tokens.success, foreground: tokens.onSuccess)
        case .primary:
            prominentButton(tint: tokens.primary, foreground: tokens.onPrimary)
        }
    }

    @ViewBuilder
    private func prominentButton(tint: Color, foreground: Color) -> some View {
        button
            .buttonStyle(.borderedProminent)
            .tint(configuration.disabled ? tokens.surfaceVariant : tint)
            .foregroundStyle(configuration.disabled ? tokens.onSurfaceVariant : foreground)
    }

    private var button: some View {
        Button(action: onPress) {
            Group {
                if configuration.loading {
                    ProgressView()
                        .controlSize(.small)
                        .accessibilityHidden(true)
                } else {
                    NativeIconView(configuration.icon, pointSize: metrics.iconSize)
                        .font(.system(size: metrics.iconSize, weight: .semibold))
                        .accessibilityHidden(true)
                }
            }
            .frame(width: metrics.visualSize, height: metrics.visualSize)
            .frame(
                minWidth: metrics.minimumTarget,
                minHeight: metrics.minimumTarget
            )
            .contentShape(Rectangle())
        }
        .buttonBorderShape(.circle)
        .controlSize(metrics.controlSize)
        .disabled(!configuration.isEnabled)
        .accessibilityElement(children: .ignore)
        .accessibilityLabel(Text(configuration.accessibilityLabel))
        .accessibilityHint(Text(configuration.accessibilityHint))
        .modifier(IconButtonLoadingAccessibilityValue(loading: configuration.loading))
    }
}

private struct IconButtonLoadingAccessibilityValue: ViewModifier {
    let loading: Bool

    func body(content: Content) -> some View {
        if loading {
            content.accessibilityValue(Text("Loading"))
        } else {
            content
        }
    }
}
