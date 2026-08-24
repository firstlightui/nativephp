---
title: Firstlight Media field implementation plan
description: Test-first delivery plan for MediaValue, ValidatesFields integration, paired field and crop renderers, documentation, showcase, and review.
status: current
sources:
  - spec/designs/2026-08-24-firstlight-media-field-design.md
  - Constitution.md
  - spec/reference/catalogue-boundary.md
  - spec/reference/field-validation.md
  - spec/workflows/adding-components.md
  - .agents/skills/firstlight-create-component/SKILL.md
---

# Firstlight Media Field Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:subagent-driven-development` (recommended) or `superpowers:executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking. Coordinate platform and docs work with `firstlight-create-component` and its required sub-skills.

**Goal:** Deliver a development-proven `<firstlight:media>` field for one image or one document, with author-controlled crop, `MediaValue` on Laravel Storage (default `mobile_public`), and full `ValidatesFields` participation.

**Architecture:** Paired Firstlight field + crop-sheet renderers. NativePHP Camera/Photos/file seams feed temp paths; PHP commits via `File::move` into Storage and publishes `MediaValue`. Crop is Firstlight-owned (not system editors). Validation uses the same `error` slot binder as other fields.

**Tech Stack:** PHP 8.4, NativePHP Mobile ^4.2, Mobile UI ^0.4, Laravel Storage / Validator, SwiftUI, Jetpack Compose, Pest 5.

**Spec:** [spec/designs/2026-08-24-firstlight-media-field-design.md](../designs/2026-08-24-firstlight-media-field-design.md)

## Global Constraints

- Public type `firstlight.media`; element `FirstlightUI\Elements\Media`; Blade `FirstlightUI\Components\Media`; renderers `MediaRenderer` / package Android id per scaffold conventions.
- Exactly one committed value; empty is `null`; no multi-file, video, remote URL import, or gallery CMS in v1.
- Image sources: camera + library. Document: system file picker only.
- Crop/aspect rejected in document mode; `aspect` implies required crop; `crop` is `optional`|`required`.
- Default disk `mobile_public`; override via `disk` / `directory`.
- No device/simulator/screenshot work without explicit target permission.
- Do not publish/tag; preserve unrelated package and showcase work; keep `roadmap.md` gitignored.
- Public alpha identical-publication gate (`#365`) remains separate; never publish `MediaValue` before Storage success.

## File map

| Path | Responsibility |
| --- | --- |
| `src/Media/MediaValue.php` | Immutable public value (disk, path, mime, size, optional width/height) |
| `src/Media/MediaStorage.php` | Commit temp path → Storage; clear/delete; build `MediaValue` |
| `src/Media/MediaValidation.php` | Adapt `MediaValue`/`null` for Validator (`image`/`file`/`mimes`/`max`/`dimensions`) |
| `src/Concerns/HandlesMedia.php` | Screen helpers: `commitMedia`, `clearMedia` (optional; only if Element alone is insufficient) |
| `src/Elements/Media.php` | EDGE props, diagnostics, FieldErrorBinder, callbacks |
| `src/Components/Media.php` | Blade adapter |
| `resources/ios/MediaControl.swift`, `MediaRenderer.swift`, crop sheet types | Field + crop |
| `resources/android/…Media…` | Field + crop |
| `tests/Feature/MediaElementTest.php`, `MediaValueTest.php`, `MediaStorageTest.php`, `MediaValidationTest.php` | PHP contracts |
| `tests/ios/…`, `tests/android/…` | Platform behaviour / snapshots |
| `docs/components/media.md`, how-to if needed | Public docs |
| `spec/components/media.md` | Maintained contract after implementation |
| Showcase sibling | Gallery + `/captures/media` |

---

### Task 1: MediaValue + Storage commit (PHP)

**Files:**
- Create: `src/Media/MediaValue.php`, `src/Media/MediaStorage.php`
- Test: `tests/Feature/MediaValueTest.php`, `tests/Feature/MediaStorageTest.php`

**Interfaces:**
- Produces: `MediaValue(string $disk, string $path, string $mime, int $size, ?int $width = null, ?int $height = null)`
- Produces: `MediaStorage::commit(string $tempAbsolutePath, string $disk, string $directory, ?string $filename = null): MediaValue`
- Produces: `MediaStorage::delete(?MediaValue $value): void` (best-effort; no throw on missing)

- [ ] **Step 1: Write failing MediaValue construction/equality tests**

