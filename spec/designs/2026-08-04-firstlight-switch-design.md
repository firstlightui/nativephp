---
title: Firstlight Switch design
description: Approved public contract, native state model, platform expression, and evidence boundary for Firstlight Switch.
status: approved
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - nativephp.json
  - vendor/nativephp/mobile-ui/src/Elements/Toggle.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIToggleRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/ToggleRenderer.kt
---

# Firstlight Switch

Date: 2026-08-04

Status: approved for implementation

## Purpose

`<firstlight:switch>` turns a persistent setting or capability on or off. It
is a strictly boolean, server-authoritative field expressed as a genuine
SwiftUI `Toggle` on iOS and Material 3 `Switch` on Android.

Switch is not a universal checkbox, consent control, nullable selection, or
toggle-button style. Visible single and multiple choices belong to
`choice-group`; action buttons with selected appearance are a separate intent.

## Public API

```blade
<firstlight:switch
    label="Notifications"
    helper="Receive updates about new activity."
    native:model="notifications"
/>
```

The public tag accepts:

| API | Accepted type | Behaviour |
| --- | --- | --- |
| `value` / `native:model` | `bool` | Accepted on/off state; omitted value defaults to `false`. |
| `label` | `string` | Visible setting label and accessibility-label fallback. |
| `helper` | `string` | Supporting guidance beneath the label. |
| `error` | `string` | Visible and accessible validation feedback; replaces helper while present. |
| `disabled` | `bool` | Disables the complete row and prevents change events. |
| `a11y-label` | `string` | Explicit accessible name when a visible label is inappropriate. |
| `a11y-hint` | `string` | Supplementary VoiceOver and TalkBack guidance. |
| `class` | `string` | External EDGE layout for the complete row only. |

`@change` receives the proposed boolean value. Plain `native:model` and
`native:model.live` are supported and equivalent because Switch is discrete.
`blur`, `lazy`, and `debounce` modes are rejected with guidance rather than
pretending that deferred timing has meaningful switch semantics.

Switch intentionally has no `required`, loading, empty, nullable, variant,
tone, or placement API. Required state has no coherent meaning for a
persistent boolean setting. The control remains trailing in the settings row
and mirrors automatically in right-to-left layout.

## Boolean validation

The accepted value is an actual PHP boolean. Omitted `value` defaults to
`false`. Authored `null`, integers, strings, arrays, and objects fail with an
actionable development exception.

In particular, `value="false"` is rejected because Blade supplies a string and
PHP would otherwise cast it to `true`; the diagnostic recommends
`:value="false"` or `native:model`. This follows Laravel's strict boolean
conventions rather than arbitrary PHP truthiness. Bare boolean field flags,
such as `disabled`, retain ordinary Blade presence-attribute behaviour.

## NativePHP primitive audit

NativePHP Mobile UI 0.3.0 already renders `native:toggle` as SwiftUI `Toggle`
and Material 3 `Switch`. It supplies semantic theme colours, native motion and
interaction, boolean callbacks, disabled state, label and accessibility
attributes, and programmatic reconciliation.

It is not an adequate adapter for the approved Firstlight contract because:

- user interaction changes the renderer's local checked state before PHP
  publishes an accepted value;
- it does not expose Firstlight helper and error field semantics; and
- it accepts deferred sync modes even though they do not alter a discrete tap.

Firstlight therefore adds an ordinary `firstlight.switch` EDGE element and
paired native renderers. It does not add a bridge or copy the upstream state
machine.

## State and event flow

The element publishes the accepted boolean, field metadata, accessibility
attributes, disabled state, and one registered `on_change` callback through
the standard Element Tree.

Each renderer keeps only a non-visual pending-proposal marker:

1. The switch displays the boolean from the latest published tree.
2. A user tap proposes the inverse value through the official toggle-change
   event without changing the visible checked state.
3. Further taps are ignored while that proposal is pending, preventing
   duplicate activation during a PHP round trip.
4. The next tree publication clears pending state.
5. If PHP accepts the proposal, the newly published value moves the switch.
6. If PHP rejects it, the old value remains visible throughout.
7. Programmatic publications update the switch without emitting an event.

Pressed state, focus, haptics where supplied by the platform, and other local
interaction feedback remain native. Only the accepted checked state waits for
PHP.

Clearing pending state after a rejected proposal depends on NativePHP exposing
every PHP response publication to plugin renderers, including a response whose
tree is value-identical to the preceding tree. Development may continue
against the local fork, but an unreleased publication prerequisite blocks the
public alpha.

## Platform expression

### iOS

The iOS renderer uses SwiftUI `Toggle` with a read-only server-derived binding
whose setter proposes a change. Its label contains native text for the title
and supporting state. The control uses the host semantic primary tint,
platform typography, Dynamic Type, automatic right-to-left mirroring, and a
minimum 44-point interaction target.

### Android

The Android renderer composes a Material 3 settings row with a text column and
trailing `Switch`. The row owns one `Role.Switch` toggleable semantic and one
48-dp minimum target; the inner switch is not a second accessibility target.
Material typography, state layers, motion, semantic colours, font scaling,
and automatic right-to-left mirroring remain native.

On both platforms the complete row is tappable. Error text replaces helper
text, uses the host destructive/error token, and is exposed through native
error semantics. A visible label or explicit `a11y-label` is mandatory in
development review.

## Internal identity and tooling

PHP reserves `switch`, so the internal Blade component and EDGE element are
named `FirstlightUI\Components\SwitchControl` and
`FirstlightUI\Elements\SwitchControl`. The public type and tag remain
`firstlight.switch` and `<firstlight:switch>`.

The native renderer identifiers are read from the current plugin namespace:

- iOS: `SwitchRenderer`
- Android: `dev.firstlightui.plugins.firstlight_ui.ui.SwitchRenderer`

The component tooling will gain a tested convention for an internal class
ending in `Control`: strip that suffix for the public tag, documentation slug,
and renderer base name. `bin/scaffold-component SwitchControl` therefore
creates the `switch` public component without exposing the reserved-word
workaround to consumers.

## Testing and evidence

Implementation follows strict red-green-refactor cycles. Evidence includes:

- Pest 5 tests for public tag compilation, strict booleans, default-off state,
  callback registration, field metadata, unsupported sync modes, diagnostics,
  disabled state, and web no-op compilation;
- iOS tests for proposal deduplication, acceptance, rejection, identical
  publication, programmatic changes, accessibility, and light, dark, disabled,
  error, long-label, and accessibility-size snapshots;
- Android tests for the same state transitions, `Role.Switch` semantics,
  error semantics, 48-dp targeting, font scale `2.0`, and Paparazzi states;
- exhaustive `firstlightui/showcase` fixtures for off, on, disabled-off,
  disabled-on, helper, error, long label, rapid taps, rejection, programmatic
  updates, right-to-left layout, and accessibility;
- focused and full package tests, both platform test suites, exact-commit
  showcase tests, plugin validation, and iOS and Android showcase host builds;
- representative simulator screenshots and manual VoiceOver and TalkBack
  inspection for development; and
- a separate dated physical-device pass before component release.

Loading and empty fixtures do not apply. A passing Switch review establishes
component-development readiness only; it does not satisfy the complete alpha
catalogue gate.

## Non-goals

Switch does not expose leading placement, arbitrary colours, shape, thumb
icons, animation controls, a platform-style escape hatch, optimistic mode,
nullable values, or a separate commit event. These can be reconsidered only
when a durable cross-platform use case justifies expanding the contract.
