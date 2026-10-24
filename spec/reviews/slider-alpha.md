---
title: Slider alpha review evidence
description: Component-release evidence, showcase audit and remaining device and approval gates for Firstlight Slider.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/workflows/testing.md
  - spec/workflows/showcase-and-screenshots.md
  - spec/screenshots.json
  - docs/components/slider.md
  - spec/components/slider.md
  - spec/reviews/slider-development.md
  - nativephp.json
  - src/Components/Slider.php
  - src/Elements/Slider.php
  - tests/Feature/SliderElementTest.php
  - resources/ios/SliderControl.swift
  - resources/ios/SliderRenderer.swift
  - resources/android/SliderControl.kt
  - resources/android/SliderRenderer.kt
  - tests/ios/SliderSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SliderTest.kt
---

# Slider Alpha Review Evidence

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-10-25 |
| Review mode | Component-release (`bin/check-component Slider` without `--development`) |
| Component | `Slider` / `<firstlight:slider>` |
| State class | Continuous; native-thread gesture with live/blur/debounce publication |
| Implementation | Paired Firstlight SwiftUI and Material 3 renderers |
| Package revision captured | `e6ab75fa82a27b32ab8163b2f94d166a8429d9f1` on `release/component-reviews`, clean (recaptured after the fixes on this branch) |
| Package revision tested | `42e875a91949981b324cd20854e591a292b9475f` on `release/component-reviews`, clean; later commits on the branch change only reviews and documentation |
| Showcase revision | `37669c2e077d3100550de4f5c4822ecb112f1d26` (local branch `release/component-review-fixtures` on `c7ed28ef489e1caae305fc84961af9f549eb409d`), clean; its lock installs `firstlightui/nativephp` at `e6ab75fa82a27b32ab8163b2f94d166a8429d9f1` |
| iOS simulator | Firstlight iPhone 17 Pro iOS 26.5 (`758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC`); XCTest also on Firstlight iPhone 17 Pro iOS 27.0 (`731CC3F2-1651-4522-A5A3-A781710EE166`) |
| Android emulator | `emulator-5554`, AVD Medium_Phone, Android 17 (API 37), `google_apis_playstore_ps16k` (not a Pixel 9 Pro) |
| Visual reviewer | Pending maintainer approval (row below) |
| Physical devices | None used; both device rows remain BLOCKED |

## Release capture

