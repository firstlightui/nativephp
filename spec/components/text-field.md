---
title: Text Field component contract
description: Public API, focused single-line editing, input masks, icons, accessibility, and renderer decision for Firstlight Text Field.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-text-field-design.md
  - spec/designs/2026-10-25-firstlight-text-field-masks-design.md
  - spec/reference/icons.md
  - docs/components/text-field.md
  - CHANGELOG.md
  - src/Components/TextField.php
  - src/Elements/TextField.php
  - src/Support/Chrome.php
  - src/Support/WarningColors.php
  - src/Validation/FieldErrorBinder.php
  - resources/ios/TextFieldRenderer.swift
  - resources/ios/TextFieldControl.swift
  - resources/ios/FirstlightTextMask.swift
  - resources/ios/FirstlightFocusedField.swift
  - resources/android/TextFieldRenderer.kt
  - resources/android/TextFieldControl.kt
  - resources/android/TextFieldMask.kt
  - resources/android/FirstlightInputFocus.kt
  - tests/Feature/TextFieldElementTest.php
  - tests/Feature/TextInputAccessibilityWiringTest.php
  - vendor/nativephp/mobile-ui/src/Elements/BaseTextInput.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIOutlinedTextInputRenderer.swift
---

# Text Field Component Contract

## Purpose and state class

`<firstlight:text-field>` enters and edits one line of plain text. It is a
focused-text control: native code owns the editing buffer, focus, cursor,
selection, and marked-text or IME composition while editing. PHP owns the
published string and field metadata.

Text Field is not a multiline editor, search control, rich-text editor,
picker, or validation engine. Multiline entry belongs to Text Area and native
search with an invariant clear affordance belongs to Search Field.

## Public API

```blade
<firstlight:text-field
    label="Email"
    placeholder="you@example.com"
    helper="Used for appointment updates."
    keyboard="email"
    content-type="email"
    autocapitalize="none"
    :autocorrect="false"
    clearable
    native:model.blur="email"
/>
```

| Prop or event | Contract |
| --- | --- |
| `value` / `native:model` | Strict string; omission defaults to `''`. |
| `label` | Visible field label and accessibility-name fallback. |
| `placeholder` | Short empty-state example; never replaces a label. |
| `helper` | Supporting guidance below the field. |
| `helper-tone` | `neutral` (default), `success`, or `warning`. Colours the helper only; `error` always replaces it. |
| `error` | Validation feedback that replaces helper text. |
| `required` | Required metadata shown as `label *`; performs no validation. |
| `disabled` | Prevents focus, editing, clearing, and trailing actions. |
| `read-only` | Keeps the native field for selection and copy while rejecting edits and clear. |
| `keyboard` | `text`, `email`, `phone`, `url`, `number`, or `decimal`. |
| `content-type` | `name`, `username`, `email`, `password`, `new-password`, or `one-time-code`. |
| `secure` | Masks display without changing the stored string. |
| `mask` | Display pattern: `#` digit, `A` ASCII letter, `*` either, anything else a literal. `mask=""` removes the mask. |
| `autocapitalize` | `none`, `sentences`, `words`, or `characters`; omission keeps platform policy. |
| `autocorrect` | Explicitly enables or disables platform autocorrection. |
| `submit-label` | `done`, `go`, `next`, `search`, or `send`; omission keeps the platform default. |
| `leading-icon`, `leading-icon-ios`, `leading-icon-android` | Decorative leading icon under the [shared icon contract](../reference/icons.md). |
| `trailing-icon`, `trailing-icon-ios`, `trailing-icon-android` | Decorative trailing icon, or an authored action with `trailing-a11y-label` and `@press`. |
| `trailing-a11y-label` | Accessible name of the authored trailing action. |
| `clearable` | Native clear action while editable text is non-empty. |
| `revealable` | Native show/hide password action; requires `secure`. |
| `a11y-label` | Explicit accessible name when no visible label is appropriate. |
| `a11y-hint` | Additional VoiceOver or TalkBack guidance. |
| `@change` | Receives the current string according to the sync policy. |
| `@submit` | Flushes any pending change, then receives the current string. |
| `@press` | Authored trailing action; receives no text argument. |
| `class` | External EDGE layout for the complete field; padding is removed from the published layout. |

