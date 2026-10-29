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

A scrollable filter row gives each option an icon, shows only the selected pill's label, and tints the selection with a semantic tone:

```blade
<firstlight:pill-group
    :options="$filterOptions"
    native:model="filter"
    a11y-label="Filter"
    scrollable
    collapse-labels
    tone="success"
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
| `options` | Simple strings, a homogeneous value-to-label map, or rich option arrays with `value`, `label`, optional `disabled`, and optional `icon`, `icon-ios`, and `icon-android`. |
| `value` / `native:model` | Single mode accepts `string`, `int`, or `null`. Multiple mode accepts a list of homogeneous strings or integers, or `null` for no selection. |
| `multiple` | Enables zero-or-more selection. Defaults to `false`. |
| `scrollable` | Presents the pills as one horizontally scrolling row instead of wrapping. Defaults to `false`. |
| `collapse-labels` | Shows only the icon of each unselected pill and reveals the label of each selected pill. Requires an icon on every option. Defaults to `false`. |
| `tone` | Semantic colour of selected pills: `primary` (default), `accent`, `success`, `warning`, or `danger`. Unselected pills always use the neutral surface style. |
| `label` | Visible group label. |
| `helper` | Supporting guidance below the group. |
| `error` | Visible and accessible validation feedback. |
| `required` | Communicates required state without inventing client-side validation. |
| `disabled` | Disables the complete group. |
| `a11y-label` | Explicit group accessibility label when a visible label is inappropriate. |
| `a11y-hint` | Supplementary VoiceOver and TalkBack guidance. |
| `class` | External EDGE layout only. |

Pill Group has no public colour, shape, radius, orientation, variant, group-level icon, loading, or platform-widget prop. Icons belong to individual options; `icon`, `icon-ios`, and `icon-android` on the group fail with guidance to move them onto options.

## Options and values

Pill Group uses the shared Firstlight option normalizer. Values are stable domain strings or integers, never labels or renderer indexes. Values in one group are unique and have one type. Rich options may disable individual pills.

Rich options may carry the [shared icon contract](../reference/icons.md): a shared `icon` fallback with `icon-ios` and `icon-android` overrides (camelCase `iconIos` and `iconAndroid` aliases are accepted). PHP resolves each option's platform icon before publication; renderers receive only resolved `option_icons` and `option_icon_variants` lists aligned with the options. Option icons are decorative and silent to assistive technology because every option already has a label.

In single mode, `null` means no selection. Tapping the selected pill proposes `null`; tapping another enabled pill proposes its stable value.

In multiple mode, `null` and `[]` both mean no selection. Tapping an unselected pill appends its stable value to the proposed list. Tapping a selected pill removes it while preserving the order of all remaining authored values. A same-typed selected value absent from `options` remains visually unselected and is preserved in the proposed list until PHP removes it.

`required` is field metadata. It does not prevent clearing the last selection; PHP remains responsible for accepting the proposal and publishing validation feedback.

## Presentation

Without `scrollable`, pills wrap onto further lines. With `scrollable`, they stay on one line and scroll horizontally; the row follows right-to-left layout, and labels do not wrap within a pill.

Without `collapse-labels`, every pill shows its label. A selected pill replaces its leading icon, if any, with a checkmark. With `collapse-labels`, an unselected pill shows only its icon and a selected pill shows its icon and label. The label reveal is the non-colour selection cue, so no checkmark is added. `collapse-labels` is independent of `scrollable` but is intended for scrolling filter rows.

The label reveal animates only when PHP publishes a changed selection. Initial rendering and reconciliation without a selection change do not animate, and Reduce Motion or disabled system animations remove the motion.

`tone` changes only the selected pill's container and content colours, which come from the NativePHP semantic theme (`primary`, `accent`, `success`, the Firstlight warning pair, and `destructive` for `danger`). Selected content falls back to black or white when the theme pair does not reach 4.5:1 contrast. Unselected pills stay uniform regardless of tone.

## Events and state timing

`@change` receives the complete proposed value: a stable scalar or `null` in single mode and a stable list in multiple mode. `native:model` normally creates this callback for property synchronisation.

Interaction emits once and does not change the selected visuals locally. The next published PHP value determines the visible state. Rejected changes, programmatic updates, and reconciliation do not emit. While a proposal is awaiting publication, further taps are ignored so rapid interaction cannot publish proposals calculated from stale server state.

Pill Group commits immediately. Plain `native:model` and `native:model.live` are accepted; `blur` and `debounce` modes fail with actionable guidance.

## Empty, disabled, and failure behaviour

An empty option collection renders an inert disabled group. A disabled group or disabled option emits nothing. Missing same-typed selections remain visibly unselected; Pill Group never chooses a fallback.

Pill Group fails before publication for an unsupported `tone`, an option icon that is blank or of an unsupported type, `collapse-labels` when any option lacks an icon, malformed options, mixed or duplicate option values, a single-mode array, a multiple-mode scalar, a non-list multiple value, duplicate selected values, mixed selected-value types, a selected-value type that differs from the options, unsupported value types, deferred sync modes, or incompatible public props.

During development, a blank visible label and blank `a11y-label` emit the same actionable unlabelled-field warning as other Firstlight choice controls.

## Accessibility

The group exposes its visible or explicit label, hint, required state, helper, and error. Each pill exposes its label, button role, selected state, and disabled state. An icon-only collapsed pill still exposes its full option label as its accessible name; the icon itself is silent. Native focus order follows authored option order and right-to-left layout. Labels scale without truncating meaning, and pills wrap rather than shrinking below their platform interaction targets.

The iOS hit area is at least 44 points and the Android hit area is at least 48 dp. Selected state is never communicated by colour alone.

## Platform expression

- iOS composes SwiftUI `Button` values with capsule borders, selected traits, native press feedback, and a wrapping `Layout`, or a horizontal `ScrollView` when scrollable. Option icons are SF Symbols resolved through NativePHP's icon mapping, and the label reveal uses a SwiftUI animation gated by `accessibilityReduceMotion`.
- Android uses Material 3 `FilterChip` values in a wrapping `FlowRow`, or a horizontally scrolling `Row` when scrollable, retaining Material selection, state-layer, focus, and disabled behaviour. Option icons use the chip's leading-icon slot, or the label slot when collapsing so icon-only chips keep symmetric padding. The label reveal uses `AnimatedVisibility`, which follows the system animation scale.

Both renderers use NativePHP semantic theme tokens, retain server-authoritative selection, reflow for accessibility text sizes, and expose the same authored API without imitating the other platform's geometry.

## Official primitive decision

This is a paired-renderer component. NativePHP `chip` adequately represents one Boolean filter and supplies the correct native platform primitives, but each chip owns a separate Boolean `native:model` and its `@change` event appends that Boolean. It cannot expose one stable scalar-or-list group binding, whole-value `@change`, group validation semantics, or stale-proposal suppression without leaking per-chip state into the Firstlight API.

The Firstlight renderers therefore compose the same native intents as the official primitive while owning the cross-option selection contract in one SuperNative element. No WebView, JSON bridge, or parallel binding system is introduced.
