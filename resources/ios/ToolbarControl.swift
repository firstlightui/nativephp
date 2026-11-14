import SwiftUI

struct FirstlightBottomToolbar: ViewModifier {
    let configuration: ToolbarRendererConfiguration
    let onPress: (Int, ToolbarItemConfiguration) -> Void

    func body(content: Content) -> some View {
        #if compiler(>=6.2)
        if #available(iOS 26.0, *) {
            // One ToolbarItem per action so each can carry the system badge;
            // adjacent items share one glass group until the spacer. Items
            // are keyed by their whole configuration because SwiftUI keeps a
            // bar button whose identity is unchanged, with its old badge and
            // enabled state, when PHP republishes the screen.
            content.toolbar {
                ForEach(configuration.items, id: \.self) { item in
                    ToolbarItem(placement: .bottomBar) {
                        FirstlightToolbarButton(item: item, onPress: onPress)
                            .badge(item.badge.isEmpty ? nil : Text(item.badge))
                    }
                }
                if let primary = configuration.primary {
                    ToolbarSpacer(.flexible, placement: .bottomBar)
                    ForEach([primary], id: \.self) { primary in
                        ToolbarItem(placement: .bottomBar) {
                            FirstlightToolbarButton(item: primary, onPress: onPress)
                                .badge(primary.badge.isEmpty ? nil : Text(primary.badge))
                        }
                    }
                }
            }
        } else {
            classic(content)
        }
        #else
        classic(content)
        #endif
    }

    /// Before iOS 26 a flexible spacer pushes the primary action to the
    /// trailing edge of the one bottom toolbar.
    private func classic(_ content: Content) -> some View {
        content.toolbar {
            ToolbarItemGroup(placement: .bottomBar) {
                ForEach(configuration.items, id: \.self) { item in
                    FirstlightToolbarButton(item: item, onPress: onPress)
                }
                if let primary = configuration.primary {
                    Spacer()
                    FirstlightToolbarButton(item: primary, onPress: onPress)
                        .id(primary)
                }
            }
        }
    }
}
