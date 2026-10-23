import SwiftUI

enum BottomSheetRendererEvent: Equatable {
    case dismiss(callbackId: Int, nodeId: Int)
}

struct BottomSheetRendererEvents {
    let send: @MainActor (BottomSheetRendererEvent) -> Void

    static let native = BottomSheetRendererEvents { event in
        switch event {
        case let .dismiss(callbackId, nodeId):
            NativeUIBridge.sendSheetDismissEvent(callbackId, nodeId: nodeId)
        }
    }
}

struct BottomSheetRendererConfiguration: Equatable {
    let nodeID: Int
    let visible: Bool
    let accessibilityLabel: String
    let dismissCallback: Int

    init(node: NativeUINode) {
        nodeID = node.id
        visible = node.props.getBool("visible")
        accessibilityLabel = node.props.getString("a11y_label")
        dismissCallback = node.props.getCallbackId("on_dismiss")
    }
}

struct BottomSheetRendererState {
    private(set) var configuration: BottomSheetRendererConfiguration
    private(set) var isPresented = false

    init(node: NativeUINode) {
        configuration = BottomSheetRendererConfiguration(node: node)
    }

    /// Presentation starts once the host view is on screen, as SwiftUI
    /// cannot present a sheet from a view that has not appeared.
    mutating func appear() {
        isPresented = configuration.visible
    }

    /// Swipe-down or scrim dismissal. Emits once; PHP then publishes `visible=false`.
    mutating func systemDismiss() -> BottomSheetRendererEvent? {
        guard isPresented else { return nil }

        isPresented = false

        guard configuration.dismissCallback != 0 else { return nil }

        return .dismiss(callbackId: configuration.dismissCallback, nodeId: configuration.nodeID)
    }

    /// Server publication. Only a change of `visible` opens or closes the sheet,
    /// and programmatic closure emits nothing.
    @discardableResult
    mutating func serverPublished(_ published: BottomSheetRendererConfiguration) -> Bool {
        guard published.nodeID == configuration.nodeID, published != configuration else { return false }

        let visibilityChanged = published.visible != configuration.visible
        configuration = published

        if visibilityChanged {
            isPresented = published.visible
        }

        return true
    }
}

/// iOS expression of `firstlight.bottom-sheet`: a SwiftUI sheet sized to its
/// content, matching Android. Mobile UI's renderer is not used because
/// Firstlight publishes no detents and its `medium,large` fallback opens every
/// sheet at the medium detent.
struct BottomSheetRenderer: View {
    let node: NativeUINode
    private let events: BottomSheetRendererEvents

    @ObservedObject private var themeStore = NativeUITheme.shared
    @Environment(\.colorScheme) private var colorScheme
    @State private var state: BottomSheetRendererState

    init(node: NativeUINode, events: BottomSheetRendererEvents = .native) {
        self.node = node
        self.events = events
        _state = State(initialValue: BottomSheetRendererState(node: node))
    }

    var body: some View {
        let published = BottomSheetRendererConfiguration(node: node)

        FirstlightBottomSheetControl(
            isPresented: Binding(
                get: { state.isPresented },
                set: { presented in
                    if !presented, let event = state.systemDismiss() { events.send(event) }
                }
            ),
            accessibilityLabel: state.configuration.accessibilityLabel,
            tokens: themeStore.resolve(for: colorScheme)
        ) {
            ForEach(node.children) { child in
                NodeView(node: child).equatable()
            }
        }
        .onAppear { state.appear() }
        .onChange(of: published) { _, configuration in
            state.serverPublished(configuration)
        }
    }
}
