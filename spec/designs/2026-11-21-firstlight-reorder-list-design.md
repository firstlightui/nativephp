---
title: Firstlight Reorder List design
description: Contract and native expression for a list people reorder by dragging or with accessibility actions, optionally across named sections.
status: in-development
sources:
  - spec/reference/catalogue-boundary.md
  - spec/components/choice-group.md
  - spec/components/list.md
  - src/Support/CallbackExpression.php
---

# Firstlight Reorder List Design

Date: 2026-11-21

Status: wojt approved a reorder list as a Firstlight catalogue control on
2026-11-21, as part of the `firstlightui/custom-tabs` plugin proposal. This
document fixes its contract; it is in development.

## Objective

Let people put a short list of items in their own order: favourites, the
tabs of a customisable tab bar, the steps of a checklist. Optionally the
list has named sections, such as "Tab bar" and "More", and people move items
between them. Every move works by dragging and, equally, by accessibility
actions, so dragging is never the only way.

## Catalogue boundary

The decision test passes. It is a user-facing control with its own value (an
order), not layout or navigation. `firstlight:list` is explicitly not a reorder
surface, Mobile UI 0.10 has no reorderable list, and no PHP extension over an
existing control can express dragging. The control owns no route, tab or bar:
the tab-bar plugin uses it as one of its consumers.

## Public API

```blade
<firstlight:reorder-list
    :items="$tabItems"
    :sections="['bar' => 'Tab bar', 'more' => 'More']"
    native:model="arrangement"
    label="Tabs"
    helper="Up to 5 tabs. Home stays first."
/>
```

| Prop | Contract |
| --- | --- |
| `items` | A list of rich items: `value` (string or integer, unique, one type), `label` (visible text), optional `supporting`, optional `icon` with `icon-ios` / `icon-android` overrides per `spec/reference/icons.md`, optional `locked`. |
| `sections` | Optional ordered map of section key to visible header, at least two. Without it the list has one unnamed section. |
| `value` / `native:model` | Without sections: a list of every item value, once each. With sections: a map of every section key to a list, with every item value in exactly one list. `null` means the authored order of `items`, all in the first section. |
| `label`, `helper`, `error`, `disabled` | Field metadata, as on Choice Group. |
| `a11y-label`, `a11y-hint` | As on other fields. |
| `class` | External EDGE layout only. |

There is no `multiple`, selection, press, delete or add prop. Removing items is
moving them to another section.

## Values and moves

A move takes one unlocked item to a position in a section. `@change` receives
the complete proposed value in the bound shape; `native:model` supplies the
callback. PHP owns acceptance: it may accept, rewrite (for example to enforce
a section limit) or reject the proposal by publishing a value. Programmatic
publications never emit.

The native side cannot send a list back through NativePHP's event bridge, so
the element precomputes one callback per possible move, as Choice Group does
per option. For an item, the destinations are numbered in section order: each
section contributes one slot per position the item can take there, which is
the section's length without the item, plus one. The callbacks are published
as one flat list, row by row, `move_slot_count` slots per row. Locked rows
publish `0` for every slot. To keep this bounded, a list may have at most 20
items.

Locked items have no drag handle and no move actions. Other items may still be
dropped around them; PHP decides the outcome.

## State timing

Dragging is a continuous gesture, so the dropped order shows at once as a
native draft rather than snapping back. The next PHP publication replaces the
draft, whether it accepted the move or not. While a proposal awaits
publication, further moves are ignored, so proposals are never calculated from
stale state. As with Choice Group, an identical publication must release this
guard.

Only plain `native:model` and `native:model.live` are accepted.

## Accessibility

- Every unlocked row offers custom actions: Move up and Move down within its
  section, and Move to each other section (to its end). These reuse the same
  precomputed callbacks. Their labels are package chrome strings.
- Each row reads as its label, its section and position ("Inbox, Tab bar, 2 of
  3"), and "Locked" when locked.
- After a move, the new position is announced.
- Rows meet 44 pt on iOS and 48 dp on Android, scale with text size, and do not
  truncate labels at accessibility sizes. Reduce Motion removes the lift
  animation.

## Platform expression

- **iOS:** a SwiftUI `List` in the inset-grouped style, permanently in edit
  mode, with `onMove` and `moveDisabled(true)` for locked rows. Sections are
  drawn as one `ForEach` with header rows so rows can cross them; `onMove`
  maps the drop to a section and position. `accessibilityActions` provide the
  move actions.
- **Android:** a Material 3 list of grouped rows with a `drag_indicator`
  handle. Dragging the handle moves the row at once; a long press anywhere on
  the row also lifts it. The row lifts with elevation and swaps past
  neighbours, headers included, with a haptic tick. Implemented in-house with
  `detectDragGestures` and `detectDragGesturesAfterLongPress`, with no new
  dependency. Section headers
  are Material list subheaders. `CustomAccessibilityAction` semantics provide
  the move actions.

## Failure behaviour

Fails before publication for: malformed items, blank labels, duplicate or mixed
item values, more than 20 items, fewer than two sections when `sections` is
given, a value that misses, repeats or adds item values, a value in the wrong
shape, unknown section keys, and `blur` or `debounce` sync modes.
