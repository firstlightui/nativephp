import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class FeedbackCenterSnapshotTests: XCTestCase {
    func testRootHostRegistrationUsesTheExactProductionSignature() {
        let exactHost: (NativeUINode, AnyView) -> AnyView = firstlightFeedbackCenterRootHost
        let registryHost: NativeRootHostRegistry.Host = firstlightFeedbackCenterRootHost

        _ = exactHost
        _ = registryHost
    }

    func testControlConstructionOwnsAccessibleNativeActions() {
        let automatic = makeControl(configuration: configuration(message: "Saved"))
        XCTAssertTrue(automatic.renderingPolicy.toneSymbol.accessibilityHidden)
        XCTAssertNil(automatic.renderingPolicy.action)
        XCTAssertNil(automatic.renderingPolicy.dismiss)
        XCTAssertEqual(
            automatic.renderingPolicy.layoutCandidates,
            [.horizontal, .vertical]
        )

        let actionable = makeControl(configuration: configuration(
            message: "Appointment saved",
            tone: .success,
            actionLabel: "Undo",
            actionCallback: 41
        ))
        XCTAssertEqual(actionable.renderingPolicy.action?.visibleLabel, "Undo")
        XCTAssertEqual(actionable.renderingPolicy.action?.accessibilityLabel, "Undo")
        XCTAssertNil(actionable.renderingPolicy.dismiss)
        XCTAssertEqual(
            actionable.renderingPolicy.action?.minimumTarget,
            CGSize(width: 44, height: 44)
        )

        let held = makeControl(configuration: configuration(
            message: "Connection lost",
            tone: .warning,
            hold: true,
            timeoutCallback: nil,
            manualCallback: 51
        ))
        XCTAssertEqual(held.renderingPolicy.dismiss?.visibleLabel, "Dismiss")
        XCTAssertEqual(held.renderingPolicy.dismiss?.accessibilityLabel, "Dismiss feedback")
        XCTAssertTrue(held.renderingPolicy.dismiss?.symbol?.accessibilityHidden == true)
        XCTAssertEqual(
            held.renderingPolicy.dismiss?.minimumTarget,
            CGSize(width: 44, height: 44)
        )
    }

    func testOneTimeAnnouncementAndReducedMotionPoliciesAreDeterministic() {
        var announcements = FeedbackCenterAnnouncementState()
        let first = configuration(message: "Saved", feedbackID: "one")
        let updated = configuration(message: "Appointment saved", feedbackID: "one")
        let second = configuration(message: "Queued", feedbackID: "two")

        XCTAssertEqual(announcements.consume(visible: first), "Saved")
        XCTAssertNil(announcements.consume(visible: updated))
        XCTAssertEqual(announcements.consume(visible: second), "Queued")
        XCTAssertNil(announcements.consume(visible: nil))
        XCTAssertEqual(announcements.consume(visible: first), "Saved")
        XCTAssertEqual(FeedbackCenterPresentation.motionStyle(reduceMotion: true), .opacityOnly)
        XCTAssertEqual(FeedbackCenterPresentation.motionStyle(reduceMotion: false), .moveAndFade)
    }

    func testLightDarkToneActionHoldLongCopyAndAccessibilitySnapshots() {
        let record: SnapshotTestingConfiguration.Record = ProcessInfo.processInfo.environment[
            "FIRSTLIGHT_RECORD_SNAPSHOTS"
        ] == "1" ? .all : .never

        let cases: [(String, FeedbackCenterItemConfiguration)] = [
            ("default", configuration(message: "Changes saved")),
            ("success-action", configuration(
                message: "Appointment saved",
                tone: .success,
                actionLabel: "Undo",
                actionCallback: 41
            )),
            ("warning-hold", configuration(
                message: "Connection lost",
                tone: .warning,
                hold: true,
                timeoutCallback: nil,
                manualCallback: 51
            )),
            ("danger", configuration(
                message: "Could not send referral",
                tone: .danger
            )),
            ("long-copy", configuration(
                message: "This update could not be completed while the device was offline. Check the connection and try again when network access returns.",
                tone: .warning,
                actionLabel: "Retry",
                actionCallback: 61
            )),
        ]

        for style: UIUserInterfaceStyle in [.light, .dark] {
            for (name, item) in cases {
                assertSnapshot(
                    of: controller(item, style: style, contentSize: .large),
                    as: .image(size: CGSize(width: 390, height: 180)),
                    named: "\(name)-\(style == .light ? "light" : "dark")",
                    record: record
                )
            }
        }

        assertSnapshot(
            of: controller(
                cases[1].1,
                style: .light,
                contentSize: .accessibilityExtraExtraExtraLarge
            ),
            as: .image(size: CGSize(width: 390, height: 300)),
            named: "accessibility-extra-extra-extra-large",
            record: record
        )
    }

    private func makeControl(
        configuration: FeedbackCenterItemConfiguration
    ) -> FirstlightFeedbackCenterControl {
        FirstlightFeedbackCenterControl(
            configuration: configuration,
            tokens: .fallback,
            onAction: {},
            onDismiss: {},
            onAccessibilityFocusChanged: { _ in }
        )
    }

    private func controller(
        _ configuration: FeedbackCenterItemConfiguration,
        style: UIUserInterfaceStyle,
        contentSize: UIContentSizeCategory
    ) -> UIViewController {
        let notice = makeControl(configuration: configuration)
            .padding(12)
            .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .bottom)
            .background(Color(uiColor: .systemBackground))
        let host = UIHostingController(rootView: notice)
        host.overrideUserInterfaceStyle = style
        host.traitOverrides.preferredContentSizeCategory = contentSize
        return host
    }

    private func configuration(
        message: String,
        feedbackID: String = "feedback",
        tone: FeedbackCenterTone = .default,
        hold: Bool = false,
        actionLabel: String? = nil,
        actionCallback: Int? = nil,
        timeoutCallback: Int? = 31,
        manualCallback: Int? = nil
    ) -> FeedbackCenterItemConfiguration {
        FeedbackCenterItemConfiguration(
            nodeID: 7,
            feedbackID: feedbackID,
            message: message,
            tone: tone,
            hold: hold,
            actionLabel: actionLabel,
            actionCallback: actionCallback,
            timeoutCallback: timeoutCallback,
            manualCallback: manualCallback
        )
    }
}
