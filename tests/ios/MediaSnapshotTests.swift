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
            zoom: .constant(1),
            onConfirm: {},
            onCancel: {},
            onSkip: {},
            onZoomIn: {},
            onZoomOut: {}
        )

        let controller = UIHostingController(rootView: view.frame(width: 390, height: 720))
        assertSnapshot(of: controller, as: .image(on: .iPhoneSe))
    }

    func testEmptyFieldSnapshot() {
        let view = FirstlightMediaControl(
            configuration: MediaRendererConfiguration(node: makeNode()),
            tokens: .fallback,
            onPick: {},
            onClear: {}
        )
        .frame(width: 360)
        .padding()

        let controller = UIHostingController(rootView: view)
        assertSnapshot(of: controller, as: .image(on: .iPhoneSe))
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
        .frame(width: 360)
        .padding()

        let disabled = FirstlightMediaControl(
            configuration: MediaRendererConfiguration(node: makeNode(disabled: true)),
            tokens: .fallback,
            onPick: {},
            onClear: {}
        )
        .frame(width: 360)
        .padding()

        assertSnapshot(of: UIHostingController(rootView: valued), as: .image(on: .iPhoneSe), named: "value-error")
        assertSnapshot(of: UIHostingController(rootView: disabled), as: .image(on: .iPhoneSe), named: "disabled")
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
        clearCallback: Int = 42
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
            "preview_url": "",
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
