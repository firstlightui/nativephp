---
title: Choice Group component contract
description: Current option, selection, accessibility, platform, and failure contract for the Firstlight Choice Group.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - src/Support/OptionNormalizer.php
  - vendor/nativephp/mobile-ui/src/Elements/RadioGroup.php
  - vendor/nativephp/mobile-ui/src/Elements/Checkbox.php
---

# Choice Group Component Contract

## Purpose and state class

`<firstlight:choice-group>` presents a visible list of labelled choices. It selects exactly zero or one stable value by default, or zero or more stable values when `multiple` is enabled. It is a discrete, server-authoritative field.

Use Choice Group when options should remain visible as full-width rows. Use Segmented for a compact mutually exclusive control and Pill Group for compact, wrapping filters.

## Public API

```blade
<firstlight:choice-group
    :options="$priorityOptions"
    native:model="priority"
    label="Priority"
    helper="Choose one priority."
/>
```

Multiple selection uses the same option shape and a list-valued binding:

```blade
<firstlight:choice-group
    :options="$notificationOptions"
    native:model="notifications"
    label="Notifications"
    multiple
/>
```

| Prop | Contract |
| --- | --- |
| `options` | Simple strings, a homogeneous value-to-label map, or rich option arrays with `value`, `label`, and optional `disabled`. Labels must contain visible text. |
| `value` / `native:model` | Single mode accepts `string`, `int`, or `null`. Multiple mode accepts a list of homogeneous strings or integers, or `null`. |
| `multiple` | Enables zero-or-more selection. Defaults to `false`. |
| `label` | Visible group label. |
| `helper` | Supporting guidance shown when there is no error. |
| `error` | Visible and accessible validation feedback that replaces helper text. |
| `required` | Communicates required state without inventing client-side validation. |
| `disabled` | Disables the complete group. |
| `a11y-label` | Explicit group accessibility label when a visible label is inappropriate. |
| `a11y-hint` | Supplementary VoiceOver and TalkBack guidance. |
| `class` | External EDGE layout only. |

Choice Group has no public orientation, density, tone, variant, icon, loading, per-option callback, press, or submit prop.

## Options and values

Choice Group uses the shared Firstlight option normalizer. Values are unique, stable domain strings or integers, never labels or renderer indexes. One group cannot mix value types. Rich options can disable individual rows.

In single mode, `null` means no selection. Tapping an unselected enabled row proposes its stable value. Tapping the selected radio-equivalent row does nothing; a user cannot clear radio selection by selecting it again. PHP can still publish `null` programmatically.

In multiple mode, `null` and `[]` both mean no selection. Tapping a row proposes a complete list with that stable value added or removed. Authored order of the existing selection is preserved. A same-typed selected value absent from `options` remains visually unselected and is preserved until PHP removes it.

`required` is metadata only. PHP owns validation and acceptance.

## Events and state timing

`@change` receives the complete proposed value: a scalar in single mode and a stable-value list in multiple mode. `native:model` supplies this callback for property synchronisation.

Interaction emits once and does not change selected visuals locally. The next PHP publication is authoritative. Rejected changes, programmatic publications, and reconciliation do not emit. While a proposal awaits publication, later taps are ignored so proposals cannot be calculated from stale state. An identical publication must also release this guard; Choice Group therefore depends on NativePHP's identical-publication delivery fix.

Choice Group commits immediately. Plain `native:model` and `native:model.live` are accepted. `blur` and `debounce` fail with actionable guidance.

## Empty, disabled, and failure behaviour

An empty option collection renders an inert disabled group. A disabled group, disabled option, selected single option, or option without a callback emits nothing. Choice Group never invents a fallback.

Choice Group fails before publication for blank option labels, malformed options, mixed or duplicate option values, incompatible selection shape or type, duplicate selected values, deferred sync modes, or incompatible public props.

During development, a blank visible label and blank `a11y-label` emit an actionable warning.

## Accessibility

The group exposes its visible or explicit label, hint, required state, helper, and error. Each row exposes one label, its selected or checked state, and its disabled state. Focus follows authored order and supports right-to-left layout and accessibility text sizes without truncating meaning.

The complete row is interactive. iOS rows meet the 44-point target and Android rows meet the 48-dp target. Selection uses a checkmark or platform control as well as colour.

## Platform expression

- iOS uses native SwiftUI button rows and trailing checkmarks, matching Apple's option-list convention. Single and multiple modes share the row geometry but expose exclusive-selection or toggle accessibility semantics respectively.
- Android uses one Material 3 `RadioButton` row set in single mode and one homogeneous `Checkbox` row set in multiple mode. The row owns the selectable or toggleable semantics and the nested indicator does not create a duplicate TalkBack target.

Both renderers use NativePHP semantic theme tokens and the same server-authoritative state contract.

## Official primitive decision

This is a paired-renderer component. NativePHP `radio-group` is a single-choice string control and cannot express stable integer values, multiple selection, field helper/error/required metadata, or stale-proposal suppression. NativePHP `checkbox` owns one Boolean value per element and cannot publish one complete stable-value list.

Choice Group therefore composes genuine Apple and Material controls while publishing one Firstlight SuperNative element. It adds no WebView, JSON bridge, or parallel binding system.
