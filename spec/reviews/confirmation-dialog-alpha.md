---
title: Confirmation Dialog alpha review evidence
description: Component-release evidence, showcase audit and remaining device and approval gates for Firstlight Confirmation Dialog.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/workflows/testing.md
  - spec/workflows/showcase-and-screenshots.md
  - spec/screenshots.json
  - docs/components/confirmation-dialog.md
  - spec/components/confirmation-dialog.md
  - spec/reviews/confirmation-dialog-development.md
  - nativephp.json
  - src/Components/ConfirmationDialog.php
  - src/Elements/ConfirmationDialog.php
  - tests/Feature/ConfirmationDialogElementTest.php
  - resources/ios/ConfirmationDialogControl.swift
  - resources/ios/ConfirmationDialogRenderer.swift
  - resources/android/ConfirmationDialogControl.kt
  - resources/android/ConfirmationDialogRenderer.kt
  - tests/ios/ConfirmationDialogSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/ConfirmationDialogTest.kt
---

# Confirmation Dialog Alpha Review Evidence

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-10-25 |
| Review mode | Component-release (`bin/check-component ConfirmationDialog` without `--development`) |
| Component | `ConfirmationDialog` / `<firstlight:confirmation-dialog>` |
| State class | Action/display; server-controlled confirm/cancel decision |
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
| PASS | `bin/capture-doc-screenshot-batch Switch AlertDialog ConfirmationDialog Modal BottomSheet --showcase=../showcase --ios=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC --android=emulator-5554 --release` — exit 0 at 2026-10-25 07:17:07 AEDT (batch 6). The batch built and launched each host once, ran the focused showcase capture tests, cold-launched `/captures/confirmation-dialog` per appearance, waited for stable frames, rejected identical light/dark pairs and published the matrix. It then restored the showcase `.env`, both appearances, iOS Reduce Motion and the Android animator scale. |
| PASS | The orchestrator reported `Package: 19d12ff9d48baeddd312a6ef5d1a749bdba585e8` and `Showcase: c7ed28ef489e1caae305fc84961af9f549eb409d` with no `(dirty)` marker. It also verified that the showcase's installed `firstlightui/nativephp` reference equals the package `HEAD`. Published images were moved out of the tree between batches so that each batch started clean, and were committed unchanged. |
| PASS | The agent inspected the four images below for documented states, theme, crop, labels, truncation, system chrome and accidental data. Defect: on iOS 26.5 the dialog presents as a popover anchored to the zero-size source view at the top leading edge. It covers the navigation title and omits the "Keep appointment" cancel action. The previously approved iOS image (8a0d7c6) shows a bottom action sheet with the cancel action. Android is correct. See the bug list. |
| BLOCKED | Maintainer visual approval of this release matrix is not yet recorded. |

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/confirmation-dialog/ios-light.png` | `docs/screenshots/confirmation-dialog/ios-dark.png` |
| Android | `docs/screenshots/confirmation-dialog/android-light.png` | `docs/screenshots/confirmation-dialog/android-dark.png` |

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
| PASS | `tests/Feature/ConfirmationDialogElementTest.php` — included in the full Pest run. |
| PASS | `tests/ios/ConfirmationDialogSnapshotTests.swift` — iOS 26.5: 8 passed, 0 failed; iOS 27.0: 8 passed, 0 failed. |
| PASS | `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/ConfirmationDialogTest.kt` — 7 tests, 0 failed, 0 skipped (`testDebugUnitTest` / Paparazzi verify). |

Focused showcase tests: `tests/Feature/ConfirmationDialogCaptureTest.php`, `tests/Feature/ConfirmationDialogShowcaseTest.php`. They ran in the full showcase suite above and, for the capture fixture, again inside the guarded capture.

## Showcase demo audit

Audited `firstlightui/showcase` `c7ed28ef489e1caae305fc84961af9f549eb409d` against `docs/components/confirmation-dialog.md`. Covered on the interactive page:

- default confirmation with custom labels
- destructive tone, long destructive copy
- timed programmatic close
- confirmed and cancelled counters; also used by the List destroy flow

Gaps (listed, not invented):

- Default package-chrome labels (`Confirm`/`Cancel` omitted) are not demonstrated.
- Copy-only republication while open is not demonstrated.

## Constitution review

- **Article I — PASS.** `bin/check-component ConfirmationDialog --development` passes the manifest, renderer-mapping and prohibited-bridge checks; no WebView or bridge call exists in component source.
- **Article II — PASS.** The public API matches `docs/components/confirmation-dialog.md` and is enforced by `tests/Feature/ConfirmationDialogElementTest.php`.
- **Article III — PASS for applicable state.** State class: Action/display; server-controlled confirm/cancel decision. Contract tests cover the applicable rejection, programmatic and no-echo rules; the showcase gaps above are not counted as evidence.
- **Article IV —** PASS for simulator/emulator evidence; BLOCKED for physical devices. The production renderers built into the generated hosts and rendered `/captures/confirmation-dialog` on iOS 26.5 and Android API 37 in the clean release capture.
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
- [x] Full package suites (Pest, iOS 26.5 and 27.0 XCTest, Android unit/Paparazzi), `bin/check-component ConfirmationDialog --development`, `bin/check-docs --development`, showcase suite and plugin validation pass at `461335df2bcb51e87d092bbdf3a90e6b2a4e7114`.
- [x] Showcase demo audited against every documented state; gaps listed above.
- [ ] BLOCKED — Maintainer visual approval of the four release images listed above (record reviewer, date and verdict per image).
- [ ] BLOCKED — Physical iOS device row:
  - [ ] Device: record model, iOS version, date and tester. Install the showcase built from `461335df2bcb51e87d092bbdf3a90e6b2a4e7114` or later and open **Confirmation Dialog** from the showcase home (`/components/confirmation-dialog`).
  - [ ] Confirm increments confirmed only; cancel increments cancelled only.
  - [ ] Destructive actions use native destructive styling.
  - [ ] Timed example closes with both counts unchanged.
  - [ ] Rapid input: double-tap Confirm; one confirmation.
  - [ ] Android back / outside tap counts as one cancellation.
  - [ ] VoiceOver: dialog named by its title; message read; confirm and cancel reachable in native order; destructive role conveyed.
  - [ ] Dynamic Type at the largest accessibility size (AX5): long title, message and labels wrap.
  - [ ] Reduce Motion on: presentation respects Reduce Motion / removed animations.
  - [ ] Increase Contrast on (and Smart Invert off): destructive action remains distinguishable.
  - [ ] Right-to-left (device language Arabic or Hebrew, or the Right-to-Left pseudolanguage): action order and text mirror.
  - [ ] Light and dark appearance both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.
- [ ] BLOCKED — Physical Android device row:
  - [ ] Device: record model, Android version, date and tester. Install the showcase built from `461335df2bcb51e87d092bbdf3a90e6b2a4e7114` or later and open **Confirmation Dialog** from the showcase home.
  - [ ] Confirm increments confirmed only; cancel increments cancelled only.
  - [ ] Destructive actions use native destructive styling.
  - [ ] Timed example closes with both counts unchanged.
  - [ ] Rapid input: double-tap Confirm; one confirmation.
  - [ ] Android back / outside tap counts as one cancellation.
  - [ ] TalkBack: dialog named by its title; message read; confirm and cancel reachable in native order; destructive role conveyed.
  - [ ] Font size at maximum plus Display size largest (font scale 2.0 where available): long title, message and labels wrap.
  - [ ] Remove animations on (Accessibility) or animator scale 0: presentation respects Reduce Motion / removed animations.
  - [ ] High-contrast text / contrast level high: destructive action remains distinguishable.
  - [ ] Right-to-left (Arabic or Hebrew locale, or Developer options → Force RTL layout direction): action order and text mirror.
  - [ ] Light and dark theme both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.

## Verdicts

- **Automated, consumer and simulator/emulator evidence: PASS.**
- **Component-release readiness: BLOCKED.** Only the maintainer visual approval and the two physical-device rows remain; simulator evidence does not substitute for them.
- **Catalogue readiness: BLOCKED.** Every component review, physical-device evidence and the shared alpha gate must pass.
