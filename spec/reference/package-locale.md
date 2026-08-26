---
title: Firstlight package locale chrome
description: Current PHP helper for Firstlight-owned chrome strings and Date/Time picker application locale and timezone inheritance.
status: current
audience: maintainer
sources:
  - Constitution.md
  - lang/en/chrome.php
  - src/Support/Chrome.php
  - src/FirstlightServiceProvider.php
  - src/Elements/DatePicker.php
  - src/Elements/TimePicker.php
  - src/Elements/Media.php
  - src/Elements/SearchField.php
  - src/Elements/TextField.php
  - src/Elements/Select.php
  - src/Elements/ConfirmationDialog.php
  - src/Elements/AlertDialog.php
  - src/Elements/FeedbackItem.php
  - tests/Unit/Support/ChromeTest.php
  - tests/Feature/DatePickerElementTest.php
  - tests/Feature/TimePickerElementTest.php
  - tests/Feature/MediaElementTest.php
  - tests/Feature/ConfirmationDialogElementTest.php
  - docs/how-to/localize.md
---

# Firstlight Package Locale Chrome

Package locale chrome is a PHP extension over existing Firstlight controls.
It adds no catalogue tag, translation UI, or native string tables. PHP
resolves English (or the application's vendor translations) and publishes
wire props. Native renderers read those props with English last-resort
fallbacks.

## Public PHP surface

`FirstlightUI\Support\Chrome` exposes:

- `string(string $key): string` — known keys only; unknown keys throw
- `applicationLocale(): ?string` — translator locale mapped to BCP-47
- `applicationTimezone(): ?string` — `config('app.timezone')` when IANA
- `withPickerChrome(array $props): array` — picker confirm/cancel labels plus
  omitted locale/timezone inheritance

`FirstlightServiceProvider` loads `lang/` as the `firstlight` namespace and
publishes it as `firstlight-lang` to `langPath('vendor/firstlight')`.

The English file is `lang/en/chrome.php`. Keys are `confirm`, `cancel`, `ok`,
`dismiss`, `dismiss_feedback`, `clear`, `clear_search`, `clear_text`, `skip`,
`done`, `crop`, `zoom_in`, `zoom_out`, `choose_media`, `photo_library`,
`camera`, `browse_files`, `show_password`, and `hide_password`.

Chrome uses the bound `translator` and `config` services. The package does
not depend on `illuminate/foundation` or the `__()` helper.

## Publication

| Surface | Wire props | Authored Blade |
| --- | --- | --- |
| Confirmation Dialog | `confirm_label`, `cancel_label` | `confirm-label` / `cancel-label` win |
| Alert Dialog | `action_label` | `action-label` wins |
| Date Picker / Time Picker | `confirm_label`, `cancel_label`; omitted `locale` / `timezone` inherit | `confirm-label` / `cancel-label` rejected |
| Media | `confirm_label`, `cancel_label`, `clear_label`, `skip_label`, `crop_label`, `zoom_in_label`, `zoom_out_label`, `choose_media_label`, `photo_library_label`, `camera_label`, `browse_files_label` | none |
| Search Field | `clear_a11y_label` | none; iOS system clear button uses this accessibility label when present |
| Text Field | `clear_a11y_label`, `show_password_a11y_label`, `hide_password_a11y_label` | none |
| Select | `done_label` | none; iOS searchable Done |
| Feedback item | `dismiss_label`, `dismiss_a11y_label` | none |

## Inheritance rules

`applicationLocale()` strips a `.UTF-8` suffix, maps `_` to `-`, and validates
the same BCP-47 grammar as Date Picker. Invalid inherited locales are omitted.

`applicationTimezone()` requires membership in
`DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC)`. Invalid inherited
timezones are omitted.

Authored picker `locale` and `timezone` still throw when malformed.

## Out of scope

Extra locales in the package. Native `.strings` / Android resource tables as
the source of truth. Field Choose/Replace actions remain English chrome for
now.
