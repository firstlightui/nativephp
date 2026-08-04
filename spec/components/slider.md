---
title: Slider component contract
description: Strict Float-compatible grids, native gesture drafts, synchronization, accessibility, and paired-renderer decision for Firstlight Slider.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - vendor/nativephp/mobile-ui/src/Elements/Slider.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUISliderRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/SliderRenderer.kt
---

# Slider Component Contract

## Purpose and state class

`<firstlight:slider>` selects one value from an inclusive, evenly spaced
numeric grid. It is a continuous-gesture, server-authoritative field. PHP owns
the accepted value; each native renderer owns only the gesture draft.

Authored `value`, `min`, `max`, and `step` accept finite PHP `int|float` values.
The Element Tree normalizes all four to floats because NativePHP's standard
slider event and both production platform primitives use a 32-bit Float. The
standard `@change`/`native:model` callback therefore delivers a PHP float even
for integral grids.

## Public API

```blade
<firstlight:slider
    native:model.blur="dose"
    :min="0"
    :max="10"
    :step="0.5"
    label="Dose"
    helper="Half-milligram increments"
/>
```

| Prop | Contract |
| --- | --- |
| `value` / `native:model` | Required finite `int|float` accepted value. |
| `min` | Required finite inclusive lower bound. |
| `max` | Required finite inclusive upper bound; must exceed `min`. |
| `step` | Optional finite positive grid spacing from `min`; defaults to `1`. |
| `label` | Visible field label. |
| `helper` | Supporting guidance. |
| `error` | Visible and accessible validation feedback; replaces helper visually. |
| `disabled` | Prevents native gesture state and publication. |
| `a11y-label` | Explicit accessible name when a visible label is inappropriate. |
| `a11y-hint` | Supplementary VoiceOver and TalkBack guidance. |
| `a11y-value` | Optional spoken current value with units or domain language; never visible. |
| `class` | External EDGE layout only. |

Slider exposes no alternate event, orientation, range mode, marks, ticks,
visible value, formatter, min/max labels, required flag, visual variant, size,
colour, or component-style escape prop.

## Numeric and grid rules

Numeric strings, booleans, null, arrays, NaN, infinities, native Float overflow,
and nonzero native Float underflow fail before the tree is published. Firstlight
does not parse, clamp, truncate, or otherwise coerce authored values.

The accepted value must lie inside `[min, max]`. Both `(value - min) / step`
and `(max - min) / step` must be whole grid indices. A `1e-9` comparison
epsilon exists only for binary floating representation noise; it does not
admit an intentionally off-grid value. The range index may not exceed
`2,147,483,647`, the signed integer interval boundary consumed by Material.

PHP publishes `interval_count` alongside the four normalized float props.
Android converts it to Material's interior-step count with
`interval_count - 1`; iOS passes the authored step directly.

## Events, drafts, and reconciliation

Only the standard Float slider-change bridge event exists. Programmatic
publications and native snapping emit nothing by themselves.

- `live` publishes each changed snapped draft while the gesture moves.
- `blur` publishes a changed draft only when the gesture finishes.
- `debounce` publishes after at least 50 ms of quiet and flushes a changed final draft on release.

Duplicate snapped values do not emit. The native draft is never a second
accepted source of truth. Every observed server publication replaces the
configuration, draft, last-emitted value, and editing state, even when PHP
publishes the same accepted value to reject a proposal.

NativePHP Mobile 4.0.1 does not yet provide sufficient verified identical-tree
publication-epoch behavior for that rejection acknowledgement. This is an
upstream component-release blocker, not permission to add an optimistic latch,
second callback, custom bridge, or client-authoritative state. The renderer is
already written to reconcile every current-tree publication it receives.

## Accessibility and native expression

Visible `label` falls back as the accessible name; `a11y-label` overrides it.
The native numeric draft is the accessible value unless `a11y-value` supplies
domain language. Hint, helper/error, and disabled state remain available to
VoiceOver and TalkBack. Error replaces helper visually and uses platform error
semantics.

iOS uses SwiftUI `Slider(value:in:step:onEditingChanged:)`. Android uses
Material 3 `Slider(value:onValueChange:valueRange:steps:onValueChangeFinished:)`.
Both retain native adjustable control behavior, layout direction, contrast,
motion, and platform interaction conventions. Firstlight adds label and
supporting text without replacing either native slider primitive.

## Official primitive decision

This is a paired-renderer component. NativePHP Mobile UI's installed Slider
defaults its range, accepts broad numeric coercion, exposes size, and maintains
optimistic native acknowledgement behavior. Those choices conflict with
Firstlight's required explicit range, strict grid, narrow visual API, and
publication-authoritative reconciliation.

Firstlight still uses NativePHP's ordinary Element Tree, current-tree
publication, and standard Float slider event. It adds no WebView, JSON bridge,
parallel callback, or event normalization layer.

## Evidence plan

- Pest proves the strict Float-compatible numeric boundary, grid epsilon,
  inclusive bounds, interval limit, metadata, synchronization, diagnostics,
  callback, public tag, external layout, and exact renderer identifiers.
- XCTest and Kotlin JVM tests prove Float decoding, snapping, live/blur/debounce
  timing, duplicate suppression, disabled behavior, and authoritative
  publication reset without a device.
- Controller-owned runtime evidence covers native feel, visual states,
  accessibility services, host builds, and the upstream publication epoch.

The user's screenshot exception permits failed capture work to be bypassed.
Absent screenshots and device evidence remain recorded as absent rather than
being relabelled as passing evidence.
