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
---

# Status Label Component Contract

## Purpose and state class

`<firstlight:status-label>` presents short display-only metadata or status text in a native capsule. It is an action/display component: it has no model, mutable state, event, selection, disabled state, loading state, or validation state.

An interactive capsule belongs to `pill-group`. A compact count or overlay marker belongs to `badge`.

## Public API

```blade
<firstlight:status-label
    label="Awaiting review"
    tone="warning"
    a11y-label="Referral status: awaiting review"
/>
```

| Prop | Contract |
| --- | --- |
| `label` | Required non-empty visible text. Whitespace-only and missing labels fail. |
| `tone` | Optional semantic tone: `neutral` (default), `info`, `success`, `warning`, or `danger`. |
| `a11y-label` | Optional screen-reader replacement for the visible label. |
| `a11y-hint` | Optional supplementary screen-reader context. |
| `class` | External EDGE layout only. |

There is no `value`, `native:model`, `@change`, `@press`, `disabled`, `loading`, `error`, `required`, `helper`, or visual escape prop. Incompatible state or event attributes fail with component-specific guidance instead of being ignored.

## Empty and failure behaviour

`null`, a missing label, and an empty or whitespace-only label are invalid because the component would have no display purpose or accessible meaning. Unsupported tones and incompatible interactive or field props throw `InvalidArgumentException` before publication. No default label, tone substitution, event, or hidden state is invented.

## Accessibility

The visible label is the default accessible name. `a11y-label` replaces that name and `a11y-hint` adds context. Both platforms expose static text semantics only: no button, selected, disabled, or live-region role. Text scales without truncating meaning, and the capsule expands or wraps for long labels and accessibility text sizes.

## Platform expression

- iOS composes SwiftUI `Text` with native font scaling, a `Capsule` background, and VoiceOver text semantics.
- Android composes Material 3 `Text` in a capsule-shaped `Surface` with merged TalkBack semantics and no click action.

Both renderers inherit NativePHP semantic theme tokens. Tone maps to neutral surface, primary informational, success, accent warning, or destructive colour pairs. If a themed foreground/background pair is below 4.5:1 contrast, the renderer selects black or white text with the stronger ratio. Light, dark, increased-contrast, Dynamic Type/font-scale, RTL, and long-label behaviour remain native.

## Official primitive decision

This is a paired-renderer component. NativePHP `badge` is an overlay/count marker with a destructive default and cannot represent the neutral status treatment. NativePHP `chip` is interactive and model-bound. Adapting either would leak the wrong semantics or omit an applicable tone.
