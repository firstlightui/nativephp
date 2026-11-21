---
title: Reorder List
description: Public API, values, moves, state timing, accessibility, and platform behaviour for Firstlight Reorder List.
type: reference
audience: consumer
sources:
  - spec/designs/2026-11-21-firstlight-reorder-list-design.md
  - src/Components/ReorderList.php
  - src/Elements/ReorderList.php
  - resources/ios/ReorderListControl.swift
  - resources/ios/ReorderListRenderer.swift
  - resources/android/ReorderListControl.kt
  - resources/android/ReorderListRenderer.kt
  - tests/Feature/ReorderListElementTest.php
---

# Reorder List

Reorder List lets people put a short list of items in their own order, optionally across named sections such as "Tab bar" and "More". Every move works by dragging and, equally, through VoiceOver and TalkBack actions.

## Complete examples

A single list binds a list of item values:

```php
public array $favourites = ['inbox', 'calendar', 'reports'];
```

```blade
<firstlight:reorder-list
    :items="[
        ['value' => 'inbox', 'label' => 'Inbox', 'icon' => 'inbox'],
        ['value' => 'calendar', 'label' => 'Calendar', 'icon' => 'calendar'],
        ['value' => 'reports', 'label' => 'Reports', 'supporting' => 'Weekly'],
    ]"
    native:model="favourites"
    label="Favourites"
/>
```

With `sections`, the value maps every section key to a list:

```php
public array $arrangement = [
    'bar' => ['home', 'inbox', 'reports'],
    'more' => ['files'],
];
```

```blade
<firstlight:reorder-list
    :items="$tabItems"
    :sections="['bar' => 'Tab bar', 'more' => 'More']"
    native:model="arrangement"
    label="Tabs"
    helper="Up to 5 tabs. Home stays first."
/>
```

## Props

| API | Accepted type | Purpose |
| --- | --- | --- |
| `items` | `array` | Rich items: `value`, `label`, and optional `supporting`, `icon` (with `icon-ios` and `icon-android` overrides) and `locked`. |
| `sections` | `array` | Optional ordered map of at least two section keys to visible headers. |
| `value` / `native:model` | `array` or `null` | The order. A list without sections; a map of section key to list with sections. `null` means the authored order of `items`, all in the first section. |
| `label` | `string` | Visible field label. |
| `helper` | `string` | Supporting text shown when there is no error. |
| `error` | `string` | Error text; replaces helper text. |
| `disabled` | `bool` | Disables every move. |
| `a11y-label` | `string` | Explicit accessibility label when a visible label is inappropriate. |
| `a11y-hint` | `string` | Additional VoiceOver and TalkBack guidance. |
| `class` | `string` | External EDGE layout utilities. |

## Items and values

Item values must be unique and all strings or all integers. Labels must contain visible text. A list holds at most 20 items, because each possible move is published ahead of time.

The bound value must contain every item value exactly once. Section keys in the value must be the declared keys; a missing section is an empty list.

A locked item shows a lock instead of a drag handle and offers no move actions. Other items may still be dropped around it; PHP decides whether to accept the result.

There is no add, delete or selection behaviour. To let people hide items, give the list a second section, such as "Hidden", and move them there.

## Events and state timing

`@change` receives the complete proposed value in the bound shape. `native:model` normally generates this callback.

Dragging is continuous, so the dropped order shows at once as a draft rather than snapping back. PHP stays [server-authoritative](../concepts/server-authoritative-state.md): its next publication replaces the draft, whether it accepted the move, rewrote it (for example to enforce a section limit) or rejected it. Moves are ignored while a proposal is in flight. Programmatic publications never emit.

An unchanged PHP response must also release the pending-move guard. Host the control in `FirstlightUI\NativeComponent` for the [nonstreaming response-revision contract](../reference/compatibility.md#runtime-response-contract).

Use plain `native:model` or `native:model.live`; deferred `blur` and `debounce` modes are rejected.

## Accessibility

Provide either a visible `label` or an explicit `a11y-label`. Each row is one accessibility target that reads its label, its section and position ("Inbox, Tab bar, 2 of 3"), and "Locked" when locked. Unlocked rows offer Move up, Move down and Move to each other section (to its end). After a move, the new position is announced. Section headers are headings. Rows meet 44-point iOS and 48-dp Android targets and wrap long labels.

## Validation and failure behaviour

Contract exceptions reject malformed items, blank labels, duplicate or mixed values, more than 20 items, fewer than two sections, a value that misses, repeats or adds item values, a value in the wrong shape, unknown section keys and deferred sync modes, before publication. Screens that `use ValidatesFields` show the first MessageBag message for the field, as other fields do.

## Platform behaviour

iOS uses a SwiftUI `List` in the inset-grouped style, held in edit mode so rows show the system reorder handles. Rows can be dragged across section headers.

Android draws Material 3 grouped rows with a drag handle. Dragging the handle moves the row at once; a long press anywhere on the row also lifts it. The row lifts with elevation, and a haptic tick marks each place it passes.

## Compatibility

Reorder List supports the versions listed in the current [compatibility reference](../reference/compatibility.md) and requires both native renderers in the host application plus Firstlight's screen response contract above.
