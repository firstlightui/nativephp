import SwiftUI

struct MetricRendererConfiguration: Equatable {
    let label: String
    let value: String
    let detail: String
    let tone: StatusLabelTone
    let accessibilityLabel: String
    let accessibilityHint: String

    init(node: NativeUINode) {
        label = node.props.getString("label")
        value = node.props.getString("value")
        detail = node.props.getString("detail")
        tone = StatusLabelTone(rawValue: node.props.getString("tone")) ?? .neutral
        let explicit = node.props.getString("a11y_label")
        accessibilityLabel = explicit.isEmpty
            ? "\(label): \(value)" + (detail.isEmpty ? "" : ", \(detail)") : explicit
        accessibilityHint = node.props.getString("a11y_hint")
    }
}

struct MetricRenderer: View {
    let node: NativeUINode
    @ObservedObject private var themeStore = NativeUITheme.shared
    @Environment(\.colorScheme) private var colorScheme

    var body: some View {
        let configuration = MetricRendererConfiguration(node: node)
        let theme = themeStore.resolve(for: colorScheme)
        let valueColor: Color = switch configuration.tone {
        case .neutral: theme.onSurface
        case .info: theme.primary
        case .success: theme.success
        case .warning: FirstlightWarningColors(node: node).resolve(isDark: colorScheme == .dark).background
        case .danger: theme.destructive
        }
        MetricControl(
            label: configuration.label, value: configuration.value, detail: configuration.detail,
            foreground: theme.onSurface, valueColor: valueColor,
            accessibilityLabel: configuration.accessibilityLabel,
            accessibilityHint: configuration.accessibilityHint
        )
    }
}
