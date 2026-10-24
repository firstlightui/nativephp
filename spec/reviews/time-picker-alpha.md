---
title: Time Picker alpha review evidence
description: Component-release evidence, showcase audit and remaining device and approval gates for Firstlight Time Picker.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/workflows/testing.md
  - spec/workflows/showcase-and-screenshots.md
  - spec/screenshots.json
  - docs/components/time-picker.md
  - spec/components/time-picker.md
  - spec/reviews/time-picker-development.md
  - nativephp.json
  - src/Components/TimePicker.php
  - src/Elements/TimePicker.php
  - tests/Feature/TimePickerElementTest.php
  - resources/ios/TimePickerControl.swift
  - resources/ios/TimePickerRenderer.swift
  - resources/android/TimePickerControl.kt
  - resources/android/TimePickerRenderer.kt
  - tests/ios/TimePickerSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/TimePickerTest.kt
---

# Time Picker Alpha Review Evidence

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-10-25 |
| Review mode | Component-release (`bin/check-component TimePicker` without `--development`) |
| Component | `TimePicker` / `<firstlight:time-picker>` |
| State class | Discrete; confirmation-only wall-clock proposals, server-authoritative |
| Implementation | Paired Firstlight SwiftUI and Material 3 renderers |
| Package revision captured | `e6ab75fa82a27b32ab8163b2f94d166a8429d9f1` on `release/component-reviews`, clean (recaptured after the fixes on this branch) |
| Package revision tested | `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02` on `release/component-reviews`, clean; later commits on the branch change only reviews and documentation |
| Showcase revision | `37669c2e077d3100550de4f5c4822ecb112f1d26` (local branch `release/component-review-fixtures` on `c7ed28ef489e1caae305fc84961af9f549eb409d`), clean; its lock installs `firstlightui/nativephp` at `e6ab75fa82a27b32ab8163b2f94d166a8429d9f1` |
| iOS simulator | Firstlight iPhone 17 Pro iOS 26.5 (`758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC`); XCTest also on Firstlight iPhone 17 Pro iOS 27.0 (`731CC3F2-1651-4522-A5A3-A781710EE166`) |
| Android emulator | `emulator-5554`, AVD Medium_Phone, Android 17 (API 37), `google_apis_playstore_ps16k` (not a Pixel 9 Pro) |
| Visual reviewer | Wojt Janowski (maintainer), 2026-10-24 |
| Physical devices | None used; both device rows remain BLOCKED |

## Release capture

