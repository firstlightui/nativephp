import XCTest

/// Drives an iOS Simulator for documentation captures: brings the app under capture to the front,
/// rotates the Simulator, and taps a button. Simulator has no command for either, and AXe taps do
/// not reach iOS 27 Simulators. xcodebuild passes TEST_RUNNER_FIRSTLIGHT_* variables through
/// without the prefix.
final class OrientationDriver: XCTestCase {
    func testDrive() throws {
        let environment = ProcessInfo.processInfo.environment
        var app: XCUIApplication?
        if let identifier = environment["FIRSTLIGHT_APP"], !identifier.isEmpty {
            app = XCUIApplication(bundleIdentifier: identifier)
            app?.activate()
        }
        if let orientation = environment["FIRSTLIGHT_ORIENTATION"] {
            XCUIDevice.shared.orientation = orientation == "landscape" ? .landscapeLeft : .portrait
            sleep(1)
        }
        if let label = environment["FIRSTLIGHT_TAP"], !label.isEmpty, let app {
            // Prefer the button: a field's visible label is a separate static text with the same name.
            let until = environment["FIRSTLIGHT_UNTIL"] ?? ""
            let shown = app.descendants(matching: .any).matching(NSPredicate(format: "label == %@", until)).firstMatch
            // Already open from an earlier pass: on iPad a second tap lands outside the popover
            // and closes it.
            if !until.isEmpty, shown.exists { return }
            let button = app.buttons.matching(NSPredicate(format: "label BEGINSWITH %@", label)).firstMatch
            XCTAssertTrue(button.waitForExistence(timeout: 10), "No button labelled \(label)")
            button.tap()
            if !until.isEmpty {
                XCTAssertTrue(shown.waitForExistence(timeout: 10), "\(until) did not appear after tapping \(label)")
            }
        }
    }
}
