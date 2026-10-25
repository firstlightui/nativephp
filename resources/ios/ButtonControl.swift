import SwiftUI

/// The props Firstlight's ghost Button reads from the published node. The
/// other variants keep the official Mobile UI renderer unchanged.
struct ButtonGhostConfiguration: Equatable {
    let nodeID: Int
    let label: String
    let leadingIcon: String
    let trailingIcon: String
    let size: String
    let disabled: Bool
    let loading: Bool
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
    let configuration: ButtonGhostConfiguration
    let tokens: NativeUITokens
    let onPress: () -> Void

    static let minimumTarget: CGFloat = 44

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
        Button(action: onPress) {
            HStack(spacing: 8) {
                if configuration.loading {
                    ProgressView()
                        .controlSize(.small)
                        .tint(tokens.onSurfaceVariant)
                } else if !configuration.leadingIcon.isEmpty {
                    Image(systemName: getIconForName(configuration.leadingIcon))
                        .font(.system(size: textSize, weight: .medium))
                        .accessibilityHidden(true)
                }
                Text(configuration.label)
                    .font(.system(size: textSize, weight: .medium))
                if !configuration.loading && !configuration.trailingIcon.isEmpty {
                    Image(systemName: getIconForName(configuration.trailingIcon))
                        .font(.system(size: textSize, weight: .medium))
                        .accessibilityHidden(true)
                }
            }
            .frame(minHeight: Self.minimumTarget)
            .contentShape(Rectangle())
        }
        .buttonStyle(.plain)
        .foregroundStyle(configuration.isEnabled ? tokens.primary : tokens.onSurfaceVariant)
        .disabled(!configuration.isEnabled)
        .modifier(GhostButtonAccessibility(configuration: configuration))
    }
}

private struct GhostButtonAccessibility: ViewModifier {
    let configuration: ButtonGhostConfiguration

    func body(content: Content) -> some View {
        content
            .accessibilityElement(children: .combine)
            .accessibilityLabel(Text(configuration.accessibilityLabel.isEmpty ? configuration.label : configuration.accessibilityLabel))
            .accessibilityHint(Text(configuration.accessibilityHint))
            .accessibilityValue(Text(configuration.loading ? "Loading" : ""))
    }
}
