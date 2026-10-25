import ImageIO
import SwiftUI
import UIKit

struct MediaRendererConfiguration: Equatable {
    enum Mode: String, Equatable {
        case image
        case document
    }

    enum CropMode: String, Equatable {
        case optional
        case required
    }

    let nodeID: Int
    let mode: Mode
    let label: String
    let helper: String
    let error: String
    let required: Bool
    let disabled: Bool
    let disk: String
    let directory: String
    let aspect: String
    let crop: CropMode?
    let hasValue: Bool
    let path: String
    let mime: String
    let size: Int
    let width: Int?
    let height: Int?
    let previewPath: String
    let accessibilityLabel: String
    let accessibilityHint: String
    let confirmLabel: String
    let cancelLabel: String
    let clearLabel: String
    let skipLabel: String
    let cropLabel: String
    let zoomInLabel: String
    let zoomOutLabel: String
    let moveCropLeftLabel: String
    let moveCropRightLabel: String
    let moveCropUpLabel: String
    let moveCropDownLabel: String
    let chooseMediaLabel: String
    let photoLibraryLabel: String
    let cameraLabel: String
    let browseFilesLabel: String
    let onChangeCallback: Int
    let onClearCallback: Int

    init(node: NativeUINode) {
        let props = node.props
        nodeID = node.id
        mode = Mode(rawValue: props.getString("mode")) ?? .image
        label = props.getString("label")
        helper = props.getString("helper")
        error = props.getString("error")
        required = props.getBool("required")
        disabled = props.getBool("disabled")
        disk = props.getString("disk", default: "mobile_public")
        directory = props.getString("directory", default: "media")
        aspect = props.getString("aspect")
        let cropRaw = props.getString("crop")
        crop = CropMode(rawValue: cropRaw)
        hasValue = props.getBool("has_value")
        path = props.getString("path")
        mime = props.getString("mime")
        size = props.getInt("size")
        let widthValue = props.getInt("width")
        width = widthValue > 0 ? widthValue : nil
        let heightValue = props.getInt("height")
        height = heightValue > 0 ? heightValue : nil
        previewPath = props.getString("preview_path")
        let explicitLabel = props.getString("a11y_label")
        accessibilityLabel = explicitLabel.isEmpty ? label : explicitLabel
        accessibilityHint = props.getString("a11y_hint")
        confirmLabel = props.getString("confirm_label", default: "Confirm")
        cancelLabel = props.getString("cancel_label", default: "Cancel")
        clearLabel = props.getString("clear_label", default: "Clear")
        skipLabel = props.getString("skip_label", default: "Skip")
        cropLabel = props.getString("crop_label", default: "Crop")
        zoomInLabel = props.getString("zoom_in_label", default: "Zoom in")
        zoomOutLabel = props.getString("zoom_out_label", default: "Zoom out")
        moveCropLeftLabel = props.getString("move_crop_left_label", default: "Move crop left")
        moveCropRightLabel = props.getString("move_crop_right_label", default: "Move crop right")
        moveCropUpLabel = props.getString("move_crop_up_label", default: "Move crop up")
        moveCropDownLabel = props.getString("move_crop_down_label", default: "Move crop down")
        chooseMediaLabel = props.getString("choose_media_label", default: "Choose media")
        photoLibraryLabel = props.getString("photo_library_label", default: "Photo Library")
        cameraLabel = props.getString("camera_label", default: "Camera")
        browseFilesLabel = props.getString("browse_files_label", default: "Browse Files")
        onChangeCallback = props.getCallbackId("on_change")
        onClearCallback = props.getCallbackId("on_clear")
    }

    var supportingText: String {
        error.isEmpty ? helper : error
    }

    var isInteractive: Bool {
        !disabled
    }

    var canClear: Bool {
        hasValue && isInteractive && onClearCallback != 0
    }

    var cropPolicy: MediaCropPolicy {
        MediaCropPolicy.resolve(mode: mode, aspect: aspect, crop: crop)
    }
}

enum MediaCropPolicy: Equatable {
    case none
    case optionalFreeform
    case requiredFreeform
    case requiredAspect(String)

    static func resolve(mode: MediaRendererConfiguration.Mode, aspect: String, crop: MediaRendererConfiguration.CropMode?) -> MediaCropPolicy {
        guard mode == .image else { return .none }

        if !aspect.isEmpty {
            return .requiredAspect(aspect)
        }

        switch crop {
        case .optional:
            return .optionalFreeform
        case .required:
            return .requiredFreeform
        case nil:
            return .none
        }
    }

