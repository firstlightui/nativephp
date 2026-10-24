import SwiftUI

enum FirstlightListItemLeadingType: String {
    case none = ""
    case icon
    case avatar
    case monogram

    init(wireValue: String) {
        self = Self(rawValue: wireValue) ?? .none
    }
}

enum FirstlightListItemTrailingType: String {
    case none = ""
    case icon
    case text

    init(wireValue: String) {
        self = Self(rawValue: wireValue) ?? .none
    }
}

func firstlightListItemAccessibilityLabel(
    headline: String,
    supporting: String,
    explicit: String
) -> String {
    guard explicit.isEmpty else { return explicit }

    return [headline, supporting]
        .filter { !$0.isEmpty }
        .joined(separator: ", ")
}

struct FirstlightListItemControl: View {
    let configuration: ListItemRendererConfiguration
    let tokens: NativeUITokens
    let onPress: () -> Void
    var onAction: (Int) -> Void = { _ in }
    @Environment(\.colorScheme) private var colorScheme

    let leadingContentIsDecorative = true
    let trailingContentIsDecorative = true
    let minimumTarget: CGFloat = 44

    var body: some View {
        Button(action: onPress) {
            HStack(spacing: 16) {
                leadingContent

                VStack(alignment: .leading, spacing: 2) {
                    Text(configuration.headline)
                        .font(.body)
                        .foregroundStyle(tokens.onSurface)
                        .multilineTextAlignment(.leading)
                        .frame(maxWidth: .infinity, alignment: .leading)

                    if !configuration.supporting.isEmpty {
                        Text(configuration.supporting)
                            .font(.subheadline)
                            .foregroundStyle(tokens.onSurfaceVariant)
                            .multilineTextAlignment(.leading)
                            .frame(maxWidth: .infinity, alignment: .leading)
                    }
                }

                trailingContent
            }
            .padding(.horizontal, 16)
            .padding(.vertical, 12)
            .frame(maxWidth: .infinity, minHeight: minimumTarget, alignment: .leading)
            .contentShape(Rectangle())
        }
        .buttonStyle(.plain)
        .disabled(!configuration.isEnabled)
        .opacity(configuration.disabled ? 0.5 : 1)
        .accessibilityElement(children: .ignore)
        .accessibilityLabel(Text(configuration.resolvedAccessibilityLabel))
        .accessibilityHint(Text(configuration.accessibilityHint))
        .modifier(FirstlightListItemContextMenu(
            items: configuration.hasContextMenu ? configuration.contextMenu : [],
            onAction: { callbackID in
                guard configuration.actionEvent(callbackID: callbackID) != nil else { return }
                onAction(callbackID)
            }
        ))
        .swipeActions(edge: .leading, allowsFullSwipe: false) {
            actionButtons(configuration.leadingActions)
        }
        .swipeActions(edge: .trailing, allowsFullSwipe: false) {
            actionButtons(configuration.trailingActions)
        }
    }

    @ViewBuilder
    private func actionButtons(_ actions: [ListItemSwipeAction]) -> some View {
        if configuration.isEnabled {
            ForEach(Array(actions.enumerated()), id: \.offset) { _, action in
                // A SwiftUI destructive-role swipe button can dismiss the row
                // optimistically. Only PHP may remove it (possibly after confirmation).
                Button {
                    guard configuration.actionEvent(callbackID: action.cb) != nil else { return }
                    onAction(action.cb)
                } label: {
                    if action.icon.isEmpty {
                        Text(action.label)
                    } else {
                        Label(action.label, systemImage: getIconForName(action.icon))
                    }
                }
                .tint(action.role == "destructive" ? tokens.destructive : tokens.primary)
                .accessibilityLabel(Text(action.label))
            }
        }
    }

