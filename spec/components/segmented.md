---
title: Segmented component contract
description: Current selection, option, size, accessibility, platform, and failure contract for the Firstlight Segmented control.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - docs/components/segmented.md
  - CHANGELOG.md
  - src/Components/Segmented.php
  - src/Elements/Segmented.php
  - src/Support/OptionNormalizer.php
  - src/Support/CallbackExpression.php
  - src/Validation/FieldErrorBinder.php
  - resources/ios/SegmentedRenderer.swift
  - resources/ios/SegmentedControl.swift
  - resources/ios/FirstlightControlSize.swift
  - resources/android/SegmentedRenderer.kt
  - resources/android/SegmentedControl.kt
  - resources/android/FirstlightControlSize.kt
  - tests/Feature/SegmentedElementTest.php
  - tests/Feature/IosSegmentedContrastContractTest.php
  - tests/ios/SegmentedControlSnapshotTests.swift
  - tests/ios/SegmentedRendererContractTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SegmentedRendererContractTest.kt
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SegmentedSelectionStateTest.kt
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SegmentedControlScreenshotTest.kt
  - vendor/nativephp/mobile-ui/src/Elements/ButtonGroup.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIButtonGroupRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/ButtonGroupRenderer.kt
---

# Segmented Component Contract

## Purpose and state class

`<firstlight:segmented>` presents a small set of mutually exclusive choices as
one joined native control and selects zero or one stable value. It is a
discrete, server-authoritative choice control.

Individually shaped or multiple-selection choices belong to Pill Group, long
or descriptive choice lists belong to Choice Group, and screen sections belong
to Tabs.

## Public API

```blade
<firstlight:segmented
    :options="$priorityOptions"
    native:model="priority"
    label="Priority"
    helper="Choose a response priority."
    a11y-hint="Urgent may be unavailable."
/>
```

| Prop | Contract |
| --- | --- |
| `options` | Simple strings, a homogeneous value-to-label map, or rich option arrays with `value`, `label`, and optional `disabled`. |
| `value` / `native:model` | `string`, `int`, or `null`; a non-null value's type must match the option values. |
| `size` | `md` (default) or `lg`. `lg` matches Button `size="lg"`. |
| `label` | Visible field label. |
| `helper` | Supporting guidance below the control. |
| `error` | Visible and accessible validation feedback. |
| `required` | Required metadata; performs no validation. |
| `disabled` | Disables the complete control. |
| `a11y-label` | Explicit accessible name when a visible label is inappropriate. |
| `a11y-hint` | Supplementary VoiceOver and TalkBack guidance. |
| `@change` | Receives the selected stable value. |
| `class` | External EDGE layout only. |

Segmented has no multiple, icon, tone, colour, orientation, scrolling, or
platform-widget prop. `label`, `helper`, `error`, and `size` are cast to
strings and `required` and `disabled` to Booleans.

## Options and values

Segmented uses the shared Firstlight option normalizer without content fields.
Values are stable strings or integers, never labels or renderer indexes.
Values in one control are unique and share one type; labels are non-blank
strings; rich options accept only `value`, `label`, and a Boolean `disabled`.

`null` means no selection. An empty string is a distinct string value, so
`has_selection` separates the two on the wire. A same-typed selected value
absent from `options` remains visibly unselected; Segmented never selects the
first option as a fallback.

## Published contract

The element type is `firstlight.segmented`. Props are `label`, `helper`,
`error`, `required`, `disabled`, `value_type` (`string` or `integer`),
`has_selection`, `selected_value` (the value as a string, or `''`),
`option_values` (strings), `option_labels`, `option_enabled` (`'1'` or
`'0'`), `size`, and optional `a11y_label` and `a11y_hint`. An empty option
collection publishes `value_type: string` and forces `disabled: true`.

String-valued controls register one `on_change` callback. Integer-valued
controls instead publish `option_callbacks`, one registered callback per
option whose expression appends that option's integer to the authored handler
and its existing arguments, so PHP receives a true integer.

`FieldErrorBinder` fills `error` from the current MessageBag for the
`native:model` or `error-for` name unless the author supplied a non-empty
`error`. A blank `label` and blank `a11y-label` trigger a development warning
outside production.

## Events and state timing

