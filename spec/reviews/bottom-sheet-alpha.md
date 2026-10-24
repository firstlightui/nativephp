---
title: Bottom Sheet alpha review evidence
description: Component-release evidence, showcase audit and remaining device and approval gates for Firstlight Bottom Sheet.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/workflows/testing.md
  - spec/workflows/showcase-and-screenshots.md
  - spec/screenshots.json
  - docs/components/bottom-sheet.md
  - spec/components/bottom-sheet.md
  - spec/reviews/bottom-sheet-development.md
  - nativephp.json
  - src/Components/BottomSheet.php
  - src/Elements/BottomSheet.php
  - tests/Feature/BottomSheetElementTest.php
  - resources/ios/BottomSheetControl.swift
  - resources/ios/BottomSheetRenderer.swift
  - resources/android/BottomSheetControl.kt
  - resources/android/BottomSheetRenderer.kt
  - tests/ios/BottomSheetRendererTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/BottomSheetTest.kt
---

# Bottom Sheet Alpha Review Evidence

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-10-25 |
| Review mode | Component-release (`bin/check-component BottomSheet` without `--development`) |
| Component | `BottomSheet` / `<firstlight:bottom-sheet>` |
| State class | Action/display; server-controlled content-sized sheet with dismiss event |
| Implementation | Paired Firstlight SwiftUI and Material 3 renderers |
| Package revision captured | `8008f508f62eaf1ff92fbb5fc720eac0f3f6cb88` on `release/component-reviews`, clean (recaptured after fixes) |
| Package revision tested | `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02` on `release/component-reviews`, clean; later commits on the branch change only reviews and documentation |
| Showcase revision | `f48dbb7ea0c1666984a3b500241d46488ae24ccf` (`firstlightui/showcase` branch `release/component-review-fixtures`, on `c7ed28e`), clean; its lock installs `firstlightui/nativephp` at `8008f508f62eaf1ff92fbb5fc720eac0f3f6cb88` |
| iOS simulator | Firstlight iPhone 17 Pro iOS 26.5 (`758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC`); XCTest also on Firstlight iPhone 17 Pro iOS 27.0 (`731CC3F2-1651-4522-A5A3-A781710EE166`) |
| Android emulator | `emulator-5554`, AVD Medium_Phone, Android 17 (API 37), `google_apis_playstore_ps16k` (not a Pixel 9 Pro) |
| Visual reviewer | Pending maintainer approval (row below) |
| Physical devices | None used; both device rows remain BLOCKED |

## Release capture

