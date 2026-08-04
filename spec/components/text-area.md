---
title: Text Area component contract
description: Public API, focused multiline editing, accessibility, and renderer decision for Firstlight Text Area.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - spec/designs/2026-08-04-firstlight-text-field-design.md
  - src/Elements/TextField.php
  - vendor/nativephp/mobile-ui/src/Elements/BaseTextInput.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUITextInputCore.swift
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIOutlinedTextInputRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/OutlinedTextInputRenderer.kt
---

# Text Area Component Contract

## Purpose and state class

`<firstlight:text-area>` enters and edits plain multiline text. It is a
focused-text control: native code owns the editing buffer, focus, cursor,
selection, marked-text or IME composition, and scrolling while editing. PHP
owns the published string and validation metadata.

Text Area is not a single-line field, search control, rich-text editor, secure
input, or submit action. Those intents belong to other components.

## Public API

```blade
<firstlight:text-area
    label="Clinical notes"
    placeholder="Add relevant history and observations"
    helper="Do not include information that is not required."
    :required="true"
    :min-lines="4"
    :max-lines="10"
    autocapitalize="sentences"
    native:model.debounce.500ms="notes"
/>
```

| Prop or event | Contract |
| --- | --- |
| `value` / `native:model` | Strict string; omission defaults to `''`. |
| `label` | Visible field label and accessibility-name fallback. |
| `placeholder` | Short empty-state guidance; never replaces a label. |
| `helper` | Supporting guidance below the field. |
| `error` | Validation feedback that replaces helper text. |
| `required` | Communicates required metadata without performing validation. |
| `disabled` | Prevents focus and editing. |
| `read-only` | Preserves native focus, selection, copy, and scrolling while preventing edits. |
| `min-lines` | Positive integer minimum visible height; defaults to `3`. |
| `max-lines` | Positive integer maximum visible height before native scrolling; defaults to `8` and must be at least `min-lines`. |
| `autocapitalize` | `none`, `sentences`, `words`, or `characters`; omission keeps platform policy. |
| `autocorrect` | Explicitly enables or disables platform autocorrection. |
| `a11y-label` | Explicit accessible name when no visible label is appropriate. |
| `a11y-hint` | Additional VoiceOver or TalkBack guidance. |
| `@change` | Receives the current string according to the standard sync policy. |
| `class` | External EDGE layout for the complete field. |

`minLines`, `maxLines`, `readOnly`, `syncMode`, and `debounceMs` camel-case
aliases are accepted by the PHP element API, while public Blade documentation
uses kebab-case.

## Editing and synchronisation

Plain `native:model` and `.live` publish while editing. `.blur` and `.lazy`
publish on focus loss. `.debounce.500ms` publishes after the quiet period and
flushes on blur; durations below 50 milliseconds fail before publication.

Focused server acknowledgements never replace the native editing buffer, move
the cursor or selection, clear marked text, dismiss the keyboard, or reset the
scroll position. A different server publication is retained for reconciliation
without disturbing the active edit. Unfocused programmatic publications replace
the displayed value without emitting `@change`.

The field publishes no submit or press event. It has no icons, secure mode,
keyboard/content-type picker, clear/reveal affordance, loading state, prefix,
suffix, single-line mode, or platform styling escape prop.

## Validation and accessibility

All textual attributes are strict strings. Line counts must be positive
integers and `min-lines` cannot exceed `max-lines`. Unsupported sync modes,
invalid capitalization, non-boolean boolean flags, and excluded single-line or
action props fail with actionable diagnostics.

A visible `label` or explicit non-empty `a11y-label` is required during
development. Error text replaces helper text and is exposed as validation
feedback without replacing the field's accessible name or current value.
Disabled and read-only states remain distinguishable. Native type scaling,
light/dark appearance, increased contrast, Reduced Motion, and RTL remain
platform-owned.

## Platform expression

- iOS uses SwiftUI `TextEditor` in Apple-native field composition. Its local
  string binding preserves native focus, selection, marked text, and scrolling
  while the renderer observes SuperNative publications.
- Android uses Material 3 `OutlinedTextField` with `singleLine = false`,
  `TextFieldValue`, native selection and composition, `minLines` / `maxLines`,
  supporting/error slots, and Compose semantics.

## Renderer decision

Mobile UI 0.3.0 exposes multiline mode through its general Text Input primitive,
including standard model sync. It is not an adequate Firstlight adapter: the
iOS outlined renderer intentionally reproduces Material-style field chrome
instead of an Apple-native TextEditor composition, and the primitive's public
surface includes single-line submission, secure input, icons, keyboard/content
hints, prefixes, suffixes, loading, and other capabilities intentionally absent
from this narrow contract. Firstlight therefore uses paired renderers through
the official SuperNative element and wire-event seams.

## Evidence plan

- Pest 5 proves strict strings and booleans, line validation, sync modifiers,
  callback registration, accessibility diagnostics, exclusions, layout-only
  classes, real Blade compilation, and exact manifest identifiers.
- XCTest proves prop decoding, local focused editing, acknowledgement and
  programmatic reconciliation, live/blur/debounce publication, disabled and
  read-only behavior, genuine TextEditor composition, accessibility, and
  representative snapshots.
- Kotlin tests prove equivalent state transitions, `TextFieldValue` selection
  and IME composition retention, Material multiline configuration, semantics,
  font scale `2.0`, and Paparazzi states.
- The showcase dogfoods empty, filled, helper, error, required, disabled,
  read-only, line-range, long, rapid, and programmatic-update states plus an
  isolated `/captures/text-area` route.
