import Foundation
import XCTest

/// SwiftUI publishes its in-process UIKit accessibility tree only while system
/// accessibility is enabled, which normally happens when VoiceOver or
/// Accessibility Inspector attaches. Rendered-tree assertions enable it
/// explicitly so they do not depend on the simulator's earlier state.
///
/// Enabling also persists `AccessibilityEnabled` in the simulator's
/// `com.apple.Accessibility` domain. Restoring it mid-run would switch the
/// tree off for later tests, so the original preference is restored once
/// when the test bundle finishes.
@MainActor
enum AccessibilityRuntime {
    private typealias Setter = @convention(c) (Bool) -> Void
    private static var restorer: PreferenceRestorer?

    static func enable() {
        guard let set = dlsym(dlopen("/usr/lib/libAccessibility.dylib", RTLD_NOW), "_AXSSetAccessibilityEnabled") else {
            XCTFail("libAccessibility does not export _AXSSetAccessibilityEnabled")
            return
        }
        if restorer == nil {
            let restorer = PreferenceRestorer(original: PreferenceRestorer.read())
            XCTestObservationCenter.shared.addTestObserver(restorer)
            self.restorer = restorer
        }
        unsafeBitCast(set, to: Setter.self)(true)
    }
}

private final class PreferenceRestorer: NSObject, XCTestObservation, Sendable {
    private static let domain = "com.apple.Accessibility"
    private static let key = "AccessibilityEnabled"
    private let original: Bool?

    init(original: Bool?) {
        self.original = original
    }

    static func read() -> Bool? {
        CFPreferencesCopyValue(key as CFString, domain as CFString, kCFPreferencesCurrentUser, kCFPreferencesAnyHost) as? Bool
    }

    func testBundleDidFinish(_ testBundle: Bundle) {
        let value = original.map { $0 as CFBoolean }
        CFPreferencesSetValue(Self.key as CFString, value, Self.domain as CFString, kCFPreferencesCurrentUser, kCFPreferencesAnyHost)
        CFPreferencesSynchronize(Self.domain as CFString, kCFPreferencesCurrentUser, kCFPreferencesAnyHost)
    }
}
