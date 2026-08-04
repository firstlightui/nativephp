---
title: Firstlight Text Field design
description: Proposed public contract, focused editing model, platform expression, and evidence boundary for Firstlight Text Field.
status: approved
sources:
  - Constitution.md
  - spec/reference/icons.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - nativephp.json
  - vendor/nativephp/mobile-ui/src/Elements/BaseTextInput.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUITextInputCore.swift
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIOutlinedTextInputRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/TextInputShared.kt
  - vendor/nativephp/mobile-ui/resources/android/OutlinedTextInputRenderer.kt
  - https://nativephp.com/docs/mobile/4/edge-components/text-input
  - https://developer.apple.com/documentation/swiftui/textfield
  - https://developer.android.com/develop/ui/compose/text/user-input
---

# Firstlight Text Field

Date: 2026-08-04

Status: approved for implementation

## Purpose

`<firstlight:text-field>` enters and edits one line of text. It provides a
native editing buffer, semantic keyboard and autofill hints, familiar field
metadata, and standard NativePHP change and submit events.

Text Field is not a multiline editor, search control, rich-text editor,
masked formatter, picker, or validation engine. Multiline entry belongs to
`text-area`; native search and clear behaviour belong to `search-field`.

## Public API

```blade
<firstlight:text-field
    label="Email"
    placeholder="you@example.com"
    helper="We use this for appointment updates."
    keyboard="email"
    content-type="email"
    native:model.blur="email"
/>
```

The public tag accepts:

| API | Accepted type | Behaviour |
| --- | --- | --- |
| `value` / `native:model` | `string` | Published PHP value; omitted value defaults to an empty string. |
| `label` | `string` | Visible field label and accessibility-label fallback. |
| `placeholder` | `string` | Short input example or empty-state prompt; never a label substitute. |
| `helper` | `string` | Supporting guidance beneath the field. |
| `error` | `string` | Visible and accessible validation feedback; replaces helper while present. |
| `required` | `bool` | Communicates required state visually and accessibly; it does not validate or submit a form. |
| `disabled` | `bool` | Prevents focus, editing, change, and submit events. |
| `read-only` | `bool` | Allows focus, selection, and copy while preventing edits and events. |
| `keyboard` | enum string | `text`, `email`, `phone`, `url`, `number`, or `decimal`. |
| `content-type` | enum string | `name`, `username`, `email`, `password`, `new-password`, or `one-time-code`. |
| `secure` | `bool` | Masks entered text without changing its stored string value. |
| `autocapitalize` | enum string | `none`, `sentences`, `words`, or `characters`; omitted uses platform policy. |
| `autocorrect` | `bool` | Explicitly enables or disables platform autocorrection; omitted uses platform policy. |
| `submit-label` | enum string | `done`, `go`, `next`, `search`, or `send`; omitted uses the platform default. |
| `leading-icon` | `string` | Optional shared fallback icon before the editable text. |
| `leading-icon-ios` | `IosSymbol|string` | Optional iOS-specific leading icon override. |
| `leading-icon-android` | `AndroidSymbol|string` | Optional Android-specific leading icon override, including its Material variant. |
| `trailing-icon` | `string` | Optional shared fallback icon after the editable text; decorative unless a trailing action is configured. |
| `trailing-icon-ios` | `IosSymbol|string` | Optional iOS-specific trailing icon override. |
| `trailing-icon-android` | `AndroidSymbol|string` | Optional Android-specific trailing icon override, including its Material variant. |
| `trailing-a11y-label` | `string` | Accessible name for an interactive trailing icon; required with `@press`. |
| `clearable` | `bool` | Adds a platform-native clear action while the value is non-empty; the action owns its icon and localized accessible label. |
| `revealable` | `bool` | Adds a platform-native show/hide action for a `secure` field; the action owns its icon and localized accessible state. |
| `a11y-label` | `string` | Explicit accessible name when a visible label is inappropriate. |
| `a11y-hint` | `string` | Supplementary VoiceOver and TalkBack guidance. |
| `class` | `string` | External EDGE layout for the complete field only. |

`@change` receives the current string when the selected sync policy commits.
`@submit` receives the current string after any pending change is flushed.
When a trailing icon, `trailing-a11y-label`, and `@press` are supplied
together, `@press` handles that separate trailing action and receives no text
argument.

`clearable` clears the native editing buffer, retains focus, and immediately
publishes the empty string through standard `@change` so PHP and the visible
field cannot diverge behind a blur or debounce policy. It is unavailable while
disabled or read-only. `revealable` toggles only local presentation; it
never changes or republishes the string. `revealable` requires `secure`.
`clearable` and `revealable` are mutually exclusive, and neither may be
combined with authored trailing-icon or trailing-action attributes because
only one semantic affordance may own the trailing slot.

Plain `native:model` and `native:model.live` publish on every edit.
`native:model.blur` and its `lazy` alias publish on focus loss or submission.
`native:model.debounce.500ms` publishes after the requested quiet period and
flushes on focus loss or submission. Debounce durations below 50 milliseconds
are rejected rather than silently clamped.

