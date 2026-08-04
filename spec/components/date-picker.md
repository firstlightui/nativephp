---
title: Date Picker component contract
description: Canonical date values, native draft presentation, bounds, internationalisation, accessibility, and paired-renderer decision for Firstlight Date Picker.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - vendor/nativephp/mobile-ui/src/Elements/DatePicker.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIDatePickerRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/DatePickerRenderer.kt
---

# Date Picker Component Contract

## Purpose and state class

`<firstlight:date-picker>` chooses one calendar date or no date. It is a
discrete, server-authoritative field. Its public value is a canonical
proleptic-Gregorian `YYYY-MM-DD` string with a year from `0001` through
`9999`, or `null` when no date is accepted.

The native picker owns a temporary draft while presented. Confirm proposes
the draft through `@change`; cancel discards it. The closed trigger always
shows PHP's accepted value and never changes optimistically. The renderer has
no pending latch, so a user may reopen and submit another draft while a prior
proposal is being handled.

## Public API

```blade
<firstlight:date-picker
    native:model="appointmentDate"
    min="2026-01-01"
    max="2026-12-31"
    label="Appointment date"
    placeholder="Choose a date"
    helper="Local clinic date"
    locale="en-AU"
    timezone="Australia/Sydney"
/>
```

| Prop | Contract |
| --- | --- |
| `value` / `native:model` | Optional public state. Accepts only canonical `YYYY-MM-DD` or `null`; omission defaults to `null`. |
| `min` | Optional inclusive canonical lower bound. |
| `max` | Optional inclusive canonical upper bound. |
| `label` | Visible field label. |
| `placeholder` | Trigger text while the accepted value is `null`. |
| `helper` | Supporting guidance. |
| `error` | Visible and accessible validation feedback; replaces helper visually. |
| `required` | Communicates required metadata. It does not invent client-side validation or prevent a null accepted value. |
| `disabled` | Prevents opening and confirmation. An already-open presentation is dismissed when disabled changes. |
| `locale` | Optional nonblank, well-formed BCP-47 display locale. It never changes the wire value. |
| `timezone` | Optional IANA timezone used to determine today, the null draft seed, and Swift date mapping. It never shifts the wire value. |
| `a11y-label` | Explicit field name when a visible label is inappropriate. |
| `a11y-hint` | Supplementary VoiceOver and TalkBack guidance. |
| `class` | External EDGE layout only. |

Date Picker has no public mode, time, datetime, picker-style, display-style,
hour-format, clearable, title, confirm-label, cancel-label, icon, range,
loading, tone, variant, size, or per-control visual escape prop.

## Values, null, and bounds

Date values are exact strings. Firstlight does not trim, coerce, truncate, or
accept `DateTimeInterface`. The shape must be ten ASCII characters and the
date must exist in the proleptic Gregorian calendar. `0000-01-01`, invalid
leap days, timestamps, offsets, and whitespace fail before publication.

The Element Tree always publishes the accepted value as an explicit pair:

```text
has_value = true,  value = "2026-07-25"
has_value = false, value = ""
```

`min` and `max` are inclusive. When both exist, `min` must not follow `max`.
An accepted non-null value must lie inside both authored bounds. Firstlight
never clamps an authored value.

An empty draft begins at today in `timezone`, or the device timezone when it
is omitted. If today lies outside the range, only the native draft seed is
clamped to the nearest bound. This does not select, publish, or emit anything.

## Events and reconciliation

`@change` receives the proposed canonical date string after explicit native
confirmation. `native:model` normally creates that callback. A user cancelling
the presentation, confirming the already accepted date, opening the picker,
or moving the native draft emits nothing. Programmatic publications emit
nothing.

Date Picker commits discretely. Plain `native:model` and
`native:model.live` are accepted; `blur`, `lazy`, and `debounce` fail with
actionable guidance. There is no `@press`, `@submit`, `@clear`, or parallel
bridge vocabulary.

The native draft is rebuilt from the current accepted value each time the
picker opens. A publication changing the accepted value, bounds, locale,
timezone, or disabled state while the picker is open dismisses it and
discards the draft. A publication that leaves those inputs unchanged does not
create an event or accepted local state.

## Empty, disabled, and failure behaviour

`null` is the only empty state and shows `placeholder`; omitting `value` is
equivalent to publishing `null`. If no change callback
is attached, the trigger is inert rather than appearing to accept an
unpublishable date. Disabled controls and inert bounds emit nothing.

Date Picker fails with actionable diagnostics for non-string non-null values,
empty or padded strings, noncanonical or impossible dates,
invalid or reversed bounds, an accepted value outside its bounds, blank or
malformed locales, non-IANA timezones, deferred sync modes, and unsupported
props or events.

During development, blank visible `label` and blank `a11y-label` emit the
standard unlabelled-field warning.

## Accessibility

The trigger exposes its visible or explicit name, accepted localized date or
placeholder, hint, required state, disabled state, helper, and error. The
calendar presentation retains native focus, traversal, selection, today, and
cancel/confirm semantics. Errors are exposed through platform error semantics
and selected dates are not communicated by colour alone.

The trigger and actions retain at least a 44-point iOS or 48-dp Android
interaction target. Labels and supporting text reflow at accessibility sizes.
Both renderers support VoiceOver or TalkBack, Dynamic Type or font scaling,
dark mode, increased contrast, right-to-left layout, and Reduced Motion.

## Platform expression

- iOS uses an Apple-native labelled trigger and an adaptive popover or sheet
  containing a genuine SwiftUI `DatePicker` restricted to `.date`, plus
  explicit native Cancel and Confirm actions. The draft maps through a
  Gregorian calendar in `timezone` without changing the wire string.
- Android uses a read-only Material trigger and Material 3
  `DatePickerDialog`. `selectedDateMillis` is decoded as UTC midnight because
  it identifies the selected calendar cell rather than an instant in the
  display timezone. Bounds use `SelectableDates` and an inclusive year range.

Locale affects displayed month, weekday, and formatted trigger text only.
Timezone affects today and the null seed only. The authored API and accepted
wire value are identical on both platforms.

## Official primitive decision

This is a paired-renderer component. NativePHP Mobile UI's DatePicker exposes
date, time, and datetime modes plus platform-specific picker styles and
presentation labels that Firstlight deliberately excludes. Its production
renderers also update a local displayed value before PHP republishes the
accepted tree. Adapting that primitive would therefore leak a broader public
API or violate Firstlight's accepted-trigger and explicit confirm/cancel
contract.

Firstlight adds one ordinary SuperNative element with paired native renderers.
It reuses the genuine platform date controls and standard string change event;
it adds no WebView, JSON bridge, or parallel state system.

## Evidence plan

- Pest 5 proves exact nullable values, explicit null wire props, inclusive
  bounds, locale/timezone validation, field metadata, callback registration,
  sync modes, diagnostics, public tag compilation, layout-only classes, and
  exact manifest identifiers.
- XCTest proves configuration, native draft seed and bounds, confirm/cancel,
  accepted-trigger timing, publication-driven dismissal, accessibility, and
  light/dark/accessibility-size snapshots.
- Kotlin tests prove the same state contract, UTC-midnight conversion,
  Material trigger/dialog semantics, selectable bounds, locale/timezone
  handling, 48-dp targets, font scale `2.0`, and Paparazzi states.
- The showcase proves null, accepted, bounded, required/error, disabled,
  locale/timezone, server rejection, and programmatic publication through one
  interactive screen and an isolated `/captures/date-picker` route.

Physical-device accessibility and presentation rows remain component-release
evidence and do not block a truthful off-device development verdict.
