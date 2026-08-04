---
title: Select component contract
description: Stable values, searchable presentation policy, accessibility, platform expression, and failure contract for Firstlight Select.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - src/Support/OptionNormalizer.php
  - vendor/nativephp/mobile-ui/src/Elements/Select.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUISelectRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/SelectRenderer.kt
---

# Select Component Contract

## Purpose and state class

`<firstlight:select>` chooses zero or one stable value from a collapsed collection. It is a discrete, server-authoritative field. Use Choice Group when all choices need to remain visible and Segmented when a small exclusive set benefits from a joined control.

## Public API

```blade
<firstlight:select
    :options="$priorityOptions"
    native:model="priority"
    label="Priority"
    placeholder="Select a priority"
    helper="Choose one priority."
/>
```

| Prop | Contract |
| --- | --- |
| `options` | Shared simple strings, homogeneous value-to-label maps, or rich arrays with `value`, nonblank `label`, and optional `disabled`. |
| `value` / `native:model` | A stable `string`, `int`, or `null`. Every non-null value must match one option exactly by type and value. |
| `searchable` | Forces the searchable native presentation for fewer than 13 options. Thirteen or more options always use search. |
| `label` | Visible field label. |
| `placeholder` | Trigger text when the published value is `null`; never the accessible name. |
| `helper` | Supporting guidance shown when there is no error. |
| `error` | Visible and accessible feedback that replaces helper text. |
| `required` | Communicates required state without inventing client-side validation. |
| `disabled` | Disables the trigger and every option. |
| `a11y-label` | Explicit accessible field name when a visible label is inappropriate. |
| `a11y-hint` | Supplementary VoiceOver and TalkBack guidance. |
| `class` | External EDGE layout only. |

Select has no public mode, style, multiple, clearable, tone, variant, icon, loading, press, or submit prop.

## Options, values, and presentation policy

Select uses the shared option normalizer. Values are unique domain strings or integers and one collection cannot mix types. Labels must contain visible text. Rich options can disable individual choices.

`null` means no selection. Unlike visible Choice Group controls, Select treats a missing non-null value as authoring failure: the collapsed trigger could otherwise present an accepted value without a corresponding option label. Matching is strict, so integer `10` does not match string `'10'`. Empty options accept only `null` and render an inert disabled trigger.

Collections of 12 or fewer options use the compact platform presentation unless `searchable` is true. Collections of 13 or more always use a searchable native presentation. Search filters option labels locally and does not publish an event or alter accepted selection.

## Events and state timing

`@change` receives the selected stable scalar with its authored PHP type. `native:model` supplies this callback for property synchronisation.

Selecting an enabled value emits once and does not optimistically change the trigger. The next PHP publication owns the visible value. Selecting the already accepted option, dismissing a presentation, changing a search query, reconciliation, rejection, and programmatic updates do not emit. While a proposal awaits publication, later selections are ignored. An identical publication must release the guard, so Select depends on NativePHP's identical-publication delivery fix.

Plain `native:model` and `native:model.live` commit immediately. `blur` and `debounce` fail with actionable guidance.

## Accessibility and field behaviour

The trigger exposes the visible or explicit label, accepted option label or placeholder, hint, required, disabled, helper, and error semantics. Option rows expose labels, selection, and disabled state. Search entry retains native editable-text semantics. Targets meet 44 points on iOS and 48 dp on Android, and labels support accessibility text sizes and right-to-left layout.

During development, a blank visible label and blank `a11y-label` emit an actionable warning. `required` remains metadata; PHP owns validation.

## Platform expression

- iOS uses a SwiftUI `Menu` for compact collections. Searchable collections open a native sheet with SwiftUI search and checkmarked option rows.
- Android uses Material 3 `ExposedDropdownMenuBox` for compact collections. Searchable collections open a Material dialog with a search field and radio option rows.

Both renderers inherit NativePHP semantic theme tokens and share the same stable value, disabled-option, threshold, event, and reconciliation semantics.

## Official primitive decision

This is a paired-renderer component. NativePHP's Select is an adequate string-list menu/dropdown, but it stringifies option values, accepts no rich disabled choices, optimistically changes visible selection, and provides no searchable presentation or field helper/error/required contract. Adapting it would lose stable integer identity and the server-authoritative stale-proposal rules.

Firstlight therefore composes the same genuine Apple and Material intents through one SuperNative element. It adds no WebView, JSON bridge, or parallel binding system.
