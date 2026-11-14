---
title: Segmented
description: Public API, state, accessibility, validation, compatibility, and visual evidence for Firstlight Segmented.
type: reference
audience: consumer
sources:
  - src/Components/Segmented.php
  - src/Elements/Segmented.php
  - src/Elements/Concerns/AcceptsHaptic.php
  - src/Support/OptionNormalizer.php
  - resources/ios/SegmentedControl.swift
  - resources/ios/SegmentedRenderer.swift
  - resources/ios/FirstlightControlSize.swift
  - resources/android/SegmentedControl.kt
  - resources/android/SegmentedRenderer.kt
  - resources/android/FirstlightControlSize.kt
  - tests/Feature/SegmentedElementTest.php
  - tests/Unit/Support/OptionNormalizerTest.php
---

# Segmented

Segmented presents a small set of mutually exclusive choices as a native segmented control.

## Complete example

```php
public int $priority = 10;

public array $priorityOptions = [
    ['value' => 10, 'label' => 'Routine'],
    ['value' => 20, 'label' => 'Urgent', 'disabled' => true],
];
```

```blade
<firstlight:segmented
    :options="$priorityOptions"
    native:model="priority"
    label="Priority"
    helper="Choose a response priority."
    a11y-hint="Urgent may be unavailable."
/>
```

## Props

| API | Accepted type | Purpose |
| --- | --- | --- |
| `options` | `array` | Simple strings, a value-to-label map, or rich option arrays. |
| `value` / `native:model` | `string`, `int`, or `null` | Stable selected value. Its type must match the option values. |
| `label` | `string` | Visible field label. |
| `helper` | `string` | Supporting text below the control. |
| `error` | `string` | Error text and error semantics. |
| `required` | `bool` | Marks the field as required. |
| `disabled` | `bool` | Disables the complete control. |
| `size` | `string` | `md` (default) or `lg`. `lg` is as tall as a Button with `size="lg"`. |
| `a11y-label` | `string` | Explicit accessibility label when a visible label is inappropriate. |
| `a11y-hint` | `string` | Additional VoiceOver and TalkBack guidance. |
| `haptic` | `none` | Silences the haptic this control plays by default. Any other value throws. See [Play haptics](../how-to/play-haptics.md). |
| `class` | `string` | External EDGE layout utilities. |

## Sizes

`md` is the default. Use `lg` when the control sits with `size="lg"` Buttons, so the row of controls shares one height:

```blade
<firstlight:segmented :options="$viewOptions" native:model="view" a11y-label="View" size="lg" />
<firstlight:button label="Admit patient" icon="add" size="lg" />
```

| Size | iOS | Android |
| --- | --- | --- |
| `md` | At least 44 pt, footnote-sized titles. | At least 48 dp, Material label titles. |
| `lg` | Button lg's height: 54 pt at the default text size, subheadline-sized titles. | Button lg's height: 48 dp at the default text size, Material title-sized labels. |

Both sizes grow with Dynamic Type and Android font scaling; `lg` grows with the text the same way a `size="lg"` Button does. `size` changes only height and type size, not selection, state, or accessibility behaviour. Other values, including `sm`, are rejected because they would fall below the platform interaction target.

## Events

`@change` receives the selected stable value. `native:model` normally generates this callback for property synchronisation. A user selection emits immediately; server reconciliation and programmatic updates do not emit.

## Options and accepted values

Simple string options use the same value and label:

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

All values in one control must be uniquely typed strings or uniquely typed integers. `null` represents no selection; an empty string remains a distinct string value.

## State timing

Segmented is [server-authoritative](../concepts/server-authoritative-state.md). A tap emits immediately, while PHP's next published value determines the visible selection. Use plain `native:model` or `native:model.live`; deferred `blur` and `debounce` modes are rejected.

## Disabled behaviour

`disabled` prevents every choice from emitting. A rich option's `disabled: true` prevents only that option from emitting. An empty option set becomes an inert disabled control. A disabled or already-selected choice does not emit a new change.

## Accessibility

Provide either a visible `label` or an explicit `a11y-label`. Firstlight warns during development when both are blank. The renderers expose the label or explicit accessibility label, optional hint, selected state, disabled state, required indication, helper text, and error text through native platform semantics.

## Validation and failure behaviour

Contract exceptions still reject mixed or duplicate option values,
unsupported types, and sizes other than `md` and `lg` before publication. User validation is separate: screens
that `use ValidatesFields` auto-bind the first MessageBag message for the
field's `native:model` or `error-for` name. An authored `error` wins.
`required` is display metadata and does not run Laravel rules. See
[Validate fields](../how-to/validate-fields.md). A same-typed value absent
from `options` remains visibly unselected rather than selecting the first
option.

## Platform behaviour

iOS uses a native SwiftUI field backed by `UISegmentedControl`. Android uses Material 3 `SingleChoiceSegmentedButtonRow` and `SegmentedButton`. The controls share behaviour and authored API while retaining platform-native styling, layout, and interaction.

On iOS 26 and later the selected segment is the system's Liquid Glass thumb, which turns into a glass lens while it is dragged. Before iOS 26 it is filled with the theme's primary colour, with `on-primary` text or black or white when that contrast is too low.

On iOS, `UISegmentedControl` plays its own selection haptic. On Android, Segmented plays `selection` when the user picks another segment. `haptic="none"` silences the Android haptic. See [Play haptics](../how-to/play-haptics.md).

## Compatibility

Segmented supports the versions listed in the current [compatibility reference](../reference/compatibility.md) and requires both native renderers to be compiled into the host application.

## Screenshots

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | ![Segmented on iOS in light mode](../screenshots/segmented/ios-light.png) | ![Segmented on iOS in dark mode](../screenshots/segmented/ios-dark.png) |
| Android | ![Segmented on Android in light mode](../screenshots/segmented/android-light.png) | ![Segmented on Android in dark mode](../screenshots/segmented/android-dark.png) |
