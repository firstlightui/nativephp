---
title: Localize Firstlight chrome
description: Override Firstlight-owned chrome labels and inherit application locale and timezone for Date and Time pickers.
type: how-to
audience: consumer
sources:
  - lang/en/chrome.php
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

The package ships English. There is no translation UI. Applications add other
locales through Laravel vendor language publishing.

## Publish the language files

```bash
php artisan vendor:publish --tag=firstlight-lang
```

Laravel copies the files to `lang/vendor/firstlight`. Add a locale next to
English, for example `lang/vendor/firstlight/fr/chrome.php`, and keep the same
keys:

```php
<?php

return [
    'confirm' => 'Confirmer',
    'cancel' => 'Annuler',
    'ok' => 'OK',
    'dismiss' => 'Fermer',
    'dismiss_feedback' => 'Fermer le message',
    'clear' => 'Effacer',
    'clear_search' => 'Effacer la recherche',
    'clear_text' => 'Effacer le texte',
    'skip' => 'Ignorer',
    'done' => 'OK',
    'crop' => 'Recadrer',
    'zoom_in' => 'Zoom avant',
    'zoom_out' => 'Zoom arrière',
    'choose_media' => 'Choisir un média',
    'photo_library' => 'Photothèque',
    'camera' => 'Appareil photo',
    'browse_files' => 'Parcourir les fichiers',
    'show_password' => 'Afficher le mot de passe',
    'hide_password' => 'Masquer le mot de passe',
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
