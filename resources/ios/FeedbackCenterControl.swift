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

struct FirstlightFeedbackCenterControl: View {
    let configuration: FeedbackCenterItemConfiguration
    let tokens: NativeUITokens
    let onAction: () -> Void
    let onDismiss: () -> Void
    let onAccessibilityFocusChanged: (Bool) -> Void

    @AccessibilityFocusState private var focusedControl: FeedbackCenterFocusedControl?

    let symbolIsDecorative = true
    let minimumTarget: CGFloat = 44
    let reflowsActionsWhenConstrained = true
    let dismissAccessibilityLabel = "Dismiss feedback"

    var hasActionButton: Bool {
        configuration.actionLabel != nil && configuration.actionCallback != nil
    }

    var hasDismissButton: Bool {
        configuration.hold && configuration.manualCallback != nil
    }

    var actionAccessibilityLabel: String? { configuration.actionLabel }

    var body: some View {
        ViewThatFits(in: .horizontal) {
            horizontalLayout
            verticalLayout
        }
        .padding(.leading, 16)
        .padding(.trailing, 10)
        .padding(.vertical, 10)
        .foregroundStyle(tokens.onSurface)
        .background(.regularMaterial, in: RoundedRectangle(cornerRadius: 18, style: .continuous))
        .overlay {
            RoundedRectangle(cornerRadius: 18, style: .continuous)
                .stroke(Color(uiColor: .separator).opacity(0.35), lineWidth: 0.5)
                .allowsHitTesting(false)
        }
        .shadow(color: .black.opacity(0.12), radius: 14, y: 5)
        .fixedSize(horizontal: false, vertical: true)
        .onChange(of: focusedControl) { _, focusedControl in
            onAccessibilityFocusChanged(focusedControl != nil)
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

            if hasActionButton || hasDismissButton {
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
        Image(systemName: symbolName)
            .font(.title3.weight(.semibold))
            .foregroundStyle(accentColor)
            .accessibilityHidden(true)
    }

    @ViewBuilder
    private var actions: some View {
        HStack(spacing: 4) {
            if let label = configuration.actionLabel,
               configuration.actionCallback != nil {
                Button(label) {
                    onAction()
                }
                .buttonStyle(.bordered)
                .controlSize(.regular)
                .tint(accentColor)
                .frame(minHeight: minimumTarget)
                .accessibilityLabel(Text(label))
                .accessibilityFocused($focusedControl, equals: .action)
            }

            if configuration.hold, configuration.manualCallback != nil {
                Button {
                    onDismiss()
                } label: {
                    HStack(spacing: 5) {
                        Image(systemName: "xmark")
                            .accessibilityHidden(true)
                        Text("Dismiss")
                    }
                }
                .buttonStyle(.bordered)
                .controlSize(.regular)
                .frame(minHeight: minimumTarget)
                .accessibilityLabel(Text(dismissAccessibilityLabel))
                .accessibilityFocused($focusedControl, equals: .dismiss)
            }
        }
    }

    private var symbolName: String {
        switch configuration.tone {
        case .default: "info.circle.fill"
        case .success: "checkmark.circle.fill"
        case .warning: "exclamationmark.triangle.fill"
        case .danger: "exclamationmark.octagon.fill"
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
