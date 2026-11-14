import SwiftUI

struct ToolbarMenuItem: Decodable, Hashable {
    let cb: Int
    let label: String
    let icon: String
    let icon_variant: String
    let role: String
    let disabled: Bool

    var isEnabled: Bool { !disabled && cb != 0 }

    static func decode(_ json: String) -> [Self] {
        guard let data = json.data(using: .utf8),
              let items = try? JSONDecoder().decode([Self].self, from: data) else { return [] }
        return items.filter { ($0.cb != 0 || $0.disabled) && !$0.label.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty }
    }
}

struct ToolbarItemConfiguration: Hashable, Identifiable {
    let id: Int
    let icon: String
    let accessibilityLabel: String
    let primary: Bool
    let disabled: Bool
    let badge: String
    let haptic: FirstlightHaptic?
    let callbackID: Int
    let menu: [ToolbarMenuItem]

    init(node: NativeUINode) {
        id = node.id
        icon = node.props.getString("icon")
        accessibilityLabel = node.props.getString("a11y_label")
        primary = node.props.getBool("primary")
        disabled = node.props.getBool("disabled")
        badge = node.props.getString("badge")
        haptic = FirstlightHaptic(rawValue: node.props.getString("haptic"))
        callbackID = node.onPress
        menu = ToolbarMenuItem.decode(node.props.getString("menu_json"))
    }

    var isEnabled: Bool {
        !disabled && (menu.isEmpty ? callbackID != 0 : menu.contains(where: \.isEnabled))
    }
}

/// Never shown on its own: Toolbar renders its items.
struct ToolbarItemRenderer: View {
    let node: NativeUINode

    var body: some View { EmptyView() }
}
