---
title: Firstlight Activity Indicator design
description: Approved public contract, native presentation, accessibility announcement, and evidence boundary for Firstlight Activity Indicator.
status: approved
sources:
  - Constitution.md
  - roadmap-v2.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - spec/components/progress.md
  - vendor/nativephp/mobile-ui/src/Elements/ActivityIndicator.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIActivityIndicatorRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/ActivityIndicatorRenderer.kt
  - https://nativephp.com/docs/mobile/4/edge-components/activity-indicator
  - https://nativephp.com/docs/mobile/4/digging-deeper/lifecycle-hooks
  - https://developer.apple.com/documentation/swiftui/progressview
  - https://developer.apple.com/documentation/accessibility/accessibilitynotification/announcement
  - https://developer.android.com/develop/ui/compose/components/progress
  - https://developer.android.com/develop/ui/compose/accessibility/semantics
---

# Firstlight Activity Indicator

Date: 2026-08-05

Status: approved for implementation

## Purpose and state class

`<firstlight:activity-indicator>` communicates that local work is ongoing when
no meaningful completion fraction is available. It is a compact, circular,
indeterminate display component for loading content, processing a bounded
region, or waiting for a background result.

It is separate from `<firstlight:progress>`, which owns linear determinate or
indeterminate progress. Activity Indicator is an action/display component: it
has no application value, model, accepted state, event, task lifecycle, or
PHP-owned animation.

Activity Indicator does not start work, infer whether work is active, replace
visible status copy, or track a NativePHP action automatically.

## Public API

```blade
@if ($loading)
    <firstlight:activity-indicator
        size="md"
        a11y-label="Loading appointments"
        class="my-4"
    />
@endif
```

| API | Accepted type | Behaviour |
| --- | --- | --- |
| `size` | enum string | `sm`, `md`, or `lg`; omission defaults to `md`. |
| `a11y-label` | non-empty `string` | Required accessible name and polite appearance announcement. The documented form is kebab-case; `a11yLabel` remains an accepted authoring alias. |
| `class` | `string` | External EDGE layout for the complete indicator. |

The component is self-closing and has no visible slot. It rejects `label`,
`a11y-hint`, `value`, `native:model`, `@change`, `@submit`, `@press`,
`loading`, `active`, `visible`, `disabled`, `color`, `tone`, `variant`, and
arbitrary component styling.

The indicator inherits the host semantic primary colour. Firstlight does not
expose Mobile UI's arbitrary colour override. Button and Icon Button continue
to own the contrasting indicators used by their loading states.

## Presence and empty behaviour

Presence means ongoing activity. Consumers conditionally author the component
with ordinary Blade control flow and remove it when work finishes. Firstlight
does not publish a hidden or inactive native node.

NativePHP Mobile 4 does not provide Livewire's `wire:loading` or `wire:target`
request-tracking directives for EDGE. A component-level `loading` prop would
therefore control visibility only and would misleadingly imitate automatic
request state. NativePHP's `#[Lazy]` placeholder remains available for lazy
component mounting; other asynchronous workflows expose their own explicit
loading state to Blade.

## Validation and failure behaviour

Omitted `size` publishes `md`. The authored size must be exactly `sm`, `md`,
or `lg`; legacy integers and long-form aliases such as `small` or `large` fail.
The accessibility label must be a non-empty string after trimming.

Unsupported attributes, events, invalid sizes, and invalid label types throw
actionable `InvalidArgumentException`s before publication. The native
renderers do not coerce malformed input or supply generic fallback labels.

The element publishes only `size` and `a11y_label` as component props. It has
no callback identifier, sync metadata, bridge function, or style payload.
External EDGE layout remains in the ordinary element layout payload.

## NativePHP primitive audit and path decision

Mobile UI 0.3.0 exposes `activity_indicator` with genuine SwiftUI
`ProgressView` and Material 3 `CircularProgressIndicator` renderers. It already
supports the same three semantic sizes and theme-default colour.

The installed primitive is not an adequate adapter for the approved
accessibility contract. Its Android renderer explicitly marks the indicator as
a polite live region, while its iOS renderer only attaches an accessibility
label. Firstlight requires the authored loading context to be announced once
when the indicator appears on either platform, without moving accessibility
focus or repeating on ordinary reconciliation.

