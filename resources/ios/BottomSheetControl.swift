import SwiftUI

/// Band at the top of the sheet that holds the system drag indicator, like
/// Material's drag handle on Android. The system draws the indicator about
/// 5 pt below the sheet edge and 5 pt tall, so the band leaves the same gap
/// under it, and authored padding starts below the indicator rather than
/// beside it in the curve of the top corners.
let firstlightBottomSheetTopInset: CGFloat = 16

/// The single detent for a sheet whose content measures `contentHeight` points,
/// plus what sits above it: the drag-indicator band, or a titled sheet's bar.
/// Before the first measurement the sheet uses `.medium` so presentation never
/// starts at full height. Height detents exclude the bottom safe area, which
/// the system adds, and are capped by the system at `.large`; content beyond
/// that scrolls.
func firstlightBottomSheetDetent(
    contentHeight: CGFloat,
    topInset: CGFloat = firstlightBottomSheetTopInset
) -> PresentationDetent {
    contentHeight > 0 ? .height((contentHeight + topInset).rounded(.up)) : .medium
}

/// SwiftUI sheet sized to its content with a visible drag indicator. Swipe
/// down, taps on the dimmed background and a titled sheet's close button
/// clear `isPresented`.
struct FirstlightBottomSheetControl<Content: View, Footer: View>: View {
    let isPresented: Binding<Bool>
    let accessibilityLabel: String
    var title: String = ""
    var closeLabel: String = ""
    let tokens: NativeUITokens
    @ViewBuilder let footer: () -> Footer
    @ViewBuilder let content: () -> Content

    @State private var contentHeight: CGFloat = 0
    @State private var footerHeight: CGFloat = 0
    @State private var barHeight: CGFloat = 0

    var body: some View {
        Color.clear
            .frame(width: 0, height: 0)
            .sheet(isPresented: isPresented) {
                FirstlightBottomSheetTitleBar(
                    title: title,
                    closeLabel: closeLabel,
                    surface: tokens.surface,
                    close: { isPresented.wrappedValue = false },
                    onBarHeight: { barHeight = $0 }
                ) {
                    FirstlightBottomSheetLayout(
                        footer: footer,
                        content: content,
                        onContentHeight: { contentHeight = $0 },
                        onFooterHeight: { footerHeight = $0 },
                        footerSurface: tokens.surface,
                        // A titled sheet's bar already keeps content clear of the indicator.
                        topInset: title.isEmpty ? firstlightBottomSheetTopInset : 0
                    )
                }
                .presentationDetents([firstlightBottomSheetDetent(
                    contentHeight: contentHeight + footerHeight,
                    topInset: title.isEmpty ? firstlightBottomSheetTopInset : barHeight
                )])
                .presentationDragIndicator(.visible)
                .modifier(FirstlightBottomSheetBackground(surface: tokens.surface))
                .accessibilityElement(children: .contain)
                .modifier(BottomSheetAccessibilityLabel(label: accessibilityLabel))
            }
    }
}

/// The sheet's scrolling body with the optional pinned footer below it.
struct FirstlightBottomSheetLayout<Content: View, Footer: View>: View {
    @ViewBuilder let footer: () -> Footer
    @ViewBuilder let content: () -> Content
    var onContentHeight: (CGFloat) -> Void = { _ in }
    var onFooterHeight: (CGFloat) -> Void = { _ in }
    /// Fills the footer before iOS 26, so body content never shows through.
    var footerSurface: Color = .clear
    var topInset: CGFloat = firstlightBottomSheetTopInset

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 0) {
                content()
            }
            .frame(maxWidth: .infinity, alignment: .topLeading)
            .onGeometryChange(for: CGFloat.self) { $0.size.height } action: { onContentHeight($0) }
        }
        .scrollBounceBehavior(.basedOnSize)
        // Outside the ScrollView, so scrolled content never runs up under the indicator.
        .padding(.top, topInset)
        .modifier(FirstlightBottomSheetFooterBar(surface: footerSurface) {
            // The Mobile UI column inside the footer accepts the full
            // proposed height; without this the bar takes the sheet and
            // the body ScrollView is squeezed out of view.
            footer()
                // The footer floats over the sheet, so its buttons take glass.
                .environment(\.firstlightFloatingGlass, true)
                .frame(maxWidth: .infinity, alignment: .leading)
                .fixedSize(horizontal: false, vertical: true)
                .onGeometryChange(for: CGFloat.self) { $0.size.height } action: { onFooterHeight($0) }
        })
    }
}

