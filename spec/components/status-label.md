---
title: Status Label component contract
description: Current semantic, platform, accessibility, and failure contract for the display-only Firstlight Status Label.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - vendor/nativephp/mobile-ui/src/Elements/Badge.php
  - vendor/nativephp/mobile-ui/src/Elements/Chip.php
  - src/Elements/StatusLabel.php
  - resources/ios/StatusLabelControl.swift
  - resources/ios/StatusLabelRenderer.swift
  - resources/android/StatusLabelControl.kt
  - resources/android/StatusLabelRenderer.kt
  - spec/reference/icons.md
---

# Status Label Component Contract

## Purpose and state class

`<firstlight:status-label>` presents short display-only metadata or status text in a native capsule. It is an action/display component: it has no model, mutable state, event, selection, disabled state, loading state, or validation state.

Interactive capsules are outside this component's contract and require an appropriate interactive control. A compact count or overlay marker belongs to `badge`.

## Public API

```blade
<firstlight:status-label
    label="Awaiting review"
    tone="warning"
    icon="warning"
    icon-ios="exclamationmark.triangle"
    icon-android="warning"
    a11y-label="Referral status: awaiting review"
/>
```

| Prop | Contract |
| --- | --- |
| `label` | Required non-empty visible text. Whitespace-only and missing labels fail. |
| `tone` | Optional semantic tone: `neutral` (default), `info`, `success`, `warning`, or `danger`. |
| `icon` | Optional string shared fallback for a decorative leading icon. |
| `icon-ios` | Optional `IosSymbol|string|null` iOS override; `iconIos` alias accepted. |
| `icon-android` | Optional `AndroidSymbol|string|null` Android override; `iconAndroid` alias accepted. |
| `a11y-label` | Optional screen-reader replacement for the visible label. |
| `a11y-hint` | Optional supplementary screen-reader context. |
| `class` | External EDGE layout only. |

There is no `value`, `native:model`, `@change`, `@press`, `disabled`, `loading`, `error`, `required`, `helper`, or visual escape prop. Incompatible state or event attributes fail with component-specific guidance instead of being ignored.

## Empty and failure behaviour

Icons follow [the shared icon contract](../reference/icons.md). `icon(?string $name = null, IosSymbol|string|null $ios = null, AndroidSymbol|string|null $android = null)` resolves through NativePHP `IconResolver` before publication. Only resolved `icon` and, for typed Android overrides, `icon_variant` are published. Unknown platforms use the shared fallback. Absent icons remain absent; replacing or removing a fluent icon clears stale names and variants. Invalid authored icon types throw with prop-specific guidance. NativePHP's current Android `MaterialIcon` helper accepts a name but no variant font parameter: the variant is preserved on the wire and in renderer configuration, while the helper renders the resolved name.

`null`, a missing label, and an empty or whitespace-only label are invalid because the component would have no display purpose or accessible meaning. Unsupported tones and incompatible interactive or field props throw `InvalidArgumentException` before publication. No default label, tone substitution, event, or hidden state is invented.

## Accessibility

The visible label is the default accessible name. `a11y-label` replaces that name and `a11y-hint` adds context. Both platforms expose static text semantics only: no button, selected, disabled, or live-region role. Text scales without truncating meaning, and the capsule expands or wraps for long labels and accessibility text sizes.

Optional leading icons are decorative and explicitly hidden from accessibility. SwiftUI `Image(systemName:)` and Android `MaterialIcon` inherit exactly the text's contrast-safe foreground. Native horizontal layout mirrors placement in RTL. Text-only initializers remain valid via default-empty icon arguments.

## Platform expression

- iOS composes SwiftUI `Text` with native font scaling and VoiceOver text semantics. Single-line labels retain a pill background; wrapped labels cap the corner radius at half the scaled single-line height plus vertical padding, keeping every line on the contrast-safe fill.
- Android composes Material 3 `Text` in a capsule-shaped `Surface` with merged TalkBack semantics and no click action.

Both renderers inherit NativePHP semantic theme tokens. Tone maps to neutral surface, primary informational, success, warning/on-warning, or destructive colour pairs. PHP resolves warning tokens through `FirstlightUI\Support\WarningColors::props()` into `warning_color`, `on_warning_color`, `dark_warning_color`, and `dark_on_warning_color`; native fixed token structs do not retain custom keys. Missing effective warning tokens fall back to `#B45309` / `#FFFFFF` in light appearance and `#FBBF24` / `#1C1917` in dark appearance, shared with Callout and Feedback. If a themed foreground/background pair is below 4.5:1 contrast, the renderer selects black or white text with the stronger ratio. Light, dark, increased-contrast, Dynamic Type/font-scale, RTL, and long-label behaviour remain native.

## Official primitive decision

This is a paired-renderer component. NativePHP `badge` is an overlay/count marker with a destructive default and cannot represent the neutral status treatment. NativePHP `chip` is interactive and model-bound. Adapting either would leak the wrong semantics or omit an applicable tone.