| Result | Command and evidence |
| --- | --- |
| PASS | `bin/capture-doc-screenshot-batch TimePicker Slider Stepper ListSection --showcase=../showcase --ios=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC --android=emulator-5554 --release` — exit 0 at 2026-10-25 08:02:39 AEDT (recapture batch 2). The batch built and launched each host once, ran the focused showcase capture tests, cold-launched `/captures/time-picker` per appearance, waited for stable frames, rejected identical light/dark pairs and published the matrix. It then restored the showcase `.env`, both appearances, iOS Reduce Motion and the Android animator scale. |
| PASS | The orchestrator reported `Package: e6ab75fa82a27b32ab8163b2f94d166a8429d9f1` and `Showcase: 37669c2e077d3100550de4f5c4822ecb112f1d26` with no `(dirty)` marker. It also verified that the showcase's installed `firstlightui/nativephp` reference equals the package `HEAD`. Published images were moved out of the tree between batches so that each batch started clean, and were committed unchanged. |
| PASS | The agent inspected the four images below for documented states, theme, crop, labels, truncation, system chrome and accidental data. Every enabled field is now bound, so required and error states render with interactive styling; only Disabled time is dimmed. No defects seen. |
| PASS | The maintainer approved all four images of this release matrix on 2026-10-24. |

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/time-picker/ios-light.png` | `docs/screenshots/time-picker/ios-dark.png` |
| Android | `docs/screenshots/time-picker/android-light.png` | `docs/screenshots/time-picker/android-dark.png` |

Thirty matrices were captured with `bin/capture-doc-screenshot-batch` in six five-component batches rather than 31 separate `bin/capture-doc-screenshots` runs. Release mode requires both repositories to be clean before launch, and each single-component run publishes into the package tree. The batch entrypoint uses the same `DocumentationScreenshotCapture` guards. This matrix was recaptured after the component and fixture fixes on this branch, from clean package and showcase trees, using the same guarded batch entrypoint. The showcase fixes are on the local branch `release/component-review-fixtures`, which is not yet pushed.

## Automated and consumer evidence

| Result | Command and evidence |
| --- | --- |
| PASS | `vendor/bin/pest` at `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02` — 1470 passed, 5 skipped, 1 deprecation, 4 warnings, exit 0 (106.9 s). |
| PASS | `xcodebuild -scheme FirstlightIOSControls -destination 'platform=iOS Simulator,id=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC' -resultBundlePath ../evidence/run3/ios26.xcresult test` (iPhone 17 Pro, iOS 26.5) — TEST SUCCEEDED: 305 tests, 299 passed, 0 failed, 6 skipped. |
| PASS | Same command on `id=731CC3F2-1651-4522-A5A3-A781710EE166` (iPhone 17 Pro, iOS 27.0) — TEST SUCCEEDED: 305 tests, 299 passed, 0 failed, 6 skipped. |
| PASS | `JAVA_HOME=<jdk-21> tests/android/gradlew -p tests/android testDebugUnitTest verifyPaparazziDebug --rerun-tasks` — BUILD SUCCESSFUL, 35 tasks executed; 311 tests in 88 classes, 0 failures, 0 errors. |
| PASS | `bin/check-docs --development` — "Documentation checks passed.", exit 0. |
| PASS | `bin/check-component <Name> --development` for all 31 manifest components — exit 0 each. |
| PASS | Showcase `php artisan test` at `f48dbb7` (branch `release/component-review-fixtures` on `c7ed28e`) — 206 passed (4206 assertions). |
| PASS | Showcase `php artisan native:plugin:validate ../nativephp` — exit 0, with one warning: "No bridge_functions defined in manifest". |

Component-specific tests within those runs:

| Result | Test file and evidence |
| --- | --- |
| PASS | `tests/Feature/TimePickerElementTest.php` — included in the full Pest run. |
| PASS | `tests/ios/TimePickerSnapshotTests.swift` — iOS 26.5: 9 passed, 0 failed; iOS 27.0: 9 passed, 0 failed. |
| PASS | `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/TimePickerTest.kt` — 8 tests, 0 failed, 0 skipped (`testDebugUnitTest` / Paparazzi verify). |

Focused showcase tests: `tests/Feature/TimePickerCaptureTest.php`, `tests/Feature/TimePickerShowcaseTest.php`. They ran in the full showcase suite above and, for the capture fixture, again inside the guarded capture.

## Showcase demo audit

Audited `firstlightui/showcase` `c7ed28ef489e1caae305fc84961af9f549eb409d` against `docs/components/time-picker.md`. Covered on the interactive page:

- null with placeholder (en-AU, Sydney), required accepted time
- static error, de-DE Berlin formatting, `disabled`
- server rejection with an attempt counter
- programmatic publish and clear

Gaps (listed, not invented):

- Changing value or `disabled` while the picker is open is not demonstrated.
- A 12-hour locale (for example en-US) beside 24-hour de-DE is not demonstrated.
- A field named only by `a11y-label` is not demonstrated.

## Constitution review

- **Article I — PASS.** `bin/check-component TimePicker --development` passes the manifest, renderer-mapping and prohibited-bridge checks; no WebView or bridge call exists in component source.
- **Article II — PASS.** The public API matches `docs/components/time-picker.md` and is enforced by `tests/Feature/TimePickerElementTest.php`.
- **Article III — PASS for applicable state.** State class: Discrete; confirmation-only wall-clock proposals, server-authoritative. Contract tests cover the applicable rejection, programmatic and no-echo rules; the showcase gaps above are not counted as evidence.
- **Article IV —** PASS for simulator/emulator evidence; BLOCKED for physical devices. The production renderers built into the generated hosts and rendered `/captures/time-picker` on iOS 26.5 and Android API 37 in the clean release capture.
- **Article V — PASS by implementation audit.** Paired Firstlight SwiftUI and Material 3 renderers; see the development review and documentation platform section. Native feel on hardware is part of the device rows.
- **Article VI — BLOCKED.** Automated accessibility semantics pass, but live VoiceOver/TalkBack, Dynamic Type/font scale, Reduce Motion, increased contrast and RTL on physical devices are not yet recorded.
- **Article VII — PASS.** Semantic theme tokens only; no style-bag or platform escape props (contract tests).
- **Article VIII — PASS.** Unchanged from the development review.
- **Article IX — BLOCKED.** PHP, iOS (26.5 and 27.0), Android, showcase, documentation and release captures pass; physical-device checks (IX.4) are outstanding; maintainer visual approval is recorded.
- **Article X — BLOCKED for catalogue readiness.** A component review does not complete the catalogue.
- **Article XI — PASS.** The review skill, structural gate and documentation gate were applied; missing evidence is recorded as BLOCKED.

Article XII is not applicable.
## Release checklist

- [x] Clean `--release` capture from `e6ab75fa82a27b32ab8163b2f94d166a8429d9f1` and `37669c2e077d3100550de4f5c4822ecb112f1d26` published the four-image matrix (2026-10-25).
- [x] Full package suites (Pest, iOS 26.5 and 27.0 XCTest, Android unit/Paparazzi), `bin/check-component TimePicker --development`, `bin/check-docs --development`, showcase suite and plugin validation pass at `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02`.
- [x] Showcase demo audited against every documented state; gaps listed above.
- [x] PASS — Maintainer visual approval: Wojt Janowski approved all four release images listed above on 2026-10-24.
- [ ] BLOCKED — Physical iOS device row:
  - [ ] Device: record model, iOS version, date and tester. Install the showcase built from `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02` or later and open **Time Picker** from the showcase home (`/components/time-picker`).
  - [ ] Choose a time and Confirm; Cancel leaves PHP unchanged.
  - [ ] Berlin field formats as 24-hour German time.
  - [ ] Server-approved time snaps back and counts each attempt.
  - [ ] Publish later time / Clear time update the field without an event.
  - [ ] Rapid input: open/confirm twice quickly; one sheet, one proposal.
  - [ ] VoiceOver: field announces label, localized time or placeholder, hint, error; wheel/clock values and Cancel/Confirm reachable.
  - [ ] Dynamic Type at the largest accessibility size (AX5): sheet/dialog and field text scale without clipping.
  - [ ] Reduce Motion on: sheet/dialog presentation respects Reduce Motion / removed animations.
  - [ ] Increase Contrast on (and Smart Invert off): selection and actions remain legible.
  - [ ] Right-to-left (device language Arabic or Hebrew, or the Right-to-Left pseudolanguage): sheet actions mirror; time digits keep their natural order.
  - [ ] Light and dark appearance both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.
- [ ] BLOCKED — Physical Android device row:
  - [ ] Device: record model, Android version, date and tester. Install the showcase built from `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02` or later and open **Time Picker** from the showcase home.
  - [ ] Choose a time and Confirm; Cancel leaves PHP unchanged.
  - [ ] Berlin field formats as 24-hour German time.
  - [ ] Server-approved time snaps back and counts each attempt.
  - [ ] Publish later time / Clear time update the field without an event.
  - [ ] Rapid input: open/confirm twice quickly; one sheet, one proposal.
  - [ ] TalkBack: field announces label, localized time or placeholder, hint, error; wheel/clock values and Cancel/Confirm reachable.
  - [ ] Font size at maximum plus Display size largest (font scale 2.0 where available): sheet/dialog and field text scale without clipping.
  - [ ] Remove animations on (Accessibility) or animator scale 0: sheet/dialog presentation respects Reduce Motion / removed animations.
  - [ ] High-contrast text / contrast level high: selection and actions remain legible.
  - [ ] Right-to-left (Arabic or Hebrew locale, or Developer options → Force RTL layout direction): sheet actions mirror; time digits keep their natural order.
  - [ ] Light and dark theme both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.

## Verdicts

- **Automated, consumer and simulator/emulator evidence: PASS.**
- **Component-release readiness: BLOCKED.** Only the two physical-device rows remain; simulator evidence does not substitute for them.
- **Catalogue readiness: BLOCKED.** Every component review, physical-device evidence and the shared alpha gate must pass.
