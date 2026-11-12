---
title: Switch component contract
description: Public Boolean setting API, server-authoritative proposal flow, accessibility, native expression, and renderer decision for Firstlight Switch.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-switch-design.md
  - docs/components/switch.md
  - src/Components/SwitchControl.php
  - src/Elements/SwitchControl.php
  - src/Validation/FieldErrorBinder.php
  - resources/ios/SwitchRenderer.swift
  - resources/ios/SwitchControl.swift
  - resources/android/SwitchRenderer.kt
  - resources/android/SwitchControl.kt
  - tests/Feature/SwitchControlElementTest.php
  - tests/ios/SwitchControlRendererContractTests.swift
  - tests/ios/SwitchControlSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SwitchRendererContractTest.kt
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SwitchControlTest.kt
  - vendor/nativephp/mobile-ui/src/Elements/Toggle.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIToggleRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/ToggleRenderer.kt
---

# Switch Component Contract

## Purpose and state

`<firstlight:switch>` turns one persistent setting on or off. It is a strict
Boolean, server-authoritative discrete control: a tap proposes the inverse
value once, but the visible on/off state changes only after PHP republishes
the accepted tree.

Use Checkbox for form or checklist agreement and Choice Group for selection
from a visible collection.

## Public API

```blade
<firstlight:switch
    native:model="notifications"
    label="Notifications"
    helper="Receive account updates."
    a11y-hint="Controls notification delivery"
/>
```

The self-closing component supports strict Boolean `value` or `native:model`,
`label`, `helper`, `error`, `disabled`, `a11y-label`, `a11y-hint`, standard
`@change`, and external layout `class`. Omitted value defaults to `false`.
Only immediate/default and `live` binding are supported.

`value` rejects every non-Boolean, including `null` and the string `'false'`,
whose message recommends `:value="false"` or `native:model`. `required` and
`placement` fail as unsupported attributes, and `blur`, `lazy`, and
`debounce` modes fail with guidance to use `native:model` or
`native:model.live`. `label`, `helper`, and `error` are cast to strings and
`disabled` to a Boolean. A visible label or explicit accessible name is
expected during development.

## Published contract

The element type is `firstlight.switch`. Props are `value`, `label`,
`helper`, `error`, `disabled`, optional `a11y_label`, optional `a11y_hint`,
and optional registered `on_change`. No sync timing props are published.
Layout remains in the standard Element Tree layout payload.

`FieldErrorBinder` fills `error` from the current MessageBag for the
`native:model` or `error-for` name unless the author supplied a non-empty
`error`. A blank `label` and blank `a11y-label` trigger a development warning
outside production.

## State timing

Each renderer keeps only a pending-proposal marker. A tap proposes
`!value` through NativePHP's toggle-change transport and ignores further taps
until the next publication. Every publication of the node, including one that
republishes the same value, replaces the configuration and clears the pending
proposal, so a rejected proposal leaves the visible state unchanged and the
next tap proposes again. Programmatic publications update the accepted state
without emitting. A disabled switch, or one without `on_change`, never
proposes.

## Native expression

- iOS composes a SwiftUI `Toggle` with `.switch` style, theme `primary` tint,
  and a read-only binding whose setter only proposes. The label uses `body`
  and the supporting text `footnote`, coloured destructive when it is an
  error. The control fills the width with a 44-point minimum height and
  exposes one toggle element with the explicit or visible label, the hint,
  and the value `On` or `Off`, followed by `Error: <text>` when present.
- Android composes one full-width row of at least 48 dp with
  `toggleable(role = Role.Switch)`, merged semantics carrying label and hint
  as the content description and the error as error semantics, label and
  supporting text, and a trailing Material 3 `Switch` with
  `onCheckedChange = null` and cleared semantics so TalkBack meets one switch.
  Track and thumb colours come from NativePHP semantic tokens, with 0.38 alpha
  while disabled.

On both platforms an error replaces the helper text.

## Path decision

Mobile UI's `native:toggle` already renders SwiftUI `Toggle` and Material 3
`Switch`, but it changes local checked state before PHP accepts a value, lacks
Firstlight helper and error field semantics, and accepts deferred sync modes
that have no meaning for a discrete tap. Switch therefore uses paired
Firstlight renderers and NativePHP's existing toggle-change transport rather
than an adapter or new bridge.

## Evidence boundary

Pest proves strict Boolean authoring and the string-false hint, the default,
metadata, live-only sync, rejected `required` and `placement`, callback
registration, the unlabelled warning, tag-boundary preservation, and web
no-op compilation. XCTest and Kotlin tests prove decoding, error precedence,
label fallback, accepted-versus-proposed state, deduplication until
publication, identical rejection, disabled and callback-less inertness,
stable node lookup, one rendered accessibility control on iOS, and light,
dark, disabled, error, long-label, and font-scale `2.0` snapshots. Showcase
and device evidence remain separate integration and release requirements.
