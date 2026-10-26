import SwiftUI

/// iOS renderer for `firstlight.button`. Ghost buttons use Firstlight's
/// control for a 44 pt target; every other variant stays on the official
/// Mobile UI renderer the Button adapter wraps.
struct FirstlightButtonRenderer: View {
    let node: NativeUINode

    @ObservedObject private var themeStore = NativeUITheme.shared
    @Environment(\.colorScheme) private var colorScheme

    var body: some View {
        let labelNode = firstlightButtonLabelNode(node)
        if node.props.getString("variant") == "ghost" {
            let configuration = ButtonGhostConfiguration(node: labelNode)
            FirstlightGhostButtonControl(
                configuration: configuration,
                tokens: themeStore.resolve(for: colorScheme),
                onPress: {
                    guard configuration.isEnabled, configuration.callbackID != 0 else { return }
                    // Through the focus policy so a focused field's pending change
                    // reaches PHP before the press, as Mobile UI's Button does.
                    KeyboardFocusPolicy.dispatchPress {
                        NativeUIBridge.sendPressEvent(configuration.callbackID, nodeId: configuration.nodeID)
                    }
                }
            )
        } else {
            NativeUIButtonRenderer(node: labelNode)
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
