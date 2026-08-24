---
title: Firstlight Media field design
description: Approved catalogue Media field with image-or-document mode, author-controlled crop, MediaValue on Laravel Storage, and ValidatesFields integration.
status: approved
sources:
  - Constitution.md
  - spec/reference/catalogue-boundary.md
  - spec/reference/field-validation.md
  - spec/designs/2026-08-23-firstlight-laravel-supernative-extensions-design.md
  - docs/how-to/validate-fields.md
  - docs/concepts/firstlight-and-mobile-ui.md
  - https://nativephp.com/docs/mobile/4/the-basics/assets
  - https://nativephp.com/docs/mobile/4/the-basics/file
---

# Firstlight Media Field

Date: 2026-08-24

Status: approved for implementation planning.

This dated record is the design authority for Media until a maintained
component contract exists under `spec/components/`. It supersedes the earlier
“no crop / path-only” sketch in the Laravel SuperNative extensions design for
this feature only.

## Purpose and state class

`<firstlight:media>` is a form-grade field for one image or one document.
It is the first ranked catalogue tag justified because Mobile UI does not ship
an equal form field with Storage-backed value, validation `error` slot, and
optional crop.

Media is an action/display field with a committed value owned by PHP after
Storage succeeds. Native code owns picker chrome, the optional crop sheet, and
preview presentation. The field does not optimistically publish a `MediaValue`
while a sheet is open.

## Approach

Paired Firstlight renderers for field chrome, image preview, and crop sheet.
Image capture/library and document picking use NativePHP Camera / Photos /
system file seams. Crop is Firstlight-owned on both platforms (not system crop
editors) so aspect and accessibility stay equal.

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
    helper="PDF up to 10 MB"
    native:model="contract"
/>

<firstlight:media
    mode="image"
    label="Attachment"
    crop="optional"
    native:model="photo"
/>
```

| Prop or event | Contract |
| --- | --- |
| `mode` | Required: `image` or `document`. |
| `label`, `helper`, `error`, `required`, `disabled` | Same field metadata family as Text Field. Authored `error` wins over binder. |
| `aspect` | Image only. Authored aspect ratio (e.g. `1:1`, `4:3`). Implies **required** crop to that aspect. Rejected in document mode. |
| `crop` | Image only: `optional` or `required`. Without `aspect`, optional is skippable freeform; required is freeform but must confirm. Rejected in document mode. Neither `crop` nor `aspect` → no crop UI. |
| `disk` | Laravel Storage disk name. Defaults to `mobile_public`. |
| `directory` | Storage directory prefix under the disk. Package default when omitted. |
| `a11y-label`, `a11y-hint` | Optional accessibility overrides for the field control. |
| `native:model` | Binds `?MediaValue`. Empty is `null`. |
| `class` | External EDGE layout only. |
| Clear | When a value is present and not disabled, an explicit clear affordance sets the model to `null` and best-effort deletes the stored object. |

### Crop prop composition

1. Neither `crop` nor `aspect` → pick/camera → store (no crop sheet).
2. `aspect` present → required crop locked to that aspect.
3. `crop="optional"` without aspect → freeform crop; Skip or confirm-without-adjust allowed.
4. `crop="required"` without aspect → freeform crop; must confirm.
5. Document mode rejects `crop` and `aspect` before publication.

### Sources

- Image: camera and photo library.
- Document: system file picker.
- No picking documents through the camera sheet; no camera permission for document mode.

### Cardinality

Exactly one value per field. Replace overwrites after a successful new commit.
Multi-file is out of v1.

## MediaValue

Public model type (name may be `FirstlightUI\Media\MediaValue`):

- `disk` (string)
- `path` (string) — path relative to the disk root
- `mime` (string)
- `size` (int, bytes)
- `width` / `height` (optional ints, images only when known)

Default disk is NativePHP’s documented `mobile_public` (persistent user content
symlinked for display). Authors may override `disk`. Path helpers and
`Storage::disk($value->disk)->url($value->path)` are the display path.

Temp camera/library/file results are moved into Storage with NativePHP `File`
move/copy before the model publishes. A bare path string is not the public
model; `MediaValue` is, with path accessors for Storage and validation.

## Flow

1. Empty field: user activates → image chooser (camera/library) or document picker.
2. Cancel / permission deny → no model change; actionable field error or Feedback for hard failures.
3. Image + crop rules → Firstlight crop sheet; Cancel returns without changing the committed value; optional Skip commits without adjusting.
4. On confirm: PHP stores under disk/directory, builds `MediaValue`, publishes.
5. Replace: same flow; after new value is stored, best-effort delete of the previous object. Delete failure does not roll back the new value.
6. Clear: `null` model + best-effort delete.

No Confirmation Dialog required for replace in v1.

## Validation

Media participates in `ValidatesFields`.

- Key resolution matches other fields (`error-for`, model, sync property).
- Authored `error` wins.
- `required` is authored, not inferred from rules.
- Rules run against an UploadedFile-compatible or path/mime/size adapter derived from `MediaValue` so stock `image`, `mimes`, `max`, `dimensions`, and `file` rules work for the common case.
- `null` fails `required`.
- Document mode must not silently accept `image`/`dimensions` authoring mistakes — fail closed with an actionable diagnostic when those rules are incompatible with mode.
- Early MIME/size rejects before Storage commit still surface through the field `error` slot on republish.
- Crop does not invoke Laravel rules mid-gesture; validation applies to the committed value.
- No native rule engine and no HTML/`@error` clone.

## Accessibility

- Field: labelled control; helper and error follow existing field patterns; preview is decorative unless `a11y-label` replaces the name.
- Crop sheet: accessible name; Confirm / Cancel / Skip as buttons meeting 44pt / 48dp minima; focus containment while presented.
- Zoom must not be pinch-only: v1 ships explicit zoom in/out controls so VoiceOver/TalkBack users can complete a required crop.
- Reduced Motion: no ornamental crop animation.

## Platform expression

- iOS and Android: paired field chrome and paired crop sheet; native pickers for camera, library, and documents via NativePHP seams.
- Preview loads from Storage URL / resolvable path after commit.
- Geometry, materials, and system pickers stay platform-native; crop semantics stay shared.

## Evidence boundary

Development requires Pest contracts (props, mode/crop diagnostics, MediaValue, Storage commit/clear, ValidatesFields participation), paired native behaviour/accessibility tests for field and crop, showcase + capture fixture, public docs, and constitutional review.

Release requires crop screenshot matrices and dated VoiceOver/TalkBack evidence on the crop sheet. Package public alpha remains additionally gated on NativePHP identical-publication ([mobile-air#365](https://github.com/NativePHP/mobile-air/issues/365)) for other server-authoritative controls; Media must not publish a value before Storage success so it does not depend on identical-tree acknowledgement for commit.

## Non-goals (v1)

Multi-file, video, PDF page preview, remote URL import, in-field drawing, gallery CMS, system crop-editor delegation, automatic sync to a remote HTTP API, and Confirmation Dialog on replace.

## Implementation follow-through

After this design is accepted as written, produce an implementation plan via the
writing-plans workflow, then deliver through `firstlight-create-component` and
the paired platform/docs/review skills. Update the SuperNative extensions
historical note so Media is “designed / in progress” rather than “no crop”.
