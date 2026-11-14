import SwiftUI

/// iOS renderer for `firstlight.button`. Ghost buttons use Firstlight's
/// control for a 44 pt target; every other variant stays on the official
/// Mobile UI renderer the Button adapter wraps.
struct FirstlightButtonRenderer: View {
    let node: NativeUINode

    @ObservedObject private var themeStore = NativeUITheme.shared
    @Environment(\.colorScheme) private var colorScheme
    @Environment(\.firstlightFloatingGlass) private var floatingGlass

    var body: some View {
        let labelNode = firstlightButtonLabelNode(node)
        let variant = node.props.getString("variant", default: "primary")
        let glass = firstlightUsesLiquidGlass
            ? FirstlightGlassStyle(node: node) ?? (floatingGlass ? FirstlightGlassStyle(floatingVariant: variant) : nil)
            : nil
        if variant == "ghost" {
            let configuration = ButtonGhostConfiguration(node: labelNode)
            FirstlightGhostButtonControl(
                configuration: configuration,
                tokens: themeStore.resolve(for: colorScheme),
                glass: glass,
                onPress: {
                    guard configuration.isEnabled, configuration.callbackID != 0 else { return }
                    // Firstlight draws ghost buttons, so the haptic plays with the action.
                    if let haptic = FirstlightHaptic(rawValue: node.props.getString("haptic")) {
                        FirstlightHaptics.shared.play(haptic)
                    }
                    // Through the focus policy so a focused field's pending change
                    // reaches PHP before the press, as Mobile UI's Button does.
                    KeyboardFocusPolicy.dispatchPress {
                        NativeUIBridge.sendPressEvent(configuration.callbackID, nodeId: configuration.nodeID)
                    }
                }
            )
        } else {
            // Mobile UI draws the other variants; the haptic rides alongside its tap.
            NativeUIButtonRenderer(node: firstlightGlassNode(labelNode, style: glass))
                .modifier(FirstlightRegularGlassLabel(active: glass == .regular))
                .firstlightPressHaptic(node, enabled: !node.props.getBool("disabled") && !node.props.getBool("loading"))
        }
    }
}

/// Mobile UI expands its Button label only for `widthMode == fill`. The
/// host also allocates width for flex growth and explicit fixed/percent widths.
/// Adapt ONLY the renderer's private input: NodeView/FlexContainer must keep
/// the original width, constraints, padding and margins when allocating space.
func firstlightButtonLabelNode(_ node: NativeUINode) -> NativeUINode {
    guard let layout = node.layout,
          layout.widthMode != SizeMode.fill,
          layout.flexGrow > 0 || (
              (layout.widthMode == SizeMode.fixed || layout.widthMode == SizeMode.percent)
                  && layout.width > 0
          )
    else { return node }

    return NativeUINode(
        id: node.id,
        type: node.type,
        layout: layout.applying(["width": "fill"]),
        style: node.style,
        props: node.props,
        onPress: node.onPress,
        onLongPress: node.onLongPress,
        children: node.children,
        variants: node.variants
    )
}

/// Mobile UI draws `.glass` or `.glassProminent` from its `glass` flags (bit 0
/// on, bit 1 prominent). Firstlight publishes a named style instead and sets
/// the flags only on this private copy, and only where glass draws, so earlier
/// iOS keeps the variant's look and the host never adds a second glass layer.
func firstlightGlassNode(_ node: NativeUINode, style: FirstlightGlassStyle?) -> NativeUINode {
    guard let style else { return node }

    var props = node.props.rawMap
    props["glass"] = style == .prominent ? 3 : 1

    return NativeUINode(
        id: node.id,
        type: node.type,
        layout: node.layout,
        style: node.style,
        props: GenericProps(props),
        onPress: node.onPress,
        onLongPress: node.onLongPress,
        children: node.children,
        variants: node.variants
    )
}

/// Regular glass takes its light or dark look from what is behind it, so a
/// theme colour chosen for the screen's appearance can vanish on it: a dark
/// mode token is light, and so is glass over bright content. The label uses
/// the system's primary style, which the glass keeps legible, and colour
/// stays with prominent glass. Mobile UI tints the regular glass label with
/// the variant, which this outer style replaces.
struct FirstlightRegularGlassLabel: ViewModifier {
    let active: Bool

    func body(content: Content) -> some View {
        if active {
            content.foregroundStyle(.primary)
        } else {
            content
        }
    }
}
