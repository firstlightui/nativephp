---
title: Stepper alpha review evidence
description: Component-release evidence, showcase audit and remaining device and approval gates for Firstlight Stepper.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/workflows/testing.md
  - spec/workflows/showcase-and-screenshots.md
  - spec/screenshots.json
  - docs/components/stepper.md
  - spec/components/stepper.md
  - spec/reviews/stepper-development.md
  - nativephp.json
  - src/Components/Stepper.php
  - src/Elements/Stepper.php
  - tests/Feature/StepperElementTest.php
  - resources/ios/StepperControl.swift
  - resources/ios/StepperRenderer.swift
  - resources/android/StepperControl.kt
  - resources/android/StepperRenderer.kt
  - tests/ios/StepperSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/StepperTest.kt
---

# Stepper Alpha Review Evidence

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-10-25 |
| Review mode | Component-release (`bin/check-component Stepper` without `--development`) |
| Component | `Stepper` / `<firstlight:stepper>` |
| State class | Discrete; server-authoritative adjacent-value proposals |
| Implementation | Paired Firstlight SwiftUI and Material 3 renderers |
| Package revision captured | `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` (`v1.0.0-RC6`), clean |
| Package revision tested | `461335df2bcb51e87d092bbdf3a90e6b2a4e7114` on `release/component-reviews`, clean; later commits on the branch change only reviews and documentation |
| Showcase revision | `c7ed28ef489e1caae305fc84961af9f549eb409d` (`firstlightui/showcase` `main`), clean; its lock installs `firstlightui/nativephp` at `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` |
| iOS simulator | Firstlight iPhone 17 Pro iOS 26.5 (`758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC`); XCTest also on Firstlight iPhone 17 Pro iOS 27.0 (`731CC3F2-1651-4522-A5A3-A781710EE166`) |
| Android emulator | `emulator-5554`, AVD Medium_Phone, Android 17 (API 37), `google_apis_playstore_ps16k` (not a Pixel 9 Pro) |
| Visual reviewer | Pending maintainer approval (row below) |
| Physical devices | None used; both device rows remain BLOCKED |

## Release capture

