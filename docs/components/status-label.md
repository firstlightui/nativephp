---
title: Status Label
description: Public API, tones, accessibility, validation, compatibility, and visual evidence for Firstlight Status Label.
type: reference
audience: consumer
sources:
  - src/Components/StatusLabel.php
  - src/Elements/StatusLabel.php
  - resources/ios/StatusLabelControl.swift
  - resources/ios/StatusLabelRenderer.swift
  - resources/android/StatusLabelControl.kt
  - resources/android/StatusLabelRenderer.kt
  - tests/Feature/StatusLabelElementTest.php
  - tests/ios/StatusLabelSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/StatusLabelTest.kt
---

# Status Label

Status Label presents short display-only metadata or status text as a native capsule. Use an appropriate interactive control when the interface must respond to a press.

## Complete example

```blade
<firstlight:status-label
    label="Awaiting review"
    tone="warning"
    icon="warning"
    icon-ios="exclamationmark.triangle"
    icon-android="warning"
    a11y-label="Referral status: awaiting review"
    a11y-hint="Updated by the referrals team"
/>
```

The same authored tag renders on iOS and Android.

## Props

| API | Accepted type | Purpose |
| --- | --- | --- |
| `label` | non-empty `string` | Visible status text. Required. |
| `tone` | `neutral`, `info`, `success`, `warning`, or `danger` | Semantic colour intent. Defaults to `neutral`. |
| `icon` | `string` or `null` | Optional decorative leading icon; shared fallback. |
| `icon-ios` | `IosSymbol`, `string`, or `null` | iOS override of `icon`. |
| `icon-android` | `AndroidSymbol`, `string`, or `null` | Android override of `icon`. |
| `a11y-label` | `string` | Replaces the visible text as the accessible name. |
| `a11y-hint` | `string` | Adds supplementary VoiceOver or TalkBack context. |
| `class` | `string` | External EDGE layout utilities. |

## Icons

Icons use NativePHP's `Native\Mobile\Icon\IconResolver`. PHP also accepts `iconIos` and `iconAndroid` aliases. The fluent API is `StatusLabel::make('Warning')->icon('warning', ios: 'exclamationmark.triangle', android: 'warning')`; typed symbols implement `Native\Mobile\Icon\IosSymbol` or `AndroidSymbol`. Each platform uses its override when supplied, otherwise the shared name; tooling with no platform uses the shared name. Omitting icons keeps the text-only presentation. Android typed variants are retained in the published props; NativePHP's current Material icon-font helper renders the resolved name, not a separate variant font.

## Events and values

Status Label has no value, options, model binding, or events. It is static text from the user's perspective. A later server publication may replace its label, tone, or accessibility metadata without producing a callback.

Do not use `native:model`, `@change`, `@press`, `value`, `disabled`, `loading`, `error`, `required`, or `helper`. Firstlight rejects those attributes instead of silently adding interactive or field semantics.

## State timing

The component renders the latest published Element Tree metadata. It keeps no native selection, editing buffer, pending proposal, or animation state. Programmatic updates reconcile by the element's stable node ID and do not emit events.

## Disabled, loading, and error behaviour

Disabled, loading, and validation states do not apply to display-only status text. Choose the semantic `tone` that describes the current metadata and publish a new label or tone when that status changes.

## Accessibility

The visible `label` is the accessible name by default. `a11y-label` replaces that name, and `a11y-hint` adds context. Both renderers expose static text semantics without a button, selected, disabled, or live-region role.

The optional icon is decorative and silent to VoiceOver and TalkBack. It uses the same contrast-checked tone foreground as the text in light and dark appearances; it never replaces the required label. Leading placement follows the native layout direction.

Text follows Dynamic Type on iOS and system font scaling on Android. Long labels and accessibility sizes expand or wrap rather than truncating their meaning. Semantic theme colours are checked at render time; when a customised foreground and background are below 4.5:1 contrast, Firstlight uses whichever of black or white has the stronger ratio.

## Validation and failure behaviour

A missing, `null`, empty, or whitespace-only `label` throws an `InvalidArgumentException`. An unsupported tone also throws and lists the accepted values. Malformed tone data that reaches a native renderer unexpectedly falls back defensively to `neutral` instead of crashing the host.

Non-string shared icons and platform overrides other than strings, matching platform symbol types, or `null` throw with the public prop name.

## Platform behaviour

iOS composes SwiftUI `Text` with native text accessibility. A single line keeps its pill shape; for wrapped labels, the corner radius is capped at half the scaled single-line height plus vertical padding so all text remains on the contrasting background. Android composes Material 3 `Text` in a capsule-shaped `Surface` with merged TalkBack semantics. Both use NativePHP semantic theme tokens while retaining platform-native typography, scaling, colour-scheme, and layout behaviour.

## Compatibility

Status Label supports the versions listed in the current [compatibility reference](../reference/compatibility.md) and requires both native renderers to be compiled into the host application.

## Screenshots

These approved native captures use iPhone 17 Pro / iOS 26.5 and Pixel 9 Pro /
Android 16 API 36. Draft retains the optional text-only presentation; the other
tones include decorative platform icons.

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | ![Status Label on iOS in light mode](../screenshots/status-label/ios-light.png) | ![Status Label on iOS in dark mode](../screenshots/status-label/ios-dark.png) |
| Android | ![Status Label on Android in light mode](../screenshots/status-label/android-light.png) | ![Status Label on Android in dark mode](../screenshots/status-label/android-dark.png) |

Long labels wrap within their fill at maximum iOS accessibility text size and
Android font scale 2.0. The interactive showcase is scrolled to show the complete
long label below.

| iOS maximum accessibility text | Android font scale 2.0 |
| --- | --- |
| ![Status Label with a wrapped long label on iOS](../screenshots/status-label/ios-largest-text.png) | ![Status Label with a wrapped long label on Android](../screenshots/status-label/android-largest-text.png) |

These are simulator/emulator visual checks, not spoken VoiceOver/TalkBack or
physical-device release qualification.
