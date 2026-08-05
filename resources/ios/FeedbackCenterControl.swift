import SwiftUI

enum FeedbackCenterFocusedControl: Hashable {
    case action
    case dismiss
}

enum FeedbackCenterMotionStyle: Equatable {
    case opacityOnly
    case moveAndFade
}

enum FeedbackCenterPresentation {
    static func motionStyle(reduceMotion: Bool) -> FeedbackCenterMotionStyle {
        reduceMotion ? .opacityOnly : .moveAndFade
    }

    static func transition(reduceMotion: Bool) -> AnyTransition {
        switch motionStyle(reduceMotion: reduceMotion) {
        case .opacityOnly:
            .opacity
        case .moveAndFade:
            .move(edge: .bottom).combined(with: .opacity)
        }
    }
}

struct FeedbackCenterAnnouncementState {
    private var announcedID: String?

    mutating func consume(
        visible: FeedbackCenterItemConfiguration?
    ) -> String? {
        guard let visible else {
            announcedID = nil
            return nil
        }
        guard announcedID != visible.feedbackID else { return nil }

        announcedID = visible.feedbackID
        return visible.message
    }
}

enum FeedbackCenterLayoutCandidate: Equatable {
    case horizontal
    case vertical
}

struct FeedbackCenterSymbolRenderingPolicy: Equatable {
    let systemName: String
    let accessibilityHidden: Bool
}

struct FeedbackCenterActionRenderingPolicy: Equatable {
    let visibleLabel: String
    let accessibilityLabel: String
    let minimumTarget: CGSize
    let symbol: FeedbackCenterSymbolRenderingPolicy?
}

struct FeedbackCenterRenderingPolicy: Equatable {
    static let minimumActionTarget = CGSize(width: 44, height: 44)

    let toneSymbol: FeedbackCenterSymbolRenderingPolicy
    let action: FeedbackCenterActionRenderingPolicy?
    let dismiss: FeedbackCenterActionRenderingPolicy?
    let layoutCandidates: [FeedbackCenterLayoutCandidate]

    init(configuration: FeedbackCenterItemConfiguration) {
        let toneSymbolName = switch configuration.tone {
        case .default: "info.circle.fill"
        case .success: "checkmark.circle.fill"
        case .warning: "exclamationmark.triangle.fill"
        case .danger: "exclamationmark.octagon.fill"
        }
        toneSymbol = FeedbackCenterSymbolRenderingPolicy(
            systemName: toneSymbolName,
            accessibilityHidden: true
        )

        if let label = configuration.actionLabel,
           configuration.actionCallback != nil {
            action = FeedbackCenterActionRenderingPolicy(
                visibleLabel: label,
                accessibilityLabel: label,
                minimumTarget: Self.minimumActionTarget,
                symbol: nil
            )
        } else {
            action = nil
        }

        if configuration.hold, configuration.manualCallback != nil {
            dismiss = FeedbackCenterActionRenderingPolicy(
                visibleLabel: "Dismiss",
                accessibilityLabel: "Dismiss feedback",
                minimumTarget: Self.minimumActionTarget,
                symbol: FeedbackCenterSymbolRenderingPolicy(
                    systemName: "xmark",
                    accessibilityHidden: true
                )
            )
        } else {
            dismiss = nil
        }

        layoutCandidates = [.horizontal, .vertical]
    }
}

struct FeedbackCenterButtonLabel: View {
    let renderingPolicy: FeedbackCenterActionRenderingPolicy

    var body: some View {
        HStack(spacing: renderingPolicy.symbol == nil ? 0 : 5) {
            if let symbol = renderingPolicy.symbol {
                Image(systemName: symbol.systemName)
                    .accessibilityHidden(symbol.accessibilityHidden)
            }
            Text(renderingPolicy.visibleLabel)
        }
        .frame(
            minWidth: renderingPolicy.minimumTarget.width,
            minHeight: renderingPolicy.minimumTarget.height
        )
        .contentShape(Rectangle())
    }
}

struct FirstlightFeedbackCenterControl: View {
    let configuration: FeedbackCenterItemConfiguration
    let tokens: NativeUITokens
    let onAction: () -> Void
    let onDismiss: () -> Void
    let onAccessibilityFocusChanged: (Bool) -> Void
    let renderingPolicy: FeedbackCenterRenderingPolicy

