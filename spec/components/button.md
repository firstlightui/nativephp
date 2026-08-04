---
title: Button component contract
description: Current semantic, adapter, accessibility, state, and failure contract for the Firstlight Button.
status: current
audience: maintainer
sources:
  - Constitution.md
  - nativephp.json
  - src/Components/Button.php
  - src/Elements/Button.php
  - tests/Feature/ButtonElementTest.php
  - vendor/nativephp/mobile-ui/src/Components/Button.php
  - vendor/nativephp/mobile-ui/src/Elements/Button.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIButtonRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/ButtonRenderer.kt
---

# Button Component Contract

## Purpose and state class

`<firstlight:button>` performs one immediate, labelled action. It is an action/display component: PHP owns the action outcome, while the native control owns only transient press feedback and loading presentation.

Firstlight includes Button because consumers reasonably expect a coherent UI library to provide its foundational action control under the same namespace and semantic rules as the rest of the catalogue. The current implementation deliberately wraps the adequate `nativephp/mobile-ui` Button rather than duplicating its mature SwiftUI and Material 3 renderers.

## Public API

```blade
<firstlight:button
    variant="primary"
    size="md"
    icon="check"
    :loading="$saving"
    :disabled="! $canSave"
    @press="save"
>
    Save changes
</firstlight:button>
```

| Prop or event | Contract |
| --- | --- |
| Text slot / `label` | Required non-empty visible text. Use the slot for ordinary Blade authoring. |
| `variant` | `primary` (default), `secondary`, `destructive`, `success`, or `ghost`. |
| `size` | `sm`, `md` (default), or `lg`. |
| `disabled` | Prevents presses and applies native disabled semantics. Defaults to `false`. |
| `loading` | Replaces ordinary content with native progress presentation and prevents presses. Defaults to `false`. |
| `icon` | Optional leading cross-platform icon name. |
| `icon-trailing` | Optional trailing cross-platform icon name. |
| `a11y-label` | Optional screen-reader replacement for the visible label. |
| `a11y-hint` | Optional supplementary screen-reader context. |
| `@press` | Emits the action through NativePHP's official press callback seam. |
| `class` | External EDGE layout only. |

Icon-only actions are excluded and belong to Icon Button. Attached menus, custom font and line-height props, Liquid Glass classes, navigation directives, field props or bindings, long press, double tap, press-down, and press-up callbacks are not part of the Firstlight Button contract.

## State and failure behaviour

A press emits immediately. The button does not optimistically change application state. PHP performs the action and publishes any subsequent disabled, loading, label, or layout state. Programmatic publications never emit a press.

Disabled and loading buttons do not emit. Both state props require real booleans. A missing, `null`, empty, or whitespace-only label throws `InvalidArgumentException`. Unsupported variants, sizes, field semantics, and excluded Mobile UI escape attributes also fail before publication instead of falling through to renderer defaults.

## Accessibility

The visible label is the default accessible name. `a11y-label` replaces it and `a11y-hint` adds context. Disabled and loading states remain native; Android additionally announces a loading state description. The renderer owns native press feedback, focus, Dynamic Type or font scaling, contrast, and platform target sizing.

## Platform expression

- iOS delegates to the official Mobile UI SwiftUI `Button` renderer with native control sizing, button styles, disabled behaviour, progress presentation, icons, VoiceOver metadata, and semantic theme tokens.
- Android delegates to the official Mobile UI Material 3 `Button`, `FilledTonalButton`, or `TextButton` renderer with native state layers, disabled behaviour, progress presentation, icons, TalkBack metadata, and semantic theme tokens.

`destructive` currently expresses visual and product intent through the shared semantic variant. The adapter does not add a separate Apple-only `ButtonRole` prop.

## Adapter decision and exit criteria

The official primitive expresses every current Firstlight prop, event, state, diagnostic boundary, and accessibility semantic, so Button uses the adapter path declared in `nativephp.json`. The public Element Tree type remains `firstlight.button`; consumers do not author `<native:button>` as a second Firstlight API.

Firstlight may replace the delegated renderer identifiers with package-owned iOS and Android renderers without changing the public tag when a new, durable, cross-platform semantic requirement cannot be expressed by the official primitive. Platform-only novelty, visual preference, speculative future functionality, or component-count optics are not sufficient reasons to fork it. Prefer contributing generally useful gaps, such as richer semantic action roles, upstream when they fit Mobile UI.
