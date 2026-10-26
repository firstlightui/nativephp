import SwiftUI

extension KeyboardFocusPolicy {
    /// Registers a focused Firstlight text control with NativePHP's press dispatch, which calls
    /// `flush` before sending a Button press so PHP sees a pending `blur` or `debounce` edit first.
    /// Each control passes its own token, so a late blur from the previous field is ignored.
    /// Firstlight text controls do not support `keep-focus-on-submit`, so a press always resigns.
    static func firstlightFieldFocusChanged(_ focused: Bool, token: UUID, flush: @escaping () -> Void) {
        if focused {
            fieldFocused(token, keepsFocus: false, flush: flush)
        } else {
            fieldBlurred(token)
        }
    }
}

/// Reuse Mobile UI's enclosing scroll view rather than owning keyboard insets.
/// A bottom-anchored chat log deliberately supplies no proxy: it owns scrolling.
struct FirstlightFocusedFieldScroll: ViewModifier {
    let nodeID: Int
    let focused: Bool
    @Environment(\.nativeUIScrollProxy) private var scrollProxy

    func body(content: Content) -> some View {
        content.task(id: focused) {
            guard focused, let scrollProxy else { return }
            // Match Mobile UI's focus-scroll policy: wait for keyboard presentation.
            do { try await Task.sleep(for: .milliseconds(350)) }
            catch { return }
            withAnimation(.easeOut(duration: 0.2)) {
                scrollProxy.scrollTo(nodeID, anchor: .center)
            }
        }
    }
}
