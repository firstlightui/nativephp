---
title: Firstlight Text Field input masks addendum
description: Approved pattern-mask contract for Text Field, superseding the formatting-mask non-goal in the Text Field design.
status: approved
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-text-field-design.md
  - spec/reference/catalogue-boundary.md
  - src/Elements/TextField.php
  - resources/ios/FirstlightTextMask.swift
  - resources/ios/TextFieldRenderer.swift
  - resources/android/TextFieldMask.kt
  - resources/android/TextFieldRenderer.kt
  - tests/Feature/TextFieldElementTest.php
  - tests/ios/TextFieldMaskTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/TextFieldMaskTest.kt
  - https://developer.apple.com/documentation/uikit/uitextfielddelegate/textfield(_:shouldchangecharactersin:replacementstring:)
  - https://developer.android.com/reference/kotlin/androidx/compose/ui/text/input/VisualTransformation
---

# Firstlight Text Field Input Masks

Date: 2026-10-25

Status: approved by the maintainer for 1.0. This addendum supersedes the
"formatting masks" entry in the non-goals and the "masked formatter" exclusion
in the purpose of the [Text Field design](2026-08-04-firstlight-text-field-design.md).
Every other part of that design still applies.

## Decision

Text Field accepts one display-only `mask` pattern:

```blade
<firstlight:text-field
    label="Card number"
    mask="#### #### #### ####"
    keyboard="number"
    native:model="cardNumber"
/>
```

- `#` is a digit, `A` is a letter, and `*` is a letter or digit. These three
  characters are always slots; there is no escape syntax.
- Every other pattern character is a literal that the field inserts while the
  person types.
- The bound `native:model` value, `@change`, and `@submit` receive only the
  characters that fill slots, for example `4111111111111111`. The mask
  changes the display only.
- There are no presets. Phone numbers and postcodes are not universal, so
  authors write the pattern for their market.
- The mask never changes the keyboard, autocapitalization, or autocorrection.
  Authors set `keyboard="number"`, `autocapitalize="characters"`, and
  `:autocorrect="false"` themselves where they fit.

A digit slot accepts any Unicode decimal digit and stores its ASCII
equivalent, so a locale keyboard with other digits still produces `0`–`9`. A
letter slot accepts ASCII letters `a`–`z` and `A`–`Z` and keeps their case.
Masks are for structured codes such as card numbers, postcodes, and
reference numbers, not free text.

`mask=""` means no mask, so a conditional binding can turn the mask off. A
non-empty pattern without any slot, or a non-string value, fails with a
development exception.

This stays inside the [catalogue boundary](../reference/catalogue-boundary.md):
it adds no tag, no parallel formatted value, no presets, and no client-side
validation. The PHP value is still the one Laravel validates.

## Display and editing

Literals are displayed only once the slot after them is filled. Trailing
literals are displayed once every slot is filled. An empty field therefore
shows its placeholder rather than a leading literal such as `(`, and the
caret never sits after a literal that is waiting for input.

Native editing stays on the native UI thread:

- **Typing** inserts the character into the next slot and inserts any
  literals before it. The caret stays after the typed character.
- **Rejected characters**, such as a letter in a digit slot, are dropped
  without an event.
- **A full mask** ignores further input. When several characters are
  inserted at once, those that fit the remaining slots are kept and the rest
  are dropped.
- **Paste** keeps text that already fits the slots as it is. Otherwise the
  pasted text is treated as formatted: characters that match the pattern's
  literals are consumed, and each remaining character fills the next slot
  that accepts it. Pasting `(415) 555-0123` into `(###) ###-####` and
  pasting `4155550123` both produce `4155550123`.
- **Deleting a literal on its own**, such as backspacing just after a space,
  deletes the slot character before it. Deleting a leading literal has no
  slot to remove and changes nothing.
- **Inserting into or deleting from the middle** shifts the following
  characters into the next slots. With mixed slot kinds, a shifted character
  that no longer fits its new slot is dropped.

On iOS a masked field is a `UITextField` hosted in the same Apple field
composition, because SwiftUI `TextField` reports edits only after applying
them: rapid typing on the simulator lost keystrokes when the renderer
reformatted afterwards. The delegate's `shouldChangeCharactersIn` receives
the exact replaced range before UIKit applies it, so the renderer applies the
edit to the raw value and writes back the formatted text and caret in one
step. Marked text is composed natively and conformed when committed. Unmasked
fields keep SwiftUI `TextField` and `SecureField` unchanged. On Android the Compose `TextFieldValue` holds the raw
value and a `VisualTransformation` with an offset mapping displays it
formatted. The IME edits the raw buffer and the renderer filters each edit.
Both platforms share the same algorithm, which XCTest and JVM tests cover
case for case.

## Combinations

| Combination | Decision | Reason |
| --- | --- | --- |
| `mask` + `secure` | Rejected with a development exception. | Secure entry hides characters, so literals would reveal only the structure of a secret, and secure fields expose no selection control. |
| `mask` + `revealable` | Rejected, because `revealable` requires `secure`. | As above. |
| `mask` + `clearable` | Supported. | Clearing empties the raw value and publishes `''` immediately, as without a mask. |
| `mask` + authored trailing icon or `@press` | Supported. | The trailing slot is independent of formatting. |
| `mask` + `read-only` or `disabled` | Supported. | The formatted value is displayed. Edits are refused as without a mask. |
| `mask` + `keyboard`, `content-type`, `autocapitalize`, `autocorrect` | Independent. | The mask never changes input hints. |

## Values that do not fit

Authored and server values are data, not authoring errors, so a value that
does not fit the pattern does not throw. The renderer conforms it with the
same rules as paste: a raw value is kept, a formatted value such as
`4111 1111 1111 1111` loses its literals, and characters that fit no slot or
exceed the last slot are dropped. The field displays the conformed value.

Conforming is not a user change. No event is sent, and PHP keeps the original
value until the person's next edit publishes the conformed raw value.
Acknowledgement and pending-correction comparisons while focused use conformed
values, so PHP can echo the value raw or formatted without disturbing the
draft.

If a publication changes the pattern while the field is focused, the draft is
conformed to the new pattern.

## Synchronisation

`.live`, `.blur`, `.lazy`, and `.debounce` keep their meaning and always
publish the raw value. A keystroke that is rejected, or that only causes a
literal to be displayed, does not change the raw value. It publishes nothing
and does not restart the debounce timer.

## Accessibility

VoiceOver and TalkBack announce the field's text as displayed, formatted,
for example "4111 1111 1111 1111". That is the native text value on both
platforms, and it matches what sighted users see. The accessible name and
hint are unchanged, and the pattern itself is not announced. Authors describe
the expected format with `placeholder`, `helper`, or `a11y-hint`.

## Evidence

- Pest contract tests for pattern publication, the empty pattern, the
  unchanged keyboard, the non-string and slotless pattern errors, the `secure`
  and `revealable` rejection, and `clearable` with blur sync through the Blade
  pipeline.
- XCTest and JVM tests for formatting, conforming, offset mapping, typing,
  rejected characters, full masks, repeated characters, paste,
  selection replacement, literal deletion, and suffix shifting. The tests
  also cover renderer state: raw publication, blur, conformed server values
  without events, focused acknowledgements, and read-only refusal.
- A Robolectric test that types through the production Material field and
  checks the formatted `EditableText` semantics that TalkBack reads, and an
  XCTest that drives the production `UITextField` delegate for typing,
  literal deletion, mid-text insertion, paste and a full mask.
- Real typing in the showcase Text Field page on an iOS simulator and an
  Android emulator.

Physical-device evidence remains a release gate, as for the rest of Text
Field.
