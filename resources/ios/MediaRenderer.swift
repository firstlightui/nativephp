import PhotosUI
import SwiftUI
import UniformTypeIdentifiers
import UIKit

struct MediaRenderer: View {
    @ObservedObject private var themeStore = NativeUITheme.shared
    @ObservedObject private var bridge = NativeUIBridge.shared
    @Environment(\.colorScheme) private var colorScheme
    @State private var state: MediaRendererState
    @State private var photoItem: PhotosPickerItem?
    @State private var photoPickerTrigger = false
    @State private var showCamera = false
    @State private var showDocumentImporter = false
    private let events: MediaRendererEvents

    init(node: NativeUINode, events: MediaRendererEvents = .native) {
        _state = State(initialValue: MediaRendererState(node: node))
        self.events = events
    }

    var body: some View {
        let tokens = themeStore.resolve(for: colorScheme)

        FirstlightMediaControl(
            configuration: state.configuration,
            tokens: tokens,
            onPick: { handlePick() },
            onClear: {
                if let event = state.clear() {
                    events.send(event)
                }
            }
        )
        .confirmationDialog(
            state.configuration.chooseMediaLabel,
            isPresented: sourceChooserBinding,
            titleVisibility: .visible
        ) {
            if state.configuration.mode == .image {
                Button(state.configuration.photoLibraryLabel) { photoPickerTrigger = true }
                Button(state.configuration.cameraLabel) { showCamera = true }
            } else {
                Button(state.configuration.browseFilesLabel) { showDocumentImporter = true }
            }
            Button(state.configuration.cancelLabel, role: .cancel) { state.dismissSheet() }
        }
        .photosPicker(
            isPresented: $photoPickerTrigger,
            selection: $photoItem,
            matching: .images
        )
        .onChange(of: photoItem) { _, item in
            Task { await handlePhotoItem(item) }
        }
        .sheet(isPresented: cropPresentedBinding) {
            if case let .crop(image) = state.sheet {
                FirstlightMediaCropSheet(
                    image: image,
                    policy: state.configuration.cropPolicy,
                    zoom: cropZoomBinding,
                    onConfirm: { confirmCrop(image) },
                    onCancel: { state.dismissSheet() },
                    onSkip: { skipCrop(image) },
                    onZoomIn: { state.zoomIn() },
                    onZoomOut: { state.zoomOut() },
                    confirmLabel: state.configuration.confirmLabel,
                    cancelLabel: state.configuration.cancelLabel,
                    skipLabel: state.configuration.skipLabel,
                    cropLabel: state.configuration.cropLabel,
                    zoomInLabel: state.configuration.zoomInLabel,
                    zoomOutLabel: state.configuration.zoomOutLabel
                )
            }
        }
        .fullScreenCover(isPresented: $showCamera) {
            MediaCameraPicker { image in
                showCamera = false
                handlePickedImage(image)
            } onCancel: {
                showCamera = false
            }
            .ignoresSafeArea()
        }
        .fileImporter(
            isPresented: $showDocumentImporter,
            allowedContentTypes: [.item],
            allowsMultipleSelection: false
        ) { result in
            handleDocumentResult(result)
        }
        .onReceive(bridge.$currentTree.compactMap { $0 }) { tree in
            _ = state.serverPublished(tree)
        }
    }

    private var sourceChooserBinding: Binding<Bool> {
        Binding(
            get: {
                if case .sourceChooser = state.sheet { return true }
                return false
            },
            set: { presented in
                if !presented, case .sourceChooser = state.sheet {
                    state.dismissSheet()
                }
            }
        )
    }

    private var cropPresentedBinding: Binding<Bool> {
        Binding(
            get: {
                if case .crop = state.sheet { return true }
                return false
            },
            set: { presented in
                if !presented { state.dismissSheet() }
            }
        )
    }

    private var cropZoomBinding: Binding<CGFloat> {
        Binding(
            get: { state.cropZoom },
            set: { state.cropZoom = $0 }
        )
    }

