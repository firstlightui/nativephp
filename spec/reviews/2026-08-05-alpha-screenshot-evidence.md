---
title: Alpha catalogue development screenshot evidence
description: Approved iOS and Android light and dark documentation matrices for every Firstlight alpha component.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/documentation-constitution.md
  - spec/screenshots.json
  - bin/capture-doc-screenshots
  - bin/capture-doc-screenshot-batch
  - bin/support/DocumentationScreenshotCapture.php
  - tests/Feature/DocumentationScreenshotCaptureTest.php
---

# Alpha Catalogue Development Screenshot Evidence

## Evidence boundary

This record closes the development screenshot and visual-approval rows for all
17 alpha components. It does not claim clean release capture, physical-device
coverage, or live VoiceOver and TalkBack review.

| Evidence | Recorded value |
| --- | --- |
| Capture dates | 2026-08-04 to 2026-08-05 |
| Package revision captured | Dirty working tree based on `c2de840560003ea7321ef25868e58e3baaa899f4` |
| Showcase revision captured | Dirty working tree based on `a07da05cba6dd1c2cd553d6420e604696093f509` |
| Installed package lock | Showcase path reference updated to `c2de840560003ea7321ef25868e58e3baaa899f4` |
| iOS simulator | iPhone 17 Pro, iOS 26.5, `EB44C64E-1579-4C13-A1F9-C44FBD496763` |
| Android emulator | Pixel 9 Pro AVD, `emulator-5554` |
| Visual approval | The maintainer approved Segmented and Status Label directly, then authorized the agent to approve every remaining matrix after visual inspection on 2026-08-05. |

The guarded workflow ran every focused showcase capture test, built each native
host once for the batched pass, captured by platform appearance, restored the
showcase start route and exact prior device settings, and published only
complete four-image matrices. The agent rejected Icon Button's first iOS dark
frame because it showed the NativePHP launch screen. After the iOS route-settle
guard was increased from one to three seconds and covered by a regression test,
the complete Icon Button matrix was recaptured and approved.

## Approved matrices

