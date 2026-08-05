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
    }

    @ViewBuilder
    private var leadingContent: some View {
        switch configuration.leadingType {
        case .none:
            EmptyView()
        case .icon:
            Image(systemName: getIconForName(configuration.leadingValue))
                .font(.title3.weight(.medium))
                .foregroundStyle(tokens.primary)
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
                .foregroundStyle(tokens.onSurfaceVariant)
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
