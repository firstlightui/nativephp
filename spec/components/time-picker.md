---
title: Time Picker component contract
description: Canonical time values, native draft presentation, internationalisation, accessibility, and paired-renderer decision for Firstlight Time Picker.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/components/date-picker.md
  - vendor/nativephp/mobile-ui/src/Elements/DatePicker.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIDatePickerRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/DatePickerRenderer.kt
---

# Time Picker Component Contract

## Purpose and state class

`<firstlight:time-picker>` chooses one wall-clock time or no time. It is a
discrete, server-authoritative field. Its public value is exactly `HH:mm` in
24-hour wire notation, from `00:00` through `23:59`, or `null`.

The native presentation owns a temporary draft. Confirm proposes the draft
through `@change`; cancel discards it. The trigger always shows PHP's accepted
value and never changes optimistically. There is no pending latch.

## Public API

```blade
<firstlight:time-picker
    native:model="appointmentTime"
    label="Appointment time"
    placeholder="Choose a time"
    helper="Clinic local time"
    locale="en-AU"
    timezone="Australia/Sydney"
/>
```

| Prop | Contract |
| --- | --- |
| `value` / `native:model` | Optional state. Exact `HH:mm` or `null`; omission defaults to `null`. |
| `label` | Visible field label. |
| `placeholder` | Trigger text while the accepted value is `null`. |
| `helper` | Supporting guidance. |
| `error` | Accessible validation feedback that replaces helper visually. |
| `required` | Required metadata; it does not invent client validation. |
| `disabled` | Prevents opening and confirmation. |
| `locale` | Optional nonblank BCP-47 display locale. |
| `timezone` | Optional IANA timezone used only for the current-time draft seed. |
| `a11y-label` | Explicit field name when a visible label is inappropriate. |
| `a11y-hint` | Supplementary platform accessibility guidance. |
| `class` | External EDGE layout only. |

Time Picker has no public hour-format, min, max, range, step, mode,
picker-style, display-style, clearable, read-only, title, action-label, icon,
loading, tone, variant, size, or per-control visual escape prop.

## Values and internationalisation

Time values are exact ASCII strings. Firstlight does not trim, coerce,
truncate, parse timestamps, or accept seconds. `7:05`, `07:5`, `07:05:00`,
`24:00`, offsets, whitespace, numbers, and date objects fail before
publication. The Element Tree always publishes an explicit pair:

```text
has_value = true,  value = "14:30"
has_value = false, value = ""
```

Locale controls the native trigger and picker display, including the
platform's 12/24-hour expression; it never changes the wire value. Timezone
only determines the current wall-clock minute used to seed a null draft. An
accepted `HH:mm` value is never shifted between zones.

## Events and reconciliation

`@change` receives the proposed canonical time after explicit confirmation.
Moving the native draft, cancelling, confirming the accepted value, opening,
or programmatic publication emits nothing. Plain `native:model` and
`native:model.live` are accepted; blur, lazy, and debounce fail.

Each opening rebuilds the draft from the accepted value, or the current minute
in `timezone` when null. A publication changing accepted value, locale,
timezone, or disabled state while open dismisses and discards the draft. The
renderer never creates local accepted state and allows immediate reopening.

If no change callback exists, the trigger is inert. Development warns when
both visible `label` and `a11y-label` are blank.

## Platform expression and accessibility

- iOS uses a native labelled trigger and adaptive popover or sheet containing
  a genuine SwiftUI `DatePicker` restricted to `.hourAndMinute`, with native
  Cancel and Confirm actions.
- Android uses a read-only Material trigger and genuine Material 3
  `TimePickerDialog` plus `TimePicker`. Its 12/24-hour presentation follows
  platform locale and clock convention; no override leaks into the API.

The trigger exposes its name, accepted localized time or placeholder, hint,
required and disabled state, helper, and error. Native picker traversal and
actions remain intact. Targets are at least 44 points on iOS and 48 dp on
Android, with dark mode, font scaling, RTL, contrast, and reduced-motion
support inherited from platform controls and theme tokens.

## Official primitive decision

This is a paired-renderer component. NativePHP Mobile UI combines date, time,
and datetime modes, exposes picker style, hour-format and presentation-label
APIs, normalizes broader inputs, and displays optimistic local values.
Adapting it would leak unsupported behavior or violate the accepted-trigger
and explicit confirm/cancel contract. Firstlight uses the same genuine native
controls and standard string change event without a new bridge vocabulary.

## Evidence plan

Pest proves strict nullable values, explicit wire state, metadata, locale and
timezone validation, callbacks, sync modes, diagnostics, tag compilation,
layout-only classes, and manifest identifiers. XCTest and Kotlin tests prove
draft seeding, confirmation, cancellation, accepted-trigger timing,
publication dismissal, localized display helpers, and native renderer
compilation. Showcase fixtures cover null, accepted, required/error,
disabled, locale/timezone, server rejection, and programmatic publication.

Physical-device accessibility evidence remains a component-release check and
does not block the honest off-device development verdict.