| Component | iOS | Android |
| --- | --- | --- |
| [Badge](../../docs/components/badge.md) | [Light](../../docs/screenshots/badge/ios-light.png) / [Dark](../../docs/screenshots/badge/ios-dark.png) | [Light](../../docs/screenshots/badge/android-light.png) / [Dark](../../docs/screenshots/badge/android-dark.png) |
| [Button](../../docs/components/button.md) | [Light](../../docs/screenshots/button/ios-light.png) / [Dark](../../docs/screenshots/button/ios-dark.png) | [Light](../../docs/screenshots/button/android-light.png) / [Dark](../../docs/screenshots/button/android-dark.png) |
| [Choice Group](../../docs/components/choice-group.md) | [Light](../../docs/screenshots/choice-group/ios-light.png) / [Dark](../../docs/screenshots/choice-group/ios-dark.png) | [Light](../../docs/screenshots/choice-group/android-light.png) / [Dark](../../docs/screenshots/choice-group/android-dark.png) |
| [Date Picker](../../docs/components/date-picker.md) | [Light](../../docs/screenshots/date-picker/ios-light.png) / [Dark](../../docs/screenshots/date-picker/ios-dark.png) | [Light](../../docs/screenshots/date-picker/android-light.png) / [Dark](../../docs/screenshots/date-picker/android-dark.png) |
| [Time Picker](../../docs/components/time-picker.md) | [Light](../../docs/screenshots/time-picker/ios-light.png) / [Dark](../../docs/screenshots/time-picker/ios-dark.png) | [Light](../../docs/screenshots/time-picker/android-light.png) / [Dark](../../docs/screenshots/time-picker/android-dark.png) |
| [Icon Button](../../docs/components/icon-button.md) | [Light](../../docs/screenshots/icon-button/ios-light.png) / [Dark](../../docs/screenshots/icon-button/ios-dark.png) | [Light](../../docs/screenshots/icon-button/android-light.png) / [Dark](../../docs/screenshots/icon-button/android-dark.png) |
| [Pill Group](../../docs/components/pill-group.md) | [Light](../../docs/screenshots/pill-group/ios-light.png) / [Dark](../../docs/screenshots/pill-group/ios-dark.png) | [Light](../../docs/screenshots/pill-group/android-light.png) / [Dark](../../docs/screenshots/pill-group/android-dark.png) |
| [Progress](../../docs/components/progress.md) | [Light](../../docs/screenshots/progress/ios-light.png) / [Dark](../../docs/screenshots/progress/ios-dark.png) | [Light](../../docs/screenshots/progress/android-light.png) / [Dark](../../docs/screenshots/progress/android-dark.png) |
| [Search Field](../../docs/components/search-field.md) | [Light](../../docs/screenshots/search-field/ios-light.png) / [Dark](../../docs/screenshots/search-field/ios-dark.png) | [Light](../../docs/screenshots/search-field/android-light.png) / [Dark](../../docs/screenshots/search-field/android-dark.png) |
| [Segmented](../../docs/components/segmented.md) | [Light](../../docs/screenshots/segmented/ios-light.png) / [Dark](../../docs/screenshots/segmented/ios-dark.png) | [Light](../../docs/screenshots/segmented/android-light.png) / [Dark](../../docs/screenshots/segmented/android-dark.png) |
| [Select](../../docs/components/select.md) | [Light](../../docs/screenshots/select/ios-light.png) / [Dark](../../docs/screenshots/select/ios-dark.png) | [Light](../../docs/screenshots/select/android-light.png) / [Dark](../../docs/screenshots/select/android-dark.png) |
| [Slider](../../docs/components/slider.md) | [Light](../../docs/screenshots/slider/ios-light.png) / [Dark](../../docs/screenshots/slider/ios-dark.png) | [Light](../../docs/screenshots/slider/android-light.png) / [Dark](../../docs/screenshots/slider/android-dark.png) |
| [Status Label](../../docs/components/status-label.md) | [Light](../../docs/screenshots/status-label/ios-light.png) / [Dark](../../docs/screenshots/status-label/ios-dark.png) | [Light](../../docs/screenshots/status-label/android-light.png) / [Dark](../../docs/screenshots/status-label/android-dark.png) |
| [Stepper](../../docs/components/stepper.md) | [Light](../../docs/screenshots/stepper/ios-light.png) / [Dark](../../docs/screenshots/stepper/ios-dark.png) | [Light](../../docs/screenshots/stepper/android-light.png) / [Dark](../../docs/screenshots/stepper/android-dark.png) |
| [Switch](../../docs/components/switch.md) | [Light](../../docs/screenshots/switch/ios-light.png) / [Dark](../../docs/screenshots/switch/ios-dark.png) | [Light](../../docs/screenshots/switch/android-light.png) / [Dark](../../docs/screenshots/switch/android-dark.png) |
| [Text Area](../../docs/components/text-area.md) | [Light](../../docs/screenshots/text-area/ios-light.png) / [Dark](../../docs/screenshots/text-area/ios-dark.png) | [Light](../../docs/screenshots/text-area/android-light.png) / [Dark](../../docs/screenshots/text-area/android-dark.png) |
| [Text Field](../../docs/components/text-field.md) | [Light](../../docs/screenshots/text-field/ios-light.png) / [Dark](../../docs/screenshots/text-field/ios-dark.png) | [Light](../../docs/screenshots/text-field/android-light.png) / [Dark](../../docs/screenshots/text-field/android-dark.png) |

Visual review covered authored selected, disabled, loading, helper, and error
states where applicable; light and dark differentiation; native platform
expression; crop; labels; truncation; and accidental data. All 68 files were
present at their manifest paths with iOS dimensions of 1206 by 2622 and Android
dimensions of 1280 by 2856.

## Verification

| Result | Evidence |
| --- | --- |
| PASS | `composer test` — 751 tests and 2,202 assertions passed; five model evals were intentionally skipped without `--evals`. |
| PASS | `vendor/bin/pest tests/Feature/DocumentationScreenshotCaptureTest.php` — 13 tests, 81 assertions. |
| PASS | `bin/build-docs-artifacts` regenerated `llms.txt` and `llms-full.txt`. |
| PASS | `bin/check-docs --development`. |
| PASS | `git diff --check`. |

## Remaining release gates

- Re-run the guarded matrices with `--release` from clean package and showcase
  revisions and retain the factual reports.
- Record dated physical iOS and Android interaction and accessibility evidence,
  including VoiceOver, TalkBack, scaling, increased contrast, Reduced Motion,
  right-to-left layout, offline behaviour, reconciliation, and rapid input.
- Preserve component-specific upstream runtime and native-test blockers in the
  individual development reviews; screenshot approval does not clear them.
