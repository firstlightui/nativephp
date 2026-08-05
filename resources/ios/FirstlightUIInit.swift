import Foundation
import SwiftUI

func registerFirstlightUI() {
    NativeRootHostRegistry.shared.register(
        "firstlight.feedback-center",
        consumes: "firstlight_feedback_center"
    ) { root, content in
        let center = root.children.first { $0.type == "firstlight_feedback_center" }
        return AnyView(FirstlightFeedbackCenterHost(centerNode: center) { content })
    }
}

#if SWIFT_PACKAGE
final class NativeRootHostRegistry: @unchecked Sendable {
    static let shared = NativeRootHostRegistry()

    typealias Host = @MainActor (_ root: NativeUINode, _ content: AnyView) -> AnyView

    private struct Entry {
        let consumedType: String?
        let host: Host
    }

    private var entries: [Entry] = []
    private let lock = NSLock()

    private init() {}

    func register(_ name: String, consumes: String? = nil, host: @escaping Host) {
        lock.lock()
        defer { lock.unlock() }
        entries.append(Entry(consumedType: consumes, host: host))
    }

    func consumes(_ type: String) -> Bool {
        lock.lock()
        defer { lock.unlock() }
        return entries.contains { $0.consumedType == type }
    }

    @MainActor
    func wrap(root: NativeUINode, content: AnyView) -> AnyView {
        lock.lock()
        let hosts = entries
        lock.unlock()
        return hosts.reduce(content) { view, entry in entry.host(root, view) }
    }
}
#endif
