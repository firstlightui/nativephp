---
title: Icon Button component contract
description: Public API, state, icon resolution, native expression, and renderer decision for Firstlight Icon Button.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/reference/icons.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - spec/components/button.md
  - vendor/nativephp/mobile-ui/src/Elements/Button.php
  - vendor/nativephp/mobile-ui/src/Elements/Icon.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIButtonRenderer.swift
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIIconRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/ButtonRenderer.kt
  - vendor/nativephp/mobile-ui/resources/android/IconRenderer.kt
---

# Icon Button Component Contract

## Purpose and state class

`<firstlight:icon-button>` performs one immediate compact action represented by
an icon. It is an action/display component: PHP owns the action outcome while
native code owns transient press feedback and loading presentation.

Icon Button is not a decorative icon, toggle, menu trigger, navigation link,
or labelled Button. Decorative icons use the official Icon primitive; labelled
actions use `<firstlight:button>`.

## Public API

```blade
<firstlight:icon-button
    icon="plus"
    icon-ios="plus"
    icon-android="add"
    a11y-label="Add item"
    variant="primary"
    size="md"
    :loading="$adding"
    :disabled="! $canAdd"
    @press="addItem"
/>
```

| Prop or event | Contract |
| --- | --- |
| `icon` | Required non-empty shared icon name and platform-neutral fallback. |
| `icon-ios` | Optional `IosSymbol|string` iOS override. |
| `icon-android` | Optional `AndroidSymbol|string` Android override; typed variants are preserved. |
| `a11y-label` | Required non-empty accessible action name. It is never derived from the icon name. |
| `a11y-hint` | Optional supplementary screen-reader context. |
| `variant` | `primary` (default), `secondary`, `destructive`, `success`, or `ghost`. |
| `size` | `sm`, `md` (default), or `lg`; the interaction target never shrinks below platform minimums. |
| `disabled` | Prevents presses and applies native disabled semantics. Defaults to `false`. |
| `loading` | Replaces the glyph with native progress, announces loading, and prevents duplicate presses. Defaults to `false`. |
| `@press` | Required standard NativePHP action callback. |
| `class` | External EDGE layout only. |

Blade documentation uses kebab-case icon overrides. The PHP adapter also
accepts `iconIos` and `iconAndroid`, and its fluent API follows NativePHP's
established argument order and types:

```php
->icon(
    ?string $name = null,
    IosSymbol|string|null $ios = null,
    AndroidSymbol|string|null $android = null,
)
```

The base icon is required even when overrides are supplied. This guarantees a
deterministic unknown-platform fallback for tests and tooling and ensures the
same authored component always has a glyph on both supported platforms.

## State and failure behaviour

A user press emits once. Programmatic publication never emits. Disabled and
loading controls do not emit; loading preserves the action's accessible name
while adding a native loading state. The control has no application value,
model, or change event.

Missing, null, non-string, empty, or whitespace-only `icon` and `a11y-label`
values fail with actionable `InvalidArgumentException` messages. `disabled`
and `loading` require real booleans. Unsupported variants, sizes, icon override
types, a missing press callback, slot or label text, attached menus, navigation,
extra gesture callbacks, and per-control visual escape props fail before
publication. Firstlight never substitutes an unrelated glyph.

## Accessibility

The icon is decorative inside one native button accessibility node. The
required `a11y-label` names that action and `a11y-hint` supplies optional
context. Native semantics expose button role, disabled state, loading state,
focus, press feedback, contrast, and a target of at least 44 points on iOS or
48 dp on Android. Glyph scaling never reduces that interaction target.

## Platform expression

- iOS uses a genuine SwiftUI `Button` containing an icon-only `Label` or
  `Image`, Apple-native button styles, semantic theme tint, control sizing,
  progress presentation, and an explicit 44-point minimum target.
- Android uses the Material 3 Icon Button family: `IconButton` for `ghost`,
  `FilledTonalIconButton` for `secondary`, and `FilledIconButton` for primary,
  destructive, and success intent. It preserves Material pressed/disabled
  states and a minimum 48-dp target.

Both platforms resolve authored icon choices in PHP. Renderers consume only
the resolved `icon` and optional `icon_variant` primitive props.

## Renderer decision

NativePHP Mobile UI 0.3.0 has no standalone icon-button manifest component.
Its Button accepts an icon without visible text and supplies disabled/loading
state, but renders the padded labelled Button family on Android and does not
guarantee the compact icon-only geometry and minimum target on every iOS style.
Its standalone Icon has explicit press targets, but lacks disabled/loading
state and does not use the platform Icon Button control family. List Item's
trailing Icon Button is inseparable from row semantics.

No official primitive therefore meets the complete contract. Firstlight uses
the renderer path through ordinary SuperNative component manifest entries and
paired package-owned native renderers. There is no WebView or parallel bridge.

## Evidence plan

- Pest 5 proves strict defaults, required icon/label/press, callback
  registration, every variant and size, booleans, icon fallback and overrides,
  Android variant preservation, camel aliases, layout-only classes, excluded
  APIs, public tag compilation, and exact manifest identifiers.
- XCTest proves prop decoding, disabled/loading suppression, accessibility
  label/hint/value, button trait, 44-point targeting, semantic style mapping,
  and light/dark/accessibility-size snapshots.
- Kotlin tests prove the same renderer contract, Material Icon Button family
  mapping, TalkBack semantics, Android icon variants, 48-dp targeting, font
  scale `2.0`, and Paparazzi states.
- The showcase provides interactive default, variant, size, disabled, loading,
  platform-override, rapid-press, and programmatic-state fixtures plus an
  isolated `/captures/icon-button` route.
- Development review requires focused and full package tests, both native unit
  suites, focused and full showcase tests, plugin validation, consumer host
  builds where available, and separate controller-authorized screenshots.

Physical-device accessibility and interaction rows remain component-release
evidence and do not block a truthful development verdict.
