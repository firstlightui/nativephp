import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class ButtonGhostTests: XCTestCase {
    func testAllocatedWidthsReachTheButtonLabelRatherThanOnlyAnOuterFrame() {
        // The host owns allocation, just as NodeLayoutModifier/FlexContainer do.
        // Measuring only the outer view would miss the intrinsic painted button.
        // In SwiftPM, non-ghost variants use the explicit width-contract double
        // in NativePHPTestShims, not production Mobile UI styling/dispatch.
        for variant in ["primary", "secondary", "destructive", "success", "ghost"] {
            for layout in [
                NodeLayout.empty.applying(["flex_grow": 1]),
                NodeLayout.empty.applying(["width": "fill"]),
                NodeLayout.empty.applying(["width": 220]),
                NodeLayout.empty.applying(["width": "60%"]),
            ] {
                let node = makeNode(variant: variant, layout: layout)
                let width: CGFloat = 220
                let geometry = hostedButtonGeometry(
                    FirstlightButtonRenderer(node: node).frame(width: width),
                    width: width
                )
                XCTAssertEqual(geometry.frame.width, width, accuracy: 2, "\(variant), \(layout)")
                if variant == "ghost" {
                    XCTAssertGreaterThanOrEqual(geometry.frame.height, 44)
                    let control = UIHostingController(rootView: FirstlightGhostButtonControl(
                        configuration: ButtonGhostConfiguration(node: firstlightButtonLabelNode(node)),
                        tokens: .fallback,
                        onPress: {}
                    ))
                    let fitted = control.sizeThatFits(in: CGSize(width: width, height: 160))
                    XCTAssertEqual(
                        fitted.width, width, accuracy: 1,
                        "the ghost label/target itself must expand, not just the renderer's outer frame"
                    )
                } else {
                    XCTAssertEqual(
                        geometry.paintedWidth, width, accuracy: 2,
                        "an allocated outer frame is insufficient: \(variant), \(layout)"
                    )
                }
            }
        }
    }

    func testIntrinsicButtonsDoNotBecomeFullWidth() {
        for variant in ["primary", "ghost"] {
            for layout in [nil, NodeLayout.empty, NodeLayout.empty.applying(["max_width": 280])] {
                let geometry = hostedButtonGeometry(
                    FirstlightButtonRenderer(node: makeNode(variant: variant, layout: layout))
                        .frame(width: 360),
                    width: 360
                )
                XCTAssertGreaterThan(geometry.frame.width, 0)
                XCTAssertLessThan(geometry.frame.width, 340, variant)
                XCTAssertLessThan(geometry.paintedWidth, 340, variant)
            }
        }
    }

    private func hostedButtonGeometry<Content: View>(
        _ view: Content, width: CGFloat
    ) -> (frame: CGRect, paintedWidth: CGFloat) {
        AccessibilityRuntime.enable()
        let host = UIHostingController(rootView: view.tint(Color(uiColor: .systemTeal)))
        host.overrideUserInterfaceStyle = .light
        host.view.backgroundColor = .white
        let scene = UIApplication.shared.connectedScenes.compactMap { $0 as? UIWindowScene }.first
        let window = scene.map(UIWindow.init(windowScene:)) ?? UIWindow()
        window.frame = CGRect(x: 0, y: 0, width: width, height: 160)
        window.rootViewController = host
        window.makeKeyAndVisible()
        host.view.setNeedsLayout()
        host.view.layoutIfNeeded()
        RunLoop.current.run(until: Date().addingTimeInterval(0.05))
        defer {
            window.isHidden = true
            window.rootViewController = nil
        }

        let buttons = accessibilityButtons(in: host.view)
        XCTAssertEqual(buttons.count, 1, "the rendered button must expose exactly one native action")
        let format = UIGraphicsImageRendererFormat()
        format.scale = 1
        format.opaque = true
        let image = UIGraphicsImageRenderer(bounds: host.view.bounds, format: format).image { context in
            UIColor.white.setFill()
            context.fill(host.view.bounds)
            host.view.layer.render(in: context.cgContext)
        }
        if coloredSpan(in: image) == 0 {
            let attachment = XCTAttachment(image: image)
            attachment.name = "button-zero-colored-span"
            attachment.lifetime = .keepAlways
            add(attachment)
        }
        return (buttons.first?.accessibilityFrame ?? .zero, coloredSpan(in: image))
    }

    /// The fixture has a white background and a tinted native button. Measure
    /// actual coloured pixels, not an accessibility/outer frame that may fill
    /// even while the button's chrome remains intrinsic.
    private func coloredSpan(in image: UIImage) -> CGFloat {
        guard let image = image.cgImage else {
            XCTFail("host did not render an image")
            return 0
        }
        let width = image.width
        let height = image.height
        var pixels = [UInt8](repeating: 0, count: width * height * 4)
        let rendered = pixels.withUnsafeMutableBytes { bytes -> Bool in
            guard let context = CGContext(
                data: bytes.baseAddress, width: width, height: height,
                bitsPerComponent: 8, bytesPerRow: width * 4,
                space: CGColorSpaceCreateDeviceRGB(),
                bitmapInfo: CGImageAlphaInfo.premultipliedLast.rawValue | CGBitmapInfo.byteOrder32Big.rawValue
            ) else { return false }
            context.draw(image, in: CGRect(x: 0, y: 0, width: CGFloat(width), height: CGFloat(height)))
            return true
        }
        XCTAssertTrue(rendered)
        var first = width
        var last = -1
        for y in 0..<height {
            for x in 0..<width {
                let offset = (y * width + x) * 4
                let channels = pixels[offset...offset + 2].map(Int.init)
                if (channels.max() ?? 0) - (channels.min() ?? 0) > 40 {
                    first = min(first, x)
                    last = max(last, x)
                }
            }
        }
        return last >= first ? CGFloat(last - first + 1) : 0
    }

    private func accessibilityButtons(in object: NSObject) -> [NSObject] {
        if object.isAccessibilityElement {
            return object.accessibilityTraits.contains(.button) ? [object] : []
        }
        if let elements = object.accessibilityElements, !elements.isEmpty {
            return elements.compactMap { $0 as? NSObject }.flatMap(accessibilityButtons)
        }
        let count = object.accessibilityElementCount()
        if count != NSNotFound, count > 0 {
            return (0..<count).compactMap { object.accessibilityElement(at: $0) as? NSObject }
                .flatMap(accessibilityButtons)
        }
        return (object as? UIView)?.subviews.flatMap(accessibilityButtons) ?? []
    }

    func testGhostKeepsA44PointTargetAtEverySize() {
        for size in ["sm", "md", "lg"] {
            let host = UIHostingController(rootView: FirstlightGhostButtonControl(
                configuration: ButtonGhostConfiguration(node: makeNode(size: size)),
                tokens: .fallback,
                onPress: {}
            ))
            let fitted = host.sizeThatFits(in: CGSize(width: 390, height: CGFloat.greatestFiniteMagnitude))

            // Mobile UI's plain ghost measured about 19 pt tall on iOS 26.
            XCTAssertGreaterThanOrEqual(fitted.height, FirstlightGhostButtonControl.minimumTarget, size)
        }
    }

    func testConfigurationDecodesTheActionContract() {
        let configuration = ButtonGhostConfiguration(node: makeNode())

        XCTAssertEqual(configuration.nodeID, 12)
        XCTAssertEqual(configuration.label, "settings recipe")
        XCTAssertEqual(configuration.trailingIcon, "chevron.right")
        XCTAssertEqual(configuration.accessibilityHint, "Opens the bundled article")
        XCTAssertEqual(configuration.callbackID, 52)
        XCTAssertTrue(configuration.isEnabled)
        XCTAssertFalse(ButtonGhostConfiguration(node: makeNode(disabled: true)).isEnabled)
        XCTAssertFalse(ButtonGhostConfiguration(node: makeNode(loading: true)).isEnabled)
    }

    private func makeNode(
        size: String = "md", disabled: Bool = false, loading: Bool = false,
        variant: String = "ghost", layout: NodeLayout? = nil
    ) -> NativeUINode {
        NativeUINode(
            id: 12,
            type: "firstlight.button",
            layout: layout,
            style: nil,
            props: GenericProps([
                "label": "settings recipe",
                "variant": variant,
                "size": size,
                "trailing_icon": "chevron.right",
                "disabled": disabled,
                "loading": loading,
                "a11y_hint": "Opens the bundled article",
            ]),
            onPress: 52,
            onLongPress: 0,
            children: []
        )
    }
}
