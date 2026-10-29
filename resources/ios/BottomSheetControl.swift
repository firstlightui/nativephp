import SwiftUI

/// The single detent for a sheet whose content measures `contentHeight` points.
/// Before the first measurement the sheet uses `.medium` so presentation
/// never starts at full height. Height detents exclude the bottom safe area,
/// which the system adds, and are capped by the system at `.large`; content
/// beyond that scrolls.
func firstlightBottomSheetDetent(contentHeight: CGFloat) -> PresentationDetent {
    contentHeight > 0 ? .height(contentHeight.rounded(.up)) : .medium
}

/// SwiftUI sheet sized to its content with a visible drag indicator. Swipe
/// down and taps on the dimmed background clear `isPresented`.
struct FirstlightBottomSheetControl<Content: View, Footer: View>: View {
    let isPresented: Binding<Bool>
    let accessibilityLabel: String
    let tokens: NativeUITokens
    @ViewBuilder let footer: () -> Footer
    @ViewBuilder let content: () -> Content

    @State private var contentHeight: CGFloat = 0
    @State private var footerHeight: CGFloat = 0

    var body: some View {
        Color.clear
            .frame(width: 0, height: 0)
            .sheet(isPresented: isPresented) {
                ScrollView {
                    VStack(alignment: .leading, spacing: 0) {
                        content()
                    }
                    .frame(maxWidth: .infinity, alignment: .topLeading)
                    .onGeometryChange(for: CGFloat.self) { proxy in
                        proxy.size.height
                    } action: { height in
                        contentHeight = height
                    }
                }
                .scrollBounceBehavior(.basedOnSize)
                .safeAreaInset(edge: .bottom, spacing: 0) {
                    footer()
                        .frame(maxWidth: .infinity, alignment: .leading)
                        .onGeometryChange(for: CGFloat.self) { $0.size.height } action: { footerHeight = $0 }
                }
                .presentationDetents([firstlightBottomSheetDetent(contentHeight: contentHeight + footerHeight)])
                .presentationDragIndicator(.visible)
                .presentationBackground(tokens.surface)
                .accessibilityElement(children: .contain)
                .modifier(BottomSheetAccessibilityLabel(label: accessibilityLabel))
            }
    }
}

private struct BottomSheetAccessibilityLabel: ViewModifier {
    let label: String

    func body(content: Content) -> some View {
        if label.isEmpty {
            content
        } else {
            content.accessibilityLabel(Text(label))
        }
    }
}
