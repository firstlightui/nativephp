import Foundation
import SwiftUI

func registerFirstlightUI() {
    NativeRootHostRegistry.shared.register(
        "firstlight.feedback-center",
        consumes: "firstlight.feedback-center",
        host: firstlightFeedbackCenterRootHost
    )
}

private struct FeedbackCenterUncheckedTransfer<Value>: @unchecked Sendable {
    let value: Value
}

func firstlightFeedbackCenterRootHost(
    _ root: NativeUINode,
    _ content: AnyView
) -> AnyView {
    // NativeRootHostRegistry.wrap is invoked from SwiftUI's main-actor render
    // path. Preserve the registry's exact nonisolated production signature and
    // assert that official invocation context before constructing a View.
    let input = FeedbackCenterUncheckedTransfer(value: (root, content))
    let output = MainActor.assumeIsolated {
        let (root, content) = input.value
        let center = root.children.first { $0.type == "firstlight.feedback-center" }
        return FeedbackCenterUncheckedTransfer(value: AnyView(
            FirstlightFeedbackCenterHost(centerNode: center) { content }
        ))
    }

    return output.value
}

#if SWIFT_PACKAGE
final class NativeRootHostRegistry: @unchecked Sendable {
    static let shared = NativeRootHostRegistry()

    typealias Host = (_ root: NativeUINode, _ content: AnyView) -> AnyView

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

    func wrap(root: NativeUINode, content: AnyView) -> AnyView {
        lock.lock()
        let hosts = entries
        lock.unlock()
        return hosts.reduce(content) { view, entry in entry.host(root, view) }
    }
}
#endif