| Result | Command and evidence |
| --- | --- |
| PASS | `bin/capture-doc-screenshot-batch Modal BottomSheet Checkbox Progress SearchField --showcase=../showcase --ios=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC --android=emulator-5554 --release` — exit 0 at 2026-10-25 09:04:53 AEDT (recapture after the heading, Checkbox, Progress and Search Field fixes; attempt 1). The batch built and launched each host once, ran the focused showcase capture tests, cold-launched `/captures/bottom-sheet` per appearance, waited for stable frames, rejected identical light/dark pairs and published the matrix. It then restored the showcase `.env`, both appearances, iOS Reduce Motion and the Android animator scale. |
| PASS | The orchestrator reported `Package: 8008f508f62eaf1ff92fbb5fc720eac0f3f6cb88` and `Showcase: f48dbb7ea0c1666984a3b500241d46488ae24ccf` with no `(dirty)` marker. It also verified that the showcase's installed `firstlightui/nativephp` reference equals the package `HEAD`. The published images were committed unchanged. |
| PASS | The agent inspected the four images below for documented states, theme, crop, labels, truncation, system chrome and accidental data. Fixed: the heading is now title-weight `native:text`, not a Status Label badge. No layout, theme, truncation or chrome defects seen. |
| BLOCKED | Maintainer visual approval of this release matrix is not yet recorded. |

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/bottom-sheet/ios-light.png` | `docs/screenshots/bottom-sheet/ios-dark.png` |
| Android | `docs/screenshots/bottom-sheet/android-light.png` | `docs/screenshots/bottom-sheet/android-dark.png` |

Thirty matrices were captured with `bin/capture-doc-screenshot-batch` in six five-component batches rather than 31 separate `bin/capture-doc-screenshots` runs. Release mode requires both repositories to be clean before launch, and each single-component run publishes into the package tree. The batch entrypoint uses the same `DocumentationScreenshotCapture` guards. This matrix was then recaptured, after the fixes listed in the CHANGELOG Unreleased section, in one five-component batch (Modal, BottomSheet, Checkbox, Progress, SearchField) from clean trees.

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
| PASS | `tests/Feature/BottomSheetElementTest.php` — included in the full Pest run. |
| PASS | `tests/ios/BottomSheetRendererTests.swift` — iOS 26.5: 7 passed, 0 failed; iOS 27.0: 7 passed, 0 failed. |
| PASS | `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/BottomSheetTest.kt` — 4 tests, 0 failed, 0 skipped (`testDebugUnitTest` / Paparazzi verify). |

Focused showcase tests: `tests/Feature/BottomSheetCaptureTest.php`, `tests/Feature/BottomSheetShowcaseTest.php`. They ran in the full showcase suite above and, for the capture fixture, again inside the guarded capture.

## Showcase demo audit

Audited `firstlightui/showcase` `c7ed28ef489e1caae305fc84961af9f549eb409d` (re-checked at `f48dbb7ea0c1666984a3b500241d46488ae24ccf`, which only changes the fixtures: the Status Label heading replaced with title-weight `native:text` in `bottom-sheet-showcase` and `captures/bottom-sheet`; coverage is unchanged) against `docs/components/bottom-sheet.md`. Covered on the interactive page:

- default sheet dismissed by drag or outside tap
- programmatic close from PHP
- dismissal counter
- scrollable sheet content in the List test options

Gaps (listed, not invented):

- Content height changes while open, content taller than half the screen, and content taller than the screen (iOS scroll / Android clip) are not demonstrated on this page.
- `a11y-hint` is not demonstrated.

## Constitution review

- **Article I — PASS.** `bin/check-component BottomSheet --development` passes the manifest, renderer-mapping and prohibited-bridge checks; no WebView or bridge call exists in component source.
- **Article II — PASS.** The public API matches `docs/components/bottom-sheet.md` and is enforced by `tests/Feature/BottomSheetElementTest.php`.
- **Article III — PASS for applicable state.** State class: Action/display; server-controlled content-sized sheet with dismiss event. Contract tests cover the applicable rejection, programmatic and no-echo rules; the showcase gaps above are not counted as evidence.
- **Article IV —** PASS for simulator/emulator evidence; BLOCKED for physical devices. The production renderers built into the generated hosts and rendered `/captures/bottom-sheet` on iOS 26.5 and Android API 37 in the clean release capture.
- **Article V — PASS by implementation audit.** Paired Firstlight SwiftUI and Material 3 renderers; see the development review and documentation platform section. Native feel on hardware is part of the device rows.
- **Article VI — BLOCKED.** Automated accessibility semantics pass, but live VoiceOver/TalkBack, Dynamic Type/font scale, Reduce Motion, increased contrast and RTL on physical devices are not yet recorded.
- **Article VII — PASS.** Semantic theme tokens only; no style-bag or platform escape props (contract tests).
- **Article VIII — PASS.** Unchanged from the development review.
- **Article IX — BLOCKED.** PHP, iOS (26.5 and 27.0), Android, showcase, documentation and release captures pass; physical-device checks (IX.4) and maintainer visual approval are outstanding.
- **Article X — BLOCKED for catalogue readiness.** A component review does not complete the catalogue.
- **Article XI — PASS.** The review skill, structural gate and documentation gate were applied; missing evidence is recorded as BLOCKED.

Article XII is not applicable.
## Release checklist

- [x] Clean `--release` capture from `8008f508f62eaf1ff92fbb5fc720eac0f3f6cb88` and `f48dbb7ea0c1666984a3b500241d46488ae24ccf` published the four-image matrix (2026-10-25, recaptured after fixes).
- [x] Full package suites (Pest, iOS 26.5 and 27.0 XCTest, Android unit/Paparazzi), `bin/check-component BottomSheet --development`, `bin/check-docs --development`, showcase suite and plugin validation pass at `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02`.
- [x] Showcase demo audited against every documented state; gaps listed above.
- [ ] BLOCKED — Maintainer visual approval of the four release images listed above (record reviewer, date and verdict per image).
- [ ] BLOCKED — Physical iOS device row:
  - [ ] Device: record model, iOS version, date and tester. Install the showcase built from `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02` or later and open **Bottom Sheet** from the showcase home (`/components/bottom-sheet`).
  - [ ] Open filters; drag down and tap outside; dismissed rises once each.
  - [ ] Programmatic example: Close from PHP leaves dismissed unchanged; native dismissal increments.
  - [ ] Collection List → Test options opens fully and scrolls.
  - [ ] Rapid input: drag down and tap outside at the same time; one dismissal.
  - [ ] VoiceOver: sheet named by `a11y-label`; drag indicator and content reachable; dismiss available through the accessibility escape / back.
  - [ ] Dynamic Type at the largest accessibility size (AX5): sheet height follows larger text up to full height.
  - [ ] Reduce Motion on: sheet presentation respects Reduce Motion / removed animations.
  - [ ] Increase Contrast on (and Smart Invert off): scrim and surface remain distinguishable.
  - [ ] Right-to-left (device language Arabic or Hebrew, or the Right-to-Left pseudolanguage): content mirrors.
  - [ ] Light and dark appearance both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.
- [ ] BLOCKED — Physical Android device row:
  - [ ] Device: record model, Android version, date and tester. Install the showcase built from `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02` or later and open **Bottom Sheet** from the showcase home.
  - [ ] Open filters; drag down and tap outside; dismissed rises once each.
  - [ ] Programmatic example: Close from PHP leaves dismissed unchanged; native dismissal increments.
  - [ ] Collection List → Test options opens fully and scrolls.
  - [ ] Rapid input: drag down and tap outside at the same time; one dismissal.
  - [ ] TalkBack: sheet named by `a11y-label`; drag indicator and content reachable; dismiss available through the accessibility escape / back.
  - [ ] Font size at maximum plus Display size largest (font scale 2.0 where available): sheet height follows larger text up to full height.
  - [ ] Remove animations on (Accessibility) or animator scale 0: sheet presentation respects Reduce Motion / removed animations.
  - [ ] High-contrast text / contrast level high: scrim and surface remain distinguishable.
  - [ ] Right-to-left (Arabic or Hebrew locale, or Developer options → Force RTL layout direction): content mirrors.
  - [ ] Light and dark theme both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.

## Verdicts

- **Automated, consumer and simulator/emulator evidence: PASS.**
- **Component-release readiness: BLOCKED.** Only the maintainer visual approval and the two physical-device rows remain; simulator evidence does not substitute for them.
- **Catalogue readiness: BLOCKED.** Every component review, physical-device evidence and the shared alpha gate must pass.
