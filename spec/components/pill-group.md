---
title: Pill Group component contract
description: Current selection, option, accessibility, platform, and failure contract for the Firstlight Pill Group.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - src/Support/OptionNormalizer.php
  - vendor/nativephp/mobile-ui/src/Elements/Chip.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIChipRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/ChipRenderer.kt
---

# Pill Group Component Contract

## Purpose and state class

`<firstlight:pill-group>` presents compact, individually shaped choices and lets a user select zero or one value, or zero or more values when `multiple` is enabled. It is a discrete, server-authoritative choice control.

An interactive capsule belongs to Pill Group. Display-only capsule text belongs to Status Label, a mutually exclusive joined control belongs to Segmented, and long inline choice lists belong to Choice Group.

## Public API

Single selection is the default:

```blade
<firstlight:pill-group
    :options="$queueOptions"
    native:model="queue"
    label="Queue"
    helper="Choose the queues to include."
/>
```

Multiple selection uses the same option contract and an array-valued binding:

```blade
<firstlight:pill-group
    :options="$queueOptions"
    native:model="queues"
    label="Queues"
    multiple
/>
```

| Prop | Contract |
| --- | --- |
| `options` | Simple strings, a homogeneous value-to-label map, or rich option arrays with `value`, `label`, and optional `disabled`. |
| `value` / `native:model` | Single mode accepts `string`, `int`, or `null`. Multiple mode accepts a list of homogeneous strings or integers, or `null` for no selection. |
| `multiple` | Enables zero-or-more selection. Defaults to `false`. |
| `label` | Visible group label. |
| `helper` | Supporting guidance below the group. |
| `error` | Visible and accessible validation feedback. |
| `required` | Communicates required state without inventing client-side validation. |
| `disabled` | Disables the complete group. |
| `a11y-label` | Explicit group accessibility label when a visible label is inappropriate. |
| `a11y-hint` | Supplementary VoiceOver and TalkBack guidance. |
| `class` | External EDGE layout only. |

Pill Group has no public shape, radius, orientation, tone, variant, icon, loading, or platform-widget prop.

## Options and values

Pill Group uses the shared Firstlight option normalizer. Values are stable domain strings or integers, never labels or renderer indexes. Values in one group are unique and have one type. Rich options may disable individual pills.

In single mode, `null` means no selection. Tapping the selected pill proposes `null`; tapping another enabled pill proposes its stable value.

In multiple mode, `null` and `[]` both mean no selection. Tapping an unselected pill appends its stable value to the proposed list. Tapping a selected pill removes it while preserving the order of all remaining authored values. A same-typed selected value absent from `options` remains visually unselected and is preserved in the proposed list until PHP removes it.

`required` is field metadata. It does not prevent clearing the last selection; PHP remains responsible for accepting the proposal and publishing validation feedback.

## Events and state timing

`@change` receives the complete proposed value: a stable scalar or `null` in single mode and a stable list in multiple mode. `native:model` normally creates this callback for property synchronisation.

Interaction emits once and does not change the selected visuals locally. The next published PHP value determines the visible state. Rejected changes, programmatic updates, and reconciliation do not emit. While a proposal is awaiting publication, further taps are ignored so rapid interaction cannot publish proposals calculated from stale server state.

Pill Group commits immediately. Plain `native:model` and `native:model.live` are accepted; `blur` and `debounce` modes fail with actionable guidance.

## Empty, disabled, and failure behaviour

An empty option collection renders an inert disabled group. A disabled group or disabled option emits nothing. Missing same-typed selections remain visibly unselected; Pill Group never chooses a fallback.

Pill Group fails before publication for malformed options, mixed or duplicate option values, a single-mode array, a multiple-mode scalar, a non-list multiple value, duplicate selected values, mixed selected-value types, a selected-value type that differs from the options, unsupported value types, deferred sync modes, or incompatible public props.

During development, a blank visible label and blank `a11y-label` emit the same actionable unlabelled-field warning as other Firstlight choice controls.

## Accessibility

The group exposes its visible or explicit label, hint, required state, helper, and error. Each pill exposes its label, button role, selected state, and disabled state. Native focus order follows authored option order and right-to-left layout. Labels scale without truncating meaning, and pills wrap rather than shrinking below their platform interaction targets.

The iOS hit area is at least 44 points and the Android hit area is at least 48 dp. Selected state is never communicated by colour alone.

## Platform expression

- iOS composes SwiftUI `Button` values with capsule borders, selected traits, native press feedback, and a wrapping `Layout`.
- Android uses Material 3 `FilterChip` values in a wrapping `FlowRow`, retaining Material selection, state-layer, focus, and disabled behaviour.

Both renderers use NativePHP semantic theme tokens, retain server-authoritative selection, reflow for accessibility text sizes, and expose the same authored API without imitating the other platform's geometry.

## Official primitive decision

This is a paired-renderer component. NativePHP `chip` adequately represents one Boolean filter and supplies the correct native platform primitives, but each chip owns a separate Boolean `native:model` and its `@change` event appends that Boolean. It cannot expose one stable scalar-or-list group binding, whole-value `@change`, group validation semantics, or stale-proposal suppression without leaking per-chip state into the Firstlight API.

The Firstlight renderers therefore compose the same native intents as the official primitive while owning the cross-option selection contract in one SuperNative element. No WebView, JSON bridge, or parallel binding system is introduced.
