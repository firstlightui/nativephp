import Foundation

/// Formats one line of text against a Text Field `mask` pattern.
///
/// `#` accepts a digit, `A` a letter and `*` either; every other pattern character is a literal.
/// The raw value holds only slot characters. Literals are displayed once the slot after them is
/// filled, and trailing literals once every slot is filled, so an empty field shows its placeholder.
struct FirstlightTextMask: Equatable {
    private enum Token: Equatable {
        case slot(Character)
        case literal(Character)
    }

    private let tokens: [Token]
    /// Pattern index of every slot, in order.
    private let slotPositions: [Int]

    var slotCount: Int { slotPositions.count }

    init?(pattern: String) {
        let tokens = pattern.map { character -> Token in
            "#A*".contains(character) ? .slot(character) : .literal(character)
        }
        let positions = tokens.indices.filter { if case .slot = tokens[$0] { true } else { false } }
        guard !positions.isEmpty else { return nil }
        self.tokens = tokens
        slotPositions = positions
    }

    /// The displayed text for an already conformed raw value.
    func format(_ raw: String) -> String {
        let characters = Array(raw)
        var output = ""
        var slot = 0
        for token in tokens {
            switch token {
            case let .literal(literal):
                guard slot < characters.count || characters.count == slotCount else { return output }
                output.append(literal)
            case .slot:
                guard slot < characters.count else { return output }
                output.append(characters[slot])
                slot += 1
            }
        }
        return output
    }

    /// Normalises an authored, server or pasted value to raw slot characters, dropping characters
    /// that fit no slot and anything beyond the last slot.
    func conform(_ value: String) -> String {
        String(fill(Array(value), fromSlot: 0, capacity: slotCount))
    }

    /// Display offset for a caret placed after `rawOffset` raw characters.
    func displayOffset(forRaw rawOffset: Int, rawCount: Int) -> Int {
        if rawOffset >= rawCount, rawCount >= slotCount { return tokens.count }
        let offset = min(rawOffset, rawCount)
        return offset <= 0 ? 0 : slotPositions[offset - 1] + 1
    }

    /// Number of raw characters displayed before `displayOffset`.
    func rawOffset(forDisplay displayOffset: Int, rawCount: Int) -> Int {
        min(rawCount, slotPositions.filter { $0 < displayOffset }.count)
    }

    /// Replaces a raw range with typed or pasted text. Characters that fit no slot are dropped and
    /// insertion stops when every slot is full. Returns the new raw value and raw caret.
    func replace(raw: String, range: Range<Int>, with inserted: String) -> (raw: String, caret: Int) {
        let characters = Array(raw)
        let lower = max(0, min(range.lowerBound, characters.count))
        let upper = max(lower, min(range.upperBound, characters.count))
        let prefix = Array(characters[..<lower])
        let suffix = Array(characters[upper...])
        let accepted = fill(Array(inserted), fromSlot: lower, capacity: slotCount - prefix.count - suffix.count)
        var result = prefix + accepted
        for character in suffix where result.count < slotCount {
            if let normalized = accept(character, slot: result.count) { result.append(normalized) }
        }
        return (String(result), lower + accepted.count)
    }

    /// Applies a native replacement of `displayRange` in the formatted text with `inserted`, as
    /// UIKit reports it. Returns the new raw value and the display caret.
    func edit(raw: String, displayRange: Range<Int>, inserted: String) -> (raw: String, caret: Int) {
        let rawCount = raw.count
        var lower = rawOffset(forDisplay: displayRange.lowerBound, rawCount: rawCount)
        let upper = rawOffset(forDisplay: displayRange.upperBound, rawCount: rawCount)
        // Deleting only literals deletes the slot character before them.
        if inserted.isEmpty, !displayRange.isEmpty, lower == upper {
            guard lower > 0 else { return (raw, displayOffset(forRaw: 0, rawCount: rawCount)) }
            lower -= 1
        }
        let result = replace(raw: raw, range: lower..<upper, with: inserted)
        return (result.raw, displayOffset(forRaw: result.caret, rawCount: result.raw.count))
    }

    private func fill(_ text: [Character], fromSlot: Int, capacity: Int) -> [Character] {
        guard capacity > 0, fromSlot < slotCount else { return [] }
        // Text that already fits the slots is taken verbatim, so raw values and typed characters
        // that equal a literal still fill slots.
        var verbatim: [Character] = []
        for character in text.prefix(capacity) {
            guard let normalized = accept(character, slot: fromSlot + verbatim.count) else { break }
            verbatim.append(normalized)
        }
        if verbatim.count == min(text.count, capacity) { return verbatim }

        // Otherwise treat the text as formatted: matching literals are consumed, then each
        // remaining character fills the next slot that accepts it.
        var output: [Character] = []
        var position = fromSlot == 0 ? 0 : slotPositions[fromSlot - 1] + 1
        for character in text {
            guard output.count < capacity, position < tokens.count else { break }
            var next = position
            var consumedLiteral = false
            while next < tokens.count, case let .literal(literal) = tokens[next] {
                if literal == character {
                    position = next + 1
                    consumedLiteral = true
                    break
                }
                next += 1
            }
            if consumedLiteral || next >= tokens.count { continue }
            if let normalized = accept(character, slot: fromSlot + output.count) {
                output.append(normalized)
                position = next + 1
            }
        }
        return output
    }

    private func accept(_ character: Character, slot: Int) -> Character? {
        guard slot < slotCount, case let .slot(kind) = tokens[slotPositions[slot]] else { return nil }
        let digit = Self.asciiDigit(character)
        let letter = character.isASCII && character.isLetter ? character : nil
        switch kind {
        case "#": return digit
        case "A": return letter
        default: return digit ?? letter
        }
    }

    /// Any Unicode decimal digit is stored as its ASCII equivalent.
    private static func asciiDigit(_ character: Character) -> Character? {
        guard character.unicodeScalars.count == 1,
              let scalar = character.unicodeScalars.first,
              scalar.properties.numericType == .decimal,
              let value = character.wholeNumberValue
        else { return nil }
        return Character(String(value))
    }
}
