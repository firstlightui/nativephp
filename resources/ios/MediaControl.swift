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
    let previewURL: String
    let accessibilityLabel: String
    let accessibilityHint: String
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
        previewURL = props.getString("preview_url")
        let explicitLabel = props.getString("a11y_label")
        accessibilityLabel = explicitLabel.isEmpty ? label : explicitLabel
        accessibilityHint = props.getString("a11y_hint")
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
    }

    mutating func beginCrop(with image: UIImage) {
        cropZoom = 1
        sheet = .crop(image)
    }

    mutating func zoomIn() {
        cropZoom = min(4, cropZoom + 0.25)
    }

    mutating func zoomOut() {
        cropZoom = max(1, cropZoom - 0.25)
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
                    .disabled(!configuration.isInteractive)
                    .frame(minHeight: 44)

                    if configuration.canClear {
                        Button("Clear", role: .destructive, action: onClear)
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
        if configuration.hasValue {
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

struct FirstlightMediaCropSheet: View {
    let image: UIImage
    let policy: MediaCropPolicy
    @Binding var zoom: CGFloat
    let onConfirm: () -> Void
    let onCancel: () -> Void
    let onSkip: () -> Void
    let onZoomIn: () -> Void
    let onZoomOut: () -> Void

    var body: some View {
        NavigationStack {
            VStack(spacing: 16) {
                ZStack {
                    Image(uiImage: image)
                        .resizable()
                        .scaledToFit()
                        .scaleEffect(zoom)
                        .frame(maxWidth: .infinity, maxHeight: .infinity)
                        .clipped()

                    if let aspect = policy.aspectRatio {
                        RoundedRectangle(cornerRadius: 2)
                            .stroke(Color.white, lineWidth: 2)
                            .aspectRatio(aspect, contentMode: .fit)
                            .padding(24)
                            .allowsHitTesting(false)
                    }
                }
                .frame(maxWidth: .infinity, maxHeight: .infinity)
                .background(Color.black)

                HStack(spacing: 16) {
                    Button(action: onZoomOut) {
                        Image(systemName: "minus.magnifyingglass")
                            .frame(minWidth: 44, minHeight: 44)
                    }
                    .accessibilityLabel("Zoom out")

                    Button(action: onZoomIn) {
                        Image(systemName: "plus.magnifyingglass")
                            .frame(minWidth: 44, minHeight: 44)
                    }
                    .accessibilityLabel("Zoom in")
                }

                HStack {
                    Button("Cancel", action: onCancel)
                        .frame(minHeight: 44)
                    Spacer()
                    if policy.allowsSkip {
                        Button("Skip", action: onSkip)
                            .frame(minHeight: 44)
                    }
                    Button("Confirm", action: onConfirm)
                        .buttonStyle(.borderedProminent)
                        .frame(minHeight: 44)
                }
                .padding(.horizontal)
                .padding(.bottom)
            }
            .navigationTitle("Crop")
            .navigationBarTitleDisplayMode(.inline)
        }
    }
}
