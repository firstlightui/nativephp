---
title: Choice Group
description: Public API, selection modes, state timing, accessibility, and platform behaviour for Firstlight Choice Group.
type: reference
audience: consumer
sources:
  - spec/components/choice-group.md
  - src/Components/ChoiceGroup.php
  - src/Elements/ChoiceGroup.php
  - src/Support/OptionNormalizer.php
  - resources/ios/ChoiceGroupControl.swift
  - resources/ios/ChoiceGroupRenderer.swift
  - resources/android/ChoiceGroupControl.kt
  - resources/android/ChoiceGroupRenderer.kt
  - tests/Feature/ChoiceGroupElementTest.php
---

# Choice Group

Choice Group presents a visible list of labelled options for selecting zero or one value, or zero or more values.

## Complete examples

Single choice is the default:

```php
public ?string $priority = 'routine';

public array $priorityOptions = [
    ['value' => 'routine', 'label' => 'Routine'],
    ['value' => 'urgent', 'label' => 'Urgent'],
    ['value' => 'critical', 'label' => 'Critical', 'disabled' => true],
];
```

```blade
<firstlight:choice-group
    :options="$priorityOptions"
    native:model="priority"
    label="Priority"
    helper="Choose one priority."
/>
```

Use `multiple` with a list-valued property:

```php
public array $notifications = ['email'];
```

```blade
<firstlight:choice-group
    :options="['email' => 'Email', 'sms' => 'SMS', 'push' => 'Push notification']"
    native:model="notifications"
    label="Notifications"
    helper="Choose any that apply."
    multiple
/>
```

## Props

| API | Accepted type | Purpose |
| --- | --- | --- |
| `options` | `array` | Simple strings, a value-to-label map, or rich option arrays. Labels must contain visible text. |
| `value` / `native:model` | `string`, `int`, `array`, or `null` | Stable selection. Use a scalar or `null` in single mode and a homogeneous list or `null` in multiple mode. |
| `multiple` | `bool` | Enables zero-or-more selection. Defaults to `false`. |
| `label` | `string` | Visible field label. |
| `helper` | `string` | Supporting text shown when there is no error. |
| `error` | `string` | Error text and native error semantics; replaces helper text. |
| `required` | `bool` | Communicates required state; PHP still owns validation. |
| `disabled` | `bool` | Disables the complete group. |
| `a11y-label` | `string` | Explicit accessibility label when a visible label is inappropriate. |
| `a11y-hint` | `string` | Additional VoiceOver and TalkBack guidance. |
| `class` | `string` | External EDGE layout utilities. |

## Options and values

Simple strings use the same value and label:

```php
['Routine', 'Urgent']
```

A map separates stable values from labels and may use all-string or all-integer keys:

```php
['routine' => 'Routine', 'urgent' => 'Urgent']
```

Rich options accept only `value`, `label`, and optional `disabled` fields:

```php
[
    ['value' => 'routine', 'label' => 'Routine'],
    ['value' => 'urgent', 'label' => 'Urgent', 'disabled' => true],
]
```

All option labels must contain visible text. Values in one group must be unique and must all be strings or all be integers.

In single mode, tapping an unselected row proposes its value. Tapping the selected row does nothing, matching radio-choice semantics. PHP can publish `null` to clear the field programmatically.

In multiple mode, tapping toggles that stable value while preserving the order of the other values. `null` and `[]` both mean no selection.

## Events and state timing

`@change` receives the complete proposed selection: a scalar in single mode and a list in multiple mode. `native:model` normally generates this callback for property synchronisation.

Choice Group is [server-authoritative](../concepts/server-authoritative-state.md). A tap emits immediately, but selected visuals wait for PHP's next publication. Further taps are ignored while that proposal is in flight so a second proposal cannot be calculated from stale state. Rejected and programmatic publications do not emit.

An identical PHP publication must also release the pending-interaction guard. This component therefore requires a NativePHP build containing identical-publication delivery for custom EDGE renderers.

Use plain `native:model` or `native:model.live`; deferred `blur` and `debounce` modes are rejected.

## Disabled and required behaviour

`disabled` prevents every row from emitting. A rich option's `disabled: true` affects only that row. An empty option set becomes an inert disabled group.

`required` is metadata rather than client-side enforcement. Validate the value in PHP and publish `error` when needed. Error text replaces helper text on both platforms.

## Accessibility

Provide either a visible `label` or an explicit `a11y-label`. Firstlight warns during development when both are blank. Each complete row is one accessibility target with its label, selected or checked state, and disabled state. Selection is also shown with a native indicator, so it does not depend on colour alone. Rows support wrapping labels and retain at least a 44-point iOS or 48-dp Android interaction target.

## Validation and failure behaviour

Firstlight throws an actionable exception for blank or malformed options, mixed or duplicate option values, a selection with the wrong type or shape, duplicate or mixed multiple selections, unsupported presentation props, and deferred sync modes. A same-typed selected value absent from `options` remains unselected rather than choosing a fallback.

## Platform behaviour

iOS uses native SwiftUI option rows with trailing checkmarks. Android renders one Material 3 radio-row set for single choice and one homogeneous checkbox-row set for multiple choice. Both inherit NativePHP semantic theme tokens and preserve native focus, selection, disabled, and accessibility behaviour without exposing platform-widget props.

## Compatibility

Choice Group supports the versions listed in the current [compatibility reference](../reference/compatibility.md), requires both native renderers in the host application, and currently depends on the unreleased NativePHP identical-publication fix described above.

## Screenshots

Runtime screenshot evidence is controller-owned. If capture is available, the stable paths are:

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/choice-group/ios-light.png` | `docs/screenshots/choice-group/ios-dark.png` |
| Android | `docs/screenshots/choice-group/android-light.png` | `docs/screenshots/choice-group/android-dark.png` |
