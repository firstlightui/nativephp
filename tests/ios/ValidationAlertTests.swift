import SwiftUI
import XCTest
@testable import FirstlightIOSControls

final class ValidationAlertTests: XCTestCase {
    func testReadsTheOneTimeAlertPHPPublishes() {
        let alert = FirstlightValidationAlert(node: node(props: [
            "validation_alert": 7,
            "validation_announcement": "There are 2 errors. The name field is required. The email field is required.",
            "validation_focus": true,
        ]))

        XCTAssertEqual(alert, FirstlightValidationAlert(
            revision: 7,
            announcement: "There are 2 errors. The name field is required. The email field is required.",
            movesFocus: true
        ))
        XCTAssertTrue(FirstlightValidationAlert(node: node(props: ["error": "Required"])).isEmpty)
    }

    func testActsOncePerRevisionIncludingTheFrameThatCreatesTheNode() {
        var state = FirstlightValidationAlertState()
        let first = FirstlightValidationAlert(revision: 3, announcement: "The email field is required.", movesFocus: true)

        XCTAssertEqual(state.consume(first), first)
        XCTAssertNil(state.consume(first), "Republishing the same tree must not repeat speech or focus")
        XCTAssertNil(state.consume(FirstlightValidationAlert(revision: 0, announcement: "", movesFocus: false)))

        let repeated = FirstlightValidationAlert(revision: 4, announcement: "The email field is required.", movesFocus: true)
        XCTAssertEqual(state.consume(repeated), repeated, "A repeated identical failure is a new alert")
    }

    func testFocusOnlyAlertIsNotEmptyButBareRevisionIs() {
        XCTAssertFalse(FirstlightValidationAlert(revision: 2, announcement: "", movesFocus: true).isEmpty)
        XCTAssertTrue(FirstlightValidationAlert(revision: 2, announcement: "", movesFocus: false).isEmpty)
    }

    func testAnnouncementWaitsForCurrentVoiceOverSpeech() {
        let announcement = firstlightValidationAnnouncement("The email field is required.")

        XCTAssertEqual(String(announcement.characters), "The email field is required.")
        XCTAssertEqual(announcement.accessibilitySpeechAnnouncementPriority, .low)
    }

    private func node(props: [String: Any]) -> NativeUINode {
        NativeUINode(
            id: 11,
            type: "firstlight.text-field",
            layout: nil,
            style: nil,
            props: GenericProps(props),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
    }
}