Activity Indicator therefore uses one Firstlight EDGE element and paired
Firstlight renderers through the official SuperNative seam. The manifest maps
`firstlight.activity-indicator` to `ActivityIndicatorRenderer` on iOS and
`dev.firstlightui.plugins.firstlight_ui.ui.ActivityIndicatorRenderer` on
Android. It does not declare a Mobile UI adapter mapping.

## Platform expression

### iOS

iOS renders a genuine SwiftUI `ProgressView` in circular indeterminate form.
The semantic size maps to the closest supported native control size without
promising shared points or scaled Material geometry. SwiftUI and the host
theme retain responsibility for native animation, light and dark appearance,
Increased Contrast, and Reduced Motion behaviour.

The renderer exposes the authored accessibility label and posts a polite
`AccessibilityNotification.Announcement` when a newly mounted element first
appears. An identity-scoped native guard prevents body recomputation and
ordinary reconciliation from repeating the announcement. The notification
does not move VoiceOver focus.

### Android

Android renders a genuine Material 3 `CircularProgressIndicator`. Semantic
sizes map to Material-appropriate dp dimensions without promising shared pixel
geometry. Material and the host theme retain responsibility for native
animation, colour, dark appearance, contrast, and system motion policy.

The renderer exposes the authored label as its content description and marks
the newly mounted indicator as a polite live region. Stable node identity and
unchanged semantics prevent ordinary recomposition from repeating the
announcement. It exposes no click action or interactive role.

## Accessibility contract

The non-visible name is mandatory because the indicator owns no visible text.
VoiceOver and TalkBack receive the authored context rather than a generic
"Loading" fallback. Appearance produces one polite, non-interrupting
announcement per mount; the indicator never steals accessibility focus.

The component has no hint because it performs no action. It exposes no value
or percentage because progress is indeterminate. It has no interaction-target
requirement because it is display-only. Native animation follows the
platform's accessibility and motion policy; Firstlight adds no timer or custom
animation loop.

## Data flow and reconciliation

PHP validates the authored contract and publishes one ordinary EDGE element.
The native renderer reads `size` and `a11y_label`, renders the platform
indicator, and owns its animation and mount-scoped announcement locally.

Changing size or external layout reconciles presentation without emitting an
event. Re-publishing an identical mounted element does not announce again.
Removing and later re-adding the element creates a new appearance and announces
the current label once. There is no server-authoritative value because the
component has no mutable state.

## Showcase

The public showcase adds one Activity Indicator screen containing:

- all three sizes with separately composed visible descriptions;
- the default `md` size;
- a long contextual accessibility label;
- an interactive example that conditionally mounts and removes the indicator;
  and
- explanatory copy distinguishing Activity Indicator from Progress.

`ShowcaseHome` registers the exact `<firstlight:activity-indicator>` tag and a
short summary. `/captures/activity-indicator` is a separate deterministic
fixture suitable for light and dark iOS and Android captures. Navigation,
appearance, and the `/` start route remain owned by the shared showcase chrome.

## Testing and evidence

Implementation begins with failing Pest 5 contracts for the default and all
accepted sizes, required accessibility naming, rejected props and events,
self-closing Blade compilation, strict published props, external layout, and
paired manifest registration.

iOS tests cover size mapping, accessible naming, and the identity-scoped
once-per-mount announcement guard. Android tests cover Material sizing,
content-description and polite-live-region semantics, and stable recomposition
without repeated announcement state. Tests must exercise production helpers
rather than source-text assertions when a behaviour seam is available.

Development evidence also requires focused and complete package tests,
component structure and documentation gates, the complete installed showcase
suite, both platform test suites, and both consumer host builds. The component
guide, capture fixture, accessibility evidence, and constitutional review are
part of the same delivery boundary.

Simulator, emulator, screenshot, and physical-device actions require explicit
permission for their exact targets. Release remains blocked until the required
visual, VoiceOver, TalkBack, scaling, contrast, motion, offline, and device
rows are recorded. Failed or missing evidence is reported rather than waived.

## Non-goals

- Determinate or linear progress.
- Automatic tracking of NativePHP action or request lifecycles.
- Visible loading text, skeletons, placeholders, overlays, or layout.
- Consumer-authored colours, tones, tracks, stroke widths, or animation styles.
- Model binding, values, callbacks, disabled state, or task cancellation.
- Re-exporting `<native:activity-indicator>` as a second public Firstlight API.