    private func handlePick() {
        guard state.configuration.isInteractive else { return }
        if state.configuration.mode == .document {
            showDocumentImporter = true
            return
        }
        _ = state.openSourceChooser()
    }

    @MainActor
    private func handlePhotoItem(_ item: PhotosPickerItem?) async {
        guard let item else { return }
        photoItem = nil
        state.dismissSheet()
        guard let data = try? await item.loadTransferable(type: Data.self),
              let image = UIImage(data: data) else {
            return
        }
        handlePickedImage(image)
    }

    private func handlePickedImage(_ image: UIImage) {
        if state.configuration.cropPolicy.requiresCropSheet {
            state.beginCrop(with: image)
            return
        }
        emitImage(image)
    }

    private func confirmCrop(_ image: UIImage) {
        emitImage(image)
        state.dismissSheet()
    }

    private func skipCrop(_ image: UIImage) {
        guard state.configuration.cropPolicy.allowsSkip else { return }
        emitImage(image)
        state.dismissSheet()
    }

    private func emitImage(_ image: UIImage) {
        guard let path = MediaTempFileWriter.writeJPEG(image) else { return }
        if let event = state.commitChange(tempPath: path) {
            events.send(event)
        }
    }

    private func handleDocumentResult(_ result: Result<[URL], Error>) {
        state.dismissSheet()
        guard case let .success(urls) = result, let url = urls.first else { return }
        let accessed = url.startAccessingSecurityScopedResource()
        defer {
            if accessed { url.stopAccessingSecurityScopedResource() }
        }
        guard let path = MediaTempFileWriter.copy(url) else { return }
        if let event = state.commitChange(tempPath: path) {
            events.send(event)
        }
    }
}

enum MediaTempFileWriter {
    static func writeJPEG(_ image: UIImage) -> String? {
        guard let data = image.jpegData(compressionQuality: 0.92) else { return nil }
        let url = FileManager.default.temporaryDirectory
            .appendingPathComponent("firstlight-media-\(UUID().uuidString).jpg")
        do {
            try data.write(to: url, options: .atomic)
            return url.path
        } catch {
            return nil
        }
    }

    static func copy(_ source: URL) -> String? {
        let destination = FileManager.default.temporaryDirectory
            .appendingPathComponent("firstlight-media-\(UUID().uuidString)-\(source.lastPathComponent)")
        do {
            if FileManager.default.fileExists(atPath: destination.path) {
                try FileManager.default.removeItem(at: destination)
            }
            try FileManager.default.copyItem(at: source, to: destination)
            return destination.path
        } catch {
            return nil
        }
    }
}

private struct MediaCameraPicker: UIViewControllerRepresentable {
    let onImage: (UIImage) -> Void
    let onCancel: () -> Void

    func makeUIViewController(context: Context) -> UIImagePickerController {
        let picker = UIImagePickerController()
        picker.sourceType = UIImagePickerController.isSourceTypeAvailable(.camera) ? .camera : .photoLibrary
        picker.delegate = context.coordinator
        return picker
    }

    func updateUIViewController(_ uiViewController: UIImagePickerController, context: Context) {}

    func makeCoordinator() -> Coordinator {
        Coordinator(onImage: onImage, onCancel: onCancel)
    }

    final class Coordinator: NSObject, UIImagePickerControllerDelegate, UINavigationControllerDelegate {
        let onImage: (UIImage) -> Void
        let onCancel: () -> Void

        init(onImage: @escaping (UIImage) -> Void, onCancel: @escaping () -> Void) {
            self.onImage = onImage
            self.onCancel = onCancel
        }

        func imagePickerControllerDidCancel(_ picker: UIImagePickerController) {
            onCancel()
        }

        func imagePickerController(
            _ picker: UIImagePickerController,
            didFinishPickingMediaWithInfo info: [UIImagePickerController.InfoKey: Any]
        ) {
            if let image = info[.originalImage] as? UIImage {
                onImage(image)
            } else {
                onCancel()
            }
        }
    }
}
