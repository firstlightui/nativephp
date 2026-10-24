import Foundation

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
