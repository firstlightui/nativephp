import SwiftUI

struct FirstlightToolbarButton: View {
    let item: ToolbarItemConfiguration
    let onPress: (Int, ToolbarItemConfiguration) -> Void

    var body: some View {
        Group {
            if item.menu.isEmpty {
                Button { onPress(item.callbackID, item) } label: { label }
            } else {
                Menu {
                    ForEach(Array(item.menu.enumerated()), id: \.offset) { _, entry in
                        // The destructive role only styles the entry; PHP owns
                        // any confirmation.
                        Button(role: entry.role == "destructive" ? .destructive : nil) {
                            onPress(entry.cb, item)
                        } label: {
                            if entry.icon.isEmpty {
                                Text(entry.label)
                            } else {
                                Label { Text(entry.label) } icon: { NativeIcon.image(entry.icon) }
                            }
                        }
                        .disabled(!entry.isEnabled)
                    }
                } label: { label }
            }
        }
        .disabled(!item.isEnabled)
        .accessibilityLabel(item.accessibilityLabel)
        // System bars stop growing at the accessibility sizes, where long press shows the large
        // content viewer instead; without the cap the badge outgrows the bar and covers its icon.
        .dynamicTypeSize(...DynamicTypeSize.xxxLarge)
    }

    /// The title is kept so an overflowed item and the long-press label read
    /// correctly; toolbars show icons only. SwiftUI turns each item into a
    /// bar button, so the badge comes from the toolbar item, not the label.
    private var label: some View {
        Label {
            Text(item.accessibilityLabel)
        } icon: {
            NativeIcon.image(item.icon)
        }
    }
}
