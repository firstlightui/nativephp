---
title: Localize Firstlight chrome
description: Override Firstlight-owned chrome labels and inherit application locale and timezone for Date and Time pickers.
type: how-to
audience: consumer
sources:
  - lang/en/chrome.php
  - lang/pl/validation.php
  - lang/en/validation.php
  - src/Support/Chrome.php
  - src/FirstlightServiceProvider.php
  - src/Elements/DatePicker.php
  - src/Elements/TimePicker.php
  - src/Elements/ConfirmationDialog.php
  - src/Elements/AlertDialog.php
  - tests/Unit/Support/ChromeTest.php
  - tests/Feature/DatePickerElementTest.php
  - tests/Feature/ConfirmationDialogElementTest.php
  - tests/Feature/MediaElementTest.php
  - tests/Feature/TextFieldElementTest.php
  - src/Elements/Media.php
  - src/Elements/TextField.php
  - src/Elements/SearchField.php
---

# Localize Firstlight Chrome

Firstlight-owned chrome (Confirm, Cancel, OK, dismiss, clear, skip, Done,
crop, zoom, media source chooser, and password reveal) comes from one Laravel
language file. Authored labels on Confirmation Dialog and Alert Dialog still
win. Date Picker and Time Picker keep rejecting Blade `confirm-label` and
`cancel-label`; their sheet buttons are package chrome only.

The package ships English, German (`de`), Spanish (`es`), French (`fr`),
Italian (`it`), Japanese (`ja`), Dutch (`nl`), Polish (`pl`), Brazilian
Portuguese (`pt_BR`) and Simplified Chinese (`zh_CN`). Laravel picks the
strings for the application locale, so no setup is needed for those. There is
no translation UI. To change a packaged string or add another locale, publish
the language files.

## Publish the language files

```bash
php artisan vendor:publish --tag=firstlight-lang
```

Laravel copies every packaged locale to `lang/vendor/firstlight`. Edit a
published file to change a packaged string; your copy wins over the package.
`validation.php` holds the pluralised error summary heading (`'summary' =>
'There is :count error.|There are :count errors.'`) used by `error-summary`
Callouts and screen-reader announcements. Keep its `:count` placeholder and
one plural form per rule of the language, as Polish does with three.

To add a locale Firstlight does not ship, create its directory next to the
others, for example `lang/vendor/firstlight/sv/chrome.php`, and keep the same
keys:

```php
<?php

return [
    'confirm' => 'Bekräfta',
    'cancel' => 'Avbryt',
    'ok' => 'OK',
    'dismiss' => 'Stäng',
    'clear_search' => 'Rensa sökning',
    'show_password' => 'Visa lösenord',
    'hide_password' => 'Dölj lösenord',
    // ...the remaining keys from lang/vendor/firstlight/en/chrome.php
];
```

Missing keys fall back to the packaged English strings.

## Date and Time picker locale and timezone

When `locale` or `timezone` is omitted, Date Picker and Time Picker inherit
the application:

- locale from the translator (`en_AU` and `en_AU.UTF-8` become `en-AU`)
- timezone from `config('app.timezone')` when it is a real IANA identifier

Explicit picker props still win. Invalid inherited values are omitted so the
device calendar and clock are used; they do not throw. Authored malformed
`locale` or `timezone` attributes still fail before publication.

Authored field labels, helpers, errors, dialog titles, and Feedback messages
are ordinary Blade strings; translate those with `__()` as you already would.