```php
it('exposes disk path mime size and optional dimensions', function () {
    $value = new MediaValue('mobile_public', 'avatars/a.jpg', 'image/jpeg', 1200, 100, 100);

    expect($value->disk)->toBe('mobile_public')
        ->and($value->path)->toBe('avatars/a.jpg')
        ->and($value->mime)->toBe('image/jpeg')
        ->and($value->size)->toBe(1200)
        ->and($value->width)->toBe(100)
        ->and($value->height)->toBe(100);
});
```

- [ ] **Step 2: Run focused Pest — expect FAIL (class missing)**

Run: `vendor/bin/pest tests/Feature/MediaValueTest.php --compact`

- [ ] **Step 3: Implement readonly `MediaValue`**

- [ ] **Step 4: Write failing Storage commit/delete tests** using a fake disk (`Storage::fake('mobile_public')`) and a temp file; assert path under directory, mime/size populated, delete removes object.

- [ ] **Step 5: Implement `MediaStorage` with `File` move/copy into the disk root; map failures to `InvalidArgumentException` or domain exception with actionable message**

- [ ] **Step 6: Pest green for both files; commit**

```bash
git add src/Media tests/Feature/MediaValueTest.php tests/Feature/MediaStorageTest.php
git commit -m "Add MediaValue and Storage commit helper for Media field."
```

---

### Task 2: Validation adapter + ValidatesFields participation

**Files:**
- Create: `src/Media/MediaValidation.php`
- Modify: `src/Validation/FieldErrorBinder.php` only if Media needs explicit participation list (prefer Element calling `FieldErrorBinder::apply` like Text Field)
- Test: `tests/Feature/MediaValidationTest.php`, extend `tests/Feature/ValidatesFieldsTest.php` if required

**Interfaces:**
- Produces: `MediaValidation::attributes(?MediaValue $value): array` suitable for `Validator::make` data under a field key
- Consumes: `MediaValue`

- [ ] **Step 1: Failing tests — `null` fails `required`; image `MediaValue` passes `image|max:…`; oversized fails `max`; document value with `image` rule fails closed**

- [ ] **Step 2: Implement adapter (UploadedFile stub or array with enough shape for Laravel file rules — prefer real temp UploadedFile from committed path when on real disk; under `Storage::fake`, validate mime/size manually or via custom Rule if UploadedFile cannot bind)**

Decide in implementation: if stock `image` rules need a real file path, `MediaStorage::commit` must leave a readable absolute path or stream. Document the chosen adapter in `spec/components/media.md` later.

- [ ] **Step 3: Pest green; commit**

```bash
git commit -m "Add MediaValue validation adapter for Laravel image and file rules."
```

---

### Task 3: EDGE Media element + Blade + manifest (failing-first)

**Files:**
- Create via `bin/scaffold-component Media` (never overwrite)
- Implement: `src/Elements/Media.php`, `src/Components/Media.php`
- Modify: `nativephp.json`, precompiler if required
- Test: `tests/Feature/MediaElementTest.php`

**Interfaces:**
- Publishes props: `mode`, `label`, `helper`, `error`, `required`, `disabled`, `aspect`, `crop`, `disk`, `directory`, preview URL/path when value present, clear + pick callbacks
- Rejects: document+`crop`/`aspect`; invalid `mode`/`crop`; unsupported layout escapes

- [ ] **Step 1: Write failing public-tag Pest (class missing), run, confirm FAIL**

- [ ] **Step 2: `bin/scaffold-component Media` once**

- [ ] **Step 3: Expand Pest for mode, crop composition, FieldErrorBinder, disabled, clear callback, unsupported attrs**

- [ ] **Step 4: Implement Element + Blade + manifest registration (paired renderer path)**

- [ ] **Step 5: `vendor/bin/pest tests/Feature/MediaElementTest.php --compact` green; `bin/check-component Media --development` (may BLOCK on missing native until Tasks 4–5)**

- [ ] **Step 6: Commit PHP EDGE surface**

```bash
git commit -m "Add Firstlight Media EDGE element and Blade adapter."
```

---

### Task 4: iOS field + crop sheet

**REQUIRED SUB-SKILL:** `firstlight-ios-component`

**Files:** `resources/ios/Media*.swift`, `tests/ios/Media*Tests.swift`, crop sheet types as needed

- [ ] **Step 1: Failing XCTest for empty/value/disabled/error, pick entry points, crop required/optional/aspect, zoom controls present, 44pt targets**

