#if SWIFT_PACKAGE
import SwiftUI

// These shims mirror only the NativePHP 4.0.1 and Mobile UI 0.3.0 APIs used by
// SegmentedRenderer. NativePHP copies plugin sources into its Xcode target,
// where SWIFT_PACKAGE is not defined and the real framework APIs are used.

struct NodeLayout: Equatable {}
struct NodeStyle: Equatable {}

final class GenericProps: Equatable {
    private let map: [String: Any]

    init(_ map: [String: Any] = [:]) {
        self.map = map
    }

    func getString(_ key: String, default defaultValue: String = "") -> String {
        (map[key] as? String) ?? defaultValue
    }

    func getBool(_ key: String, default defaultValue: Bool = false) -> Bool {
        (map[key] as? Bool) ?? defaultValue
    }

    func getInt(_ key: String, default defaultValue: Int = 0) -> Int {
        (map[key] as? Int) ?? defaultValue
    }

    func getCallbackId(_ key: String) -> Int {
        if let value = map[key] as? Int { return value }
        if let value = map[key] as? NSNumber { return value.intValue }
        return 0
    }

    func getStringList(_ key: String) -> [String] {
        (map[key] as? [String]) ?? []
    }

    static func == (lhs: GenericProps, rhs: GenericProps) -> Bool {
        NSDictionary(dictionary: lhs.map).isEqual(to: rhs.map)
    }
}

struct NativeUITree {
    let version: Int
    let callbackCount: Int
    let root: NativeUINode
}

final class NativeUINode: Identifiable, Equatable {
    let id: Int
    let type: String
    let layout: NodeLayout?
    let style: NodeStyle?
    let props: GenericProps
    let onPress: Int
    let onLongPress: Int
    let children: [NativeUINode]

    init(
        id: Int,
        type: String,
        layout: NodeLayout?,
        style: NodeStyle?,
        props: GenericProps,
        onPress: Int,
        onLongPress: Int,
        children: [NativeUINode]
    ) {
        self.id = id
        self.type = type
        self.layout = layout
        self.style = style
        self.props = props
        self.onPress = onPress
        self.onLongPress = onLongPress
        self.children = children
    }

    static func == (lhs: NativeUINode, rhs: NativeUINode) -> Bool {
        lhs === rhs
    }
}

@MainActor
final class NativeUIBridge: ObservableObject {
    static let shared = NativeUIBridge()

    @Published var currentTree: NativeUITree?

    private init() {}

    static func sendSelectChangeEvent(_ callbackId: Int, nodeId: Int, value: String) {}
    static func sendPressEvent(_ callbackId: Int, nodeId: Int) {}
    static func sendTextChangeEvent(_ callbackId: Int, nodeId: Int, text: String) {}
    static func sendSubmitEvent(_ callbackId: Int, nodeId: Int, text: String) {}
}

struct NativeUITokens: Equatable {
    let primary: Color
    let onPrimary: Color
    let surface: Color
    let onSurface: Color
    let onSurfaceVariant: Color
    let destructive: Color

    static let fallback = NativeUITokens(
        primary: Color(uiColor: .systemTeal),
        onPrimary: Color(uiColor: .white),
        surface: Color(uiColor: .systemBackground),
        onSurface: Color(uiColor: .label),
        onSurfaceVariant: Color(uiColor: .secondaryLabel),
        destructive: Color(uiColor: .systemRed)
    )
}

@MainActor
final class NativeUITheme: ObservableObject {
    static let shared = NativeUITheme()

    private init() {}

    func resolve(for scheme: ColorScheme) -> NativeUITokens {
        .fallback
    }
}
#endif
