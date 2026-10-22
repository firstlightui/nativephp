import SwiftUI
import UIKit
import XCTest

@testable import FirstlightIOSControls

@MainActor
final class MetricSnapshotTests: XCTestCase {
    func testRendererPreservesFormattedValuesAndAccessibleOverride() {
        let configuration = MetricRendererConfiguration(node: NativeUINode(
            id: 7, type: "firstlight.metric", layout: nil, style: nil,
            props: GenericProps(["label": "Response", "value": "1,234.50 ms",
                "detail": "last check", "a11y_label": "Server response: 1,234.50 milliseconds"]),
            onPress: 0, onLongPress: 0, children: []
        ))
        XCTAssertEqual(configuration.value, "1,234.50 ms")
        XCTAssertEqual(configuration.detail, "last check")
        XCTAssertEqual(configuration.accessibilityLabel, "Server response: 1,234.50 milliseconds")
    }

    func testLargestTextGrowsToAccommodateLongValueRatherThanTruncating() throws {
        func height(value: String, size: DynamicTypeSize) throws -> CGFloat {
            let image = ImageRenderer(content: MetricControl(
                label: "Response", value: value, detail: "last check",
                foreground: .black, valueColor: .blue,
                accessibilityLabel: "Response: \(value), last check", accessibilityHint: ""
            ).frame(width: 180).environment(\.dynamicTypeSize, size))
            return try XCTUnwrap(image.uiImage).size.height
        }
        let regular = try height(value: "1,234.50 ms", size: .large)
        let accessible = try height(value: "1,234.50 ms", size: .accessibility5)
        let longer = try height(value: "123,456,789.50 milliseconds", size: .accessibility5)
        XCTAssertGreaterThan(accessible, regular)
        XCTAssertGreaterThan(longer, accessible)
    }
}
