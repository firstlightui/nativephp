---
title: Activity Indicator component contract
description: Current semantic, lifecycle, accessibility, renderer, and evidence contract for Firstlight Activity Indicator.
status: current
audience: maintainer
sources:
  - Constitution.md
  - nativephp.json
  - src/Components/ActivityIndicator.php
  - src/Elements/ActivityIndicator.php
  - resources/ios/ActivityIndicatorControl.swift
  - resources/ios/ActivityIndicatorRenderer.swift
  - resources/android/ActivityIndicatorControl.kt
  - resources/android/ActivityIndicatorRenderer.kt
  - tests/Feature/ActivityIndicatorElementTest.php
  - tests/ios/ActivityIndicatorSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/ActivityIndicatorTest.kt
---

# Activity Indicator Component Contract

## Purpose and state class

`<firstlight:activity-indicator>` communicates active work whose completion
cannot be measured. It is an action/display component: it has no model,
callback, value, user interaction, or PHP-controlled animation state.

Presence is the complete activity state. The application conditionally
publishes the element while work is active and removes it when that activity
ends. Activity Indicator does not start work, infer application state, replace
visible status copy, or communicate determinate completion.

## Public API

```blade
@if ($loading)
    <firstlight:activity-indicator
        size="md"
        a11y-label="Loading appointments"
    />
@endif
```

| Prop | Contract |
| --- | --- |
| `size` | Optional semantic size: `sm`, `md` (default), or `lg`. |
| `a11y-label` | Required non-empty string naming the active work. `a11yLabel` is an accepted authoring alias. |
| `class` | External EDGE layout only. |

The component is self-closing. It has no visible `label`, child content,
`a11y-hint`, `value`, `native:model`, event, disabled state, `loading`,
`active`, `visible`, validation state, sync mode, tone, variant, or colour
override. `wire:loading` is not a second native visibility API; the
application conditionally authors the native element from its server state.

Visible status text is composed as a sibling. Theme colour is always resolved
from the host NativePHP primary token.

## Empty, lifecycle, and failure behaviour

An absent element renders and announces nothing. A present element always
represents active indeterminate work. Removing and later re-adding the element
creates a new mount and a new appearance announcement.

Missing, `null`, empty, whitespace-only, and non-string accessibility labels
throw `InvalidArgumentException`. A non-string size or any string outside
`sm`, `md`, and `lg` also throws. Unsupported content, hint, state, event,
field, style, and synchronization attributes fail before publication rather
than becoming inert escape props.

PHP publishes only `size` and `a11y_label` as primitive props. External EDGE
layout remains available; the component publishes no style or callback data.

## Data flow and reconciliation

PHP validates one ordinary `firstlight.activity-indicator` EDGE element. Each
native renderer reads the semantic size and required name, resolves the host
theme, and lets the platform control own indeterminate animation.

Both renderer states retain the node's stable identity. Server publication may
update presentation metadata without emitting an event. iOS stores a
mount-scoped announcement guard that is not reset by reconciliation. Android
retains the same semantics on the stable Compose node. No renderer adds a
timer, bridge call, click handler, custom animation state, or synthetic role.

## Accessibility

The non-visible name is mandatory. It describes the work—such as `Loading
appointments`—rather than the visual spinner. The component exposes no hint,
percentage, action, selected state, disabled state, or interaction target.

A newly mounted element produces one polite, non-interrupting announcement and
does not steal accessibility focus. Ordinary reconciliation, body
recomputation, and metadata changes do not announce again. On iOS the renderer
posts one guarded `AccessibilityNotification.Announcement` from `onAppear`. On
Android the rendered semantics contain the authored content description and
`LiveRegionMode.Polite`, with no click action.

Native controls retain platform contrast, RTL, Increased Contrast, and Reduced
Motion or system animation behaviour. The component owns no visible text, so
Dynamic Type and font scaling apply only to separately composed sibling copy.

## Platform expression

- iOS renders SwiftUI `ProgressView()` with circular style, native
  `ControlSize` mapping (`small`, `regular`, `large`), and the resolved primary
  theme colour.
- Android renders Material 3 `CircularProgressIndicator` with semantic
  dimensions `20.dp`, `32.dp`, and `48.dp`, the resolved primary theme colour,
  and polite live-region semantics.

Semantic size ordering is shared, but exact geometry and animation are native.
Firstlight guarantees behavioural parity rather than identical pixels.

## Primitive audit and paired-renderer decision

Installed Mobile UI 0.3.0 supplies genuine SwiftUI and Material 3 circular
activity indicators and already owns native animation. Its public primitive,
however, does not provide the complete approved cross-platform announcement
contract: the Android renderer marks a polite live region while the iOS
renderer only assigns an accessibility label. Adapting that primitive would
therefore leave unequal appearance semantics.

Firstlight owns paired renderers behind one public
`firstlight.activity-indicator` type. The Android manifest uses the
collision-safe `FirstlightActivityIndicatorRenderer` identifier because the
installed dependency already declares `ActivityIndicatorRenderer`. The public
Blade API does not expose that internal integration name.

## Evidence boundary

Development evidence requires PHP contract and manifest tests, iOS production
and XCTest compilation, Android contract/semantics tests, stable light/dark
Paparazzi goldens, full off-device package gates, a clean-installed sibling
showcase fixture, and constitutional review.

Simulator, emulator, documentation screenshot, VoiceOver, TalkBack, and dated
physical-device rows require their explicit targets and permissions. Missing
runtime or assistive-technology evidence remains an honest release blocker; it
must not be replaced by unit snapshots or inferred from source inspection.
