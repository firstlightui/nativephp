---
title: Media component contract
description: Public image-or-document field API, MediaValue Storage commit, crop composition, ValidatesFields participation, and paired native crop sheets.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-24-firstlight-media-field-design.md
  - spec/reference/field-validation.md
  - src/Elements/Media.php
  - src/Media/MediaValue.php
  - src/Media/MediaStorage.php
  - src/Media/MediaValidation.php
  - resources/ios/MediaControl.swift
  - resources/ios/MediaRenderer.swift
  - resources/android/MediaControl.kt
  - resources/android/MediaRenderer.kt
  - tests/Feature/MediaElementTest.php
  - tests/Feature/MediaValueTest.php
  - tests/Feature/MediaStorageTest.php
  - tests/Feature/MediaValidationTest.php
---

# Media Component Contract

## Purpose and state

`<firstlight:media>` is a form-grade field for exactly one image or one
document. Native code owns picker chrome and the optional Firstlight crop
sheet. PHP owns Storage commit and publishes `MediaValue` only after Storage
succeeds. Empty is `null`.

Media is an action/display field with a committed value: the preview updates
only after PHP republishes the accepted tree.

## Public API

```blade
<firstlight:media
    mode="image"
    label="Profile photo"
    helper="Square crop required"
    aspect="1:1"
    disk="mobile_public"
    directory="avatars"
    native:model="avatar"
/>

<firstlight:media
    mode="document"
    label="Contract"
    native:model="contract"
/>
```

Required `mode` is `image` or `document`. Supported metadata: `label`,
`helper`, `error`, strict Boolean `required` and `disabled`, `disk`
(default `mobile_public`), `directory` (default `media`), `aspect`,
`crop` (`optional`|`required`), `a11y-label`, `a11y-hint`, `value` as
`MediaValue|null`, `@change`, `@clear`, and external layout `class`.

Document mode rejects `crop` and `aspect`. Invalid `mode` / `crop` /
unsupported attributes throw before publication.

### Crop composition

1. Neither `crop` nor `aspect` → no crop sheet.
2. `aspect` present → required crop locked to that aspect (published `crop`
   becomes `required` when omitted).
3. `crop="optional"` without aspect → freeform; Skip allowed.
4. `crop="required"` without aspect → freeform; must Confirm.

## MediaValue and Storage

`FirstlightUI\Media\MediaValue` is the public model: `disk`, `path`, `mime`,
`size`, optional `width`/`height`. `MediaStorage::commit` stores a readable
temp absolute path onto the disk under `directory` and returns `MediaValue`.
`MediaStorage::delete` is best-effort and never throws on missing objects.

## Validation

`MediaValidation::attributes(?MediaValue)` builds Validator data under the
`media` key as an `UploadedFile|null` from the stored path.
`ValidatesFields::validationData()` converts public `MediaValue` properties
to UploadedFile so stock `image`, `file`, `mimes`, `max`, and `dimensions`
rules work. `null` fails `required`. FieldErrorBinder participates like other
fields; authored `error` wins.

## Published contract

Element type `firstlight.media`. Props include `mode`, field metadata, `disk`,
`directory`, optional `aspect`/`crop`, `has_value`, `path`, `mime`, `size`,
optional dimensions, `preview_url`, and optional `on_change` / `on_clear`.

`@change` carries the temp absolute path string for PHP to commit.
`@clear` presses clear when a value is present.

## Native expression

- iOS: SwiftUI field chrome, PhotosPicker / camera / fileImporter, Firstlight
  crop sheet with explicit zoom in/out (44pt targets).
- Android: Material 3 field chrome and crop dialog with explicit zoom
  controls (48dp targets). Host pickers emit temp paths through the same
  change channel.

## Non-goals (v1)

Multi-file, video, remote URL import, gallery CMS, system crop-editor
delegation, Confirmation Dialog on replace.