| Result | Command and evidence |
| --- | --- |
| PASS | `bin/capture-doc-screenshot-batch Stepper StatusLabel TextField CodeEditor TextArea --showcase=../showcase --ios=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC --android=emulator-5554 --release` — exit 0 at 2026-10-25 07:14:48 AEDT (batch 5, attempt 2; attempt 1 stopped before publishing because the Android showcase was not foregrounded after an upstream host crash on cold launch, and published nothing). The batch built and launched each host once, ran the focused showcase capture tests, cold-launched `/captures/stepper` per appearance, waited for stable frames, rejected identical light/dark pairs and published the matrix. It then restored the showcase `.env`, both appearances, iOS Reduce Motion and the Android animator scale. |
| PASS | The orchestrator reported `Package: 19d12ff9d48baeddd312a6ef5d1a749bdba585e8` and `Showcase: c7ed28ef489e1caae305fc84961af9f549eb409d` with no `(dirty)` marker. It also verified that the showcase's installed `firstlightui/nativephp` reference equals the package `HEAD`. Published images were moved out of the tree between batches so that each batch started clean, and were committed unchanged. |
| PASS | The agent inspected the four images below for documented states, theme, crop, labels, truncation, system chrome and accidental data. Fixture issue: Fractional dose and Quantity with error have no model or callback, so their −/+ buttons render dimmed on both platforms, like the disabled row. |
| BLOCKED | Maintainer visual approval of this release matrix is not yet recorded. |

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/stepper/ios-light.png` | `docs/screenshots/stepper/ios-dark.png` |
| Android | `docs/screenshots/stepper/android-light.png` | `docs/screenshots/stepper/android-dark.png` |

Thirty matrices were captured with `bin/capture-doc-screenshot-batch` in six five-component batches rather than 31 separate `bin/capture-doc-screenshots` runs. Release mode requires both repositories to be clean before launch, and each single-component run publishes into the package tree. The batch entrypoint uses the same `DocumentationScreenshotCapture` guards.

## Automated and consumer evidence

| Result | Command and evidence |
| --- | --- |
| PASS | `vendor/bin/pest --log-junit ../evidence/pest.xml` at `461335df2bcb51e87d092bbdf3a90e6b2a4e7114` — 1465 passed, 5 skipped, 1 deprecation, 4 warnings, exit 0 (49.7 s). |
| PASS | `xcodebuild -scheme FirstlightIOSControls -destination 'platform=iOS Simulator,id=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC' -resultBundlePath ../evidence/ios26.xcresult test` (iPhone 17 Pro, iOS 26.5) — TEST SUCCEEDED: 304 tests, 298 passed, 0 failed, 6 skipped. |
| PASS | Same command on `id=731CC3F2-1651-4522-A5A3-A781710EE166` (iPhone 17 Pro, iOS 27.0) — TEST SUCCEEDED: 304 tests, 298 passed, 0 failed, 6 skipped. |
| PASS | `JAVA_HOME=<jdk-21> tests/android/gradlew -p tests/android testDebugUnitTest verifyPaparazziDebug` — BUILD SUCCESSFUL; 309 tests in 87 classes, 0 failures, 0 errors. |
| PASS | `bin/check-docs --development` — "Documentation checks passed.", exit 0. |
| PASS | `bin/check-component <Name> --development` for all 31 manifest components — exit 0 each. |
| PASS | Showcase `php artisan test` at `c7ed28e` — 205 passed (4194 assertions). |
| PASS | Showcase `php artisan native:plugin:validate ../nativephp` — exit 0, with one warning: "No bridge_functions defined in manifest". |

Component-specific tests within those runs:

| Result | Test file and evidence |
| --- | --- |
| PASS | `tests/Feature/StepperElementTest.php` — included in the full Pest run. |
| FAIL | `tests/ios/StepperSnapshotTests.swift` — iOS 26.5: not found; iOS 27.0: not found. |
| FAIL | `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/StepperTest.kt` — not found in JUnit results. |

Focused showcase tests: `tests/Feature/StepperCaptureTest.php`, `tests/Feature/StepperShowcaseTest.php`. They ran in the full showcase suite above and, for the capture fixture, again inside the guarded capture.

## Showcase demo audit

Audited `firstlightui/showcase` `c7ed28ef489e1caae305fc84961af9f549eb409d` against `docs/components/stepper.md`. Covered on the interactive page:

- integer model and float model with step 0.25
- lower and upper bounds disabling one direction
- static error, `disabled`
- server rejection with an attempt counter, programmatic publish

Gaps (listed, not invented):

- The rapid-tap in-flight guard has no counter on an accepting stepper.
- A stepper named only by `a11y-label` is not demonstrated.
- Capture fixture: in `captures/stepper.blade.php`, Fractional dose and Quantity with error set `:value` with no model or callback. As a result, the release images show both steppers with dimmed −/+ buttons on both platforms, which looks the same as the disabled row. This is the same showcase fixture issue as Slider, Date Picker and Time Picker.

## Constitution review

- **Article I — PASS.** `bin/check-component Stepper --development` passes the manifest, renderer-mapping and prohibited-bridge checks; no WebView or bridge call exists in component source.
- **Article II — PASS.** The public API matches `docs/components/stepper.md` and is enforced by `tests/Feature/StepperElementTest.php`.
- **Article III — PASS for applicable state.** State class: Discrete; server-authoritative adjacent-value proposals. Contract tests cover the applicable rejection, programmatic and no-echo rules; the showcase gaps above are not counted as evidence.
- **Article IV —** PASS for simulator/emulator evidence; BLOCKED for physical devices. The production renderers built into the generated hosts and rendered `/captures/stepper` on iOS 26.5 and Android API 37 in the clean release capture.
- **Article V — PASS by implementation audit.** Paired Firstlight SwiftUI and Material 3 renderers; see the development review and documentation platform section. Native feel on hardware is part of the device rows.
- **Article VI — BLOCKED.** Automated accessibility semantics pass, but live VoiceOver/TalkBack, Dynamic Type/font scale, Reduce Motion, increased contrast and RTL on physical devices are not yet recorded.
- **Article VII — PASS.** Semantic theme tokens only; no style-bag or platform escape props (contract tests).
- **Article VIII — PASS.** Unchanged from the development review.
- **Article IX — BLOCKED.** PHP, iOS (26.5 and 27.0), Android, showcase, documentation and release captures pass; physical-device checks (IX.4) and maintainer visual approval are outstanding.
- **Article X — BLOCKED for catalogue readiness.** A component review does not complete the catalogue.
- **Article XI — PASS.** The review skill, structural gate and documentation gate were applied; missing evidence is recorded as BLOCKED.

Article XII is not applicable.
## Release checklist

- [x] Clean `--release` capture from `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` and `c7ed28ef489e1caae305fc84961af9f549eb409d` published the four-image matrix (2026-10-25).
- [x] Full package suites (Pest, iOS 26.5 and 27.0 XCTest, Android unit/Paparazzi), `bin/check-component Stepper --development`, `bin/check-docs --development`, showcase suite and plugin validation pass at `461335df2bcb51e87d092bbdf3a90e6b2a4e7114`.
- [x] Showcase demo audited against every documented state; gaps listed above.
- [ ] BLOCKED — Maintainer visual approval of the four release images listed above (record reviewer, date and verdict per image).
- [ ] BLOCKED — Physical iOS device row:
  - [ ] Device: record model, iOS version, date and tester. Install the showcase built from `461335df2bcb51e87d092bbdf3a90e6b2a4e7114` or later and open **Stepper** from the showcase home (`/components/stepper`).
  - [ ] Quantity steps by whole numbers; Fractional dose by 0.25 and stays a float.
  - [ ] Minimum quantity can only increase; Maximum quantity can only decrease.
  - [ ] Disabled quantity stays 7.
  - [ ] Server-approved quantity stays 4 and counts attempts; Publish higher quantity sets 8.
  - [ ] Rapid input: tap + ten times as fast as possible; the value never skips or goes backwards, and taps during an in-flight proposal are ignored.
  - [ ] VoiceOver: announces label, value, hint, helper/error, disabled; named decrease/increase actions; the unavailable direction is disabled at a bound.
  - [ ] Dynamic Type at the largest accessibility size (AX5): label and value wrap; buttons keep 44 pt / 48 dp.
  - [ ] Reduce Motion on: no custom animation; platform press feedback only.
  - [ ] Increase Contrast on (and Smart Invert off): disabled direction remains distinguishable.
  - [ ] Right-to-left (device language Arabic or Hebrew, or the Right-to-Left pseudolanguage): minus/plus order follows platform RTL conventions.
  - [ ] Light and dark appearance both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.
- [ ] BLOCKED — Physical Android device row:
  - [ ] Device: record model, Android version, date and tester. Install the showcase built from `461335df2bcb51e87d092bbdf3a90e6b2a4e7114` or later and open **Stepper** from the showcase home.
  - [ ] Quantity steps by whole numbers; Fractional dose by 0.25 and stays a float.
  - [ ] Minimum quantity can only increase; Maximum quantity can only decrease.
  - [ ] Disabled quantity stays 7.
  - [ ] Server-approved quantity stays 4 and counts attempts; Publish higher quantity sets 8.
  - [ ] Rapid input: tap + ten times as fast as possible; the value never skips or goes backwards, and taps during an in-flight proposal are ignored.
  - [ ] TalkBack: announces label, value, hint, helper/error, disabled; named decrease/increase actions; the unavailable direction is disabled at a bound.
  - [ ] Font size at maximum plus Display size largest (font scale 2.0 where available): label and value wrap; buttons keep 44 pt / 48 dp.
  - [ ] Remove animations on (Accessibility) or animator scale 0: no custom animation; platform press feedback only.
  - [ ] High-contrast text / contrast level high: disabled direction remains distinguishable.
  - [ ] Right-to-left (Arabic or Hebrew locale, or Developer options → Force RTL layout direction): minus/plus order follows platform RTL conventions.
  - [ ] Light and dark theme both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.

## Verdicts

- **Automated, consumer and simulator/emulator evidence: PASS.**
- **Component-release readiness: BLOCKED.** Only the maintainer visual approval and the two physical-device rows remain; simulator evidence does not substitute for them.
- **Catalogue readiness: BLOCKED.** Every component review, physical-device evidence and the shared alpha gate must pass.