    var requiresCropSheet: Bool {
        self != .none
    }

    var allowsSkip: Bool {
        if case .optionalFreeform = self { return true }
        return false
    }

    var aspectRatio: CGFloat? {
        if case let .requiredAspect(raw) = self {
            let parts = raw.split(separator: ":")
            guard parts.count == 2,
                  let width = Double(parts[0]),
                  let height = Double(parts[1]),
                  width > 0,
                  height > 0 else {
                return nil
            }
            return CGFloat(width / height)
        }
        return nil
    }

    /// The size of the committed region: the largest rectangle with the
    /// policy's aspect (or the image's own aspect when freeform), divided by
    /// zoom.
    func cropSize(imageSize: CGSize, zoom: CGFloat) -> CGSize {
        let ratio = aspectRatio ?? imageSize.width / imageSize.height
        var width = imageSize.width
        var height = width / ratio
        if height > imageSize.height {
            height = imageSize.height
            width = height * ratio
        }
        let scale = min(max(zoom, 1), 4)
        return CGSize(
            width: min(max((width / scale).rounded(), 1), imageSize.width),
            height: min(max((height / scale).rounded(), 1), imageSize.height)
        )
    }

    /// Limits a pan offset (image points from the centred position) so the
    /// region never leaves the image at this zoom.
    func clampedOffset(_ offset: CGPoint, imageSize: CGSize, zoom: CGFloat) -> CGPoint {
        let size = cropSize(imageSize: imageSize, zoom: zoom)
        let maxX = max((imageSize.width - size.width) / 2, 0)
        let maxY = max((imageSize.height - size.height) / 2, 0)
        return CGPoint(x: min(max(offset.x, -maxX), maxX), y: min(max(offset.y, -maxY), maxY))
    }

    /// The committed region: `cropSize` centred in the image, moved by
    /// `offset` and kept inside the image. The crop sheet previews exactly
    /// this region.
    func cropRect(imageSize: CGSize, zoom: CGFloat, offset: CGPoint = .zero) -> CGRect {
        let size = cropSize(imageSize: imageSize, zoom: zoom)
        let maxX = imageSize.width - size.width
        let maxY = imageSize.height - size.height
        return CGRect(
            x: min(max((maxX / 2 + offset.x).rounded(.down), 0), maxX),
            y: min(max((maxY / 2 + offset.y).rounded(.down), 0), maxY),
            width: size.width,
            height: size.height
        )
    }

    /// Draws the region in orientation-corrected image coordinates.
    func crop(_ image: UIImage, zoom: CGFloat, offset: CGPoint = .zero) -> UIImage {
        let rect = cropRect(imageSize: image.size, zoom: zoom, offset: offset)
        let format = UIGraphicsImageRendererFormat.default()
        format.scale = image.scale
        return UIGraphicsImageRenderer(size: rect.size, format: format).image { _ in
            image.draw(at: CGPoint(x: -rect.minX, y: -rect.minY))
        }
    }
}

/// A direction for the accessible move actions on the crop region.
enum MediaCropDirection: CaseIterable {
    case left, right, up, down
}

enum MediaRendererEvent: Equatable {
    case change(callbackId: Int, nodeId: Int, tempPath: String)
    case clear(callbackId: Int, nodeId: Int)

    var wireName: String {
        switch self {
        case .change: "TEXT_CHANGE"
        case .clear: "PRESS"
        }
    }
}

struct MediaRendererEvents {
    let send: @MainActor (MediaRendererEvent) -> Void

    static let native = MediaRendererEvents { event in
        switch event {
        case let .change(callbackId, nodeId, tempPath):
            NativeUIBridge.sendTextChangeEvent(callbackId, nodeId: nodeId, text: tempPath)
        case let .clear(callbackId, nodeId):
            NativeUIBridge.sendPressEvent(callbackId, nodeId: nodeId)
        }
    }
}

struct MediaRendererState {
    enum Sheet: Equatable {
        case sourceChooser
        case crop(UIImage)
    }

    var configuration: MediaRendererConfiguration
    var sheet: Sheet?
    var cropZoom: CGFloat = 1
    /// Image points from the centred region; always clamped to the image.
    var cropOffset: CGPoint = .zero
    var pendingTempPath: String?

    init(node: NativeUINode) {
        configuration = MediaRendererConfiguration(node: node)
    }

    mutating func serverPublished(_ tree: NativeUITree) -> Bool {
        guard let node = Self.findNode(id: configuration.nodeID, in: tree.root) else {
            return false
        }
        configuration = MediaRendererConfiguration(node: node)
        pendingTempPath = nil
        return true
    }

