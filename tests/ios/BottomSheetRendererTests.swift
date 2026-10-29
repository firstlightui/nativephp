import SwiftUI
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class BottomSheetRendererTests: XCTestCase {
    func testConfigurationDecodesPresentationAndDismissal() {
        let configuration = BottomSheetRendererConfiguration(node: node())

        XCTAssertEqual(configuration.nodeID, 1)
        XCTAssertTrue(configuration.visible)
        XCTAssertEqual(configuration.accessibilityLabel, "Filters sheet")
        XCTAssertEqual(configuration.dismissCallback, 31)
    }

    func testVisibleSheetPresentsOnceTheHostAppears() {
        var state = BottomSheetRendererState(node: node())
        XCTAssertFalse(state.isPresented)

        state.appear()
        XCTAssertTrue(state.isPresented)

        var closed = BottomSheetRendererState(node: node(visible: false))
        closed.appear()
        XCTAssertFalse(closed.isPresented)
    }

    func testSwipeOrScrimDismissalEmitsOnce() {
        var state = BottomSheetRendererState(node: node())
        state.appear()

        XCTAssertEqual(state.systemDismiss(), .dismiss(callbackId: 31, nodeId: 1))
        XCTAssertFalse(state.isPresented)
        XCTAssertNil(state.systemDismiss())

        // PHP's handler then publishes visible=false; that emits nothing more.
        XCTAssertTrue(state.serverPublished(configuration(visible: false)))
        XCTAssertFalse(state.isPresented)
        XCTAssertNil(state.systemDismiss())
    }

    func testOnlyVisibilityChangesOpenOrCloseTheSheet() {
        var state = BottomSheetRendererState(node: node())
        state.appear()
        XCTAssertNotNil(state.systemDismiss())

        // A label-only publication while PHP is still reconciling must not reopen.
        XCTAssertTrue(state.serverPublished(configuration(label: "Sort sheet")))
        XCTAssertFalse(state.isPresented)

        XCTAssertTrue(state.serverPublished(configuration(visible: false)))
        XCTAssertTrue(state.serverPublished(configuration(visible: true)))
        XCTAssertTrue(state.isPresented)

        XCTAssertTrue(state.serverPublished(configuration(visible: false)))
        XCTAssertFalse(state.isPresented)
        XCTAssertNil(state.systemDismiss())
    }

    func testPublicationsForOtherNodesAreIgnored() {
        var state = BottomSheetRendererState(node: node())
        state.appear()

        XCTAssertFalse(state.serverPublished(BottomSheetRendererConfiguration(node: node(id: 2, visible: false))))
        XCTAssertTrue(state.isPresented)
    }

    func testDetentFollowsMeasuredContentHeight() {
        XCTAssertEqual(firstlightBottomSheetDetent(contentHeight: 0), .medium)
        XCTAssertEqual(firstlightBottomSheetDetent(contentHeight: 120), .height(120))
        XCTAssertEqual(firstlightBottomSheetDetent(contentHeight: 456.2), .height(457))
    }

    func testPinnedFooterKeepsItsIntrinsicHeightAndLeavesTheBodyScrollable() throws {
        // Mobile UI columns accept the whole proposed height, like this footer.
        var footerHeight: CGFloat = 0
        let layout = FirstlightBottomSheetLayout(
            footer: {
                VStack { Text("Bill") }
                    .frame(maxHeight: .infinity)
            },
            content: {
                ForEach(0..<40, id: \.self) { Text("Row \($0)").frame(height: 44) }
            },
            onFooterHeight: { footerHeight = $0 }
        )
        let host = UIHostingController(rootView: layout)
        let window = UIWindow(frame: CGRect(x: 0, y: 0, width: 390, height: 600))
        window.rootViewController = host
        window.makeKeyAndVisible()
        host.view.layoutIfNeeded()

        // The ScrollView always spans the window; the footer inset arrives as
        // its bottom content inset, so measure the body that stays visible.
        let scrollView = try XCTUnwrap(firstScrollView(in: host.view))
        let inset = scrollView.adjustedContentInset
        let visibleBodyHeight = scrollView.bounds.height - inset.top - inset.bottom
        // The footer is one line of text; the body must keep the rest.
        XCTAssertLessThan(footerHeight, 60)
        XCTAssertLessThan(inset.bottom, 60)
        XCTAssertGreaterThan(visibleBodyHeight, 450)
        XCTAssertGreaterThan(scrollView.contentSize.height, visibleBodyHeight)
    }

    private func firstScrollView(in view: UIView) -> UIScrollView? {
        if let scrollView = view as? UIScrollView { return scrollView }
        return view.subviews.lazy.compactMap(firstScrollView(in:)).first
    }

    func testRendererCompilesAgainstNativePHPContractShims() {
        let renderer = BottomSheetRenderer(
            node: node(children: [child(id: 2, label: "Filters", height: 120)]),
            events: BottomSheetRendererEvents { _ in }
        )

        XCTAssertNotNil(renderer.body)
    }

    private func configuration(visible: Bool = true, label: String = "Filters sheet") -> BottomSheetRendererConfiguration {
        BottomSheetRendererConfiguration(node: node(visible: visible, label: label))
    }

    private func node(
        id: Int = 1,
        visible: Bool = true,
        label: String = "Filters sheet",
        children: [NativeUINode] = []
    ) -> NativeUINode {
        NativeUINode(
            id: id,
            type: "firstlight.bottom-sheet",
            layout: nil,
            style: nil,
            props: GenericProps([
                "visible": visible,
                "a11y_label": label,
                "on_dismiss": 31,
            ]),
            onPress: 0,
            onLongPress: 0,
            children: children
        )
    }

    private func child(id: Int, label: String, height: Int) -> NativeUINode {
        NativeUINode(
            id: id,
            type: "firstlight.status-label",
            layout: nil,
            style: nil,
            props: GenericProps(["label": label, "height": height]),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
    }
}
