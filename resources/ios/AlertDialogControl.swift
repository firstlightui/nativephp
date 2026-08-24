import SwiftUI

struct AlertDialogControl: View {
    @Binding var state: AlertDialogRendererState
    let onDismiss: () -> Void

    private var configuration: AlertDialogRendererConfiguration {
        state.configuration
    }

    var body: some View {
        Color.clear
            .frame(width: 0, height: 0)
            .alert(
                configuration.title,
                isPresented: Binding(
                    get: { state.isPresented },
                    set: { presented in
                        if !presented { onDismiss() }
                    }
                )
            ) {
                Button(configuration.actionLabel) {
                    onDismiss()
                }
            } message: {
                Text(configuration.message)
            }
    }
}
