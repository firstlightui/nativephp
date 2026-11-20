import SwiftUI

struct FirstlightBottomToolbar: ViewModifier {
    let configuration: ToolbarRendererConfiguration
    let onPress: (Int, ToolbarItemConfiguration) -> Void

    func body(content: Content) -> some View {
        #if compiler(>=6.4)
        if #available(iOS 26.0, *) {
            // One ToolbarItem per action so each can carry the system badge;
            // adjacent items share one glass group until the spacer. Items
            // are keyed by their whole configuration because SwiftUI keeps a
            // bar button whose identity is unchanged, with its old badge and
            // enabled state, when PHP republishes the screen. ForEach is
            // toolbar content only from the iOS 27 SDK (Xcode 27, Swift 6.4).
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
        #elseif compiler(>=6.2)
        if #available(iOS 26.0, *) {
            // The iOS 26 SDK (Xcode 26) cannot build toolbar items with
            // ForEach, so each of Toolbar's five item slots is spelled out.
            // Keying the host by the whole configuration replaces every bar
            // button when PHP republishes a changed toolbar, so none keeps
            // an old badge or enabled state.
            content
                .toolbar { slottedItems }
                .id(configuration)
        } else {
            classic(content)
        }
        #else
        classic(content)
        #endif
    }

    #if compiler(>=6.2) && !compiler(>=6.4)
    @available(iOS 26.0, *)
    @ToolbarContentBuilder
    private var slottedItems: some ToolbarContent {
        // Toolbar::MAX_ITEMS in PHP caps the regular items at five.
        slot(0)
        slot(1)
        slot(2)
        slot(3)
        slot(4)
        if let primary = configuration.primary {
            ToolbarSpacer(.flexible, placement: .bottomBar)
            badgedItem(primary)
        }
    }

    @available(iOS 26.0, *)
    @ToolbarContentBuilder
    private func slot(_ index: Int) -> some ToolbarContent {
        if configuration.items.indices.contains(index) {
            badgedItem(configuration.items[index])
        }
    }

    @available(iOS 26.0, *)
    private func badgedItem(_ item: ToolbarItemConfiguration) -> some ToolbarContent {
        ToolbarItem(placement: .bottomBar) {
            FirstlightToolbarButton(item: item, onPress: onPress)
                .badge(item.badge.isEmpty ? nil : Text(item.badge))
        }
    }
    #endif

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
