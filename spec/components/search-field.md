---
title: Search Field component contract
description: Public API, focused query synchronisation, native search and clear affordances, accessibility, and renderer decision for Firstlight Search Field.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-search-field-design.md
  - docs/components/search-field.md
  - CHANGELOG.md
  - src/Components/SearchField.php
  - src/Elements/SearchField.php
  - src/Support/Chrome.php
  - resources/ios/SearchFieldRenderer.swift
  - resources/ios/SearchFieldControl.swift
  - resources/ios/FirstlightFocusedField.swift
  - resources/android/SearchFieldRenderer.kt
  - resources/android/SearchFieldControl.kt
  - resources/android/FirstlightInputFocus.kt
  - tests/Feature/SearchFieldElementTest.php
  - tests/Feature/TextInputAccessibilityWiringTest.php
  - vendor/nativephp/mobile-ui/src/Elements/BaseTextInput.php
---

# Search Field Component Contract

## Purpose and state class

`<firstlight:search-field>` enters and submits one query. It is a
focused-text control: native code owns the editing buffer, focus, cursor,
selection, and marked-text or IME composition while editing. PHP owns the
published query.

Search Field is not a general form field. It has no visible label, helper,
error, or validation metadata; a labelled single-line form entry belongs to
Text Field.

## Public API

```blade
<firstlight:search-field
    placeholder="Search referrals"
    a11y-label="Search referrals"
    a11y-hint="Enter a patient, provider, or specialty"
    autocapitalize="words"
    :autocorrect="false"
    native:model.debounce.300ms="query"
    @submit="search"
/>
```

| Prop or event | Contract |
| --- | --- |
| `value` / `native:model` | Strict string; omission defaults to `''`. |
| `placeholder` | Empty-query prompt; never the accessible name. |
| `disabled` | Prevents editing, clearing, change, and submit. |
| `autocapitalize` | `none`, `sentences`, `words`, or `characters`. |
| `autocorrect` | Explicitly enables or disables platform autocorrection. |
| `a11y-label` | Required, non-empty accessible name. |
| `a11y-hint` | Additional VoiceOver or TalkBack guidance. |
| `@change` | Receives the current query according to the sync policy. |
| `@submit` | Flushes any pending change, then receives the query, including an empty one. |
| `class` | External EDGE layout for the complete field; padding is removed from the published layout. |

`syncMode` and `debounceMs` camel-case aliases are accepted by the PHP element
API.

The search glyph and clear action are semantic native affordances, not icon
slots. `label`, `helper`, `error`, `required`, `read-only` / `readOnly`,
`clearable`, `secure`, `revealable`, `keyboard`, `content-type` /
`contentType`, `submit-label` / `submitLabel`, `leading-icon`, and
`trailing-icon` fail with actionable guidance; `clearable` explains that the
field owns its clear action.

## Published contract

The element type is `firstlight.search-field`. Props are `value`,
`placeholder`, `disabled`, `sync_mode` (`live`, `blur`, or `debounce`; `lazy`
normalises to `blur`), `debounce_ms` (default `300`), `a11y_label`, and,
when authored, `autocapitalize`, `autocorrect` with `autocorrect_policy`, and
`a11y_hint`. Every publication carries the package chrome
`clear_a11y_label` (`Chrome::string('clear_search')`). Registered `on_change`
and `on_submit` callbacks are props. Component style is empty.

## Editing and synchronisation

Plain `native:model` and `.live` publish every edit. `.blur` and `.lazy`
publish on focus loss or submit. `.debounce` publishes after the quiet period
and flushes on focus loss or submit; durations below 50 milliseconds fail
before publication. Change events use `TEXT_CHANGE` and submit uses `SUBMIT`.

A publication equal to the last committed query while focused is an
acknowledgement and never resets focus, cursor, selection, or composition. A
different publication while focused is recorded but not applied to the
buffer, and focus loss does not apply it; the next publication received while
unfocused replaces the displayed query. Unfocused publications never emit.

The clear action empties the buffer, retains focus, and publishes `''`
immediately through `@change`, including under blur or debounce. Submitting
from the keyboard's search action is ignored while disabled.

Both platforms register the focused field with Mobile's `KeyboardFocusPolicy`
so a Firstlight Button or Icon Button press first posts the pending blur or
debounce change.

## Validation and accessibility

`value`, `placeholder`, `autocapitalize`, and `sync-mode` are strict strings;
`debounce-ms` must be an integer. A missing or blank `a11y-label` fails before
publication, as do unsupported enum values, a debounce below 50 ms, and the
excluded attributes above.

The editable field carries `a11y-label` as its name and `a11y-hint` as
guidance, keeping its native editable-text role, value, focus, and disabled
state. The search glyph is decorative. The clear action carries the localised
`clear_a11y_label`.

## Platform expression

- iOS hosts a `UISearchTextField` subclass with a 36-point intrinsic height
  floor inside a SwiftUI frame of at least 44 points. UIKit owns search chrome,
  the search glyph, the clear button (`clearButtonMode = .whileEditing`), the
  `search` return key with empty-query submission, selection, marked text, and
  Dynamic Type. Disabled fields use tertiary label text, dim the glyph to 0.38
  alpha, and hide the clear button. Server publications are applied only
  outside marked text and keep the selection offsets.
- Android uses Material 3 `OutlinedTextField` with `singleLine = true`, a
  `CircleShape` container at least 48 dp tall, a leading `search`
  `MaterialIcon`, a trailing `close` `IconButton` of at least 48 dp while the
  query is non-empty, `TextFieldValue` selection and composition, and
  `ImeAction.Search`. Omitted `autocapitalize` maps to sentences.

## Renderer decision

Mobile UI's filled, outlined, and bare text inputs provide standard model
modifiers and text callbacks, but they expose broad general-field APIs, their
iOS renderers are not a `UISearchTextField`, and they do not make the search
and clear affordances an invariant semantic contract. Search Field therefore
uses paired renderers through the official SuperNative element and
NativePHP's existing text-change and submit transports.

## Evidence boundary

Pest proves strict strings, required `a11y-label`, defaults, enums, sync
policies and debounce bounds, callback registration, excluded attributes,
package chrome, layout, real Blade compilation, and web no-op compilation. A
source-wiring test keeps the accessible name on the iOS `UISearchTextField`
and the Android editable node.

XCTest proves prop decoding, acknowledgement and pending correction,
blur-before-submit, empty-query submit, clear, disabled inertness, UIKit search
configuration and clear-button labelling, disabled dimming, focused-press
flushing, and gated snapshots. Kotlin tests prove the same state transitions
with selection and composition retention and gated Material snapshots.
Showcase and device evidence remain separate integration and release
requirements.