    @AccessibilityFocusState private var focusedControl: FeedbackCenterFocusedControl?

    init(
        configuration: FeedbackCenterItemConfiguration,
        tokens: NativeUITokens,
        onAction: @escaping () -> Void,
        onDismiss: @escaping () -> Void,
        onAccessibilityFocusChanged: @escaping (Bool) -> Void
    ) {
        self.configuration = configuration
        self.tokens = tokens
        self.onAction = onAction
        self.onDismiss = onDismiss
        self.onAccessibilityFocusChanged = onAccessibilityFocusChanged
        renderingPolicy = FeedbackCenterRenderingPolicy(configuration: configuration)
    }

    @ViewBuilder
    var body: some View {
        if renderingPolicy.layoutCandidates.contains(.vertical) {
            ViewThatFits(in: .horizontal) {
                horizontalLayout
                verticalLayout
            }
            .feedbackCenterNoticeChrome(tokens: tokens)
            .onChange(of: focusedControl) { _, focusedControl in
                onAccessibilityFocusChanged(focusedControl != nil)
            }
        } else {
            horizontalLayout
                .feedbackCenterNoticeChrome(tokens: tokens)
                .onChange(of: focusedControl) { _, focusedControl in
                    onAccessibilityFocusChanged(focusedControl != nil)
                }
        }
    }

    private var horizontalLayout: some View {
        HStack(alignment: .center, spacing: 12) {
            toneSymbol
            message
            Spacer(minLength: 8)
            actions
        }
    }

    private var verticalLayout: some View {
        VStack(alignment: .leading, spacing: 8) {
            HStack(alignment: .firstTextBaseline, spacing: 12) {
                toneSymbol
                message
            }

            if renderingPolicy.action != nil || renderingPolicy.dismiss != nil {
                actions
                    .frame(maxWidth: .infinity, alignment: .trailing)
            }
        }
    }

    private var message: some View {
        Text(configuration.message)
            .font(.body)
            .lineLimit(nil)
            .fixedSize(horizontal: false, vertical: true)
            .accessibilityAddTraits(.isStaticText)
    }

    private var toneSymbol: some View {
        Image(systemName: renderingPolicy.toneSymbol.systemName)
            .font(.title3.weight(.semibold))
            .foregroundStyle(accentColor)
            .accessibilityHidden(renderingPolicy.toneSymbol.accessibilityHidden)
    }

    @ViewBuilder
    private var actions: some View {
        HStack(spacing: 4) {
            if let action = renderingPolicy.action {
                Button {
                    onAction()
                } label: {
                    FeedbackCenterButtonLabel(renderingPolicy: action)
                }
                .buttonStyle(.bordered)
                .controlSize(.regular)
                .tint(accentColor)
                .accessibilityLabel(Text(action.accessibilityLabel))
                .accessibilityFocused($focusedControl, equals: .action)
            }

            if let dismiss = renderingPolicy.dismiss {
                Button {
                    onDismiss()
                } label: {
                    FeedbackCenterButtonLabel(renderingPolicy: dismiss)
                }
                .buttonStyle(.bordered)
                .controlSize(.regular)
                .accessibilityLabel(Text(dismiss.accessibilityLabel))
                .accessibilityFocused($focusedControl, equals: .dismiss)
            }
        }
    }

    private var accentColor: Color {
        switch configuration.tone {
        case .default: tokens.primary
        case .success: Color(uiColor: .systemGreen)
        case .warning: Color(uiColor: .systemOrange)
        case .danger: tokens.destructive
        }
    }
}

private extension View {
    func feedbackCenterNoticeChrome(tokens: NativeUITokens) -> some View {
        padding(.leading, 16)
            .padding(.trailing, 10)
            .padding(.vertical, 10)
            .foregroundStyle(tokens.onSurface)
            .background(
                .regularMaterial,
                in: RoundedRectangle(cornerRadius: 18, style: .continuous)
            )
            .overlay {
                RoundedRectangle(cornerRadius: 18, style: .continuous)
                    .stroke(Color(uiColor: .separator).opacity(0.35), lineWidth: 0.5)
                    .allowsHitTesting(false)
            }
            .shadow(color: .black.opacity(0.12), radius: 14, y: 5)
            .fixedSize(horizontal: false, vertical: true)
    }
}
