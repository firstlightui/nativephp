import SwiftUI
import XCTest
@testable import FirstlightIOSControls

@MainActor
final class HapticsTests: XCTestCase {
    override func tearDown() {
        FirstlightHaptics.shared.recorder = nil
        FirstlightHaptics.setEnabled(true)
        super.tearDown()
    }

    func testEachMeaningPlaysItsCanonicalIOSPattern() {
        XCTAssertEqual(FirstlightHaptic.success.pattern, .notification(.success))
        XCTAssertEqual(FirstlightHaptic.warning.pattern, .notification(.warning))
        XCTAssertEqual(FirstlightHaptic.error.pattern, .notification(.error))
        XCTAssertEqual(FirstlightHaptic.selection.pattern, .selection)
        XCTAssertEqual(FirstlightHaptic.toggleOn.pattern, .selection)
        XCTAssertEqual(FirstlightHaptic.keypress.pattern, .impact(.light))
        XCTAssertEqual(FirstlightHaptic.thresholdDeactivate.pattern, .impact(.soft))
        XCTAssertEqual(FirstlightHaptic.dragStart.pattern, .impact(.medium))
        XCTAssertEqual(FirstlightHaptic.increase.pattern, .sensory("increase"))
        XCTAssertEqual(FirstlightHaptic.none.pattern, .silent)
    }

    func testEverySensoryKindNamesARealSwiftUIFeedback() {
        for haptic in FirstlightHaptic.allCases {
            if case let .sensory(kind) = haptic.pattern {
                XCTAssertNotNil(FirstlightSensoryRequest(id: 1, kind: kind).feedback, haptic.rawValue)
            }
        }
    }

    func testWireNamesMatchThePHPEnum() {
        XCTAssertEqual(FirstlightHaptic(rawValue: "threshold-activate"), .thresholdActivate)
        XCTAssertEqual(FirstlightHaptic(rawValue: "path-complete"), .pathComplete)
        XCTAssertNil(FirstlightHaptic(rawValue: "buzz"))
    }

    func testBridgeFunctionsRejectUnknownNamesAndStoreTheAppSwitch() throws {
        XCTAssertThrowsError(try FirstlightHapticsFunctions.Play().execute(parameters: ["haptic": "buzz"]))
        XCTAssertThrowsError(try FirstlightHapticsFunctions.Play().execute(parameters: ["impact": "huge"]))
        XCTAssertThrowsError(try FirstlightHapticsFunctions.SetEnabled().execute(parameters: ["enabled": "no"]))

        _ = try FirstlightHapticsFunctions.SetEnabled().execute(parameters: ["enabled": false])
        XCTAssertFalse(FirstlightHaptics.isEnabled)
        _ = try FirstlightHapticsFunctions.SetEnabled().execute(parameters: ["enabled": true])
        XCTAssertTrue(FirstlightHaptics.isEnabled)
    }

    func testSensoryKindsQueueARequestForTheRootHost() {
        let before = FirstlightHaptics.shared.sensoryRequest.id
        FirstlightHaptics.shared.play(.start)
        XCTAssertEqual(FirstlightHaptics.shared.sensoryRequest, FirstlightSensoryRequest(id: before + 1, kind: "start"))

        FirstlightHaptics.setEnabled(false)
        FirstlightHaptics.shared.play(.stop)
        XCTAssertEqual(FirstlightHaptics.shared.sensoryRequest.id, before + 1)
    }
}
