---
title: Progress component contract
description: Current semantic, adapter, accessibility, value, and failure contract for Firstlight Progress.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - nativephp.json
  - src/Components/Progress.php
  - src/Elements/Progress.php
  - tests/Feature/ProgressElementTest.php
  - vendor/nativephp/mobile-ui/src/Components/ProgressBar.php
  - vendor/nativephp/mobile-ui/src/Elements/ProgressBar.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIProgressBarRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/ProgressBarRenderer.kt
---

# Progress Component Contract

## Purpose and state class

`<firstlight:progress>` communicates the completion of ongoing work. It is an
action/display component: it has no model binding or event, and native code
owns only the platform's determinate presentation or indeterminate animation.

Determinate progress is a published fraction from `0.0` through `1.0`.
Indeterminate progress communicates that work is active when a meaningful
fraction is unavailable. Progress does not start work, control a task, or infer
application state.

## Public API

```blade
<firstlight:progress
    :value="$completed / $total"
    a11y-label="Uploading documents"
/>
```

```blade
<firstlight:progress
    indeterminate
    a11y-label="Preparing documents"
/>
```

| Prop | Contract |
| --- | --- |
| `value` | Optional finite `int` or `float` from `0.0` through `1.0`. A non-null value selects determinate mode. |
| `indeterminate` | Optional `bool`. Omitted mode is inferred from `value`; explicit `true` requires no non-null value and explicit `false` requires one. |
| `a11y-label` | Required non-empty accessible name describing the work. |
| `class` | External EDGE layout only. |

An omitted or explicit `null` value with no contradictory `indeterminate`
attribute produces indeterminate progress. A value of `0` is determinate and
distinct from missing progress. A value of `1` means complete; Firstlight does
not remove the indicator or announce task success on the application's behalf.

The component has no visible text slot, `label`, `a11y-hint`, `native:model`,
event, disabled, loading, validation, semantic tone, colour override, circular
variant, size, or icon API. Consumers compose separate visible text when the
interface needs it. The current delegated native renderers do not consume
`a11y-hint`, so Firstlight rejects it instead of publishing inaccessible
metadata.

## State and failure behaviour

PHP publishes the latest progress state through the ordinary Element Tree.
Programmatic publications update native presentation without producing an
event. Indeterminate animation remains on the native UI thread and follows
platform accessibility policy; Firstlight adds no timer, bridge traffic, or
parallel state.

Integers are normalised to floats on the wire. Non-numeric values, numeric
strings, booleans, arrays, objects, `NaN`, infinities, and values outside
`0.0...1.0` throw `InvalidArgumentException`. Firstlight never relies on the
native renderers' defensive clamping for authored values.

Supplying a non-null `value` with `indeterminate="true"`, or explicit
`indeterminate="false"` without a non-null value, is contradictory and fails.
`indeterminate` must be an actual boolean. Unsupported props and events fail
before publication rather than leaking Mobile UI escape hatches.

## Accessibility

Progress has no visible label within its own bounds, so a non-empty
`a11y-label` is mandatory. The native controls expose their progress-indicator
role and, for determinate progress, their system-formatted value. Indeterminate
progress exposes ongoing activity without inventing a percentage.

VoiceOver and TalkBack use the authored accessible name plus the platform's
native value semantics. The component retains native contrast, right-to-left
layout, Increased Contrast, Reduced Motion or disabled-animation policy, and
Dynamic Type or font-scale behaviour where applicable.

## Platform expression

- iOS delegates to Mobile UI's SwiftUI `ProgressView`, using the indeterminate
  initializer when no value is available and `ProgressView(value:)` for a
  published fraction.
- Android delegates to Mobile UI's Material 3 `LinearProgressIndicator`, using
  the indeterminate overload when no value is available and progress semantics
  for a published fraction.

Both implementations inherit the host Mobile UI semantic theme. Firstlight
does not expose Mobile UI's arbitrary `color` or Android-only `track-color`
overrides because those would weaken system-first theming and platform parity.

## Adapter decision and exit criteria

The installed `nativephp/mobile-ui` 0.3.0 primitive already provides genuine
SwiftUI and Material 3 linear indicators, determinate and indeterminate modes,
semantic theme colours, native animation, and accessible progress values.
Firstlight therefore uses a thin adapter while owning stricter authoring
defaults, validation, diagnostics, and its public `firstlight.progress` type.

The manifest maps that public type to Mobile UI's `progress_bar` adapter and
exact dependency renderer identifiers. Consumers never author
`<native:progress-bar>` as a second Firstlight API.

Firstlight may move to package-owned paired renderers without changing the
public tag if a later durable, cross-platform requirement cannot be expressed
through the official primitive. Circular presentation, arbitrary colours, or
platform-only novelty are not sufficient reasons by themselves.
