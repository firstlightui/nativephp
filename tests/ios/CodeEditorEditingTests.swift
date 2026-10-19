import Foundation
import XCTest

@testable import FirstlightIOSControls

final class CodeEditorEditingTests: XCTestCase {
    func testEverySharedFixtureCaseProducesTheExpectedTextAndSelection() throws {
        let fixture = try JSONDecoder().decode(Fixture.self, from: Data(contentsOf: Self.fixtureURL))
        var failures: [String] = []

        for testCase in fixture.cases {
            let before = MarkedText.parse(testCase.before)
            let expected = MarkedText.parse(testCase.after)
            let actual = CodeEditorEditing.apply(
                text: before.text,
                selection: before.selection,
                operation: try Self.operation(testCase.operation),
                language: CodeEditorLanguage.from(testCase.language),
                indent: testCase.indent
            )
            if actual.text != expected.text || actual.selection != expected.selection {
                failures.append(
                    "\(testCase.name): expected \(testCase.after.debugDescription) " +
                        "got \(MarkedText.render(actual.text, actual.selection).debugDescription)"
                )
            }
        }

        XCTAssertFalse(fixture.cases.isEmpty)
        XCTAssertTrue(failures.isEmpty, "\(failures.count) failing case(s):\n" + failures.joined(separator: "\n"))
    }

    func testUnknownLanguageDecodesAsPlain() {
        XCTAssertEqual(CodeEditorLanguage.from("ruby"), .plain)
        XCTAssertEqual(CodeEditorLanguage.from(nil), .plain)
    }

    private static let fixtureURL = URL(fileURLWithPath: #filePath)
        .deletingLastPathComponent()
        .deletingLastPathComponent()
        .appendingPathComponent("fixtures/code-editor/editing.json")

    private static func operation(_ raw: String) throws -> CodeEditorOperation {
        switch raw {
        case "return": return .return
        case "indent": return .indent
        case "outdent": return .outdent
        case "toggle-comment": return .toggleComment
        case _ where raw.hasPrefix("insert:"): return .insert(String(raw.dropFirst("insert:".count)))
        default: throw FixtureError.unknownOperation(raw)
        }
    }
}

private enum FixtureError: Error {
    case unknownOperation(String)
}

private struct Fixture: Decodable {
    let cases: [Case]

    struct Case: Decodable {
        let name: String
        let language: String
        let indent: Int
        let operation: String
        let before: String
        let after: String
    }
}

private struct MarkedText {
    let text: String
    let selection: CodeEditorSelection

    private static let cursor = "‸"
    private static let start = "«"
    private static let end = "»"

    static func parse(_ marked: String) -> MarkedText {
        let source = marked as NSString
        let cursorRange = source.range(of: cursor)
        if cursorRange.location != NSNotFound {
            let location = cursorRange.location
            return MarkedText(
                text: source.replacingCharacters(in: cursorRange, with: ""),
                selection: CodeEditorSelection(start: location, end: location)
            )
        }
        let startLocation = source.range(of: start).location
        let endLocation = source.range(of: end).location - 1
        let text = marked.replacingOccurrences(of: start, with: "").replacingOccurrences(of: end, with: "")
        return MarkedText(text: text, selection: CodeEditorSelection(start: startLocation, end: endLocation))
    }

    static func render(_ text: String, _ selection: CodeEditorSelection) -> String {
        let source = text as NSString
        let head = source.substring(to: selection.start)
        let tail = source.substring(from: selection.end)
        if selection.collapsed {
            return head + cursor + tail
        }
        return head + start + source.substring(with: selection.range) + end + tail
    }
}
