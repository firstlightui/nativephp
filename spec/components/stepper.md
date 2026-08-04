---
title: Stepper component contract
description: Exact numeric types, bounded server-authoritative proposals, accessibility, and paired-renderer decision for Firstlight Stepper.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - src/Support/FiniteNumber.php
  - resources/ios/StepperRenderer.swift
  - resources/android/StepperRenderer.kt
---

# Stepper Component Contract

## Purpose and state class

`<firstlight:stepper>` moves one accepted number backward or forward on a
finite, inclusive, evenly spaced grid. It is a discrete, server-authoritative
field. PHP owns the accepted value and precomputes both bounded neighbouring
proposals; native renderers display only that accepted value.

The renderer suppresses another tap after one proposal until it observes the
next publication for the stable node. It does not optimistically change the
visible value, calculate arithmetic, coerce numbers, or maintain a second
accepted state.

## Public API

```blade
<firstlight:stepper
    native:model="quantity"
    :min="0"
    :max="10"
    :step="1"
    label="Quantity"
    helper="Adjust one item at a time"
/>
```

| Prop | Contract |
| --- | --- |
| `value` / `native:model` | Required finite PHP `int|float` accepted value. |
| `min` | Required finite inclusive lower bound. |
| `max` | Required finite inclusive upper bound; must exceed `min`. |
| `step` | Optional finite positive spacing from `min`; defaults to integer `1`. |
| `label` | Visible field label and accessibility-name fallback. |
| `helper` | Supporting guidance. |
| `error` | Accessible validation feedback that replaces helper visually. |
| `disabled` | Makes both native actions inert. |
| `a11y-label` | Explicit accessible name when a visible label is inappropriate. |
| `a11y-hint` | Supplementary VoiceOver and TalkBack guidance. |
| `class` | External EDGE layout only. |

Stepper exposes no custom icons, formatter, visible min/max labels, wraparound,
orientation, long-press acceleration, required or placeholder metadata,
accessible-value override, size, variant, tone, colour, or component-style
escape prop.

## Exact number and grid rules

Authored numbers are strict: numeric strings, booleans, null, arrays, NaN,
infinities, native Float overflow, and nonzero native Float underflow fail
before publication. Firstlight does not parse, clamp, round, or truncate public
values. `min` must be less than `max`, `step` must be positive, and `value`
must remain inside the inclusive bounds.

Both `(max - min) / step` and `(value - min) / step` must be whole grid indices.
The shared `1e-9` epsilon tolerates binary floating representation noise only.
The range index may not exceed `2,147,483,647`.

When `value`, `min`, `max`, and an explicitly authored `step` are all PHP
integers, the complete published numeric contract and callback proposal remain
integers. Omitted `step` is integer `1` in this mode. If any authored number is
a float, every published numeric prop and proposal is a float, including
integral values such as `5.0`. This preserves the model's authored numeric kind
instead of passing it through a lossy 16-bit or 32-bit native value bridge.

PHP publishes `display_value`, `can_decrement`, `can_increment`, and bounded
`decrement_value` and `increment_value` props. A boundary proposal equals the
accepted boundary but its corresponding action is disabled.

## Events and reconciliation

Only the public standard `@change` contract exists. Plain `native:model` and
`native:model.live` are accepted; blur, debounce, input, click, press, submit,
and gesture events are rejected. Each enabled tap proposes exactly one adjacent
grid value. Programmatic publications emit nothing.

For exact PHP typing, the Element creates two private press callback IDs from
the one authored change expression and appends the precomputed numeric proposal
as its final callback argument. For example, `__syncProperty('quantity')`
becomes callbacks equivalent to `__syncProperty('quantity', 4)` and
`__syncProperty('quantity', 6)`. Native sends the ordinary NativePHP `PRESS`
event for the selected private callback ID; this is transport detail, not a
public press event or new bridge vocabulary.

After dispatch, native retains the accepted display and suppresses stale taps.
Every observed server publication for the node replaces configuration and
releases suppression, including an identical accepted value that means PHP
rejected the proposal. NativePHP Mobile 4.0.1 does not yet provide sufficient
verified identical-publication behavior for that rejection acknowledgement.
This is a component-release blocker, not permission to add optimistic state,
a timeout, client arithmetic, or a second callback.

## Platform expression and accessibility

iOS uses SwiftUI `Stepper(label:onIncrement:onDecrement:)` with platform
increment and decrement affordances. Android uses a Material 3 row of 48 dp
icon buttons around the accepted value because Material supplies no Stepper
primitive. The internal minus and plus affordances are semantic and are not
publicly replaceable.

The control exposes its visible or explicit label, accepted display value,
hint, helper/error, disabled state, and the individual decrease/increase action
names. Error replaces helper visually. Bounds disable only the unavailable
direction; component disablement and a pending proposal disable both.

## Official primitive decision

This is a paired-renderer component. The installed `nativephp/mobile-ui`
package has no Stepper element or native renderer to adapt. SwiftUI provides a
genuine Stepper with custom increment/decrement closures; Material 3 provides
documented `IconButton(enabled:)` primitives but no Stepper. Firstlight uses
those official platform seams and NativePHP's ordinary Element Tree, callback
registry, press transport, and current-tree publication.

## Evidence plan

- Pest proves required strict numbers, authored integer/float preservation,
  min-based grids, epsilon, bounds, interval limit, exact callback payloads,
  metadata, diagnostics, event exclusions, and external layout.
- XCTest and Kotlin JVM tests prove configuration decoding, press transport,
  no optimistic display, stale-tap suppression, equal and changed publication
  reconciliation, stable-node lookup, bounds, disablement, and callback absence.
- Controller-owned runtime evidence covers native feel, light/dark/error/
  disabled/large-text states, VoiceOver, TalkBack, and host builds.

The user's screenshot exception permits capture failures to be bypassed. Device
and screenshot evidence remains explicitly absent until the controller records
it; it is never relabelled as passing off-device evidence.