A tap on an enabled, unselected option emits once: `SELECT_CHANGE` with the
string value through `on_change`, or `PRESS` through that option's integer
callback. The renderer does not change the visible selection; the next
published value determines it. Rejected changes, programmatic updates, and
reconciliation never emit, and every publication of the node reconciles the
selection even when the value is unchanged. Unlike Pill Group, there is no
pending-proposal lock, so each rejected attempt emits again.

Segmented commits immediately. Plain `native:model` and `native:model.live`
are accepted; `blur` and `debounce` fail with guidance to use plain
`native:model` and defer in the component.

## Disabled and failure behaviour

A disabled control, disabled option, already-selected option, or empty option
collection emits nothing.

Segmented fails before publication for malformed options, blank labels,
unknown rich-option fields, non-Boolean `disabled` options, mixed or duplicate
option values, a selected value that is not a string, integer, or `null`, a
selected-value type that differs from the options, a `size` other than `md`
or `lg`, or a deferred sync mode.

## Size

`size` changes only height and title type. `md` keeps at least 44 points on
iOS and 48 dp on Android. `lg` matches Button `size="lg"`: 54 points on iOS
at the default text size, growing with the button's scaled label line height,
and 48 dp on Android with 12 dp label padding so it grows with font scale as
that button does. No size falls below the platform interaction target.

Since 1.1.0-beta2 the iOS control reports its minimum height to SwiftUI through
`sizeThatFits`, because SwiftUI otherwise sizes a hosted `UISegmentedControl`
from its 31-point intrinsic height and ignores constraints and `minHeight`
frames.

## Accessibility

On iOS the `UISegmentedControl` is one accessibility element. Its label is the
explicit or visible label, followed by `, required` when required; its hint is
`a11y-hint`; and its value lists every full option title with `selected` and
`disabled` states. The visible required asterisk is hidden, and error text is
read as `Error: <text>`.

On Android the field exposes the label and hint as its content description,
`Required` as state description, and the error as error semantics. Each
`SegmentedButton` keeps Material selectable, selected, and disabled semantics
with its option label.

Selected titles keep at least 4.5:1 contrast: both platforms composite theme
tokens to opaque colours and fall back to black or white when `on-primary`
falls short. Selection is never communicated by colour alone.

## Platform expression

- iOS composes a SwiftUI field around a hosted `UISegmentedControl` with equal
  segment widths, theme-derived selected tint and normal, selected, and
  disabled title colours, and no iOS 26 appearance override. Titles are single
  line in `footnote` (`md`, capped at 24 points) or `subheadline` (`lg`,
  capped at 28 points) and truncate rather than wrap. The coordinator restores
  the authoritative segment before reporting a tap, and a server selection
  change completes its layout immediately so the slide animates inside native
  lists. Helper and error text appear below; the field dims to 0.6 opacity
  while disabled.
- Android uses Material 3 `SingleChoiceSegmentedButtonRow` with equal-weight
  `SegmentedButton` items of at least 48 dp, labels of up to two lines in
  `labelLarge` (`md`) or `titleMedium` (`lg`), theme-token colours with 0.38
  alpha for disabled content, and `label *` above and error-or-helper text
  below the row.

## Official primitive decision

This is a paired-renderer component. Mobile UI's `button_group` renders a
system `UISegmentedControl` and a Material segmented row, but it binds a
selected integer index over flat string options and owns that index state. It
cannot expose stable string or integer values, null selection, disabled
options, field metadata, or server-authoritative selection without leaking
indexes into the Firstlight API. Firstlight renderers therefore compose the
same native primitives through NativePHP's existing select-change and press
transports.

## Evidence boundary

Pest proves string and integer publication, per-option integer callbacks with
preserved arguments, UTF-8 values, null versus empty-string selection, empty
inert controls, field metadata, sizes and their rejection, type mismatch,
missing selections, live-only sync, unlabelled warnings, real Blade and web
compilation, the manifest entry, and the iOS contrast wiring. XCTest proves
server-authoritative selection, repeated rejected attempts, integer `PRESS`,
disabled and same-selection no-ops, coordinator rejection, the 44-point and
`lg` heights across Dynamic Type, title styles, accessibility metadata,
contrast, list animation, and snapshots. Kotlin tests prove the equivalent
state transitions, field semantics, size decoding, contrast, and Paparazzi
light, dark, disabled, error, and font-scale `2.0` states. Showcase and device
evidence remain separate integration and release requirements.
