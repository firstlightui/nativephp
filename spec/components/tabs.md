---
title: Tabs component contract
description: Current section-switching, value, badge, accessibility, platform, and failure contract for Firstlight Tabs.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - spec/reference/catalogue-boundary.md
  - src/Elements/Segmented.php
  - vendor/nativephp/mobile-ui/src/Elements/TabRow.php
  - vendor/nativephp/mobile-ui/src/Elements/Tab.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUITabRowRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/TabRowRenderer.kt
---

# Tabs Component Contract

## Purpose and state class

`<firstlight:tabs>` switches between sections of the same object within one screen, such as a server's Overview, Sites, Deployments, and Manage. It is a discrete, server-authoritative choice control whose value names the visible section.

Tabs draws only the section selector. PHP renders the active pane with ordinary Blade conditionals, so inactive sections perform no work until selected. Segmented chooses a value or filter for the current content; Tabs chooses which content is shown and carries tab semantics.

## Catalogue boundary

Tabs is a semantic control, not navigation chrome. It owns no route, navigation stack, pane container, top bar, pager, or swipe gesture. Bottom tab bars, top app bars, and navigation stacks remain Mobile UI. Pinning the selector beneath a top bar is layout and remains the application's responsibility.

## Public API

```blade
<firstlight:tabs
    native:model="tab"
    :tabs="['overview' => 'Overview', 'sites' => 'Sites', 'deploys' => 'Deployments', 'manage' => 'Manage']"
    label="Server sections"
/>

@if ($tab === 'overview') … @elseif ($tab === 'sites') … @endif
```

Rich tabs add counts and per-tab availability:

```php
[
    ['value' => 'overview', 'label' => 'Overview'],
    ['value' => 'deploys', 'label' => 'Deployments', 'badge' => 2, 'badge-a11y-label' => '2 failed'],
    ['value' => 'manage', 'label' => 'Manage', 'disabled' => true],
]
```

| Prop | Contract |
| --- | --- |
| `tabs` | A value-to-label map, or a list of rich tabs with `value`, `label`, and optional `badge`, `badge-a11y-label` (alias `badgeA11yLabel`), and `disabled`. |
| `value` / `native:model` | Stable string tab value, or `null` for no selection. |
| `label` | Accessible name of the tab group. Tabs draws no visible group label. |
| `disabled` | Disables every tab. |
| `a11y-label` | Overrides `label` as the group's accessible name. |
| `a11y-hint` | Supplementary VoiceOver and TalkBack guidance for the group. |
| `class` | External EDGE layout only. |

Tabs has no `options`, `helper`, `error`, `required`, `multiple`, icon, variant, scrollable, or platform-widget prop.

## Values and tabs

Tab values are non-empty strings and are unique. Integer values, including PHP's integer conversion of numeric-string map keys such as `'1'`, are rejected so routes, notifications, and query state carry stable words. Labels are non-blank strings.

A tab group has two to five tabs. An empty collection renders an inert disabled control so permission-filtered collections remain calm; one tab or more than five tabs fail with guidance. A selected value absent from `tabs` remains visibly unselected; Tabs never selects the first tab.

A `badge` is `null` or a non-negative integer count. `0` and `null` show no badge; counts above 99 display `99+`. The accessible tab name is the label followed by `badge-a11y-label` when supplied, otherwise by the displayed count. `badge-a11y-label` is ignored when no badge is visible.

Per-tab icons are deferred. A native iOS segment shows a title or an image but not both, so a shared icon-and-label capability would be unequal (Article IV.5). Authored `icon`, `icon-ios`, or `icon-android` tab fields fail with this explanation.

## Events and state timing

`@change` receives the proposed stable tab value. `native:model` normally creates this callback. A tap on an enabled, unselected tab emits once and does not move the selection locally. PHP's next published value determines the selected tab, so an `@change` handler can reject a change by keeping the current value. Programmatic updates, including selection set during `mount()` from a route parameter, do not emit. Tapping the selected tab or a disabled tab emits nothing.

Tabs commits immediately. Plain `native:model` and `native:model.live` are accepted; `blur` and `debounce` modes fail with actionable guidance.

## Accessibility

The group exposes its `a11y-label` or `label` and optional hint. Each tab exposes its label with badge text, tab role, selected state, disabled state, and its position, such as "Tab 2 of 4". During development, blank `label` and `a11y-label` emit an actionable warning. Hit targets are at least 44 points on iOS and 48 dp on Android, and selected state is never communicated by colour alone.

## Platform expression

- iOS uses a native `UISegmentedControl`, which Apple's Human Interface Guidelines permit for switching between views, placed by the author full width directly under the navigation bar; the renderer insets it by the system horizontal margin. A visible count is appended to the segment title, such as `Deployments (2)`. A SwiftUI accessibility representation exposes the group with the tab-bar trait and each segment as a selectable tab, so VoiceOver announces the tab name, badge text, selected state, and "Tab 2 of 4".
- Android uses Material 3 `PrimaryTabRow` and `Tab` with the moving primary indicator, and a Material 3 `Badge` inline after the label for counts, because `BadgedBox` overlaps its anchor and hides text labels. When a label does not fit the fixed row, the renderer switches to `PrimaryScrollableTabRow`. Each tab carries the Material tab role and collection position semantics.

Both renderers use NativePHP semantic theme tokens and retain server-authoritative selection.

## Official primitive decision

This is a paired-renderer component. NativePHP `tab-row` binds an integer index, selects optimistically before PHP accepts the change, offers no badges, and draws a custom underline strip on iOS rather than a native Apple control. It cannot meet stable values (Article III.1), server-authoritative state (III.3), or native iOS expression (V.2) through an adapter.