    mutating func openSourceChooser() -> Bool {
        guard configuration.isInteractive else { return false }
        sheet = .sourceChooser
        return true
    }

    mutating func dismissSheet() {
        sheet = nil
        cropZoom = 1
        cropOffset = .zero
    }

    mutating func beginCrop(with image: UIImage) {
        cropZoom = 1
        cropOffset = .zero
        sheet = .crop(image)
    }

    mutating func zoomIn() {
        setCropZoom(cropZoom + 0.25)
    }

    mutating func zoomOut() {
        setCropZoom(cropZoom - 0.25)
    }

    /// Sets zoom within 1...4 (buttons and pinch) and re-clamps the pan, so
    /// zooming out pulls the region back inside the image.
    mutating func setCropZoom(_ zoom: CGFloat) {
        cropZoom = min(4, max(1, zoom))
        cropOffset = clamped(cropOffset)
    }

    /// Moves the region by `delta` image points, stopping at the image edges.
    mutating func panCrop(by delta: CGPoint) {
        cropOffset = clamped(CGPoint(x: cropOffset.x + delta.x, y: cropOffset.y + delta.y))
    }

    /// Accessible move: a tenth of the region on that axis.
    mutating func moveCrop(_ direction: MediaCropDirection) {
        guard let size = cropImageSize else { return }
        let region = configuration.cropPolicy.cropSize(imageSize: size, zoom: cropZoom)
        switch direction {
        case .left: panCrop(by: CGPoint(x: -region.width / 10, y: 0))
        case .right: panCrop(by: CGPoint(x: region.width / 10, y: 0))
        case .up: panCrop(by: CGPoint(x: 0, y: -region.height / 10))
        case .down: panCrop(by: CGPoint(x: 0, y: region.height / 10))
        }
    }

    private var cropImageSize: CGSize? {
        guard case let .crop(image) = sheet, image.size.width > 0, image.size.height > 0 else { return nil }
        return image.size
    }

    private func clamped(_ offset: CGPoint) -> CGPoint {
        guard let size = cropImageSize else { return .zero }
        return configuration.cropPolicy.clampedOffset(offset, imageSize: size, zoom: cropZoom)
    }

    func commitChange(tempPath: String) -> MediaRendererEvent? {
        guard configuration.onChangeCallback != 0 else { return nil }
        return .change(
            callbackId: configuration.onChangeCallback,
            nodeId: configuration.nodeID,
            tempPath: tempPath
        )
    }

    func clear() -> MediaRendererEvent? {
        guard configuration.canClear else { return nil }
        return .clear(
            callbackId: configuration.onClearCallback,
            nodeId: configuration.nodeID
        )
    }

    private static func findNode(id: Int, in node: NativeUINode) -> NativeUINode? {
        if node.id == id { return node }
        for child in node.children {
            if let match = findNode(id: id, in: child) {
                return match
            }
        }
        return nil
    }
}

struct FirstlightMediaControl: View {
    let configuration: MediaRendererConfiguration
    let tokens: NativeUITokens
    let onPick: () -> Void
    let onClear: () -> Void

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            if !configuration.label.isEmpty {
                Text(configuration.required ? "\(configuration.label) *" : configuration.label)
                    .font(.subheadline.weight(.medium))
                    .foregroundStyle(tokens.onSurface)
            }

            HStack(spacing: 12) {
                preview
                    .frame(width: 72, height: 72)
                    .clipShape(RoundedRectangle(cornerRadius: 10, style: .continuous))
                    .overlay {
                        RoundedRectangle(cornerRadius: 10, style: .continuous)
                            .stroke(configuration.error.isEmpty ? Color.clear : tokens.destructive, lineWidth: 1)
                    }
                    .accessibilityHidden(true)

                VStack(alignment: .leading, spacing: 8) {
                    Button(configuration.hasValue ? "Replace" : "Choose") {
                        onPick()
                    }
                    .buttonStyle(.bordered)
                    .tint(tokens.primary)
                    .disabled(!configuration.isInteractive)
                    .frame(minHeight: 44)
                    .firstlightValidationFocusTarget()

                    if configuration.canClear {
                        Button(configuration.clearLabel, role: .destructive, action: onClear)
                            .frame(minHeight: 44)
                    }
                }

                Spacer(minLength: 0)
            }
            .padding(12)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(Color(uiColor: .secondarySystemBackground))
            .clipShape(RoundedRectangle(cornerRadius: 10, style: .continuous))

