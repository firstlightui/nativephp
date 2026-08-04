---
title: Pill Group
description: Public API, selection modes, state timing, accessibility, validation, and platform behaviour for Firstlight Pill Group.
type: reference
audience: consumer
sources:
  - spec/components/pill-group.md
  - src/Components/PillGroup.php
  - src/Elements/PillGroup.php
  - src/Support/OptionNormalizer.php
  - resources/ios/PillGroupControl.swift
  - resources/ios/PillGroupRenderer.swift
  - resources/android/PillGroupControl.kt
  - resources/android/PillGroupRenderer.kt
  - tests/Feature/PillGroupElementTest.php
---

# Pill Group

Pill Group presents compact, individually shaped options for selecting zero or one value, or zero or more values.

## Complete examples

Single selection is the default:

```php
public ?string $queue = 'mine';

public array $queueOptions = [
    ['value' => 'mine', 'label' => 'Mine'],
    ['value' => 'all', 'label' => 'All'],
    ['value' => 'archived', 'label' => 'Archived', 'disabled' => true],
];
```

```blade
<firstlight:pill-group
    :options="$queueOptions"
    native:model="queue"
    label="Queue"
    helper="Choose the queue to include."
/>
```

Use `multiple` with an array-valued property:

```php
public array $queues = ['mine'];
```

```blade
<firstlight:pill-group
    :options="$queueOptions"
    native:model="queues"
    label="Queues"
    helper="Choose any that apply."
    multiple
/>
```

## Props

| API | Accepted type | Purpose |
| --- | --- | --- |
| `options` | `array` | Simple strings, a value-to-label map, or rich option arrays. |
| `value` / `native:model` | `string`, `int`, `array`, or `null` | Stable selection. Use a scalar or `null` in single mode and a homogeneous list or `null` in multiple mode. |
| `multiple` | `bool` | Enables zero-or-more selection. Defaults to `false`. |
| `label` | `string` | Visible field label. |
| `helper` | `string` | Supporting text below the pills. |
| `error` | `string` | Error text and native error semantics. |
| `required` | `bool` | Communicates required state; PHP still owns validation. |
| `disabled` | `bool` | Disables the complete group. |
| `a11y-label` | `string` | Explicit accessibility label when a visible label is inappropriate. |
| `a11y-hint` | `string` | Additional VoiceOver and TalkBack guidance. |
| `class` | `string` | External EDGE layout utilities. |

## Options and values

Simple strings use the same value and label:

```php
['Mine', 'All']
```

A map separates stable values from labels and may use all-string or all-integer keys:

```php
['mine' => 'Mine', 'all' => 'All']
```

Rich options accept only `value`, `label`, and optional `disabled` fields:

```php
[
    ['value' => 'mine', 'label' => 'Mine'],
    ['value' => 'all', 'label' => 'All', 'disabled' => true],
]
```

All values in one group must be unique and must all be strings or all be integers. In single mode, tapping the selected pill proposes `null`. In multiple mode, tapping toggles that stable value while preserving the order of the others. `null` and `[]` both mean no multiple selection.

## Events and state timing

`@change` receives the complete proposed selection: a scalar or `null` in single mode and a list in multiple mode. `native:model` normally generates this callback for property synchronisation.

Pill Group is [server-authoritative](../concepts/server-authoritative-state.md). A tap emits immediately, but selected visuals wait for PHP's next publication. Further taps are ignored while that proposal is in flight so a rapid second tap cannot be calculated from stale state. Programmatic updates and reconciliation do not emit.

Use plain `native:model` or `native:model.live`; deferred `blur` and `debounce` modes are rejected.

## Disabled and required behaviour

`disabled` prevents every pill from emitting. A rich option's `disabled: true` affects only that pill. An empty option set becomes an inert disabled group.

`required` is metadata rather than client-side enforcement. It does not prevent clearing the final pill; validate the resulting value in PHP and publish `error` when needed.

## Accessibility

Provide either a visible `label` or an explicit `a11y-label`. Firstlight warns during development when both are blank. Each pill exposes its label, selected state, and disabled state through native platform semantics. Selection also has a visible checkmark, so it does not depend on colour alone. Pills wrap at larger text sizes and retain at least a 44-point iOS or 48-dp Android interaction target.

## Validation and failure behaviour

Firstlight throws an actionable exception for malformed options, mixed or duplicate option values, a selection with the wrong type or shape, duplicate or mixed multiple selections, unsupported presentation props, and deferred sync modes. A same-typed selected value absent from `options` remains unselected rather than selecting a fallback.

## Platform behaviour

iOS uses native SwiftUI capsule buttons in a wrapping layout. Android uses Material 3 `FilterChip` controls in a wrapping `FlowRow`. Both inherit NativePHP semantic theme tokens and preserve native press, focus, selection, disabled, and accessibility behaviour without exposing platform-widget props.

## Compatibility

Pill Group supports the versions listed in the current [compatibility reference](../reference/compatibility.md) and requires both native renderers to be compiled into the host application.

## Screenshots

Runtime screenshot evidence is deferred for this development build. The capture manifest reserves these output paths for the later visual review:

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/pill-group/ios-light.png` | `docs/screenshots/pill-group/ios-dark.png` |
| Android | `docs/screenshots/pill-group/android-light.png` | `docs/screenshots/pill-group/android-dark.png` |