Text Field intentionally has no multiline, min/max-lines, loading, prefix,
suffix, size, font, tone, variant, arbitrary colour, shape, or
keep-focus-on-submit API. Those capabilities either belong to another alpha
component or lack a proven cross-platform semantic requirement.

Leading and trailing icons use NativePHP's shared platform icon resolution
contract from Mobile UI PR #29. Blade documentation uses kebab-case
`leading-icon`, `leading-icon-ios`, `leading-icon-android`, `trailing-icon`,
`trailing-icon-ios`, and `trailing-icon-android`; camelCase aliases are
accepted internally for parity with Mobile UI. The shared name is the fallback
when the active platform is unknown, a platform override wins when present,
and an Android symbol's filled or outlined Material variant is preserved.

The fluent API follows the same argument order and types as Mobile UI:

```php
->leadingIcon(?string $name = null, IosSymbol|string|null $ios = null, AndroidSymbol|string|null $android = null)
->trailingIcon(?string $name = null, IosSymbol|string|null $ios = null, AndroidSymbol|string|null $android = null)
```

The leading icon is decorative. The authored trailing icon is also decorative unless
`trailing-a11y-label` and standard `@press` are present; that complete
combination creates a separate native button and accessibility element. An
incomplete action combination fails with actionable guidance. Firstlight does
not infer clear, password-reveal, scan, or picker behaviour from an authored
icon name: the application handles the press and publishes any resulting
field state. The explicit `clearable` and `revealable` extensions instead own
their platform-native icon, localized label, and state internally as required
by `spec/reference/icons.md`; they do not alter consumer icon naming.

## Validation and failure behaviour

The accepted value is an actual PHP string. Omitted `value` defaults to `''`.
Authored `null`, booleans, numbers, arrays, objects, invalid enum values, and
invalid debounce durations fail with actionable development exceptions.

`required` is metadata, not client-side validation. Error text replaces
helper text, uses the host semantic destructive/error colour, and is announced
without replacing the field's accessible name or current value. A visible
label or explicit `a11y-label` is mandatory in development. Placeholder-only
fields fail that review.

`secure` and `content-type` are independent: a password content hint does not
silently mask text, and masking does not invent an autofill purpose. This
keeps security-sensitive behaviour explicit in authored markup.

## NativePHP primitive audit

NativePHP Mobile UI 0.3.0 supplies outlined, filled, and bare text inputs. Its
outlined input already provides a native editing buffer, live/blur/debounce
dispatch, change and submit callbacks, secure entry, keyboard types, disabled
and read-only state, supporting text, error styling, and basic accessibility.

It is not an adequate adapter for the proposed Firstlight contract because:

- the iOS outlined renderer deliberately composes Material-style outlined
  chrome instead of expressing the field with ordinary Apple presentation;
- neither renderer exposes cross-platform content/autofill hints,
  capitalization policy, autocorrection policy, or semantic submit labels;
- `required` and Firstlight's distinct helper/error strings are absent; and
- the broad upstream API exposes multiline, styling, decoration, loading, and
  typography controls that Firstlight intentionally assigns elsewhere or
  excludes.

Firstlight therefore adds an ordinary `firstlight.text-field` EDGE element
and paired renderers. The renderers may reuse small upstream parsing or event
patterns, but they retain Firstlight's narrower contract and do not add a
bridge or parallel state system.

## Focused state and event flow

The element publishes the latest PHP string, field metadata, semantic input
hints, resolved platform icons, accessibility attributes, sync policy, and
registered `on_change`, `on_submit`, and optional authored trailing `on_press`
callbacks through the standard Element Tree.

Each renderer owns a native editing buffer while the field is focused:

1. On first render, the editing buffer receives the published PHP value.
2. Native typing, selection, marked-text composition, cursor movement, and
   keyboard behaviour update the local buffer without waiting for PHP.
3. The sync policy decides when the buffer emits a semantic change event.
4. A publication equal to the last emitted draft is an acknowledgement and
   must not reset selection, composition, focus, or keyboard position.
5. A different publication received while focused is retained as pending
   server state; field metadata and errors update immediately, but the active
   draft is not destroyed mid-edit.
6. Focus loss or submission flushes pending local changes, then the next PHP
   publication becomes authoritative for the unfocused field.
7. Publications received while unfocused update the displayed value without
   emitting events.

IME marked text is never truncated or dispatched as a synthetic submit.
Programmatic publications do not emit change events. Renderer identity is
keyed by the Element Tree node ID so sibling fields never share drafts,
timers, focus, or acknowledgement state.

## Platform expression

### iOS

The iOS renderer uses SwiftUI `TextField` or `SecureField` with native focus,
selection, keyboard, autofill, submit, and Dynamic Type behaviour. A compact
Apple field composition presents the visible label, required state, field,
optional platform-resolved icons, and helper or error text without copying
Material floating-label geometry. System rounded-border treatment may be used
where the host context calls for field chrome; semantic theme colours apply to
accent and error state.