            if !configuration.supportingText.isEmpty {
                Text(configuration.supportingText)
                    .font(.footnote)
                    .foregroundStyle(configuration.error.isEmpty ? tokens.onSurfaceVariant : tokens.destructive)
                    .accessibilityLabel(
                        configuration.error.isEmpty
                            ? configuration.supportingText
                            : "Error: \(configuration.supportingText)"
                    )
            }
        }
        .opacity(configuration.disabled ? 0.6 : 1)
        .accessibilityElement(children: .contain)
        .accessibilityLabel(configuration.accessibilityLabel)
        .accessibilityHint(configuration.accessibilityHint)
    }

    @ViewBuilder private var preview: some View {
        if configuration.hasValue, configuration.mode == .image, !configuration.previewPath.isEmpty {
            FirstlightMediaThumbnail(path: configuration.previewPath) {
                ZStack {
                    tokens.surfaceVariant
                    Image(systemName: "photo")
                        .font(.title2)
                        .foregroundStyle(tokens.onSurfaceVariant)
                }
            }
        } else if configuration.hasValue {
            ZStack {
                tokens.surfaceVariant
                Image(systemName: configuration.mode == .document ? "doc.fill" : "photo")
                    .font(.title2)
                    .foregroundStyle(tokens.onSurfaceVariant)
            }
            .overlay(alignment: .bottom) {
                if !configuration.path.isEmpty {
                    Text(URL(fileURLWithPath: configuration.path).lastPathComponent)
                        .font(.caption2)
                        .lineLimit(1)
                        .padding(4)
                        .frame(maxWidth: .infinity)
                        .background(.ultraThinMaterial)
                }
            }
        } else {
            ZStack {
                tokens.surfaceVariant
                Image(systemName: configuration.mode == .document ? "doc.badge.plus" : "photo.badge.plus")
                    .font(.title2)
                    .foregroundStyle(tokens.onSurfaceVariant)
            }
        }
    }
}

/// Shows the stored image at `path`, decoded off the main thread as a
/// thumbnail about 72 pt on its short side. `placeholder` shows while it
/// loads or when the file cannot be decoded.
struct FirstlightMediaThumbnail<Placeholder: View>: View {
    let path: String
    @ViewBuilder let placeholder: () -> Placeholder

    @Environment(\.displayScale) private var displayScale
    @State private var image: UIImage?

    var body: some View {
        Group {
            if let image {
                Image(uiImage: image)
                    .resizable()
                    .scaledToFill()
            } else {
                placeholder()
            }
        }
        .task(id: path) {
            let pixels = 72 * displayScale
            image = await Task.detached(priority: .userInitiated) {
                FirstlightMediaThumbnail.decode(path: path, shortSide: pixels)
            }.value
        }
    }

    /// ImageIO thumbnail: reads only what it needs and applies EXIF
    /// orientation, so a 12 MP photo never decodes at full size. ImageIO
    /// caps the long side, so scale the cap until the short side reaches
    /// `shortSide`; the square preview then fills without upscaling.
    nonisolated static func decode(path: String, shortSide: CGFloat) -> UIImage? {
        guard let source = CGImageSourceCreateWithURL(URL(fileURLWithPath: path) as CFURL, nil) else { return nil }
        var maxPixelSize = shortSide
        if let properties = CGImageSourceCopyPropertiesAtIndex(source, 0, nil) as? [CFString: Any],
           let width = (properties[kCGImagePropertyPixelWidth] as? NSNumber)?.doubleValue,
           let height = (properties[kCGImagePropertyPixelHeight] as? NSNumber)?.doubleValue,
           width > 0, height > 0 {
            maxPixelSize = (shortSide * max(width, height) / min(width, height)).rounded(.up)
        }
        let options: [CFString: Any] = [
            kCGImageSourceCreateThumbnailFromImageAlways: true,
            kCGImageSourceCreateThumbnailWithTransform: true,
            kCGImageSourceThumbnailMaxPixelSize: maxPixelSize,
        ]
        guard let thumbnail = CGImageSourceCreateThumbnailAtIndex(source, 0, options as CFDictionary) else { return nil }
        return UIImage(cgImage: thumbnail)
    }
}