- [ ] **Step 2: Implement idiomatic SwiftUI field chrome + Firstlight crop sheet (not UIImagePicker crop-only delegation)**

- [ ] **Step 3: Wire temp path → PHP commit callback; publish only after PHP success**

- [ ] **Step 4: Type-check / focused XCTest without unauthorized device permission claims**

- [ ] **Step 5: Commit**

```bash
git commit -m "Add iOS Media field and crop sheet renderers."
```

---

### Task 5: Android field + crop sheet

**REQUIRED SUB-SKILL:** `firstlight-android-component`

**Files:** `resources/android/*Media*`, `tests/android/.../Media*`

- [ ] **Step 1: Failing Kotlin unit + Paparazzi cases mirroring iOS states**

- [ ] **Step 2: Material 3 field chrome + Compose crop sheet with explicit zoom controls, 48dp targets**

- [ ] **Step 3: Camera/library/document intents → temp → PHP commit**

- [ ] **Step 4: `testDebugUnitTest` + `verifyPaparazziDebug` with JDK 21**

- [ ] **Step 5: Commit**

```bash
git commit -m "Add Android Media field and crop sheet renderers."
```

---

### Task 6: Maintained contract + public docs

**REQUIRED SUB-SKILL:** `firstlight-docs-write`

**Files:**
- Create: `spec/components/media.md`, `docs/components/media.md`
- Modify: `spec/index.md`, `docs/index.md`, catalogue-boundary note if needed
- Run: `bin/build-docs-artifacts`, `bin/check-docs --development`

- [ ] **Step 1: Write `spec/components/media.md` from the design + implemented API only**

- [ ] **Step 2: Write consumer `docs/components/media.md` with complete example, props, events, validation, crop rules, screenshots table (paths reserved)**

- [ ] **Step 3: Index both; regenerate LLM artefacts; docs check green**

- [ ] **Step 4: Commit**

```bash
git commit -m "Document Firstlight Media field contract and consumer guide."
```

---

### Task 7: Showcase + capture fixture

**Files:** sibling `firstlightui/showcase` — gallery screen, `/captures/media`, focused tests

- [ ] **Step 1: Inspect showcase git status; preserve adjacent work**

- [ ] **Step 2: Add interactive Media showcase (image with aspect, optional crop, document) and isolated capture route**

- [ ] **Step 3: Register in home; path-package install; focused + full consumer tests without devices**

- [ ] **Step 4: Commit showcase separately when authorized**

---

### Task 8: Screenshot manifest + gated capture

**REQUIRED SUB-SKILL:** `firstlight-docs-screenshots` (only with explicit simulator/emulator permission)

- [ ] **Step 1: Add `media` to `spec/screenshots.json`**

- [ ] **Step 2: On permission, capture iOS/Android light/dark; commit PNGs**

- [ ] **Step 3: Without permission, leave development checker deferred gaps honest**

---

### Task 9: Constitutional development review

**REQUIRED SUB-SKILL:** `firstlight-review-component`

- [ ] **Step 1: `bin/check-component Media --development` PASS**

- [ ] **Step 2: Write `spec/reviews/media-development.md` with Article I–XII PASS/FAIL/BLOCKED**

- [ ] **Step 3: Update local `roadmap.md` only (gitignored) — Media development delivered; next notification bridge or release rows**

- [ ] **Step 4: Commit review evidence**

```bash
git commit -m "Record Media field development review evidence."
```

---

## Spec coverage checklist

| Design requirement | Task |
| --- | --- |
| `mode` image/document | 3 |
| Crop/`aspect` composition | 3, 4, 5 |
| Single `MediaValue` + `mobile_public` | 1, 3 |
| Camera + library / file picker | 4, 5 |
| ValidatesFields / `error` slot | 2, 3 |
| Clear + replace + best-effort delete | 1, 3, 4, 5 |
| Paired crop + zoom controls | 4, 5 |
| Docs + showcase + review | 6–9 |
| Non-goals (multi, video, system crop) | enforced by Element diagnostics in 3 |

## Execution handoff

Plan saved to `spec/plans/2026-08-24-firstlight-media-field.md`.

**Two execution options:**

1. **Subagent-Driven (recommended)** — fresh subagent per task, review between tasks (`superpowers:subagent-driven-development`)
2. **Inline Execution** — this session with checkpoints (`superpowers:executing-plans`)

Which approach?
