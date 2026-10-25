import XCTest

@testable import FirstlightIOSControls

final class TextFieldMaskTests: XCTestCase {
    private let card = FirstlightTextMask(pattern: "#### #### #### ####")!
    private let phone = FirstlightTextMask(pattern: "(###) ###-####")!
    private let postcode = FirstlightTextMask(pattern: "A#A #A#")!

    func testPatternWithoutSlotsIsNotAMask() {
        XCTAssertNil(FirstlightTextMask(pattern: ""))
        XCTAssertNil(FirstlightTextMask(pattern: "(---)"))
        XCTAssertEqual(card.slotCount, 16)
    }

    func testFormatShowsLiteralsOnlyBeforeFilledSlots() {
        XCTAssertEqual(card.format(""), "")
        XCTAssertEqual(card.format("4111"), "4111")
        XCTAssertEqual(card.format("41111"), "4111 1")
        XCTAssertEqual(phone.format("4"), "(4")
        XCTAssertEqual(phone.format("415555"), "(415) 555")
        XCTAssertEqual(phone.format("4155550123"), "(415) 555-0123")
        XCTAssertEqual(FirstlightTextMask(pattern: "##%")!.format("12"), "12%")
    }

    func testConformAcceptsRawAndFormattedValuesAndDropsWhatDoesNotFit() {
        XCTAssertEqual(card.conform("4111111111111111"), "4111111111111111")
        XCTAssertEqual(card.conform("4111 1111-1111 1111 9999"), "4111111111111111")
        XCTAssertEqual(phone.conform("(415) 555-0123"), "4155550123")
        XCTAssertEqual(postcode.conform("k1a 0b1"), "k1a0b1")
        XCTAssertEqual(postcode.conform("1KA"), "K", "a digit cannot fill the leading letter slot")
        XCTAssertEqual(FirstlightTextMask(pattern: "**-**")!.conform("a1-b2"), "a1b2")
        XCTAssertEqual(card.conform("٤١١١"), "4111", "Unicode decimal digits are stored as ASCII")
    }

    func testRawValueThatStartsWithLiteralCharactersIsNotTreatedAsFormatted() {
        let international = FirstlightTextMask(pattern: "+61 ### ###")!
        XCTAssertEqual(international.conform("614123"), "614123")
        XCTAssertEqual(international.conform("+61 412 345"), "412345")
    }

    func testTypingInsertsLiteralsAndPlacesTheCaretAfterTheTypedCharacter() {
        let typed = card.edit(raw: "4111", displayRange: 4..<4, inserted: "2")
        XCTAssertEqual(typed.raw, "41112")
        XCTAssertEqual(card.format(typed.raw), "4111 2")
        XCTAssertEqual(typed.caret, 6)

        // Caret after "41" in "4111 2", type 9.
        let middle = card.edit(raw: "41112", displayRange: 2..<2, inserted: "9")
        XCTAssertEqual(middle.raw, "419112")
        XCTAssertEqual(card.format(middle.raw), "4191 12")
        XCTAssertEqual(middle.caret, 3)
    }

    func testInsertingInsideARunOfRepeatedCharactersKeepsTheCaretThere() {
        let typed = card.edit(raw: "4111", displayRange: 2..<2, inserted: "1")
        XCTAssertEqual(typed.raw, "41111")
        XCTAssertEqual(typed.caret, 3)
    }

    func testRejectedCharactersAndFullMasksLeaveTheValueUnchanged() {
        let letter = card.edit(raw: "4111", displayRange: 4..<4, inserted: "x")
        XCTAssertEqual(letter.raw, "4111")
        XCTAssertEqual(letter.caret, 4)

        let full = "4111111111111111"
        let overflow = card.edit(raw: full, displayRange: 19..<19, inserted: "2")
        XCTAssertEqual(overflow.raw, full)
        XCTAssertEqual(overflow.caret, 19)

        let letterInDigitSlot = postcode.edit(raw: "K", displayRange: 1..<1, inserted: "A")
        XCTAssertEqual(letterInDigitSlot.raw, "K")
    }

    func testBackspaceAcrossALiteralDeletesThePrecedingSlotCharacter() {
        // Caret after the space in "4111 2": backspace removes the space, so the 1 before it goes.
        let deleted = card.edit(raw: "41112", displayRange: 4..<5, inserted: "")
        XCTAssertEqual(deleted.raw, "41112".replacingCharacters(at: 3))
        XCTAssertEqual(card.format(deleted.raw), "4112")
        XCTAssertEqual(deleted.caret, 3)

        let trailing = card.edit(raw: "41112", displayRange: 5..<6, inserted: "")
        XCTAssertEqual(trailing.raw, "4111")
        XCTAssertEqual(trailing.caret, 4)

        let leading = phone.edit(raw: "4", displayRange: 0..<1, inserted: "")
        XCTAssertEqual(leading.raw, "4", "deleting a leading literal has no slot to remove")
    }

    func testPasteOfFormattedTextAndReplacingASelection() {
        let pasted = phone.edit(raw: "", displayRange: 0..<0, inserted: "(415) 555-0123")
        XCTAssertEqual(pasted.raw, "4155550123")
        XCTAssertEqual(pasted.caret, 14)

        let partial = card.edit(raw: "", displayRange: 0..<0, inserted: "4111-1111-1111-1111-2222")
        XCTAssertEqual(partial.raw, "4111111111111111")

        // Select "1 2" in "4111 2" and type 9.
        let replaced = card.edit(raw: "41112", displayRange: 3..<6, inserted: "9")
        XCTAssertEqual(replaced.raw, "4119")
        XCTAssertEqual(replaced.caret, 4)
    }

    func testInsertionShiftsTheSuffixIntoSlotsThatStillAcceptIt() {
        let result = postcode.replace(raw: "K1A", range: 0..<0, with: "M")
        XCTAssertEqual(result.raw, "M1A", "K no longer fits the shifted digit slot and is dropped")
        XCTAssertEqual(result.caret, 1)

        let digits = card.replace(raw: "4111", range: 1..<1, with: "99")
        XCTAssertEqual(digits.raw, "499111")
        XCTAssertEqual(digits.caret, 3)
    }
}

private extension String {
    func replacingCharacters(at offset: Int) -> String {
        var characters = Array(self)
        characters.remove(at: offset)
        return String(characters)
    }
}
