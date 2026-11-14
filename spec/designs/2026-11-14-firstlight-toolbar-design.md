---
title: Firstlight Toolbar design
description: Proposed contract and native expression for a screen-level bottom toolbar of actions.
status: approved
sources:
  - spec/reference/catalogue-boundary.md
  - src/FirstlightServiceProvider.php
  - resources/ios/FirstlightUIInit.swift
  - resources/android/FirstlightUIInit.kt
---

# Firstlight Toolbar Design

Date: 2026-11-14

Status: approved by wojt on 2026-11-14; in development

## Objective

Give a screen a bottom toolbar of actions, the iOS 26 pattern seen in Notes:
a Liquid Glass group of icon actions on the leading side and one separate
primary action on the trailing side, floating over content. Android expresses
the same intent with the Material 3 floating toolbar and its paired FAB.

Nothing in Firstlight, Mobile UI 0.9 or mobile-air 4.7/4.8 does this today.
`<native:bottom-bar>` pins arbitrary content above the keyboard (a composer
slot): its glass is hand-drawn, it does not group items as the system does,
its items do not transition between screens, and Android ignores `glass` and
reserves space instead of floating.

## Catalogue boundary amendment

wojt placed this in Firstlight scope on 2026-11-14. The boundary changes from
"FABs are navigation chrome" to: a screen's bottom toolbar of actions is a
semantic action control in the catalogue. Tab bars, top bars, navigation
stacks, route shells and the standalone `<native:fab>` remain Mobile UI
chrome. The Toolbar owns no route, tab or pane.

## Public API

```blade
<firstlight:toolbar>
    <firstlight:toolbar-item icon="checklist" a11y-label="Checklist" @press="addChecklist" />
    <firstlight:toolbar-item icon="paperclip" icon-android="attach_file" a11y-label="Attach" @press="attach" />
    <firstlight:toolbar-item icon="pencil.tip.crop.circle" icon-android="draw" a11y-label="Markup" @press="markup" />
    <firstlight:toolbar-item primary icon="square.and.pencil" icon-android="edit_square" a11y-label="New note" @press="compose" />
</firstlight:toolbar>
```

| API | On | Purpose |
| --- | --- | --- |
| `icon`, `icon-ios`, `icon-android` | item | Required shared icon with platform overrides, per `spec/reference/icons.md`. |
| `a11y-label` | item | Required action name; also the iOS overflow and long-press label. |
| `a11y-hint` | item | Optional supplementary guidance. |
| `primary` | item | At most one. Separated trailing action: its own glass on iOS, the paired FAB on Android. |
| `disabled` | item | Prevents interaction and exposes native disabled state. |
| `haptic` | item | Same contract as Button and Icon Button. |
| `@press` | item | Required. Standard action event; no model, no `@change`. |

Rules, enforced in PHP with a clear exception:

- One `toolbar` per screen, authored as a top-level child of the screen root.
- One to five regular items plus at most one `primary`.
- Items have no visible text; Button stays the control for labelled actions.

## Native expression

### iOS

The `toolbar` node renders a zero-size view carrying
`.toolbar { ToolbarItemGroup(placement: .bottomBar) { … } ToolbarSpacer(.flexible, placement: .bottomBar) ToolbarItem(placement: .bottomBar) { primary } }`.
mobile-air renders screen content inside its SwiftUI `NavigationStack`
destination (`NativeRootStackRenderer.screenView`), so the toolbar preference
reaches the system bar. iOS 26 then supplies the glass group, separation,
sizing, overflow, scroll-edge effect and push transitions. iOS 18 to 25 get
the classic bottom toolbar with the same items. The bar stays behind the
keyboard, as the system bar does.

### Android

The `toolbar` node draws nothing in place. It publishes its items to a
Firstlight root host (registered next to the Feedback Center host), which
overlays a Material 3 `HorizontalFloatingToolbar` at the bottom centre, above
the system navigation insets, with the primary item in the
`floatingActionButton` slot. The host attaches
`FloatingToolbarDefaults.exitAlwaysScrollBehavior` through nested scroll on
the wrapped content, so the toolbar hides while content scrolls down and
returns on scroll up, which keeps the last rows reachable. It stays behind the
keyboard. TalkBack reads it as a toolbar of buttons.

## Requirements

- `nativephp/mobile` `^4.8` (approved by wojt for 1.1) and a native-chrome
  layout, which 4.8 makes the default. Without a `NavigationStack` iOS has no
  bottom bar to attach to, so the Toolbar is documented as requiring native
  chrome.
- No Firstlight version number changes without wojt's approval of the exact
  number.

## Decisions

1. **Shape** (approved): `<firstlight:toolbar>` with `<firstlight:toolbar-item>`
   children and one optional `primary` item.
2. **Android** (approved, route b): material3 1.4.0, which mobile-air's Compose
   BOM 2025.12.00 resolves, has `FloatingToolbarTokens` but no
   `HorizontalFloatingToolbar`. Firstlight draws the floating toolbar from
   stable parts to those tokens (64dp full-radius `surfaceContainer` pill,
   8dp ends, 4dp between actions, 8dp to a `secondaryContainer` FAB) and
   swaps in `HorizontalFloatingToolbar` once NativePHP's BOM ships it.
3. **Scope** (approved: try them): items take `menu` (instead of `@press`),
   `badge` and `badge-a11y-label`. On iOS SwiftUI turns each item into a
   `UIBarButtonItem`; menus become its native menu and `.badge` its native
   badge on iOS 26. Tab-root screens: Android floats the toolbar above the
   tab bar; iOS behaviour inside a tab is still to be checked on device.

## Evidence plan

Pest contracts for the PHP element and rules; Swift snapshot tests on the
iOS 27.0 iPhone 17 Pro simulator; Robolectric and Paparazzi on Android;
`docs/components/toolbar.md`; a Showcase screen and capture route; screenshots
at `docs/screenshots/toolbar/{ios,android}-{light,dark}.png`; then
`firstlight-review-component`.
