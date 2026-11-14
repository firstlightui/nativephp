import XCTest

@testable import FirstlightIOSControls

@MainActor
final class ToolbarItemRendererTests: XCTestCase {
    func testMenuItemsEnableTheirItemAndDisabledEntriesNeverDispatch() {
        let attach = ToolbarRendererConfiguration(node: toolbarFixture()).items[1]

        XCTAssertTrue(attach.isEnabled)
        XCTAssertEqual(attach.menu.map(\.cb), [31, 0])
        XCTAssertFalse(attach.menu[1].isEnabled)
        XCTAssertFalse(ToolbarItemConfiguration(node: toolbarItemFixture(9, "star", "Star", disabled: true)).isEnabled)
        XCTAssertFalse(ToolbarItemConfiguration(node: toolbarItemFixture(9, "star", "Star", callbackID: 0)).isEnabled)
    }
}