struct FirstlightMediaCropSheet: View {
    let image: UIImage
    let policy: MediaCropPolicy
    let zoom: CGFloat
    var offset: CGPoint = .zero
    let onConfirm: () -> Void
    let onCancel: () -> Void
    let onSkip: () -> Void
    let onZoomIn: () -> Void
    let onZoomOut: () -> Void
    /// Sets an absolute zoom from pinch; the state clamps it to 1...4.
    var onZoom: (CGFloat) -> Void = { _ in }
    /// Moves the region by a delta in image points; the state clamps it.
    var onPan: (CGPoint) -> Void = { _ in }
    var onMove: (MediaCropDirection) -> Void = { _ in }
    var confirmLabel: String = "Confirm"
    var cancelLabel: String = "Cancel"
    var skipLabel: String = "Skip"
    var cropLabel: String = "Crop"
    var zoomInLabel: String = "Zoom in"
    var zoomOutLabel: String = "Zoom out"
    var moveCropLeftLabel: String = "Move crop left"
    var moveCropRightLabel: String = "Move crop right"
    var moveCropUpLabel: String = "Move crop up"
    var moveCropDownLabel: String = "Move crop down"

    @State private var lastDrag: CGSize = .zero
    @State private var pinchBase: CGFloat?

    private var previewAspect: CGFloat {
        if let aspect = policy.aspectRatio { return aspect }
        guard image.size.width > 0, image.size.height > 0 else { return 1 }
        return image.size.width / image.size.height
    }

    var body: some View {
        NavigationStack {
            VStack(spacing: 16) {
                // The frame shows exactly the region Confirm commits: the same
                // cropRect, scaled to the frame width.
                Color.clear
                    .aspectRatio(previewAspect, contentMode: .fit)
                    .overlay { region }
                    .clipped()
                    .accessibilityElement()
                    .accessibilityLabel(cropLabel)
                    .accessibilityAddTraits(.isImage)
                    // SwiftUI lists the last-applied action first, so this
                    // reads Left, Right, Up, Down in the actions rotor.
                    .accessibilityAction(named: moveCropDownLabel) { onMove(.down) }
                    .accessibilityAction(named: moveCropUpLabel) { onMove(.up) }
                    .accessibilityAction(named: moveCropRightLabel) { onMove(.right) }
                    .accessibilityAction(named: moveCropLeftLabel) { onMove(.left) }
                    .padding(24)
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
                    .background(Color.black)

                HStack(spacing: 16) {
                    Button(action: onZoomOut) {
                        Image(systemName: "minus.magnifyingglass")
                            .frame(minWidth: 44, minHeight: 44)
                    }
                    .accessibilityLabel(zoomOutLabel)

                    Button(action: onZoomIn) {
                        Image(systemName: "plus.magnifyingglass")
                            .frame(minWidth: 44, minHeight: 44)
                    }
                    .accessibilityLabel(zoomInLabel)
                }

                HStack {
                    Button(cancelLabel, action: onCancel)
                        .frame(minHeight: 44)
                    Spacer()
                    if policy.allowsSkip {
                        Button(skipLabel, action: onSkip)
                            .frame(minHeight: 44)
                    }
                    Button(confirmLabel, action: onConfirm)
                        .buttonStyle(.borderedProminent)
                        .frame(minHeight: 44)
                }
                .padding(.horizontal)
                .padding(.bottom)
            }
            .navigationTitle(cropLabel)
            .navigationBarTitleDisplayMode(.inline)
        }
    }

    @ViewBuilder private var region: some View {
        GeometryReader { geometry in
            if image.size.width > 0, image.size.height > 0, geometry.size.width > 0 {
                let rect = policy.cropRect(imageSize: image.size, zoom: zoom, offset: offset)
                let scale = geometry.size.width / rect.width
                Image(uiImage: image)
                    .resizable()
                    .frame(width: image.size.width * scale, height: image.size.height * scale)
                    .offset(x: -rect.minX * scale, y: -rect.minY * scale)
                    .frame(width: geometry.size.width, height: geometry.size.height, alignment: .topLeading)
                    .contentShape(Rectangle())
                    .gesture(
                        SimultaneousGesture(
                            DragGesture()
                                .onChanged { value in
                                    // Dragging the photo right moves the region left.
                                    let dx = value.translation.width - lastDrag.width
                                    let dy = value.translation.height - lastDrag.height
                                    lastDrag = value.translation
                                    onPan(CGPoint(x: -dx / scale, y: -dy / scale))
                                }
                                .onEnded { _ in lastDrag = .zero },
                            MagnifyGesture()
                                .onChanged { value in
                                    let base = pinchBase ?? zoom
                                    pinchBase = base
                                    onZoom(base * value.magnification)
                                }
                                .onEnded { _ in pinchBase = nil }
                        )
                    )
            }
        }
    }
}
