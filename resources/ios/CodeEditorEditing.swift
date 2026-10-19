import Foundation

enum CodeEditorLanguage: String {
    case plain
    case json
    case yaml
    case env

    var hasLineComments: Bool { self == .yaml || self == .env }

    static func from(_ raw: String?) -> CodeEditorLanguage {
        raw.flatMap(CodeEditorLanguage.init(rawValue:)) ?? .plain
    }
}

/// UTF-16 selection; `start` is never greater than `end`.
struct CodeEditorSelection: Equatable {
    let start: Int
    let end: Int

    init(start: Int, end: Int) {
        self.start = start
        self.end = end
    }

    init(_ range: NSRange) {
        self.init(start: range.location, end: range.location + range.length)
    }

    var collapsed: Bool { start == end }
    var range: NSRange { NSRange(location: start, length: end - start) }
}

struct CodeEditorEdit: Equatable {
    let text: String
    let selection: CodeEditorSelection
}

enum CodeEditorOperation: Equatable {
    case `return`
    case indent
    case outdent
    case toggleComment
    case insert(String)
}

/// Pure editing rules shared with Android through tests/fixtures/code-editor/editing.json.
/// Do not add behaviour here that the fixtures do not cover.
enum CodeEditorEditing {
    static func apply(
        text: String,
        selection: CodeEditorSelection,
        operation: CodeEditorOperation,
        language: CodeEditorLanguage,
        indent: Int
    ) -> CodeEditorEdit {
        let units = Array(text.utf16)
        let unit = [UInt16](repeating: space, count: indent)
        let edit: UnitEdit
        switch operation {
        case .return:
            edit = newline(units, selection, language, unit)
        case .indent:
            edit = applyEdits(
                units,
                selection,
                touchedLineStarts(units, selection)
                    .filter { lineEnd(units, $0) > $0 }
                    .map { Splice(position: $0, deleteLength: 0, insertion: unit) }
            )
        case .outdent:
            edit = applyEdits(
                units,
                selection,
                touchedLineStarts(units, selection).compactMap { start in
                    let removable = removableIndent(units, start, indent)
                    return removable == 0 ? nil : Splice(position: start, deleteLength: removable, insertion: [])
                }
            )
        case .toggleComment:
            edit = toggleComment(units, selection, language)
        case .insert(let symbol):
            edit = insert(units, selection, Array(symbol.utf16), language, indent)
        }
        return CodeEditorEdit(text: String(decoding: edit.units, as: UTF16.self), selection: edit.selection)
    }

    private typealias Units = [UInt16]

    private struct UnitEdit {
        let units: Units
        let selection: CodeEditorSelection
    }

    private struct Splice {
        let position: Int
        let deleteLength: Int
        let insertion: Units
    }

    private static let newlineUnit = UInt16(UInt8(ascii: "\n"))
    private static let space = UInt16(UInt8(ascii: " "))
    private static let tab = UInt16(UInt8(ascii: "\t"))
    private static let hash = UInt16(UInt8(ascii: "#"))
    private static let colon = UInt16(UInt8(ascii: ":"))
    private static let doubleQuote = UInt16(UInt8(ascii: "\""))
    private static let singleQuote = UInt16(UInt8(ascii: "'"))
    private static let backslash = UInt16(UInt8(ascii: "\\"))
    private static let openBrace = UInt16(UInt8(ascii: "{"))
    private static let closeBrace = UInt16(UInt8(ascii: "}"))
    private static let openBracket = UInt16(UInt8(ascii: "["))
    private static let closeBracket = UInt16(UInt8(ascii: "]"))

    private static func newline(
        _ text: Units,
        _ selection: CodeEditorSelection,
        _ language: CodeEditorLanguage,
        _ unit: Units
    ) -> UnitEdit {
        let prefix = Units(text[lineStart(text, selection.start)..<selection.start])
        let carried = Units(prefix.prefix { $0 == space || $0 == tab })
        let opener: UInt16? = switch language {
        case .json: jsonOpenerAtEnd(prefix)
        default: nil
        }
        let increases = opener != nil ||
            (language == .yaml && trimmingTrailingWhitespace(yamlWithoutComment(prefix)).last == colon)
        let inner = [newlineUnit] + carried + (increases ? unit : [])
        let closer: UInt16? = switch opener {
        case openBrace: closeBrace
        case openBracket: closeBracket
        default: nil
        }
        let splits = closer != nil && selection.end < text.count && text[selection.end] == closer
        let insertion = splits ? inner + [newlineUnit] + carried : inner
        let cursor = selection.start + inner.count
        return UnitEdit(
            units: Units(text[..<selection.start]) + insertion + Units(text[selection.end...]),
            selection: CodeEditorSelection(start: cursor, end: cursor)
        )
    }

    private static func insert(
        _ text: Units,
        _ selection: CodeEditorSelection,
        _ symbol: Units,
        _ language: CodeEditorLanguage,
        _ indent: Int
    ) -> UnitEdit {
        let start = lineStart(text, selection.start)
        let prefix = Units(text[start..<selection.start])
        let removed = if language == .json && (symbol == [closeBrace] || symbol == [closeBracket]) &&
            !prefix.isEmpty && prefix.allSatisfy({ $0 == space || $0 == tab })
        {
            removableIndent(text, start, indent)
        } else {
            0
        }
        let result = Units(text[..<start]) + Units(prefix[removed...]) + symbol + Units(text[selection.end...])
        let cursor = selection.start - removed + symbol.count
        return UnitEdit(units: result, selection: CodeEditorSelection(start: cursor, end: cursor))
    }