`readOnly`, `helperTone`, `contentType`, `submitLabel`, `trailingA11yLabel`,
`syncMode`, `debounceMs`, and the camelCase icon aliases (`leadingIcon`,
`leadingIconIos`, `trailingIconAndroid`, and so on) are accepted by the PHP
element API. The fluent `leadingIcon()` and `trailingIcon()` methods take
`(?string $name, IosSymbol|string|null $ios, AndroidSymbol|string|null $android)`.

Text Field has no multiline, min/max lines, loading, prefix, suffix, size,
font, tone, variant, colour, shape, keep-focus-on-submit, or programmatic
focus API.

## Published contract

The element type is `firstlight.text-field`. Props are `value`, `label`,
`placeholder`, `helper`, `error`, `required`, `disabled`, `read_only`,
`secure`, `sync_mode` (`live`, `blur`, or `debounce`; `lazy` normalises to
`blur`), `debounce_ms` (default `300`), `clearable`, and `revealable`, plus,
only when authored, `helper_tone`, `mask`, `keyboard`, `content_type`,
`autocapitalize`, `autocorrect` with `autocorrect_policy`, `submit_label`,
resolved `leading_icon` / `trailing_icon` with optional `*_icon_variant`,
`trailing_a11y_label`, `a11y_label`, and `a11y_hint`. A `warning` helper tone
also publishes the Firstlight warning colour pair.

Every publication carries package chrome from `Chrome::string()`:
`done_label`, `clear_a11y_label`, `show_password_a11y_label`, and
`hide_password_a11y_label`. Registered `on_change` and `on_submit` callbacks
are props; an authored trailing action uses the standard element `on_press`.
Component style is empty.

## Editing and synchronisation

Plain `native:model` and `.live` publish while editing. `.blur` and `.lazy`
publish on focus loss or submit. `.debounce.500ms` publishes after the quiet
period and flushes on focus loss or submit; durations below 50 milliseconds
fail before publication. Change events use `TEXT_CHANGE`, submit uses
`SUBMIT`, and the trailing action uses `PRESS`.

Each renderer keeps a draft and the last committed string per Element Tree
node:

1. A publication equal to the last committed string while focused is an
   acknowledgement and never touches the native buffer, cursor, selection, or
   composition.
2. A different publication while focused updates metadata immediately and is
   held as pending. On focus loss it replaces the draft without emitting,
   unless the person edited since the last commit, in which case the edit is
   published and the pending value is discarded.
3. Publications received while unfocused replace the displayed value without
   emitting.

`clearable` empties the buffer, retains focus, and publishes `''` immediately
through `@change` even under blur or debounce. It is unavailable while
disabled or read-only. `revealable` toggles presentation only and never
publishes the string.

Both platforms register the focused field with Mobile's `KeyboardFocusPolicy`
so a Firstlight Button or Icon Button press first posts the pending blur or
debounce change. On iOS, `number`, `decimal`, and `phone` keyboards add a
package-localised Done accessory that flushes, emits `@submit`, and dismisses
the keyboard, including for masked input. iOS focus also scrolls the field
through the enclosing Mobile UI scroll proxy after keyboard presentation.

## Input masks

A mask must contain at least one slot. PHP publishes the pattern unchanged and
never alters `value`, `keyboard`, capitalization, or autocorrection.

