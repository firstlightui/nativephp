---
title: Callout component contract
description: Current semantic, platform, accessibility, and failure contract for the persistent Firstlight Callout.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - vendor/nativephp/mobile-ui/nativephp.json
---

# Callout Component Contract

## Purpose and state class

`<firstlight:callout>` presents a persistent inline semantic message with one optional labelled action. It is an action/display component: it has no model, change event, dismissal lifecycle, timeout, queue, loading state, or validation state.

Callout is for information that must remain visible in the authored layout until the server publishes a tree without it. Brief queued outcomes belong to Transient Feedback; confirmation belongs to Confirmation Dialog; compact metadata belongs to Status Label.

## Public API

```blade
<firstlight:callout
    message="Your changes have not been submitted."
    tone="warning"
    action-label="Review changes"
    @press="reviewChanges"
/>
```

| Prop or event | Contract |
| --- | --- |
| `message` | Required non-empty visible text. Missing, empty, and whitespace-only messages fail. |
| `tone` | `neutral`, `info` (default), `success`, `warning`, or `danger`. |
| `action-label` | Optional non-empty visible action label; requires `@press`. Camel-case `actionLabel` is accepted programmatically. |
| `@press` | Optional standard action callback; requires `action-label` and fires once per completed activation. |
| `a11y-label` | Optional replacement for the message's generated accessible name. |
| `a11y-hint` | Optional supplementary context for the message. |
| `class` | External EDGE layout only. |

There is no `value`, `native:model`, `@change`, dismissal prop or event, title, icon override, disabled, loading, error, helper, required, or visual escape prop. The renderer owns the tone icon because it is a semantic affordance.

## Empty and failure behaviour

An invalid message or tone throws `InvalidArgumentException` before publication. `action-label` and `@press` must either both be absent or both be present; an incomplete action fails rather than rendering inert or inaccessible UI. Unexpected malformed native data falls back to the informational tone and suppresses an incomplete action without crashing the host.

## Accessibility

Tone is never communicated by colour alone. Each renderer uses a distinct semantic symbol and prefixes the default accessible message with the tone meaning. `a11y-label` replaces that generated name, while `a11y-hint` adds context. The icon is decorative to assistive technology. The optional action remains a separate native button with the visible label as its accessible name and meets the 44-point iOS and 48-dp Android target baselines.

Text wraps and the surface grows for Dynamic Type or font scale. Neither renderer creates a live region because persistence does not imply an interruption announcement.

## Platform expression

- iOS composes SwiftUI `Image`, `Text`, and `Button` in a rounded system surface with native typography and VoiceOver containment.
- Android composes Material 3 `Surface`, `Icon`, `Text`, and `TextButton` with Material typography and TalkBack semantics.

Both renderers inherit NativePHP semantic theme tokens, preserve dark mode and increased contrast, reconcile message/tone/action metadata by stable node identity, and emit no event for programmatic changes.

## Official primitive decision

This is a paired-renderer component. Installed `nativephp/mobile-ui` 0.3.0 has no callout, banner, alert, or snackbar element. Its primitives can compose a surface visually but cannot expose a single semantic component, one action callback, tone-owned iconography, or the required accessibility contract through an unchanged official element.
