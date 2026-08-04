---
title: Checkbox component contract
description: Public Boolean field API, server-authoritative proposal flow, native expression, and renderer decision for Firstlight Checkbox.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-05-firstlight-checkbox-design.md
  - docs/components/switch.md
  - vendor/nativephp/mobile-ui/src/Elements/Checkbox.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUICheckboxRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/CheckboxRenderer.kt
---

# Checkbox Component Contract

## Purpose and state

`<firstlight:checkbox>` is one strict Boolean form or checklist field. It is a
server-authoritative discrete control: a tap proposes the inverse value once,
but the visual checkmark changes only after PHP republishes the accepted tree.

Use Switch for immediately applied settings and Choice Group for selection
from a visible collection.

## Public API

```blade
<firstlight:checkbox
    native:model="acceptedTerms"
    label="I agree to the terms"
    helper="Required before continuing."
    required
    @change="termsChanged"
/>
```

The self-closing component supports strict Boolean `value` or `native:model`,
`label`, `helper`, `error`, strict Boolean `required` and `disabled`,
`a11y-label`, `a11y-hint`, standard `@change`, and external layout `class`.
Omitted value defaults to `false`. Only immediate/default and `live` binding
are supported.

It rejects nullable or indeterminate state, deferred binding modes, slots,
`@press`, `@submit`, placement, consumer icons, colours, variants, and style
escape hatches. A visible label or explicit accessible name is mandatory for
development.

## Published contract

The element type is `firstlight.checkbox`. Props are `value`, `label`,
`helper`, `error`, `required`, `disabled`, optional `a11y_label`, optional
`a11y_hint`, and optional registered `on_change`. Layout remains in the
standard Element Tree layout payload and component style is empty.

## Native expression

- iOS composes one SwiftUI checkmarked row with a native `Button`, owned
  checkmark affordance, text metadata, 44-point target, and one toggle
  accessibility element.
- Android composes one labelled row around Material 3 `Checkbox`, with a
  48-dp `Role.Checkbox` target and the visual checkbox hidden from duplicate
  TalkBack focus.

Both renderers keep accepted state read-only, deduplicate proposals until the
next publication, clear pending state on accepted or rejected publication,
and never echo programmatic changes.

## Path decision

Mobile UI 0.3.0 mutates local checked state optimistically on both platforms
and lacks Firstlight field metadata. Checkbox therefore uses paired
Firstlight renderers and NativePHP's existing checkbox-change transport rather
than an adapter or new bridge.

## Evidence boundary

Pest proves strict authoring, public tag compilation, callback registration,
metadata, diagnostics, layout, and manifest registration. Platform tests prove
accepted-versus-proposed state, identical rejection, disabled/deduplicated
interaction, metadata reconciliation, one accessibility target, minimum hit
targets, themes, long text, and scaling. Showcase and device evidence remain
separate integration and release requirements.
