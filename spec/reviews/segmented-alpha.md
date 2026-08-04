---
title: Segmented alpha review evidence
description: Dated development screenshot evidence and remaining release gates for Firstlight Segmented.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/documentation-constitution.md
  - spec/screenshots.json
  - docs/components/segmented.md
  - nativephp.json
  - src/Elements/Segmented.php
  - resources/ios/SegmentedControl.swift
  - resources/ios/SegmentedRenderer.swift
  - resources/android/SegmentedControl.kt
  - resources/android/SegmentedRenderer.kt
  - tests/Feature/SegmentedElementTest.php
  - bin/support/DocumentationScreenshotCapture.php
  - tests/Feature/DocumentationScreenshotCaptureTest.php
---

# Segmented Alpha Review Evidence

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-08-04 |
| Component | `Segmented` / `<firstlight:segmented>` |
| Package revision captured | Dirty working tree based on `c2de840560003ea7321ef25868e58e3baaa899f4` |
| Showcase revision captured | Dirty working tree based on `a07da05cba6dd1c2cd553d6420e604696093f509` |
| iOS simulator | iPhone 17 Pro, iOS 26.5, `EB44C64E-1579-4C13-A1F9-C44FBD496763` |
| Android emulator | Pixel 9 Pro AVD, `emulator-5554` |
| Visual reviewer | Maintainer explicitly approved all four images on 2026-08-04 |

This is development screenshot evidence. It is not clean release evidence and
does not replace physical-device or assistive-technology review.

## Capture evidence

| Result | Command and evidence |
| --- | --- |
| PASS | `bin/capture-doc-screenshots Segmented --showcase=../firstlight-showcase --ios=EB44C64E-1579-4C13-A1F9-C44FBD496763 --android=emulator-5554` published one complete matrix after the focused showcase fixture passed. |
| PASS | The capture guard forced fresh NativePHP development bundles for the requested start URL, waited for the exact Android capture title, restored the original appearances, and rejected neither platform's light/dark pair as identical. |
| PASS | `php artisan test tests/Feature/DocumentationScreenshotCaptureTest.php` — 11 passed, 48 assertions. |
| PASS | Maintainer review accepted the selected, disabled, helper, and error states; light/dark differentiation; native platform presentation; crop; labels; and truncation across all four images. |
| BLOCKED | The package and showcase working trees were dirty, so this matrix was not captured with `--release`. |
| BLOCKED | Physical iOS and Android device, VoiceOver, TalkBack, font scaling, increased contrast, Reduced Motion, RTL, offline behaviour, and host-stability rows are not recorded here. |

## Approved visual evidence

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/segmented/ios-light.png` | `docs/screenshots/segmented/ios-dark.png` |
| Android | `docs/screenshots/segmented/android-light.png` | `docs/screenshots/segmented/android-dark.png` |

The four files were captured from the stable `/captures/segmented` showcase
fixture using the explicit simulator and emulator targets above. No image was
edited to conceal a rendering defect.

## Release checklist

- [x] The screenshot manifest declares the stable route, focused showcase test,
  and four output paths.
- [x] The focused showcase fixture passed against the exact local package
  revision installed by the showcase.
- [x] A complete light/dark matrix was published atomically for iOS and Android.
- [x] The maintainer explicitly approved all four development images.
- [ ] Re-run the guarded workflow with `--release` from clean package and
  showcase revisions and record its report.
- [ ] Record dated physical iOS and Android device and accessibility rows.

## Verdicts

- **Development screenshot readiness: PASS.** The current four-image matrix is
  complete and explicitly approved.
- **Component-release readiness: BLOCKED.** Clean release capture and dated
  physical-device and accessibility evidence remain missing.
- **Catalogue readiness: BLOCKED.** The remaining component matrices and their
  visual approvals are incomplete.

## Catalogue screenshot evidence update — 2026-08-05

The remaining alpha component matrices are now present and visually approved in
the [alpha catalogue screenshot evidence](2026-08-05-alpha-screenshot-evidence.md).
This closes the screenshot-only catalogue gap recorded above. Clean release
capture, physical-device, assistive-technology, and component-specific blocked
rows remain unchanged.
