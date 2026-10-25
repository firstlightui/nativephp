import SnapshotTesting
import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class MediaSnapshotTests: XCTestCase {
    func testConfigurationDecodesImageFieldContract() {
        let configuration = MediaRendererConfiguration(node: makeNode(
            mode: "image",
            aspect: "1:1",
            crop: "required",
            hasValue: true,
            path: "avatars/a.jpg",
            mime: "image/jpeg",
            size: 1200,
            width: 100,
            height: 100,
            required: true,
            error: "Choose a photo."
        ))

        XCTAssertEqual(configuration.nodeID, 7)
        XCTAssertEqual(configuration.mode, .image)
        XCTAssertEqual(configuration.label, "Profile photo")
        XCTAssertEqual(configuration.aspect, "1:1")
        XCTAssertEqual(configuration.crop, .required)
        XCTAssertTrue(configuration.hasValue)
        XCTAssertEqual(configuration.path, "avatars/a.jpg")
        XCTAssertEqual(configuration.mime, "image/jpeg")
        XCTAssertEqual(configuration.size, 1200)
        XCTAssertEqual(configuration.width, 100)
        XCTAssertEqual(configuration.height, 100)
        XCTAssertTrue(configuration.required)
        XCTAssertEqual(configuration.supportingText, "Choose a photo.")
        XCTAssertEqual(configuration.onChangeCallback, 41)
        XCTAssertEqual(configuration.onClearCallback, 42)
        XCTAssertEqual(configuration.confirmLabel, "Confirm")
        XCTAssertEqual(configuration.cancelLabel, "Cancel")
        XCTAssertEqual(configuration.clearLabel, "Clear")
        XCTAssertEqual(configuration.skipLabel, "Skip")
        XCTAssertEqual(configuration.cropLabel, "Crop")
        XCTAssertEqual(configuration.zoomInLabel, "Zoom in")
        XCTAssertEqual(configuration.zoomOutLabel, "Zoom out")
        XCTAssertEqual(configuration.chooseMediaLabel, "Choose media")
        XCTAssertEqual(configuration.photoLibraryLabel, "Photo Library")
        XCTAssertEqual(configuration.cameraLabel, "Camera")
        XCTAssertEqual(configuration.browseFilesLabel, "Browse Files")
        XCTAssertEqual(configuration.cropPolicy, .requiredAspect("1:1"))
    }

    func testThumbnailDecodesStoredImageWithinPreviewSize() throws {
        let url = FileManager.default.temporaryDirectory.appendingPathComponent("firstlight-cover-\(UUID().uuidString).png")
        let source = UIGraphicsImageRenderer(size: CGSize(width: 400, height: 300), format: {
            let format = UIGraphicsImageRendererFormat()
            format.scale = 1
            return format
        }()).image { context in
            UIColor.red.setFill()
            context.fill(CGRect(x: 0, y: 0, width: 400, height: 300))
        }
        try XCTUnwrap(source.pngData()).write(to: url)
        defer { try? FileManager.default.removeItem(at: url) }

        let configuration = MediaRendererConfiguration(node: makeNode(mode: "image", hasValue: true, previewPath: url.path))
        XCTAssertEqual(configuration.previewPath, url.path)

        // The short side reaches the requested size so a square fill never upscales.
        let thumbnail = try XCTUnwrap(FirstlightMediaThumbnail<EmptyView>.decode(path: url.path, shortSide: 150))
        XCTAssertEqual(thumbnail.size.width * thumbnail.scale, 200, accuracy: 1)
        XCTAssertEqual(thumbnail.size.height * thumbnail.scale, 150, accuracy: 1)
        XCTAssertNil(FirstlightMediaThumbnail<EmptyView>.decode(path: url.path + ".missing", shortSide: 150))
    }

    func testCropPolicyComposition() {
        XCTAssertEqual(
            MediaCropPolicy.resolve(mode: .image, aspect: "", crop: nil),
            .none
        )
        XCTAssertEqual(
            MediaCropPolicy.resolve(mode: .image, aspect: "4:3", crop: nil),
            .requiredAspect("4:3")
        )
        XCTAssertEqual(
            MediaCropPolicy.resolve(mode: .image, aspect: "", crop: .optional),
            .optionalFreeform
        )
        XCTAssertEqual(
            MediaCropPolicy.resolve(mode: .image, aspect: "", crop: .required),
            .requiredFreeform
        )
        XCTAssertEqual(
            MediaCropPolicy.resolve(mode: .document, aspect: "1:1", crop: .required),
            .none
        )
        XCTAssertTrue(MediaCropPolicy.optionalFreeform.allowsSkip)
        XCTAssertFalse(MediaCropPolicy.requiredFreeform.allowsSkip)
        XCTAssertEqual(Double(MediaCropPolicy.requiredAspect("1:1").aspectRatio ?? -1), 1, accuracy: 0.001)
    }

    func testCropRectIsCentredPolicyAspectDividedByZoom() {
        let square = MediaCropPolicy.requiredAspect("1:1")
        XCTAssertEqual(square.cropRect(imageSize: CGSize(width: 4000, height: 3000), zoom: 1), CGRect(x: 500, y: 0, width: 3000, height: 3000))
        XCTAssertEqual(square.cropRect(imageSize: CGSize(width: 4000, height: 3000), zoom: 2), CGRect(x: 1250, y: 750, width: 1500, height: 1500))
        XCTAssertEqual(
            MediaCropPolicy.requiredAspect("16:9").cropRect(imageSize: CGSize(width: 3000, height: 4000), zoom: 1),
            CGRect(x: 0, y: 1156, width: 3000, height: 1688)
        )
        XCTAssertEqual(
            MediaCropPolicy.optionalFreeform.cropRect(imageSize: CGSize(width: 4000, height: 3000), zoom: 2),
            CGRect(x: 1000, y: 750, width: 2000, height: 1500)
        )

        let image = UIGraphicsImageRenderer(size: CGSize(width: 40, height: 30)).image { context in
            UIColor.blue.setFill()
            context.fill(CGRect(x: 0, y: 0, width: 40, height: 30))
        }
        XCTAssertEqual(square.crop(image, zoom: 1).size, CGSize(width: 30, height: 30))
    }

    func testCropRectPansAndClampsAtZoomOnNonSquareImage() {
        // 16:9 on 4000x3000 at zoom 2 is 2000x1125, centred at (1000, 937).
        let wide = MediaCropPolicy.requiredAspect("16:9")
        let size = CGSize(width: 4000, height: 3000)
        XCTAssertEqual(wide.cropRect(imageSize: size, zoom: 2), CGRect(x: 1000, y: 937, width: 2000, height: 1125))

        // A corner pan lands on the bottom-left corner, not the centre.
        XCTAssertEqual(
            wide.cropRect(imageSize: size, zoom: 2, offset: CGPoint(x: -5000, y: 5000)),
            CGRect(x: 0, y: 1875, width: 2000, height: 1125)
        )
        // Just inside and just past each edge.
        XCTAssertEqual(wide.cropRect(imageSize: size, zoom: 2, offset: CGPoint(x: -999, y: 937)).origin, CGPoint(x: 1, y: 1874))
        XCTAssertEqual(wide.cropRect(imageSize: size, zoom: 2, offset: CGPoint(x: -1000, y: 937.5)).origin, CGPoint(x: 0, y: 1875))
        XCTAssertEqual(wide.cropRect(imageSize: size, zoom: 2, offset: CGPoint(x: -1001, y: 938)).origin, CGPoint(x: 0, y: 1875))
        XCTAssertEqual(wide.cropRect(imageSize: size, zoom: 2, offset: CGPoint(x: 1001, y: -938)).origin, CGPoint(x: 2000, y: 0))
        XCTAssertEqual(
            wide.clampedOffset(CGPoint(x: -1001, y: 938), imageSize: size, zoom: 2),
            CGPoint(x: -1000, y: 937.5)
        )
        XCTAssertEqual(
            wide.clampedOffset(CGPoint(x: 999, y: -937), imageSize: size, zoom: 2),
            CGPoint(x: 999, y: -937)
        )
    }

    func testConfirmStoresThePannedRegion() throws {
        // Columns 0..<5 are red, the rest blue. A square crop is 30 wide, so
        // only a full pan left includes the red strip.
        let format = UIGraphicsImageRendererFormat()
        format.scale = 1
        let image = UIGraphicsImageRenderer(size: CGSize(width: 40, height: 30), format: format).image { context in
            UIColor.blue.setFill()
            context.fill(CGRect(x: 0, y: 0, width: 40, height: 30))
            UIColor.red.setFill()
            context.fill(CGRect(x: 0, y: 0, width: 5, height: 30))
        }
        let square = MediaCropPolicy.requiredAspect("1:1")

        let centred = square.crop(image, zoom: 1)
        let panned = square.crop(image, zoom: 1, offset: CGPoint(x: -100, y: 0))
        XCTAssertEqual(panned.size, CGSize(width: 30, height: 30))
        let centredPixel = try topLeftPixel(of: centred)
        let pannedPixel = try topLeftPixel(of: panned)
        XCTAssertTrue(centredPixel[2] > 200 && centredPixel[0] < 60, "centred crop starts in the blue area: \(centredPixel)")
        XCTAssertTrue(pannedPixel[0] > 200 && pannedPixel[2] < 60, "panned crop starts on the red strip: \(pannedPixel)")
    }

    func testPanStateClampsResetsAndMovesAccessibly() {
        let format = UIGraphicsImageRendererFormat()
        format.scale = 1
        let image = UIGraphicsImageRenderer(size: CGSize(width: 400, height: 300), format: format).image { _ in }
        var state = MediaRendererState(node: makeNode(aspect: "1:1"))
        state.beginCrop(with: image)

        // Zoom 2: a 150 square with 125 of slack each side horizontally, 75 vertically.
        state.setCropZoom(2)
        state.panCrop(by: CGPoint(x: 1000, y: -1000))
        XCTAssertEqual(state.cropOffset, CGPoint(x: 125, y: -75))

        state.moveCrop(.left)
        XCTAssertEqual(state.cropOffset, CGPoint(x: 110, y: -75))
        state.moveCrop(.down)
        XCTAssertEqual(state.cropOffset, CGPoint(x: 110, y: -60))

        // Back at zoom 1 the square has no vertical slack, so it re-centres
        // there and keeps the horizontal choice inside the 50 that remains.
        state.setCropZoom(1)
        XCTAssertEqual(state.cropOffset, CGPoint(x: 50, y: 0))
        state.setCropZoom(9)
        XCTAssertEqual(state.cropZoom, 4)

        state.beginCrop(with: image)
        XCTAssertEqual(state.cropOffset, .zero)
        XCTAssertEqual(state.cropZoom, 1)

        // Freeform at zoom 1 is the whole photo, so any pan stays centred.
        var freeform = MediaRendererState(node: makeNode(crop: "optional"))
        freeform.beginCrop(with: image)
        freeform.setCropZoom(2)
        freeform.panCrop(by: CGPoint(x: 80, y: 40))
        freeform.setCropZoom(1)
        XCTAssertEqual(freeform.cropOffset, .zero)
    }

    private func topLeftPixel(of image: UIImage) throws -> [UInt8] {
        let cgImage = try XCTUnwrap(image.cgImage)
        var pixel = [UInt8](repeating: 0, count: 4)
        let context = try XCTUnwrap(CGContext(
            data: &pixel, width: 1, height: 1, bitsPerComponent: 8, bytesPerRow: 4,
            space: CGColorSpaceCreateDeviceRGB(),
            bitmapInfo: CGImageAlphaInfo.premultipliedLast.rawValue
        ))
        context.draw(cgImage, in: CGRect(x: 0, y: 1 - CGFloat(cgImage.height), width: CGFloat(cgImage.width), height: CGFloat(cgImage.height)))
        return Array(pixel[0..<3])
    }

    func testEmptyAndDisabledFieldDoNotClear() {
        let empty = MediaRendererState(node: makeNode(hasValue: false))
        let disabled = MediaRendererState(node: makeNode(hasValue: true, disabled: true))

        XCTAssertNil(empty.clear())
        XCTAssertNil(disabled.clear())
    }

    func testClearAndChangeEvents() {
        let state = MediaRendererState(node: makeNode(hasValue: true))

        XCTAssertEqual(
            state.clear(),
            .clear(callbackId: 42, nodeId: 7)
        )
        XCTAssertEqual(
            state.commitChange(tempPath: "/tmp/photo.jpg"),
            .change(callbackId: 41, nodeId: 7, tempPath: "/tmp/photo.jpg")
        )
    }

    func testZoomControlsStayWithinBounds() {
        var state = MediaRendererState(node: makeNode())
        state.beginCrop(with: UIImage())

        state.zoomOut()
        XCTAssertEqual(state.cropZoom, 1)

        for _ in 0..<20 { state.zoomIn() }
        XCTAssertEqual(state.cropZoom, 4)

        for _ in 0..<20 { state.zoomOut() }
        XCTAssertEqual(state.cropZoom, 1)
    }

    func testCropSheetExposesZoomAndActionControls() {
        let view = FirstlightMediaCropSheet(
            image: UIImage(),
            policy: .optionalFreeform,
            zoom: 1,
            onConfirm: {},
            onCancel: {},
            onSkip: {},
            onZoomIn: {},
            onZoomOut: {}
        )

        assertFirstlightSnapshot(of: controller(view), as: .image(on: .iPhoneSe))
    }

    func testEmptyFieldSnapshot() {
        let view = FirstlightMediaControl(
            configuration: MediaRendererConfiguration(node: makeNode()),
            tokens: .fallback,
            onPick: {},
            onClear: {}
        )
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding()

        assertFirstlightSnapshot(of: controller(view), as: .image(on: .iPhoneSe))
    }

    func testValueErrorDisabledSnapshots() {
        let valued = FirstlightMediaControl(
            configuration: MediaRendererConfiguration(node: makeNode(
                hasValue: true,
                path: "avatars/a.jpg",
                error: "Choose a clearer photo."
            )),
            tokens: .fallback,
            onPick: {},
            onClear: {}
        )
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding()

        let disabled = FirstlightMediaControl(
            configuration: MediaRendererConfiguration(node: makeNode(disabled: true)),
            tokens: .fallback,
            onPick: {},
            onClear: {}
        )
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding()

        assertFirstlightSnapshot(of: controller(valued), as: .image(on: .iPhoneSe), named: "value-error")
        assertFirstlightSnapshot(of: controller(disabled), as: .image(on: .iPhoneSe), named: "disabled")
    }

    private func controller<Content: View>(_ view: Content) -> UIViewController {
        let controller = UIHostingController(rootView: view
            .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .topLeading)
            .background(Color(uiColor: .systemBackground)))
        controller.overrideUserInterfaceStyle = .light
        controller.traitOverrides.preferredContentSizeCategory = .large
        return controller
    }

    private func makeNode(
        mode: String = "image",
        aspect: String = "",
        crop: String = "",
        hasValue: Bool = false,
        path: String = "",
        mime: String = "",
        size: Int = 0,
        width: Int? = nil,
        height: Int? = nil,
        required: Bool = false,
        disabled: Bool = false,
        helper: String = "Helper copy",
        error: String = "",
        accessibilityLabel: String = "Profile photo",
        changeCallback: Int = 41,
        clearCallback: Int = 42,
        previewPath: String = ""
    ) -> NativeUINode {
        var props: [String: Any] = [
            "mode": mode,
            "label": "Profile photo",
            "helper": helper,
            "error": error,
            "required": required,
            "disabled": disabled,
            "disk": "mobile_public",
            "directory": "avatars",
            "aspect": aspect,
            "crop": crop,
            "has_value": hasValue,
            "path": path,
            "mime": mime,
            "size": size,
            "preview_path": previewPath,
            "a11y_label": accessibilityLabel,
            "a11y_hint": "Opens the media picker",
            "on_change": changeCallback,
            "on_clear": clearCallback,
        ]
        if let width { props["width"] = width }
        if let height { props["height"] = height }

        return NativeUINode(
            id: 7,
            type: "firstlight.media",
            layout: nil,
            style: nil,
            props: GenericProps(props),
            onPress: 0,
            onLongPress: 0,
            children: []
        )
    }
}
