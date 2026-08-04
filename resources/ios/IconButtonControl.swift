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
    let onPress: () -> Void

    let iconIsDecorative = true
    let minimumTarget: CGFloat = 44

    var metrics: FirstlightIconButtonMetrics { configuration.size.metrics }

    var body: some View {
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
                    Image(systemName: getIconForName(configuration.icon))
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
