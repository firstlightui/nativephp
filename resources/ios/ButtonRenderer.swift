import SwiftUI

/// iOS renderer for `firstlight.button`. Ghost buttons use Firstlight's
/// control for a 44 pt target; every other variant stays on the official
/// Mobile UI renderer the Button adapter wraps.
struct FirstlightButtonRenderer: View {
    let node: NativeUINode

    @ObservedObject private var themeStore = NativeUITheme.shared
    @Environment(\.colorScheme) private var colorScheme

    var body: some View {
        if node.props.getString("variant") == "ghost" {
            let configuration = ButtonGhostConfiguration(node: node)
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
            .fillWidthIfRequested(node)
        } else {
            NativeUIButtonRenderer(node: node)
        }
    }
}
