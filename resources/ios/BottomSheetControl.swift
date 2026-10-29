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
                FirstlightBottomSheetLayout(
                    footer: footer,
                    content: content,
                    onContentHeight: { contentHeight = $0 },
                    onFooterHeight: { footerHeight = $0 }
                )
                .presentationDetents([firstlightBottomSheetDetent(contentHeight: contentHeight + footerHeight)])
                .presentationDragIndicator(.visible)
                .presentationBackground(tokens.surface)
                .accessibilityElement(children: .contain)
                .modifier(BottomSheetAccessibilityLabel(label: accessibilityLabel))
            }
    }
}

/// The sheet's scrolling body with the optional pinned footer inset below it.
struct FirstlightBottomSheetLayout<Content: View, Footer: View>: View {
    @ViewBuilder let footer: () -> Footer
    @ViewBuilder let content: () -> Content
    var onContentHeight: (CGFloat) -> Void = { _ in }
    var onFooterHeight: (CGFloat) -> Void = { _ in }

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 0) {
                content()
            }
            .frame(maxWidth: .infinity, alignment: .topLeading)
            .onGeometryChange(for: CGFloat.self) { $0.size.height } action: { onContentHeight($0) }
        }
        .scrollBounceBehavior(.basedOnSize)
        .safeAreaInset(edge: .bottom, spacing: 0) {
            // The Mobile UI column inside the footer accepts the full
            // proposed height; without this the inset takes the sheet and
            // the body ScrollView is squeezed out of view.
            footer()
                .frame(maxWidth: .infinity, alignment: .leading)
                .fixedSize(horizontal: false, vertical: true)
                .onGeometryChange(for: CGFloat.self) { $0.size.height } action: { onFooterHeight($0) }
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