| Result | Command and evidence |
| --- | --- |
| PASS | `bin/capture-doc-screenshot-batch TimePicker Slider Stepper ListSection --showcase=../showcase --ios=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC --android=emulator-5554 --release` — exit 0 at 2026-10-25 08:02:39 AEDT (recapture batch 2). The batch built and launched each host once, ran the focused showcase capture tests, cold-launched `/captures/slider` per appearance, waited for stable frames, rejected identical light/dark pairs and published the matrix. It then restored the showcase `.env`, both appearances, iOS Reduce Motion and the Android animator scale. |
| PASS | The orchestrator reported `Package: e6ab75fa82a27b32ab8163b2f94d166a8429d9f1` and `Showcase: 37669c2e077d3100550de4f5c4822ecb112f1d26` with no `(dirty)` marker. It also verified that the showcase's installed `firstlightui/nativephp` reference equals the package `HEAD`. Published images were moved out of the tree between batches so that each batch started clean, and were committed unchanged. |
| PASS | The agent inspected the four images below for documented states, theme, crop, labels, truncation, system chrome and accidental data. Temperature offset and Dose with error are now bound and render with the theme primary; only Disabled dose is dimmed. No defects seen. |
| BLOCKED | Maintainer visual approval of this release matrix is not yet recorded. |

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/slider/ios-light.png` | `docs/screenshots/slider/ios-dark.png` |
| Android | `docs/screenshots/slider/android-light.png` | `docs/screenshots/slider/android-dark.png` |

Thirty matrices were captured with `bin/capture-doc-screenshot-batch` in six five-component batches rather than 31 separate `bin/capture-doc-screenshots` runs. Release mode requires both repositories to be clean before launch, and each single-component run publishes into the package tree. The batch entrypoint uses the same `DocumentationScreenshotCapture` guards. This matrix was recaptured after the component and fixture fixes on this branch, from clean package and showcase trees, using the same guarded batch entrypoint. The showcase fixes are on the local branch `release/component-review-fixtures`, which is not yet pushed.

## Automated and consumer evidence

| Result | Command and evidence |
| --- | --- |
| PASS | `vendor/bin/pest` at `42e875a91949981b324cd20854e591a292b9475f` — 1470 passed, 5 skipped, 1 deprecation, 4 warnings, exit 0 (48.4 s). |
| PASS | `xcodebuild -scheme FirstlightIOSControls -destination 'platform=iOS Simulator,id=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC' -resultBundlePath ../evidence/run2/ios26.xcresult test` (iPhone 17 Pro, iOS 26.5) — TEST SUCCEEDED: 304 tests, 298 passed, 0 failed, 6 skipped. |
| PASS | Same command on `id=731CC3F2-1651-4522-A5A3-A781710EE166` (iPhone 17 Pro, iOS 27.0) — TEST SUCCEEDED: 304 tests, 298 passed, 0 failed, 6 skipped. |
| PASS | `JAVA_HOME=<jdk-21> tests/android/gradlew -p tests/android testDebugUnitTest verifyPaparazziDebug --rerun-tasks` — BUILD SUCCESSFUL, 35 tasks executed; 309 tests in 87 classes, 0 failures, 0 errors. |
| PASS | `bin/check-docs --development` — "Documentation checks passed.", exit 0. |
| PASS | `bin/check-component <Name> --development` for all 31 manifest components — exit 0 each. |
| PASS | Showcase `php artisan test` at `acc4dc1` (local branch `release/component-review-fixtures` on `c7ed28e`) — 206 passed (4206 assertions). |
| PASS | Showcase `php artisan native:plugin:validate ../nativephp` — exit 0, with one warning: "No bridge_functions defined in manifest". |

Component-specific tests within those runs:

| Result | Test file and evidence |
| --- | --- |
| PASS | `tests/Feature/SliderElementTest.php` — included in the full Pest run. |
| PASS | `tests/ios/SliderSnapshotTests.swift` — iOS 26.5: 5 passed, 0 failed; iOS 27.0: 5 passed, 0 failed. |
| PASS | `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SliderTest.kt` — 5 tests, 0 failed, 0 skipped (`testDebugUnitTest` / Paparazzi verify). |

Focused showcase tests: `tests/Feature/SliderCaptureTest.php`, `tests/Feature/SliderShowcaseTest.php`. They ran in the full showcase suite above and, for the capture fixture, again inside the guarded capture.

## Showcase demo audit

Audited `firstlightui/showcase` `c7ed28ef489e1caae305fc84961af9f549eb409d` against `docs/components/slider.md`. Covered on the interactive page:

- live integer slider with `a11y-value` units
- blur fractional slider with negative bounds and step 0.25
- debounced 0–1 slider with step 0.1
- static error, `disabled`
- server rejection with an attempt counter, programmatic publish

Gaps (listed, not invented):

- A slider named only by `a11y-label` is not demonstrated.
- A programmatic publication during an active drag (must replace the draft) is not instrumented.

## Constitution review

- **Article I — PASS.** `bin/check-component Slider --development` passes the manifest, renderer-mapping and prohibited-bridge checks; no WebView or bridge call exists in component source.
- **Article II — PASS.** The public API matches `docs/components/slider.md` and is enforced by `tests/Feature/SliderElementTest.php`.
- **Article III — PASS for applicable state.** State class: Continuous; native-thread gesture with live/blur/debounce publication. Contract tests cover the applicable rejection, programmatic and no-echo rules; the showcase gaps above are not counted as evidence.
- **Article IV —** PASS for simulator/emulator evidence; BLOCKED for physical devices. The production renderers built into the generated hosts and rendered `/captures/slider` on iOS 26.5 and Android API 37 in the clean release capture.
- **Article V — PASS by implementation audit.** Paired Firstlight SwiftUI and Material 3 renderers; see the development review and documentation platform section. Native feel on hardware is part of the device rows.
- **Article VI — BLOCKED.** Automated accessibility semantics pass, but live VoiceOver/TalkBack, Dynamic Type/font scale, Reduce Motion, increased contrast and RTL on physical devices are not yet recorded.
- **Article VII — PASS.** Semantic theme tokens only; no style-bag or platform escape props (contract tests).
- **Article VIII — PASS.** Unchanged from the development review.
- **Article IX — BLOCKED.** PHP, iOS (26.5 and 27.0), Android, showcase, documentation and release captures pass; physical-device checks (IX.4) and maintainer visual approval are outstanding.
- **Article X — BLOCKED for catalogue readiness.** A component review does not complete the catalogue.
- **Article XI — PASS.** The review skill, structural gate and documentation gate were applied; missing evidence is recorded as BLOCKED.

Article XII is not applicable.
## Release checklist

- [x] Clean `--release` capture from `e6ab75fa82a27b32ab8163b2f94d166a8429d9f1` and `37669c2e077d3100550de4f5c4822ecb112f1d26` published the four-image matrix (2026-10-25).
- [x] Full package suites (Pest, iOS 26.5 and 27.0 XCTest, Android unit/Paparazzi), `bin/check-component Slider --development`, `bin/check-docs --development`, showcase suite and plugin validation pass at `42e875a91949981b324cd20854e591a292b9475f`.
- [x] Showcase demo audited against every documented state; gaps listed above.
- [ ] BLOCKED — Maintainer visual approval of the four release images listed above (record reviewer, date and verdict per image).
- [ ] BLOCKED — Physical iOS device row:
  - [ ] Device: record model, iOS version, date and tester. Install the showcase built from `42e875a91949981b324cd20854e591a292b9475f` or later and open **Slider** from the showcase home (`/components/slider`).
  - [ ] Live dose updates PHP on each whole step while dragging; blur only on release; debounce after 500 ms quiet or on release.
  - [ ] Thumb snaps to the authored grid, including fractional steps.
  - [ ] Disabled dose stays at 7.
  - [ ] Server-approved dose snaps back to 4 and counts attempts; Publish higher dose sets 8 without an event.
  - [ ] Rapid input: flick back and forth quickly; the gesture stays smooth and the final PHP value matches the thumb.
  - [ ] VoiceOver: adjustable slider named by its label, value (with units for Live dose), hint, error, disabled; increment/decrement gestures move one step.
  - [ ] Dynamic Type at the largest accessibility size (AX5): label, helper and error wrap; the thumb keeps a usable target.
  - [ ] Reduce Motion on: thumb movement respects Reduce Motion / removed animations.
  - [ ] Increase Contrast on (and Smart Invert off): active/inactive track and thumb remain distinguishable.
  - [ ] Right-to-left (device language Arabic or Hebrew, or the Right-to-Left pseudolanguage): minimum is on the right; dragging left increases the value.
  - [ ] Light and dark appearance both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.
- [ ] BLOCKED — Physical Android device row:
  - [ ] Device: record model, Android version, date and tester. Install the showcase built from `42e875a91949981b324cd20854e591a292b9475f` or later and open **Slider** from the showcase home.
  - [ ] Live dose updates PHP on each whole step while dragging; blur only on release; debounce after 500 ms quiet or on release.
  - [ ] Thumb snaps to the authored grid, including fractional steps.
  - [ ] Disabled dose stays at 7.
  - [ ] Server-approved dose snaps back to 4 and counts attempts; Publish higher dose sets 8 without an event.
  - [ ] Rapid input: flick back and forth quickly; the gesture stays smooth and the final PHP value matches the thumb.
  - [ ] TalkBack: adjustable slider named by its label, value (with units for Live dose), hint, error, disabled; increment/decrement gestures move one step.
  - [ ] Font size at maximum plus Display size largest (font scale 2.0 where available): label, helper and error wrap; the thumb keeps a usable target.
  - [ ] Remove animations on (Accessibility) or animator scale 0: thumb movement respects Reduce Motion / removed animations.
  - [ ] High-contrast text / contrast level high: active/inactive track and thumb remain distinguishable.
  - [ ] Right-to-left (Arabic or Hebrew locale, or Developer options → Force RTL layout direction): minimum is on the right; dragging left increases the value.
  - [ ] Light and dark theme both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.

## Verdicts

- **Automated, consumer and simulator/emulator evidence: PASS.**
- **Component-release readiness: BLOCKED.** Only the maintainer visual approval and the two physical-device rows remain; simulator evidence does not substitute for them.
- **Catalogue readiness: BLOCKED.** Every component review, physical-device evidence and the shared alpha gate must pass.