    private static func toggleComment(
        _ text: Units,
        _ selection: CodeEditorSelection,
        _ language: CodeEditorLanguage
    ) -> UnitEdit {
        guard language.hasLineComments else { return UnitEdit(units: text, selection: selection) }
        let lines = touchedLineStarts(text, selection).filter { start in
            !text[start..<lineEnd(text, start)].allSatisfy(isWhitespace)
        }
        guard !lines.isEmpty else { return UnitEdit(units: text, selection: selection) }
        let leading = lines.map { leadingWhitespaceLength(text, $0) }
        let allCommented = lines.indices.allSatisfy { text[lines[$0] + leading[$0]] == hash }
        let splices: [Splice]
        if allCommented {
            splices = lines.indices.map { index in
                let hashOffset = lines[index] + leading[index]
                let following = hashOffset + 1 < text.count && text[hashOffset + 1] == space ? 1 : 0
                return Splice(position: hashOffset, deleteLength: 1 + following, insertion: [])
            }
        } else {
            let column = leading.min()!
            splices = lines.map { Splice(position: $0 + column, deleteLength: 0, insertion: [hash, space]) }
        }
        return applyEdits(text, selection, splices)
    }

    /// Applies ascending, non-overlapping splices and maps the selection through them.
    private static func applyEdits(_ text: Units, _ selection: CodeEditorSelection, _ splices: [Splice]) -> UnitEdit {
        guard !splices.isEmpty else { return UnitEdit(units: text, selection: selection) }
        var builder = Units()
        builder.reserveCapacity(text.count)
        var cursor = 0
        for splice in splices {
            builder += text[cursor..<splice.position]
            builder += splice.insertion
            cursor = splice.position + splice.deleteLength
        }
        builder += text[cursor...]
        let collapsed = selection.collapsed
        let start = mapOffset(selection.start, splices, movesWithInsertionAtOffset: collapsed)
        let end = collapsed ? start : mapOffset(selection.end, splices, movesWithInsertionAtOffset: true)
        return UnitEdit(units: builder, selection: CodeEditorSelection(start: start, end: end))
    }

    private static func mapOffset(_ offset: Int, _ splices: [Splice], movesWithInsertionAtOffset: Bool) -> Int {
        var shift = 0
        for splice in splices {
            let removalEnd = splice.position + splice.deleteLength
            if splice.deleteLength > 0 && offset >= removalEnd {
                shift -= splice.deleteLength
            } else if splice.deleteLength > 0 && offset > splice.position {
                shift -= offset - splice.position
            } else if !splice.insertion.isEmpty &&
                (splice.position < offset || (splice.position == offset && movesWithInsertionAtOffset))
            {
                shift += splice.insertion.count
            }
        }
        return offset + shift
    }

    private static func touchedLineStarts(_ text: Units, _ selection: CodeEditorSelection) -> [Int] {
        let last = !selection.collapsed && selection.end == lineStart(text, selection.end)
            ? selection.end - 1
            : selection.end
        var starts = [lineStart(text, selection.start)]
        while let next = text[starts.last!...].firstIndex(of: newlineUnit), next < last {
            starts.append(next + 1)
        }
        return starts
    }

    private static func removableIndent(_ text: Units, _ lineStart: Int, _ indent: Int) -> Int {
        if lineStart < text.count && text[lineStart] == tab { return 1 }
        var count = 0
        while count < indent && lineStart + count < text.count && text[lineStart + count] == space { count += 1 }
        return count
    }

    private static func leadingWhitespaceLength(_ text: Units, _ lineStart: Int) -> Int {
        var index = lineStart
        while index < text.count && (text[index] == space || text[index] == tab) { index += 1 }
        return index - lineStart
    }

    private static func lineStart(_ text: Units, _ offset: Int) -> Int {
        offset == 0 ? 0 : (text[..<offset].lastIndex(of: newlineUnit).map { $0 + 1 } ?? 0)
    }

    private static func lineEnd(_ text: Units, _ offset: Int) -> Int {
        text[offset...].firstIndex(of: newlineUnit) ?? text.count
    }

    private static func isWhitespace(_ unit: UInt16) -> Bool {
        Unicode.Scalar(unit)?.properties.isWhitespace ?? false
    }

    private static func trimmingTrailingWhitespace(_ units: Units) -> Units {
        var end = units.count
        while end > 0 && isWhitespace(units[end - 1]) { end -= 1 }
        return Units(units[..<end])
    }

    /// Returns `{` or `[` when it is the last significant character outside a string.
    private static func jsonOpenerAtEnd(_ prefix: Units) -> UInt16? {
        var inString = false
        var escaped = false
        var last: UInt16?
        for character in prefix {
            if inString && escaped {
                escaped = false
            } else if inString && character == backslash {
                escaped = true
            } else if inString && character == doubleQuote {
                inString = false
                last = doubleQuote
            } else if inString {
                continue
            } else if character == doubleQuote {
                inString = true
            } else if !isWhitespace(character) {
                last = character
            }
        }
        return !inString && (last == openBrace || last == openBracket) ? last : nil
    }

    private static func yamlWithoutComment(_ prefix: Units) -> Units {
        var quote: UInt16?
        var escaped = false
        for (index, character) in prefix.enumerated() {
            if quote == doubleQuote && escaped {
                escaped = false
            } else if quote == doubleQuote && character == backslash {
                escaped = true
            } else if quote != nil && character == quote {
                quote = nil
            } else if quote != nil {
                continue
            } else if character == doubleQuote || character == singleQuote {
                quote = character
            } else if character == hash && (index == 0 || prefix[index - 1] == space || prefix[index - 1] == tab) {
                return Units(prefix[..<index])
            }
        }
        return prefix
    }
}
