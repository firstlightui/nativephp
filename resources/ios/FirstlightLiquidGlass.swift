import SwiftUI

// Firstlight's Liquid Glass surfaces use mobile-air's two gates (see its
// LiquidGlassAvailability.swift): `#if compiler(>=6.2)` keeps iOS 26 symbols
// out of builds against an older SDK, and `#available(iOS 26.0, *)` keeps
// them off older devices. Every pre-26 branch draws what Firstlight drew
// before glass, so those devices see no change.

#if SWIFT_PACKAGE
/// Package snapshot tests render off screen, where Liquid Glass draws nothing
/// and blanks the views around it, so those tests draw the pre-26 path instead.
@MainActor var firstlightSnapshotsDrawWithoutGlass = false
#endif

/// True when this build and device draw Liquid Glass.
@MainActor var firstlightUsesLiquidGlass: Bool {
    #if SWIFT_PACKAGE
    if firstlightSnapshotsDrawWithoutGlass { return false }
    #endif
    #if compiler(>=6.2)
    if #available(iOS 26.0, *) { return true }
    #endif
    return false
}

/// The `glass` prop of Button and Icon Button.
enum FirstlightGlassStyle: String {
    case regular
    case prominent

    init?(node: NativeUINode) {
        self.init(rawValue: node.props.getString("glass_style"))
    }

    /// The style a button takes on a floating glass surface, such as a
    /// Bottom Sheet footer, when it names none: filled actions turn
    /// prominent, secondary and ghost actions regular.
    init(floatingVariant variant: String) {
        switch variant {
        case "secondary", "ghost": self = .regular
        default: self = .prominent
        }
    }
}

private struct FirstlightFloatingGlassKey: EnvironmentKey {
    static let defaultValue = false
}

extension EnvironmentValues {
    /// Set on controls-layer surfaces, such as a Bottom Sheet footer, where
    /// Firstlight buttons take Liquid Glass without a `glass` prop.
    var firstlightFloatingGlass: Bool {
        get { self[FirstlightFloatingGlassKey.self] }
        set { self[FirstlightFloatingGlassKey.self] = newValue }
    }
}

extension View {
    /// `.glass` or `.glassProminent` on iOS 26 and later. Before 26 the
    /// button keeps its earlier look: `.bordered` or `.borderedProminent`
    /// when `bordered`, otherwise the style it already has.
    func firstlightGlassButtonStyle(prominent: Bool, bordered: Bool = true) -> some View {
        modifier(FirstlightGlassButtonStyle(prominent: prominent, bordered: bordered))
    }
}

private struct FirstlightGlassButtonStyle: ViewModifier {
    let prominent: Bool
    let bordered: Bool

    func body(content: Content) -> some View {
        #if compiler(>=6.2)
        if firstlightUsesLiquidGlass, #available(iOS 26.0, *) {
            if prominent {
                content.buttonStyle(.glassProminent)
            } else {
                content.buttonStyle(.glass)
            }
        } else {
            fallback(content)
        }
        #else
        fallback(content)
        #endif
    }

    @ViewBuilder
    private func fallback(_ content: Content) -> some View {
        if !bordered {
            content
        } else if prominent {
            content.buttonStyle(.borderedProminent)
        } else {
            content.buttonStyle(.bordered)
        }
    }
}