    @ViewBuilder
    private var leadingContent: some View {
        switch configuration.leadingType {
        case .none:
            EmptyView()
        case .icon:
            Image(systemName: getIconForName(configuration.leadingValue))
                .font(.title3.weight(.medium))
                .foregroundStyle(firstlightListItemIconColor(
                    explicit: configuration.leadingIconColor, tone: configuration.leadingIconTone,
                    tokens: tokens, warningColors: configuration.warningColors,
                    isDark: colorScheme == .dark, fallback: tokens.primary
                ))
                .frame(width: 40, height: 40)
                .accessibilityHidden(true)
        case .avatar:
            AsyncImage(url: URL(string: configuration.leadingValue)) { image in
                image.resizable().scaledToFill()
            } placeholder: {
                Circle().fill(tokens.surfaceVariant)
            }
            .frame(width: 40, height: 40)
            .clipShape(Circle())
            .accessibilityHidden(true)
        case .monogram:
            ZStack {
                Circle().fill(tokens.primary)
                Text(configuration.leadingValue.uppercased())
                    .font(.headline.weight(.semibold))
                    .foregroundStyle(tokens.onPrimary)
                    .lineLimit(1)
                    .minimumScaleFactor(0.7)
            }
            .frame(width: 40, height: 40)
            .accessibilityHidden(true)
        }
    }

    @ViewBuilder
    private var trailingContent: some View {
        switch configuration.trailingType {
        case .none:
            EmptyView()
        case .icon:
            Image(systemName: getIconForName(configuration.trailingValue))
                .font(.body.weight(.semibold))
                .foregroundStyle(firstlightListItemIconColor(
                    explicit: configuration.trailingIconColor, tone: configuration.trailingIconTone,
                    tokens: tokens, warningColors: configuration.warningColors,
                    isDark: colorScheme == .dark, fallback: tokens.onSurfaceVariant
                ))
                .frame(width: 24, height: 24)
                .accessibilityHidden(true)
        case .text:
            Text(configuration.trailingValue)
                .font(.subheadline)
                .foregroundStyle(tokens.onSurfaceVariant)
                .multilineTextAlignment(.trailing)
                .accessibilityHidden(true)
        }
    }
}

/// Presents published menu items through the system context menu and
/// mirrors enabled items as VoiceOver actions, so no long press is required.
struct FirstlightListItemContextMenu: ViewModifier {
    let items: [ListItemMenuItem]
    let onAction: (Int) -> Void

    func body(content: Content) -> some View {
        if items.isEmpty {
            content
        } else {
            content
                .contextMenu {
                    ForEach(Array(items.enumerated()), id: \.offset) { _, item in
                        // A context menu destructive role only styles the item;
                        // PHP still owns confirmation and removal.
                        Button(role: item.role == "destructive" ? .destructive : nil) {
                            if item.isEnabled { onAction(item.cb) }
                        } label: {
                            if item.icon.isEmpty {
                                Text(item.label)
                            } else {
                                Label(item.label, systemImage: getIconForName(item.icon))
                            }
                        }
                        .disabled(!item.isEnabled)
                    }
                }
                .accessibilityActions {
                    ForEach(Array(items.enumerated()), id: \.offset) { _, item in
                        if item.isEnabled {
                            Button(item.label) { onAction(item.cb) }
                        }
                    }
                }
        }
    }
}

func firstlightListItemIconColor(
    explicit: String, tone: String, tokens: NativeUITokens,
    warningColors: FirstlightWarningColors, isDark: Bool, fallback: Color
) -> Color {
    if explicit.hasPrefix("#") {
        var digits = String(explicit.dropFirst())
        if digits.count == 3 { digits = digits.map { "\($0)\($0)" }.joined() }
        if [6, 8].contains(digits.count), let value = UInt64(digits, radix: 16) {
            return Color(.sRGB,
                red: Double((value >> 16) & 255) / 255,
                green: Double((value >> 8) & 255) / 255,
                blue: Double(value & 255) / 255,
                opacity: digits.count == 8 ? Double((value >> 24) & 255) / 255 : 1)
        }
    }
    return switch tone {
    case "neutral": tokens.onSurfaceVariant
    case "info": tokens.primary
    case "success": tokens.success
    case "warning": warningColors.resolve(isDark: isDark).background
    case "danger": tokens.destructive
    default: fallback
    }
}