An interactive trailing icon is a genuine SwiftUI `Button` with its own
accessible label and minimum 44-point target. It does not replace the field's
accessible name, alter the string implicitly, or masquerade as text entry.
Clear and reveal extensions use the same native button composition with
platform-standard symbols and localized `Clear text`, `Show password`, and
`Hide password` accessibility state. Reveal preserves focus and selection.

The renderer maps content purpose to `textContentType`, keyboard intent to
`keyboardType`, capitalization and correction through their SwiftUI
modifiers, and the action through `submitLabel` and `onSubmit`.

### Android

The Android renderer uses Material 3 `OutlinedTextField` with `singleLine =
true`, a local text-editing state that preserves selection and composition,
Material label/supporting/error slots, and semantic theme colours.

It maps keyboard, capitalization, correction, and IME action through
`KeyboardOptions`; content purpose uses current Compose autofill/content-type
semantics. `KeyboardActions` flushes the current draft before submit.

An interactive trailing icon uses the Material text field's trailing slot
with a genuine `IconButton`, a distinct TalkBack node, and a minimum 48-dp
target. Disabled fields disable both editing and the trailing action;
read-only fields may retain the action for picker and scanner workflows.
Clear and reveal extensions use the same Material slot with standard Material
symbols, localized semantics, focus retention, and no synthetic value event
when only password visibility changes.

Both platforms retain native focus indication, cursor and selection handles,
software and hardware keyboard behaviour, automatic right-to-left layout,
screen-reader editing semantics, and a baseline interaction target of at
least 44 points on iOS and 48 dp on Android.

### Native appearance acceptance gate

Behavioural parity does not permit shared visual geometry. The iOS field must
look and behave like an Apple field: Apple typography, spacing, focus,
selection, keyboard, autofill, submit behaviour, and system field treatment.
It must not reproduce Material's floating outline, indicator geometry, state
layers, or label motion. The Android field must look and behave like a
Material 3 field: Material typography, outlined container, floating label,
supporting/error slot, state layers, cursor, selection, and IME behaviour. It
must not reproduce an iOS form row or rounded-border convention.

Native appearance is reviewed independently on both platforms in light and
dark mode, increased contrast, the largest supported accessibility text size,
right-to-left layout, focused and unfocused states, empty and populated
values, disabled and read-only states, helper and error states, secure entry,
and software-keyboard interaction. A visually generic custom control, a
cross-platform skin, clipping, non-native focus motion, or imitation of the
other platform blocks completion even when automated tests pass.

## Internal identity

The PHP Blade component and EDGE element are named
`FirstlightUI\Components\TextField` and
`FirstlightUI\Elements\TextField`. The public type and tag are
`firstlight.text-field` and `<firstlight:text-field>`.

Renderer identifiers are derived from the current Firstlight plugin manifest
convention:

- iOS: `TextFieldRenderer`
- Android: `dev.firstlightui.plugins.firstlight_ui.ui.TextFieldRenderer`

## Testing and evidence

Implementation follows strict red-green-refactor cycles. Evidence includes:

- Pest 5 tests for public tag compilation, strict strings, defaults, complete
  field metadata, enum validation, sync policy, debounce validation, callback
  registration, unsupported props, diagnostics, and web no-op compilation;
- iOS tests for editing-buffer acknowledgement, focused programmatic updates,
  blur/debounce flush, submit ordering, secure entry, semantic hints,
  decorative icons, trailing press, clear/reveal behaviour and targeting, composition-safe
  reconciliation, accessibility, and representative snapshots;
- Android tests for the same transitions, selection/composition preservation,
  autofill and IME semantics, decorative, authored-action, clear, and reveal icon semantics,
  TalkBack error state, 48-dp targeting, font scale `2.0`, and Paparazzi states;
- exhaustive `firstlightui/showcase` fixtures for empty, populated, helper,
  error, required, disabled, read-only, secure, semantic keyboard/content
  hints, decorative icons, interactive trailing actions, clearable and
  revealable fields, conflicts, long labels,
  live/blur/debounce, submit, rejection, programmatic updates, right-to-left
  layout, and accessibility;
- focused and full package tests, both platform suites, exact-commit showcase
  tests, plugin validation, and iOS and Android showcase host builds; and
- simulator plus manual VoiceOver/TalkBack evidence during development, with
  a separate dated physical-device pass before component release.

The constitutional review includes a side-by-side visual inspection against
current first-party Apple and Material text-field behaviour. Screenshot
similarity between iOS and Android is not a goal; platform authenticity is.

A passing Text Field review establishes component-development readiness only;
it does not satisfy the complete alpha catalogue gate.

## Non-goals

Text Field does not expose multiline editing, search semantics, arbitrary
built-in trailing behaviours beyond clear/reveal, formatting masks, arbitrary
decorations, custom typography, platform escape hatches, programmatic focus
control, optimistic server ownership, or form validation. Those require their
own semantic contracts and evidence.
