---
title: List component contract
description: Public collection container, child composition, refresh and pagination hooks, and adapter contract for Firstlight List.
status: current
audience: maintainer
sources:
  - Constitution.md
  - nativephp.json
  - vendor/nativephp/mobile-ui/src/Elements/NativeList.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIListRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/ListRenderer.kt
  - spec/components/list-item.md
  - spec/components/list-section.md
  - spec/reference/list-pagination.md
---

# List Component Contract

## Purpose and state class

`<firstlight:list>` is a finite vertical collection container for application
rows. It is an action/display component: PHP owns refresh and end-reached
callbacks while native code owns scrolling, separators, grouped section chrome,
and pull-to-refresh presentation.

List is not a virtualized window, selectable collection, reorder surface, or
general layout primitive. Rows are [`firstlight:list-item`](list-item.md)
children; grouped content uses [`firstlight:list-section`](list-section.md).

## Public API

```blade
<firstlight:list separator @refresh="reloadItems" @end-reached="loadMore">
    <firstlight:list-item headline="Account" @press="openAccount" />
    <firstlight:list-section header="Preferences" footer="Changes sync automatically">
        <firstlight:list-item headline="Notifications" @press="openNotifications" />
        <firstlight:list-item headline="Privacy" @press="openPrivacy" />
    </firstlight:list-section>
</firstlight:list>
```

| Prop or event | Contract |
| --- | --- |
| `separator` | Optional boolean. When true, rows show native dividers between items. Defaults to false. |
| `plain` | Optional boolean. When any direct child is a List Section, grouped inset cards are the default; `plain` keeps flat rows with sticky section headers. Defaults to false. |
| `shows-indicators` | Optional boolean. Controls scroll indicator visibility on the native scroller. Defaults to true. |
| `@refresh` | Optional standard press callback. When authored, native pull-to-refresh is enabled. |
| `@end-reached` | Optional standard press callback. Fires once when the viewport nears the final leaf row. |
| `class` | External EDGE layout only. |

List is a paired container. Its child slot accepts only List Item and List
Section elements.

## Empty, action, and failure behaviour

An empty List publishes a native scroller with no rows. Refresh and end-reached
callbacks require real `@refresh` / `@end-reached` bindings; they are not
inferred from empty props. Laravel paginator accumulation is a PHP extension
over those events; see [List pagination](../reference/list-pagination.md).

Unsupported child types, horizontal layout, virtualized windows, row selection,
swipe actions, embedded controls, field bindings, and Mobile UI escape
attributes fail with actionable `InvalidArgumentException` messages before
publication.

Boolean props require real booleans.

## Accessibility

List delegates row accessibility to List Item. Section headers and footers are
native text with platform typography. Pull-to-refresh uses native platform
semantics where available.

## Platform expression

- iOS delegates to the official Mobile UI SwiftUI `List` renderer with grouped
  or plain style, native refreshable behaviour, section headers/footers, and
  List Item rows.
- Android delegates to the official Mobile UI `LazyColumn` renderer with grouped
  cards or flat rows, sticky section headers, pull-to-refresh, and List Item
  rows.

## Adapter decision

Mobile UI 0.4 `list` expresses the Firstlight container contract when child
types remain compatible with the upstream list renderer. List therefore uses
the adapter path declared in `nativephp.json`. The public Element Tree type is
`firstlight.list`.

List Section intentionally publishes the upstream wire type `list_section`
because the delegated list renderer recognizes grouped sections by that type.
The public Blade tag remains `<firstlight:list-section>`.

## Evidence boundary

Development evidence requires Pest contracts, manifest registration, showcase
fixtures, public documentation, and constitutional review. Adapter-backed
components do not require package-local Swift or Kotlin renderer files.
