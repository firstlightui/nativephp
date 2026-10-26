#if SWIFT_PACKAGE
import SwiftUI

// These shims mirror only the NativePHP 4.0.1 and Mobile UI 0.3.0 APIs used by
// the package renderers. NativePHP copies plugin sources into its Xcode target,
// where SWIFT_PACKAGE is not defined and the real framework APIs are used.

enum SizeMode {
    static let fixed = 0
    static let wrap = 1
    static let fill = 2
    static let percent = 3
}

// The width-facing subset of Mobile 4.6's NodeLayout. Retain the full delta
// dictionary so tests can check that renderer adaptation preserves unrelated
// host constraints, without pretending to implement the host's layout engine.
struct NodeLayout: Equatable {
    private var values: [String: Any] = [:]

    static var empty: NodeLayout { NodeLayout() }

    var widthMode: Int {
        if values["width"] is NSNumber { return SizeMode.fixed }
        switch values["width"] as? String {
        case "fill": return SizeMode.fill
        case let value? where value.hasSuffix("%"): return SizeMode.percent
        default: return SizeMode.wrap
        }
    }

    var width: Float {
        if let number = values["width"] as? NSNumber { return number.floatValue }
        if let value = values["width"] as? String, value.hasSuffix("%") {
            return Float(value.dropLast()) ?? 0
        }
        return 0
    }

    var flexGrow: Float { (values["flex_grow"] as? NSNumber)?.floatValue ?? 0 }
    var minWidth: Float { (values["min_width"] as? NSNumber)?.floatValue ?? 0 }

    func applying(_ delta: [String: Any]) -> NodeLayout {
        var copy = self
        copy.values.merge(delta) { _, value in value }
        return copy
    }

    static func == (lhs: NodeLayout, rhs: NodeLayout) -> Bool {
        NSDictionary(dictionary: lhs.values).isEqual(to: rhs.values)
    }
}
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

struct NodeVariant {
    let minWidth: CGFloat
    let layout: NodeLayout?
    let style: NodeStyle?
    let props: GenericProps
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
    let variants: [NodeVariant]

    init(
        id: Int,
        type: String,
        layout: NodeLayout?,
        style: NodeStyle?,
        props: GenericProps,
        onPress: Int,
        onLongPress: Int,
        children: [NativeUINode],
        variants: [NodeVariant] = []
    ) {
        self.id = id
        self.type = type
        self.layout = layout
        self.style = style
        self.props = props
        self.onPress = onPress
        self.onLongPress = onLongPress
        self.children = children
        self.variants = variants
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
    static func sendSheetDismissEvent(_ callbackId: Int, nodeId: Int) {}
}

// Mirrors NativePHP Mobile 4.6's focus registry. The real `dispatchPress` also resigns the
// keyboard and defers the press one runloop turn; the shim keeps the flush-before-press contract.
@MainActor
enum KeyboardFocusPolicy {
    private static var focusedField: AnyHashable?
    private static var flushFocusedField: (() -> Void)?
    private(set) static var focusedFieldKeepsFocus = false

    static var focusedFieldActive: Bool { focusedField != nil }

    static func fieldFocused(_ field: AnyHashable, keepsFocus: Bool, flush: @escaping () -> Void) {
        focusedField = field
        focusedFieldKeepsFocus = keepsFocus
        flushFocusedField = flush
    }

    static func fieldBlurred(_ field: AnyHashable) {
        guard focusedField == field else { return }
        focusedField = nil
        focusedFieldKeepsFocus = false
        flushFocusedField = nil
    }

    static func dispatchPress(_ send: @escaping () -> Void) {
        flushFocusedField?()
        send()
    }
}

// Stand-in for NativePHP's NodeView dispatch: a row `height` points tall
// showing `label`, enough to exercise container renderers.
struct NodeView: View, Equatable {
    let node: NativeUINode

    var body: some View {
        Text(node.props.getString("label"))
            .frame(maxWidth: .infinity, alignment: .leading)
            .frame(height: CGFloat(node.props.getInt("height", default: 44)))
    }
}

final class NativeElementBridge {
    static func sendToggleChangeEvent(_ callbackId: Int, nodeId: Int, value: Bool) {}
    static func sendCheckboxChangeEvent(_ callbackId: Int, nodeId: Int, value: Bool) {}
}

struct NativeUITokens: Equatable {
    let primary: Color
    let onPrimary: Color
    let surface: Color
    let onSurface: Color
    let background: Color
    let onBackground: Color
    let surfaceVariant: Color
    let onSurfaceVariant: Color
    let destructive: Color
    let onDestructive: Color
    let success: Color
    let onSuccess: Color
    let accent: Color
    let onAccent: Color

    init(
        primary: Color,
        onPrimary: Color,
        surface: Color,
        onSurface: Color,
        background: Color = .clear,
        onBackground: Color = .clear,
        surfaceVariant: Color = .clear,
        onSurfaceVariant: Color,
        destructive: Color,
        onDestructive: Color = .clear,
        success: Color = .clear,
        onSuccess: Color = .clear,
        accent: Color = .clear,
        onAccent: Color = .clear
    ) {
        self.primary = primary
        self.onPrimary = onPrimary
        self.surface = surface
        self.onSurface = onSurface
        self.background = background
        self.onBackground = onBackground
        self.surfaceVariant = surfaceVariant
        self.onSurfaceVariant = onSurfaceVariant
        self.destructive = destructive
        self.onDestructive = onDestructive
        self.success = success
        self.onSuccess = onSuccess
        self.accent = accent
        self.onAccent = onAccent
    }

    static let fallback = NativeUITokens(
        primary: Color(uiColor: .systemTeal),
        onPrimary: Color(uiColor: .white),
        surface: Color(uiColor: .systemBackground),
        onSurface: Color(uiColor: .label),
        surfaceVariant: Color(uiColor: .secondarySystemBackground),
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

// Width-contract double, NOT Mobile UI's production renderer. This deliberately
// keeps its relevant seam identical: a native Button with fillWidthIfRequested
// INSIDE the label. Package host-layout tests exercise Firstlight's adaptation;
// Mobile UI styling, loading, menus and press dispatch still need consumer tests.
struct NativeUIButtonRenderer: View {
    let node: NativeUINode

    var body: some View {
        Button {} label: {
            Text(node.props.getString("label"))
                .fillWidthIfRequested(node)
        }
        .buttonStyle(.borderedProminent)
        .disabled(node.props.getBool("disabled") || node.props.getBool("loading"))
    }
}

extension View {
    @ViewBuilder
    func fillWidthIfRequested(_ node: NativeUINode) -> some View {
        if node.layout?.widthMode == SizeMode.fill {
            frame(maxWidth: .infinity)
        } else {
            self
        }
    }
}

// SwiftPM does not compile NativePHP's host-owned IconHelper.swift. Package
// tests use canonical SF Symbol names, so preserve them here; consumer builds
// omit this branch and continue through NativePHP's real icon resolver.
func getIconForName(_ iconName: String) -> String {
    iconName
}

// Mobile UI 0.8 supplies the enclosing scroll proxy to descendant inputs.
struct NativeUIScrollProxyKey: EnvironmentKey {
    static var defaultValue: ScrollViewProxy? { nil }
}

extension EnvironmentValues {
    var nativeUIScrollProxy: ScrollViewProxy? {
        get { self[NativeUIScrollProxyKey.self] }
        set { self[NativeUIScrollProxyKey.self] = newValue }
    }
}
#endif
