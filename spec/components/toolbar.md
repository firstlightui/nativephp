---
title: Toolbar component contract
description: Public API, native expression, Android floating toolbar construction, and unverified behaviour for Firstlight Toolbar and Toolbar Item.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/reference/catalogue-boundary.md
  - spec/reference/icons.md
  - spec/designs/2026-11-14-firstlight-toolbar-design.md
  - src/Elements/Toolbar.php
  - src/Elements/ToolbarItem.php
  - resources/ios/ToolbarControl.swift
  - resources/android/ToolbarRenderer.kt
  - resources/android/ToolbarControl.kt
---

# Toolbar Component Contract

## Purpose and state class

`<firstlight:toolbar>` holds a screen's bottom row of icon actions; each
`<firstlight:toolbar-item>` is one action, menu, or the one separate primary
action. Both are action components: no model, no `@change`, PHP owns every
outcome. The catalogue boundary admits a screen's bottom toolbar of actions;
tab bars, top bars, navigation stacks and `<native:fab>` stay Mobile UI chrome.

## Public API

Toolbar takes no props and one to five regular items plus at most one
`primary` item. Toolbar Item takes `icon` with `-ios` / `-android` overrides,
`a11y-label` (required), `primary`, `disabled`, `badge`, `haptic`, and exactly
one of `@press` or `menu`. Menu
entries take `method`, `label`, `icon`, `ios`, `android`, `role`
(`destructive`) and `disabled`; disabled entries register no callback.

Wire: `firstlight.toolbar` publishes no props and a zero-size absolute layout.
`firstlight.toolbar-item` publishes `icon`, `icon_variant`, `primary`,
`disabled`, `a11y_label`, `haptic`, `badge` (omitted when empty), and
`menu_json`.

## Accessibility parity

Props stay equal across platforms, so the toolbar offers only what iOS bar
buttons carry. SwiftUI turns each item into a `UIBarButtonItem`; on the iOS 27
simulator the item had `title` (the `a11y-label`, which VoiceOver reads),
`isEnabled`, `menu`, and on iOS 26+ `badge`, while its `accessibilityLabel`,
`accessibilityHint` and `accessibilityValue` were nil: SwiftUI drops view-level
hint and value modifiers. In the Showcase app the system accessibility tree
exposes a badged item as the label with the value "3 items", so the badge
reaches VoiceOver without a Firstlight label. So Toolbar has no accessible name, and Toolbar Item
rejects `a11y-hint` and `badge-a11y-label` (wojt, 2026-11-14). Android reads
the label as the content description and the badge text as state.

## iOS expression

The Toolbar node renders a zero-size view carrying `.toolbar` items with
`.bottomBar` placement. mobile-air renders screens inside a SwiftUI
`NavigationStack` destination, so the items reach that stack's bottom bar.
iOS 26: one `ToolbarItem` per action (so each carries the view-level `.badge`,
which becomes `UIBarButtonItem.badge`), then `ToolbarSpacer(.flexible)` and the
primary item. Earlier iOS: one `ToolbarItemGroup` with a `Spacer` before the
primary item; badges are not drawn. A menu item is a SwiftUI `Menu`, which
becomes the bar item's `menu`. `ToolbarRendererTests` checks the
`UIBarButtonItem`s, menu and badge in a hosted `NavigationStack`.
SwiftUI keeps a bar button whose `ForEach` identity is unchanged, with its old
badge and enabled state, so items are keyed by their whole configuration and a
republished tree replaces them; `testBarItemsFollowARepublishedTree` covers
it.

mobile-air hides the tab bar on pushed tab levels, so the bottom toolbar shows
there. A tab root keeps the tab bar, which covers the bottom toolbar; the screen
must set `$hidesTabBar`, which mobile-air publishes as `hide_tab_bar`.

## Android expression

The Toolbar renderer draws nothing and publishes its configuration to
`FirstlightToolbarRegistry`; the newest live Toolbar wins, so a screen leaving
during a transition does not override the arriving one. The
`firstlight.toolbar` root host floats the toolbar bottom-centre above the
navigation bar inset, 16dp up, or 96dp on a `native_root_tabs` root that
shows its tab bar (no `hide_tab_bar`) to clear it. A nested-scroll connection hides it while content scrolls towards
its end and shows it on scrolling back.

material3 1.4.0, which NativePHP's Compose BOM 2025.12.00 resolves, has
`FloatingToolbarTokens` but no `HorizontalFloatingToolbar`. Firstlight draws it
to those tokens: a 64dp full-radius `surfaceContainer` pill with 8dp ends and
4dp between `IconButton`s, and 8dp to a `primary`
`FloatingActionButton` for the primary item. Replace it with
`HorizontalFloatingToolbar` once NativePHP's BOM ships it. The host is
registered before Feedback Center so snackbars draw above the toolbar.

## Not yet verified

Verified on 2026-11-14 in Showcase on an iPhone 17 Pro simulator (iOS 27.0):
pushed and tab-root screens with `$hidesTabBar`, every action and menu entry,
a disabled menu entry, and a badge and disabled state updated by PHP. The
same checks, plus scroll hiding and the 96dp offset above a shown tab bar,
passed in Showcase on an Android 17 (API 37) emulator.

- VoiceOver and TalkBack speaking the items; only accessibility trees were
  read. On Android the clickable item carries the badge as
  `stateDescription` and merges the icon's `contentDescription`, so a
  `uiautomator` dump shows an empty `content-desc` on it.
- iOS and Android with the software keyboard up.
- A Toolbar nested inside a lazy list or scroll view, which may not stay
  rendered; the public docs ask for a direct child of the screen root.
