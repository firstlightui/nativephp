---
title: Badge component contract
description: Public API, display semantics, accessibility, and renderer decision for Firstlight Badge.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - spec/components/status-label.md
  - vendor/nativephp/mobile-ui/src/Elements/Badge.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIBadgeRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/BadgeRenderer.kt
---

# Badge Component Contract

## Purpose and state class

`<firstlight:badge>` shows a compact count or short marker associated with nearby content. It is display-only: it has no model, selection, event, disabled, loading, or validation state. A longer status belongs to Status Label.

## Public API

```blade
<firstlight:badge count="3" tone="danger" a11y-label="3 unread messages" />
<firstlight:badge label="New" tone="info" />
```

Exactly one of `count` or `label` is required. `count` is a non-negative integer; zero hides the badge and values over 99 display `99+`. A count requires a non-empty contextual `a11y-label`. `label` is a non-empty short string and is its own accessible name unless overridden. `tone` is `neutral` by default and accepts `info`, `success`, `warning`, or `danger`. `a11y-hint` is optional. `class` controls external EDGE layout only.

There is no `value`, `native:model`, event, interaction state, field state, icon, arbitrary colour, or platform escape prop. Invalid or ambiguous authoring fails before publication.

## Wire and empty behaviour

PHP normalizes either input to the primitive `label` consumed by both renderers. Count formatting is therefore identical on both platforms. Count zero publishes an empty label and both renderers produce no view or accessibility node. Server publications replace display metadata without emitting events.

## Accessibility and platform expression

The badge is static supplementary text. A visible label is its default accessible name; a numeric count requires contextual authored meaning. There is no button or live-region role.

- iOS composes short SwiftUI `Text` in a native capsule because SwiftUI's [`badge` modifier](https://developer.apple.com/documentation/swiftui/view/badge(_:)-6k2u9) is contextual rather than a standalone EDGE leaf.
- Android uses Material 3 [`Badge`](https://developer.android.com/develop/ui/compose/components/badges) directly, with semantic theme token pairs.

Both support native text scaling, RTL, light/dark appearance, increased contrast, and silent hidden-zero output.

## Renderer decision

Mobile UI 0.3.0 Badge is not an adequate adapter. It lacks neutral tone and strict diagnostics, ignores `a11y-hint`, renders missing/zero as visible `0`, and its Android implementation is a custom Box/Text composition rather than Material 3 `Badge`. Firstlight therefore uses paired renderers through the official SuperNative seam.
