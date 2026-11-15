import SwiftUI

/// The props Firstlight's ghost and secondary Buttons read from the published
/// node. The other variants keep the official Mobile UI renderer unchanged.
struct FirstlightButtonConfiguration: Equatable {
    let nodeID: Int
    let label: String
    let leadingIcon: String
    let trailingIcon: String
    let size: String
    let disabled: Bool
    let loading: Bool
    let fillsAllocatedWidth: Bool
    let accessibilityLabel: String
    let accessibilityHint: String
    let callbackID: Int

    init(node: NativeUINode) {
        let props = node.props
        nodeID = node.id
        label = props.getString("label")
        leadingIcon = props.getString("leading_icon")
        trailingIcon = props.getString("trailing_icon")
        size = props.getString("size", default: "md")
        disabled = props.getBool("disabled")
        loading = props.getBool("loading")
        fillsAllocatedWidth = node.layout?.widthMode == SizeMode.fill
        accessibilityLabel = props.getString("a11y_label")
        accessibilityHint = props.getString("a11y_hint")
        let explicit = props.getCallbackId("on_press")
        callbackID = explicit != 0 ? explicit : node.onPress
    }

    var isEnabled: Bool {
        !disabled && !loading
    }
}

/// Mobile UI draws ghost as `.buttonStyle(.plain)`, whose hit area is only
/// the label's glyphs (about 19 pt tall). Firstlight keeps the same plain,
/// tinted look and gives the label a 44 pt minimum target.
struct FirstlightGhostButtonControl: View {
    let configuration: FirstlightButtonConfiguration
    let tokens: NativeUITokens
    var glass: FirstlightGlassStyle? = nil
    let onPress: () -> Void

    static let minimumTarget: CGFloat = 44

    var body: some View {
        if let glass {
            // A glass ghost takes the system glass button's own height, as
            // the other variants do, rather than the plain label's 44 pt frame.
            label(minimumHeight: nil)
                .firstlightGlassButtonStyle(prominent: glass == .prominent)
                .tint(configuration.isEnabled ? tokens.primary : tokens.surfaceVariant)
                .foregroundStyle(glassForeground(glass))
                .disabled(!configuration.isEnabled)
                .modifier(FirstlightButtonAccessibility(configuration: configuration))
        } else {
            label(minimumHeight: Self.minimumTarget)
                .buttonStyle(.plain)
                .foregroundStyle(configuration.isEnabled ? tokens.primary : tokens.onSurfaceVariant)
                .disabled(!configuration.isEnabled)
                .modifier(FirstlightButtonAccessibility(configuration: configuration))
        }
    }

    private func glassForeground(_ glass: FirstlightGlassStyle) -> Color {
        guard configuration.isEnabled else { return tokens.onSurfaceVariant }

        // Regular glass keeps a system label; see FirstlightRegularGlassLabel.
        return glass == .prominent ? tokens.onPrimary : .primary
    }

    private func label(minimumHeight: CGFloat?) -> some View {
        Button(action: onPress) {
            FirstlightButtonLabel(configuration: configuration, tokens: tokens)
                .frame(
                    maxWidth: configuration.fillsAllocatedWidth ? .infinity : nil,
                    minHeight: minimumHeight
                )
                .contentShape(Rectangle())
        }
    }
}

/// Mobile UI draws `secondary` as `.borderedProminent` in the secondary
/// colour, a second filled button beside the primary one. Apple's Human
/// Interface Guidelines give secondary actions the lower-emphasis `.bordered`
/// style: a light tinted fill with the label in the tint. Android's Material 3
/// `FilledTonalButton` is already that emphasis, so only iOS needs this. Glass
/// secondary buttons stay on Mobile UI's system glass styles.
struct FirstlightSecondaryButtonControl: View {
    let configuration: FirstlightButtonConfiguration
    let tokens: NativeUITokens
    let onPress: () -> Void

    var body: some View {
        Button(action: onPress) {
            FirstlightButtonLabel(configuration: configuration, tokens: tokens)
                .frame(maxWidth: configuration.fillsAllocatedWidth ? .infinity : nil)
        }
        .buttonStyle(.bordered)
        .controlSize(controlSize)
        .tint(tokens.secondary)
        .foregroundStyle(configuration.isEnabled ? tokens.secondary : tokens.onSurfaceVariant)
        .disabled(!configuration.isEnabled)
        .modifier(FirstlightButtonAccessibility(configuration: configuration))
    }

    // Mobile UI's sizes: the system bordered capsule at each control size.
    private var controlSize: ControlSize {
        switch configuration.size {
        case "sm": .small
        case "lg": .large
        default: .regular
        }
    }
}

/// The label both Firstlight-drawn variants share: an optional spinner or
/// leading icon, the text, and an optional trailing icon.
private struct FirstlightButtonLabel: View {
    let configuration: FirstlightButtonConfiguration
    let tokens: NativeUITokens

    // Mobile UI's sm / md / lg label sizes, scaled with Dynamic Type.
    @ScaledMetric(relativeTo: .subheadline) private var smallText: CGFloat = 14
    @ScaledMetric(relativeTo: .body) private var mediumText: CGFloat = 16
    @ScaledMetric(relativeTo: .title3) private var largeText: CGFloat = 20

    private var textSize: CGFloat {
        switch configuration.size {
        case "sm": smallText
        case "lg": largeText
        default: mediumText
        }
    }

    var body: some View {
        HStack(spacing: 8) {
            if configuration.loading {
                ProgressView()
                    .controlSize(.small)
                    .tint(tokens.onSurfaceVariant)
            } else if !configuration.leadingIcon.isEmpty {
                NativeIconView(configuration.leadingIcon, pointSize: textSize)
                    .font(.system(size: textSize, weight: .medium))
                    .accessibilityHidden(true)
            }
            Text(configuration.label)
                .font(.system(size: textSize, weight: .medium))
            if !configuration.loading && !configuration.trailingIcon.isEmpty {
                NativeIconView(configuration.trailingIcon, pointSize: textSize)
                    .font(.system(size: textSize, weight: .medium))
                    .accessibilityHidden(true)
            }
        }
    }
}

private struct FirstlightButtonAccessibility: ViewModifier {
    let configuration: FirstlightButtonConfiguration

    func body(content: Content) -> some View {
        content
            .accessibilityElement(children: .combine)
            .accessibilityLabel(Text(configuration.accessibilityLabel.isEmpty ? configuration.label : configuration.accessibilityLabel))
            .accessibilityHint(Text(configuration.accessibilityHint))
            .accessibilityValue(Text(configuration.loading ? "Loading" : ""))
    }
}