Renderers format the draft for display and keep only slot characters in the
draft, so PHP, `@change`, and `@submit` receive raw slot characters under every
sync policy. Literals appear only once the slot after them is filled, so an
empty field shows its placeholder. Characters that fit no slot are dropped and
input stops when every slot is filled; a rejected keystroke publishes
nothing. Paste accepts raw or formatted text. Deleting only a literal deletes
the slot character before it. Digit slots accept any Unicode decimal digit and
store ASCII; letter slots accept `a`–`z` and `A`–`Z` and keep their case.

A published value that does not fit is displayed conformed without an event;
the conformed value is published only with the next edit. Focused
acknowledgement compares conformed values. Assistive technology reads the
formatted on-screen text.

## Validation and accessibility

Textual attributes, enum attributes, and icon fallbacks are strict strings;
platform icon overrides accept their symbol enum or a string; `debounce-ms`
must be an integer. The element fails before publication for an unsupported
enum value, a debounce below 50 ms, a mask without a slot, `mask` with
`secure`, `revealable` without `secure`, `clearable` with `revealable`, a
semantic affordance combined with any authored trailing icon, label, or
`@press`, `@press` without `trailing-icon` or `trailing-a11y-label`, or
`trailing-a11y-label` without `@press`.

`FieldErrorBinder` fills `error` from the current MessageBag for the
`native:model` or `error-for` name unless the author supplied a non-empty
`error`. A blank `label` and blank `a11y-label` trigger a development warning
outside production.

The editable input takes `a11y-label`, otherwise `label`, as its name in plain,
secure, and masked presentation; `a11y-hint` remains guidance. Error text
replaces helper text and is exposed as an error without replacing the name or
value. Helper and error colours fall back to black or white when the preferred
colour is below 4.5:1 against the field surface, so tone is never the only
cue. Decorative icons are hidden. Clear, reveal, and authored trailing actions
are separate native buttons with at least a 44-point iOS or 48-dp Android
target and their own accessible names.

## Platform expression

- iOS composes a SwiftUI `TextField` or `SecureField` in an Apple field: label
  above, `surface-variant` rounded container at least 44 points tall,
  destructive outline on error, supporting text below, and 0.6 opacity while
  disabled. A masked field uses a hosted `UITextField` so each edit's range can
  be formatted and the caret placed in one step. Icons draw through
  `NativeIconView`; clear and reveal use the system `xmark.circle.fill`, `eye`,
  and `eye.slash` symbols.
- Android uses Material 3 `OutlinedTextField` with `singleLine = true`,
  `TextFieldValue` selection and composition, Material label, placeholder,
  supporting and error slots, `KeyboardOptions` for keyboard, capitalization,
  autocorrection, and IME action, Compose autofill `contentType`, a mask
  `VisualTransformation`, and `MaterialIcon` for authored and semantic icons.

## Renderer decision

Mobile UI's text inputs provide native editing, sync policies, and secure
entry, but the iOS outlined renderer reproduces Material-style field chrome
rather than an Apple field, and the general primitive exposes multiline,
loading, prefix, suffix, size, and other capabilities outside this contract
while lacking Firstlight `required`, helper tone, masks, and the
clear/reveal/trailing-action slot rules. Firstlight therefore uses paired
renderers through the official SuperNative element and NativePHP's existing
text-change, submit, and press transports.

## Evidence boundary

Pest proves strict strings and enums, defaults, helper tones, masks and their
conflicts, sync policies and debounce bounds, callback registration, icon
fallback, overrides, variants, and aliases, trailing-slot validation, package
chrome labels, real Blade compilation, and web no-op compilation. A
source-wiring test keeps the iOS name and hint on every input variant and the
Android name on the editable node.

XCTest proves prop decoding, acknowledgement and pending correction,
blur/debounce/submit ordering, clear and reveal events, disabled and read-only
rejection, mask formatting, paste, deletion, caret placement, numeric Done,
44-point masked targets, focused-press flushing, and snapshots. Kotlin tests
prove the same state transitions, mask editing through the Material field,
the composer layout, and Paparazzi states. Showcase and device evidence
remain separate integration and release requirements.
