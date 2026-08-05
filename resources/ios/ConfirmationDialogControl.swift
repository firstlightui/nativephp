import SwiftUI

enum FirstlightConfirmationDialogTone: String {
    case `default`
    case destructive

    var buttonRole: ButtonRole? {
        self == .destructive ? .destructive : nil
    }
}

struct ConfirmationDialogControl: View {
    @Binding var state: ConfirmationDialogRendererState
    let onConfirm: () -> Void
    let onDismiss: () -> Void

    private var configuration: ConfirmationDialogRendererConfiguration {
        state.configuration
    }

    var body: some View {
        Color.clear
            .frame(width: 0, height: 0)
            .confirmationDialog(
                configuration.title,
                isPresented: Binding(
                    get: { state.isPresented },
                    set: { presented in
                        if !presented { onDismiss() }
                    }
                ),
                titleVisibility: .visible
            ) {
                Button(configuration.confirmLabel, role: configuration.tone.buttonRole) {
                    onConfirm()
                }

                Button(configuration.cancelLabel, role: .cancel) {
                    onDismiss()
                }
            } message: {
                Text(configuration.message)
            }
    }
}