/// A titled sheet is drawn as Apple's own sheets are (HIG, Sheets): the
/// content sits in a navigation stack whose inline bar carries the title and
/// the system close button on its leading edge. Closing is the user's
/// dismissal, so it reports `@dismiss` like a swipe down. `onBarHeight` hands
/// over the bar's height, which the content-sized detent adds. An untitled
/// sheet is the bare panel.
struct FirstlightBottomSheetTitleBar<Content: View>: View {
    let title: String
    let closeLabel: String
    let surface: Color
    let close: () -> Void
    var onBarHeight: (CGFloat) -> Void = { _ in }
    @ViewBuilder let content: () -> Content

    var body: some View {
        if title.isEmpty {
            content()
        } else {
            NavigationStack {
                content()
                    .background {
                        // Reaches up under the bar, so its top safe-area inset is the bar.
                        Color.clear
                            .ignoresSafeArea()
                            .onGeometryChange(for: CGFloat.self) { $0.safeAreaInsets.top } action: { onBarHeight($0) }
                    }
                    .navigationTitle(title)
                    .navigationBarTitleDisplayMode(.inline)
                    .toolbar {
                        ToolbarItem(placement: .cancellationAction) { closeButton }
                    }
                    .modifier(FirstlightBottomSheetNavigationBackground(surface: surface))
            }
        }
    }

    /// The system close button: `role: .close` with Liquid Glass, the filled
    /// circle sheets used before it. The label is Firstlight's translated
    /// "Close", so VoiceOver speaks the app's language.
    @ViewBuilder
    private var closeButton: some View {
        #if compiler(>=6.2)
        if firstlightUsesLiquidGlass, #available(iOS 26.0, *) {
            Button(role: .close, action: close)
                .accessibilityLabel(Text(closeLabel.isEmpty ? "Close" : closeLabel))
        } else {
            circleCloseButton
        }
        #else
        circleCloseButton
        #endif
    }

    private var circleCloseButton: some View {
        Button(action: close) {
            Image(systemName: "xmark.circle.fill")
                .symbolRenderingMode(.hierarchical)
                .font(.title2)
                .foregroundStyle(.secondary)
        }
        .accessibilityLabel(Text(closeLabel.isEmpty ? "Close" : closeLabel))
    }
}

/// The navigation stack's own background would hide the theme surface before
/// iOS 26; with Liquid Glass the stack stays clear over the glass sheet.
private struct FirstlightBottomSheetNavigationBackground: ViewModifier {
    let surface: Color

    func body(content: Content) -> some View {
        if firstlightUsesLiquidGlass {
            content
        } else {
            content.containerBackground(surface, for: .navigation)
        }
    }
}

/// Pins the footer below the scrolling body. On iOS 26 and later it is a
/// safe-area bar, so the system's scroll edge effect blurs body content
/// scrolling under it, as under Apple's own toolbars, and the footer needs
/// no background. Before 26 it is an inset filled with the theme surface.
private struct FirstlightBottomSheetFooterBar<Bar: View>: ViewModifier {
    let surface: Color
    @ViewBuilder let bar: () -> Bar

    func body(content: Content) -> some View {
        #if compiler(>=6.2)
        if firstlightUsesLiquidGlass, #available(iOS 26.0, *) {
            content.safeAreaBar(edge: .bottom, spacing: 0) { bar() }
        } else {
            fallback(content)
        }
        #else
        fallback(content)
        #endif
    }

    private func fallback(_ content: Content) -> some View {
        content.safeAreaInset(edge: .bottom, spacing: 0) {
            bar().background(surface.ignoresSafeArea(edges: .bottom))
        }
    }
}

/// The system's Liquid Glass sheet on iOS 26 and later, which turns opaque at
/// full height; the theme surface before.
private struct FirstlightBottomSheetBackground: ViewModifier {
    let surface: Color

    func body(content: Content) -> some View {
        if firstlightUsesLiquidGlass {
            content
        } else {
            content.presentationBackground(surface)
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
