---
title: List Item component contract
description: Public row content, action, icon, accessibility, failure, and paired-renderer contract for Firstlight List Item.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/reference/icons.md
  - nativephp.json
  - vendor/nativephp/mobile-ui/src/Elements/ListItem.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIListItemRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/ListItemRenderer.kt
---

# List Item Component Contract

## Purpose and state class

`<firstlight:list-item>` is one tappable application row with a headline,
optional supporting text, optional leading identity, and optional trailing
affordance. It is an action/display component: PHP owns the action outcome and
published metadata while native code owns transient press feedback.

List Item is not a generic row layout, collection container, selectable row,
navigation destination, or host for arbitrary controls. List and List Section
may later arrange List Items without expanding this child contract.

## Public API

```blade
<firstlight:list-item
    headline="Account"
    supporting="Manage your profile and security"
    leading-icon="person"
    leading-icon-ios="person.crop.circle"
    leading-icon-android="account_circle"
    trailing-icon="chevron-right"
    trailing-icon-ios="chevron.right"
    trailing-icon-android="chevron_right"
    a11y-hint="Opens account settings"
    :disabled="! $canManageAccount"
    @press="openAccount"
/>
```

| Prop or event | Contract |
| --- | --- |
| `headline` | Required non-empty primary text and default accessible name. |
| `supporting` | Optional non-empty secondary text. |
| `leading-icon` | Optional non-empty shared icon name. Mutually exclusive with other leading content. |
| `leading-icon-ios` | Optional `IosSymbol|string` override; requires `leading-icon`. |
| `leading-icon-android` | Optional `AndroidSymbol|string` override; requires `leading-icon`; typed variants are preserved. |
| `leading-avatar` | Optional non-empty image source representing the row subject. Mutually exclusive with other leading content. |
| `leading-monogram` | Optional one- or two-grapheme identity marker. Mutually exclusive with other leading content. |
| `trailing-icon` | Optional non-empty shared decorative affordance icon. Mutually exclusive with `trailing-text`. |
| `trailing-icon-ios` | Optional `IosSymbol|string` override; requires `trailing-icon`. |
| `trailing-icon-android` | Optional `AndroidSymbol|string` override; requires `trailing-icon`; typed variants are preserved. |
| `trailing-text` | Optional non-empty short metadata or affordance text. Mutually exclusive with `trailing-icon`. |
| `disabled` | Prevents presses and exposes native disabled semantics. Defaults to `false`. |
| `a11y-label` | Optional replacement for the row's combined visible accessible name. |
| `a11y-hint` | Optional supplementary screen-reader context. |
| `@press` | Required standard NativePHP action callback. |
| `class` | External EDGE layout only. |

Blade documentation uses kebab-case. PHP attribute adaptation also accepts the
matching camelCase aliases. The fluent icon methods retain NativePHP's shared
argument order and types:

```php
->leadingIcon(
    ?string $name = null,
    IosSymbol|string|null $ios = null,
    AndroidSymbol|string|null $android = null,
)
->trailingIcon(
    ?string $name = null,
    IosSymbol|string|null $ios = null,
    AndroidSymbol|string|null $android = null,
)
```

## Empty, action, and failure behaviour

Supporting text and both content edges may be absent. The component then
publishes one ordinary headline-only row. Optional values that are authored
must be non-empty; Firstlight does not render ambiguous empty slots.

A user press emits once. Programmatic publication never emits. A disabled row
does not emit and remains visibly and accessibly disabled. The row has no
model, selected value, loading state, change event, or optimistic application
state.

Missing or blank `headline`, a missing press callback, non-string text, a
non-boolean `disabled`, invalid icon override types, icon overrides without a
shared fallback, multiple leading sources, multiple trailing affordances, and
unsupported APIs fail with actionable `InvalidArgumentException` messages.
Unsupported APIs include overline text, embedded controls, secondary actions,
swipe actions, menus, long press, navigation directives, selection, field
state, arbitrary colours, elevation, typography, and child content.

## Accessibility

The headline and supporting text form the default accessible row name.
`a11y-label` replaces that combined name and `a11y-hint` adds context. The row
is one native button node. Disabled state is exposed through native semantics.
Its interaction target is at least 44 points on iOS and 48 dp on Android.

Leading avatar, monogram, and icons and the trailing icon are decorative inside
that node. They remain silent to VoiceOver and TalkBack. List Item has no
independent icon-only action; consumers use a separate Icon Button rather than
nesting a second target inside the row.

## Platform expression

- iOS composes a SwiftUI `Button` row with Apple-native headline/supporting
  typography, optional avatar, monogram, or symbol, optional trailing metadata
  or symbol, native pressed and disabled behaviour, and a 44-point minimum.
- Android uses Material 3 `ListItem` inside one enabled-aware clickable surface,
  with native headline/supporting typography, content slots, ripple and disabled
  semantics, and a 48-dp minimum.

Shared hierarchy and action meaning are identical while geometry, typography,
pressed state, and spacing remain native to each platform.

## Primitive audit and paired-renderer decision

Mobile UI 0.3.0 provides a broad `list_item` primitive with genuine SwiftUI
and Material 3 row composition. Its Android renderer correctly disables the
row click target. Its iOS renderer only reduces opacity when `disabled`; the
shared `applyClickHandlers` modifier still dispatches the row callback and does
not expose disabled accessibility state. The primitive also exposes embedded
selection controls, independent trailing actions, menus, swipes, colours, and
elevation outside this contract.

An adapter therefore cannot meet the required disabled action and accessibility
semantics equally. Firstlight uses paired renderers through the official
SuperNative manifest seam while retaining the upstream component's adequate
native row composition. No WebView, parallel bridge, or general layout escape
API is introduced.

## Evidence boundary

Development evidence requires the strict Pest contract, manifest registration,
iOS behavior/accessibility/snapshot tests, Android behavior/semantics/Paparazzi
tests, full package gates, the exact-revision sibling showcase fixture, public
documentation, screenshot manifest registration, and constitutional review.

Simulator, emulator, screenshot, VoiceOver, TalkBack, and dated physical-device
evidence require explicit target permission. Missing runtime evidence remains
a release blocker and is never inferred from snapshots or source inspection.
