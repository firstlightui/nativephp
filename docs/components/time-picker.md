---
title: Time Picker
description: Choose a wall-clock time with a native, server-authoritative picker.
type: reference
audience: consumer
sources:
  - spec/components/time-picker.md
  - src/Components/TimePicker.php
  - src/Elements/TimePicker.php
  - resources/ios/TimePickerControl.swift
  - resources/ios/TimePickerRenderer.swift
  - resources/android/TimePickerControl.kt
  - resources/android/TimePickerRenderer.kt
  - tests/Feature/TimePickerElementTest.php
  - docs/how-to/localize.md
  - src/Support/Chrome.php
---

# Time Picker

Time Picker chooses an optional wall-clock time using the platform's native
time control. The model value is always exact 24-hour `HH:mm` or `null`, even
when the platform displays a localized 12-hour clock.

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

The user edits a temporary native draft. Cancel discards it and Confirm sends
`@change`; the closed trigger continues to show the server-accepted value
until PHP republishes the tree.

## Values and props

- `value` / `native:model`: exact `HH:mm` or `null`.
- `label`, `placeholder`, `helper`, and `error`: field copy.
- `required` and `disabled`: field state.
- `locale`: BCP-47 tag used only for display. Omission inherits the
  application translator locale.
- `timezone`: IANA identifier used only to seed a null draft with the current
  local minute. Omission inherits `config('app.timezone')` when it is a valid
  IANA identifier.
- `a11y-label` and `a11y-hint`: explicit accessibility copy.
- `class`: external EDGE layout.

Use plain `native:model` or `native:model.live`. Time Picker commits only on
confirmation, so blur, lazy, and debounce modes are rejected. It deliberately
does not expose min/max, seconds, steps, ranges, hour-format overrides,
presentation styles, clear affordances, read-only state, icons, or colours.
Sheet Confirm and Cancel labels are package chrome, not Blade attributes; see
[Localize chrome](../how-to/localize.md).

## Validation and accessibility

Contract exceptions still reject coerced or non-canonical time strings before
publication. User validation is separate: screens that `use ValidatesFields`
auto-bind the first MessageBag message for the field's `native:model` or
`error-for` name. An authored `error` wins. `required` is display metadata
and does not run Laravel rules. See
[Validate fields](../how-to/validate-fields.md).

Always provide either a visible `label` or `a11y-label`. Errors replace helper
text visually and are announced by platform semantics. The native presentation
retains standard VoiceOver or TalkBack traversal and Cancel/Confirm actions.

## Screenshots

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | ![Time Picker on iOS in light mode](../screenshots/time-picker/ios-light.png) | ![Time Picker on iOS in dark mode](../screenshots/time-picker/ios-dark.png) |
| Android | ![Time Picker on Android in light mode](../screenshots/time-picker/android-light.png) | ![Time Picker on Android in dark mode](../screenshots/time-picker/android-dark.png) |
