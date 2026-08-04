---
title: Firstlight Search Field design
description: Approved public contract, focused query model, platform expression, and evidence boundary for Firstlight Search Field.
status: approved
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - spec/designs/2026-08-04-firstlight-text-field-design.md
  - vendor/nativephp/mobile-ui/src/Elements/BaseTextInput.php
  - https://nativephp.com/docs/mobile/4/edge-components/text-input
  - https://developer.apple.com/documentation/uikit/uisearchtextfield
  - https://developer.android.com/develop/ui/compose/components/search-bar
---

# Firstlight Search Field

Date: 2026-08-04

Status: approved for implementation

## Purpose

`<firstlight:search-field>` enters and submits one search query. It owns the
platform search affordance and clear action, keeps a native editing buffer while
focused, and uses standard NativePHP change and submit events.

It is not a general text field, visible form field, validation surface, filter
token editor, suggestion list, or results container.

## Public API

```blade
<firstlight:search-field
    placeholder="Search referrals"
    a11y-label="Search referrals"
    autocapitalize="words"
    :autocorrect="false"
    native:model.debounce.300ms="query"
    @submit="search"
/>
```

| API | Accepted type | Behaviour |
| --- | --- | --- |
| `value` / `native:model` | `string` | Published PHP query; omission defaults to `''`. |
| `placeholder` | `string` | Short empty-query prompt; never the accessible name. |
| `disabled` | `bool` | Prevents focus, editing, clearing, change, and submit. |
| `autocapitalize` | enum string | `none`, `sentences`, `words`, or `characters`; omission retains platform policy. |
| `autocorrect` | `bool` | Explicitly enables or disables platform correction; omission retains platform policy. |
| `a11y-label` | non-empty `string` | Required accessible name for the search field. |
| `a11y-hint` | `string` | Supplementary VoiceOver and TalkBack guidance. |
| `class` | `string` | External EDGE layout for the complete field. |

`@change` receives the current string when the selected sync policy commits.
`@submit` flushes a pending change and then receives the current string. Empty
queries still submit. Plain `native:model` and `.live` publish every edit;
`.blur`/`.lazy` publish on focus loss or submit; `.debounce.*` publishes after
the quiet period and flushes on blur or submit. Durations below 50 ms fail.

The native clear affordance is always available for a non-empty, enabled query.
It clears the native buffer, retains focus, and immediately emits `@change('')`
even under blur or debounce synchronisation.

Search Field intentionally has no `label`, `helper`, `error`, `required`,
`read-only`, keyboard/content type, secure/reveal, authored icon, variant,
size, or arbitrary styling API. The search icon and clear action are semantic
native affordances, not consumer-authored icon slots.

## Validation and focused state

Values and text attributes are strict strings. `a11y-label` must be non-empty.
Unsupported field-only attributes, invalid capitalization, invalid sync mode,
and debounce durations below 50 ms fail with actionable diagnostics.

Each renderer owns a native editing buffer while focused. A PHP publication
equal to the last committed query acknowledges it without resetting focus,
selection, cursor, or marked-text composition. A different publication waits
until focus leaves. Unfocused publications replace the visible query without
emitting events. Clear commits immediately and preserves focus. Submit always
emits, including for an empty query, after flushing any pending change.

## NativePHP primitive audit

Mobile UI 0.3.0 exposes general filled, outlined, and bare text inputs with the
standard model modifiers and text callbacks. Those primitives are not adequate
adapters: they expose broad general-field APIs, their iOS renderers are not a
`UISearchTextField`, and they do not make the search and clear affordances an
invariant semantic contract. Search Field therefore uses an ordinary custom
EDGE element and paired native renderers through the official SuperNative seam.

## Platform expression

iOS embeds a genuine `UISearchTextField` in SwiftUI. UIKit owns search chrome,
search icon, clear button, keyboard, selection, cursor, marked text, focus, and
Dynamic Type. The coordinator applies server publications only when safe and
uses editing-change, return, and editing-end callbacks for standard events.

Android uses a Material 3 search input composition with `TextFieldValue` so
selection and IME composition remain native and local. It uses the Material
search icon, a genuine `IconButton` clear action with a 48-dp target, the search
IME action, built-in text semantics, and semantic theme colours.

The platforms share behaviour and API rather than geometry. iOS does not copy
Material search-bar visuals; Android does not copy UIKit search chrome.

## Evidence boundary

Development requires strict PHP and Blade tests, Swift behaviour/accessibility
tests, Kotlin behaviour/semantics/Paparazzi tests, paired production sources,
manifest and structural gates, complete public docs, installed showcase and
capture fixtures, full off-device tests, and constitutional review. Simulator,
emulator, screenshots, and physical-device interaction remain separate
controller-owned evidence and block component release, not an off-device build.
