import Foundation
import XCTest

@testable import FirstlightIOSControls

final class CodeEditorScrollAnchorTests: XCTestCase {
    func testEverySharedFixtureCaseMapsTheAnchorLine() throws {
        let fixture = try JSONDecoder().decode(Fixture.self, from: Data(contentsOf: Self.fixtureURL))
        var failures: [String] = []

        for testCase in fixture.cases {
            let actual = CodeEditorScrollAnchor.anchorLine(
                before: testCase.before,
                after: testCase.after,
                anchorLine: testCase.anchorLine
            )
            if actual != testCase.expectedLine {
                failures.append(
                    "\(testCase.name): expected \(testCase.expectedLine.map(String.init) ?? "null") " +
                        "got \(actual.map(String.init) ?? "null")"
                )
            }
        }

        XCTAssertFalse(fixture.cases.isEmpty)
        XCTAssertTrue(failures.isEmpty, "\(failures.count) failing case(s):\n" + failures.joined(separator: "\n"))
    }

    func testAnchorOutsideTheOldTextFallsBack() {
        XCTAssertNil(CodeEditorScrollAnchor.anchorLine(before: "a\nb", after: "a\nb", anchorLine: 5))
    }

    private static let fixtureURL = URL(fileURLWithPath: #filePath)
        .deletingLastPathComponent()
        .deletingLastPathComponent()
        .appendingPathComponent("fixtures/code-editor/scroll-anchor.json")
}

private struct Fixture: Decodable {
    let cases: [Case]

    struct Case: Decodable {
        let name: String
        let before: String
        let after: String
        let anchorLine: Int
        let expectedLine: Int?

        enum CodingKeys: String, CodingKey {
            case name, before, after
            case anchorLine = "anchor_line"
            case expectedLine = "expected_line"
        }
    }
}
