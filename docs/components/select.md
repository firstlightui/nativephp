---
title: Select
description: Public API, stable values, searchable presentation, state timing, accessibility, and platform behaviour for Firstlight Select.
type: reference
audience: consumer
sources:
  - spec/components/select.md
  - src/Components/Select.php
  - src/Elements/Select.php
  - src/Support/OptionNormalizer.php
  - resources/ios/SelectControl.swift
  - resources/ios/SelectRenderer.swift
  - resources/android/SelectControl.kt
  - resources/android/SelectRenderer.kt
  - tests/Feature/SelectElementTest.php
---

# Select

Select presents a collapsed, single-choice field and publishes one stable string or integer value.

## Complete example

```php
public ?string $priority = null;

public array $priorityOptions = [
    ['value' => 'routine', 'label' => 'Routine'],
    ['value' => 'urgent', 'label' => 'Urgent'],
    ['value' => 'critical', 'label' => 'Critical', 'disabled' => true],
];
```

```blade
<firstlight:select
    :options="$priorityOptions"
    native:model="priority"
    label="Priority"
    placeholder="Select a priority"
    helper="Choose one priority."
/>
```

## Props

| API | Accepted type | Purpose |
| --- | --- | --- |
| `options` | `array` | Simple strings, a value-to-label map, or rich option arrays. Labels must contain visible text. |
| `value` / `native:model` | `string`, `int`, or `null` | Stable selection. Every non-null value must exactly match one option by type and value. |
| `searchable` | `bool` | Forces search for a collection with 12 or fewer options. Collections with 13 or more are always searchable. |
| `label` | `string` | Visible field label. |
| `placeholder` | `string` | Trigger text when the published value is `null`; it is not the accessible name. |
| `helper` | `string` | Supporting text shown when there is no error. |
| `error` | `string` | Error text and native error semantics; replaces helper text. |
| `required` | `bool` | Communicates required state; PHP still owns validation. |
| `disabled` | `bool` | Disables the trigger and all options. |
| `a11y-label` | `string` | Explicit accessible label when a visible label is inappropriate. |
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
    ['value' => 10, 'label' => 'Routine'],
    ['value' => 20, 'label' => 'Urgent', 'disabled' => true],
]
```

Values in one collection must be unique and all strings or all integers. Matching is strict: integer `10` does not match string `'10'`. `null` means no selection and displays the placeholder. Empty options accept only `null` and produce an inert trigger.

## Compact and searchable presentation

Select chooses its platform presentation from the option count. Up to 12 options use a compact menu or dropdown. Thirteen or more options use a searchable presentation automatically. Set `searchable` to force search for a smaller collection.

Search filters labels locally in authored order. Typing, dismissing search, or selecting the accepted value does not publish an event.

## Events and state timing

`@change` receives the proposed stable scalar with its authored PHP type. `native:model` normally generates this callback for property synchronisation.

Select is [server-authoritative](../concepts/server-authoritative-state.md). Choosing an enabled option emits immediately, but the trigger continues to show PHP's accepted value until the next publication. Further choices are ignored while that proposal is in flight. Rejected and programmatic publications do not emit.

An identical PHP publication must also release the pending-interaction guard. Select therefore requires a NativePHP build containing identical-publication delivery for custom EDGE renderers.

Use plain `native:model` or `native:model.live`; deferred `blur` and `debounce` modes are rejected.

## Disabled, required, and error behaviour

`disabled` prevents the trigger and every option from emitting. A rich option's `disabled: true` affects only that choice. `required` communicates state but does not add client-side validation. Publish `error` from PHP when validation fails; it replaces helper text on both platforms.

## Accessibility

Provide either a visible `label` or an explicit `a11y-label`. Firstlight warns during development when both are blank. The trigger exposes its accessible name, accepted value or placeholder, hint, required state, disabled state, and error. Option rows expose labels, selection, and disabled state. Native search retains editable-text semantics, labels wrap at accessibility sizes, and controls preserve platform minimum interaction targets.

## Validation and failure behaviour

Firstlight throws an actionable exception for blank or malformed options, mixed or duplicate values, a non-null value absent from the options, type mismatches, unsupported presentation or multiple-selection props, and deferred sync modes.

## Platform behaviour

iOS uses a SwiftUI `Menu` for compact collections and a native searchable sheet with checkmarked rows for searchable collections. Android uses a Material 3 exposed dropdown for compact collections and a searchable Material dialog with radio rows for searchable collections. Both preserve the same stable-value, disabled-option, threshold, publication, and stale-selection semantics.

## Compatibility

Select supports the versions listed in the current [compatibility reference](../reference/compatibility.md), requires both native renderers in the host application, and currently depends on the unreleased NativePHP identical-publication fix described above.

## Screenshots

Runtime screenshot evidence is controller-owned. If capture is available, the stable paths are:

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/select/ios-light.png` | `docs/screenshots/select/ios-dark.png` |
| Android | `docs/screenshots/select/android-light.png` | `docs/screenshots/select/android-dark.png` |
