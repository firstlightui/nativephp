import SwiftUI
import UIKit

enum FirstlightConfirmationDialogTone: String {
    case `default`
    case destructive

    var actionStyle: UIAlertAction.Style {
        self == .destructive ? .destructive : .default
    }
}

/// Presents the decision as a UIKit action sheet. SwiftUI `confirmationDialog`
/// anchors to the view it modifies on iOS 26; this element has no visible
/// trigger, so the sheet would hang off a zero-size placeholder and hide its
/// cancel action. Without a source view, iPhone presents from the bottom edge.
struct ConfirmationDialogControl: View {
    @Binding var state: ConfirmationDialogRendererState
    let onConfirm: () -> Void
    let onDismiss: () -> Void

    var body: some View {
        ConfirmationActionSheetPresenter(
            isPresented: state.isPresented,
            configuration: state.configuration,
            onConfirm: onConfirm,
            onDismiss: onDismiss
        )
        .frame(width: 0, height: 0)
        .accessibilityHidden(true)
    }

    static func makeActionSheet(
        for configuration: ConfirmationDialogRendererConfiguration,
        onConfirm: @escaping () -> Void,
        onDismiss: @escaping () -> Void
    ) -> UIAlertController {
        let sheet = UIAlertController(
            title: configuration.title,
            message: configuration.message,
            preferredStyle: .actionSheet
        )
        sheet.addAction(UIAlertAction(title: configuration.confirmLabel, style: configuration.tone.actionStyle) { _ in
            onConfirm()
        })
        sheet.addAction(UIAlertAction(title: configuration.cancelLabel, style: .cancel) { _ in
            onDismiss()
        })

        return sheet
    }
}

private struct ConfirmationActionSheetPresenter: UIViewControllerRepresentable {
    let isPresented: Bool
    let configuration: ConfirmationDialogRendererConfiguration
    let onConfirm: () -> Void
    let onDismiss: () -> Void

    func makeCoordinator() -> Coordinator {
        Coordinator()
    }

    func makeUIViewController(context: Context) -> UIViewController {
        UIViewController()
    }

    func updateUIViewController(_ host: UIViewController, context: Context) {
        let coordinator = context.coordinator
        coordinator.onConfirm = onConfirm
        coordinator.onDismiss = onDismiss
        coordinator.wantsPresentation = isPresented
        coordinator.configuration = configuration
        coordinator.host = host
        // The host may not be in a window yet on first render.
        DispatchQueue.main.async { coordinator.reconcile() }
    }

    static func dismantleUIViewController(_ host: UIViewController, coordinator: Coordinator) {
        coordinator.wantsPresentation = false
        coordinator.reconcile()
    }

    @MainActor
    final class Coordinator {
        var onConfirm: () -> Void = {}
        var onDismiss: () -> Void = {}
        var wantsPresentation = false
        var configuration: ConfirmationDialogRendererConfiguration?
        weak var host: UIViewController?

        private weak var sheet: UIAlertController?
        private var presentedConfiguration: ConfirmationDialogRendererConfiguration?
        private var pendingAttempts = 0

        func reconcile() {
            guard wantsPresentation, let configuration else {
                // Programmatic closure: dismiss without emitting a callback.
                sheet?.dismiss(animated: true)
                sheet = nil
                return
            }

            if let sheet, sheet.presentingViewController != nil {
                guard let shown = presentedConfiguration, shown != configuration else { return }

                if shown.confirmLabel == configuration.confirmLabel,
                   shown.cancelLabel == configuration.cancelLabel,
                   shown.tone == configuration.tone {
                    sheet.title = configuration.title
                    sheet.message = configuration.message
                    presentedConfiguration = configuration
                    return
                }

                sheet.dismiss(animated: false)
                self.sheet = nil
            }

            guard let presenter = topPresenter() else {
                if pendingAttempts < 40 {
                    pendingAttempts += 1
                    DispatchQueue.main.asyncAfter(deadline: .now() + 0.05) { [weak self] in self?.reconcile() }
                }
                return
            }
            pendingAttempts = 0

            let sheet = ConfirmationDialogControl.makeActionSheet(
                for: configuration,
                onConfirm: { [weak self] in
                    self?.sheet = nil
                    self?.onConfirm()
                },
                onDismiss: { [weak self] in
                    self?.sheet = nil
                    self?.onDismiss()
                }
            )
            if let popover = sheet.popoverPresentationController {
                popover.sourceView = presenter.view
                popover.sourceRect = CGRect(x: presenter.view.bounds.midX, y: presenter.view.bounds.midY, width: 0, height: 0)
                popover.permittedArrowDirections = []
            }

            self.sheet = sheet
            presentedConfiguration = configuration
            presenter.present(sheet, animated: true)
        }

        private func topPresenter() -> UIViewController? {
            guard var presenter = host?.view.window?.rootViewController else { return nil }
            while let presented = presenter.presentedViewController, !presented.isBeingDismissed {
                presenter = presented
            }

            return presenter
        }
    }
}
