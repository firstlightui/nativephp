---
title: Switch alpha review evidence
description: Component-release evidence, showcase audit and remaining device and approval gates for Firstlight Switch.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/workflows/testing.md
  - spec/workflows/showcase-and-screenshots.md
  - spec/screenshots.json
  - docs/components/switch.md
  - nativephp.json
  - src/Components/SwitchControl.php
  - src/Elements/SwitchControl.php
  - tests/Feature/SwitchControlElementTest.php
  - resources/ios/SwitchControl.swift
  - resources/ios/SwitchRenderer.swift
  - resources/android/SwitchControl.kt
  - resources/android/SwitchRenderer.kt
  - tests/ios/SwitchControlRendererContractTests.swift
  - tests/ios/SwitchControlSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SwitchControlTest.kt
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SwitchRendererContractTest.kt
---

# Switch Alpha Review Evidence

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-10-25 |
| Review mode | Component-release (`bin/check-component SwitchControl` without `--development`) |
| Component | `SwitchControl` / `<firstlight:switch>` |
| State class | Discrete; server-authoritative Boolean |
| Implementation | Paired Firstlight SwiftUI and Material 3 renderers |
| Package revision captured | `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` (`v1.0.0-RC6`), clean |
| Package revision tested | `42e875a91949981b324cd20854e591a292b9475f` on `release/component-reviews`, clean; later commits on the branch change only reviews and documentation |
| Showcase revision | `c7ed28ef489e1caae305fc84961af9f549eb409d` (`firstlightui/showcase` `main`), clean; its lock installs `firstlightui/nativephp` at `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` |
| iOS simulator | Firstlight iPhone 17 Pro iOS 26.5 (`758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC`); XCTest also on Firstlight iPhone 17 Pro iOS 27.0 (`731CC3F2-1651-4522-A5A3-A781710EE166`) |
| Android emulator | `emulator-5554`, AVD Medium_Phone, Android 17 (API 37), `google_apis_playstore_ps16k` (not a Pixel 9 Pro) |
| Visual reviewer | Pending maintainer approval (row below) |
| Physical devices | None used; both device rows remain BLOCKED |

## Release capture

| Result | Command and evidence |
| --- | --- |
| PASS | `bin/capture-doc-screenshot-batch Switch AlertDialog ConfirmationDialog Modal BottomSheet --showcase=../showcase --ios=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC --android=emulator-5554 --release` — exit 0 at 2026-10-25 07:17:07 AEDT (batch 6). The batch built and launched each host once, ran the focused showcase capture tests, cold-launched `/captures/switch` per appearance, waited for stable frames, rejected identical light/dark pairs and published the matrix. It then restored the showcase `.env`, both appearances, iOS Reduce Motion and the Android animator scale. |
| PASS | The orchestrator reported `Package: 19d12ff9d48baeddd312a6ef5d1a749bdba585e8` and `Showcase: c7ed28ef489e1caae305fc84961af9f549eb409d` with no `(dirty)` marker. It also verified that the showcase's installed `firstlightui/nativephp` reference equals the package `HEAD`. Published images were moved out of the tree between batches so that each batch started clean, and were committed unchanged. |
| PASS | The agent inspected the four images below for documented states, theme, crop, labels, truncation, system chrome and accidental data. No layout, theme, truncation or chrome defects seen. |
| BLOCKED | Maintainer visual approval of this release matrix is not yet recorded. |

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/switch/ios-light.png` | `docs/screenshots/switch/ios-dark.png` |
| Android | `docs/screenshots/switch/android-light.png` | `docs/screenshots/switch/android-dark.png` |

Thirty matrices were captured with `bin/capture-doc-screenshot-batch` in six five-component batches rather than 31 separate `bin/capture-doc-screenshots` runs. Release mode requires both repositories to be clean before launch, and each single-component run publishes into the package tree. The batch entrypoint uses the same `DocumentationScreenshotCapture` guards.

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
| PASS | `tests/Feature/SwitchControlElementTest.php` — included in the full Pest run. |
| PASS | `tests/ios/SwitchControlRendererContractTests.swift` — iOS 26.5: 9 passed, 0 failed; iOS 27.0: 9 passed, 0 failed. |
| PASS | `tests/ios/SwitchControlSnapshotTests.swift` — iOS 26.5: 3 passed, 0 failed; iOS 27.0: 3 passed, 0 failed. |
| PASS | `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SwitchControlTest.kt` — 5 tests, 0 failed, 0 skipped (`testDebugUnitTest` / Paparazzi verify). |
| PASS | `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SwitchRendererContractTest.kt` — 6 tests, 0 failed, 0 skipped (`testDebugUnitTest` / Paparazzi verify). |

Focused showcase tests: `tests/Feature/SwitchCaptureTest.php`, `tests/Feature/SwitchShowcaseTest.php`. They ran in the full showcase suite above and, for the capture fixture, again inside the guarded capture.

## Showcase demo audit

Audited `firstlightui/showcase` `c7ed28ef489e1caae305fc84961af9f549eb409d` against `docs/components/switch.md`. Covered on the interactive page:

- off and on, disabled off and on
- helper, static error
- server rejection with an attempt counter
- programmatic enable and reset
- long wrapping label

Gaps (listed, not invented):

- A switch named only by `a11y-label`, and `a11y-hint`, are not demonstrated.
- `ValidatesFields` auto-bound error is not demonstrated on this page.

## Constitution review

- **Article I — PASS.** `bin/check-component SwitchControl --development` passes the manifest, renderer-mapping and prohibited-bridge checks; no WebView or bridge call exists in component source.
- **Article II — PASS.** The public API matches `docs/components/switch.md` and is enforced by `tests/Feature/SwitchControlElementTest.php`.
- **Article III — PASS for applicable state.** State class: Discrete; server-authoritative Boolean. Contract tests cover the applicable rejection, programmatic and no-echo rules; the showcase gaps above are not counted as evidence.
- **Article IV —** PASS for simulator/emulator evidence; BLOCKED for physical devices. The production renderers built into the generated hosts and rendered `/captures/switch` on iOS 26.5 and Android API 37 in the clean release capture.
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
- [x] Full package suites (Pest, iOS 26.5 and 27.0 XCTest, Android unit/Paparazzi), `bin/check-component SwitchControl --development`, `bin/check-docs --development`, showcase suite and plugin validation pass at `42e875a91949981b324cd20854e591a292b9475f`.
- [x] Showcase demo audited against every documented state; gaps listed above.
- [ ] BLOCKED — Maintainer visual approval of the four release images listed above (record reviewer, date and verdict per image).
- [ ] BLOCKED — Physical iOS device row:
  - [ ] Device: record model, iOS version, date and tester. Install the showcase built from `42e875a91949981b324cd20854e591a292b9475f` or later and open **Switch** from the showcase home (`/components/switch`).
  - [ ] Toggle each enabled switch; PHP state line matches.
  - [ ] Disabled switches stay fixed.
  - [ ] Rejected setting stays off and attempts rise by one per toggle.
  - [ ] Enable / Reset programmatic setting change the switch without an event.
  - [ ] Rapid input: toggle the same switch repeatedly; the visible state follows PHP and does not bounce.
  - [ ] VoiceOver: one switch element per row: label, on/off value, hint, disabled, error.
  - [ ] Dynamic Type at the largest accessibility size (AX5): long label wraps; switch keeps 44 pt / 48 dp.
  - [ ] Reduce Motion on: thumb motion respects Reduce Motion / removed animations and does not replay on reconciliation.
  - [ ] Increase Contrast on (and Smart Invert off): on/off states remain distinguishable (iOS On/Off labels setting optional).
  - [ ] Right-to-left (device language Arabic or Hebrew, or the Right-to-Left pseudolanguage): switch moves to the left edge; thumb direction mirrors.
  - [ ] Light and dark appearance both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.
- [ ] BLOCKED — Physical Android device row:
  - [ ] Device: record model, Android version, date and tester. Install the showcase built from `42e875a91949981b324cd20854e591a292b9475f` or later and open **Switch** from the showcase home.
  - [ ] Toggle each enabled switch; PHP state line matches.
  - [ ] Disabled switches stay fixed.
  - [ ] Rejected setting stays off and attempts rise by one per toggle.
  - [ ] Enable / Reset programmatic setting change the switch without an event.
  - [ ] Rapid input: toggle the same switch repeatedly; the visible state follows PHP and does not bounce.
  - [ ] TalkBack: one switch element per row: label, on/off value, hint, disabled, error.
  - [ ] Font size at maximum plus Display size largest (font scale 2.0 where available): long label wraps; switch keeps 44 pt / 48 dp.
  - [ ] Remove animations on (Accessibility) or animator scale 0: thumb motion respects Reduce Motion / removed animations and does not replay on reconciliation.
  - [ ] High-contrast text / contrast level high: on/off states remain distinguishable (iOS On/Off labels setting optional).
  - [ ] Right-to-left (Arabic or Hebrew locale, or Developer options → Force RTL layout direction): switch moves to the left edge; thumb direction mirrors.
  - [ ] Light and dark theme both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.

## Verdicts

- **Automated, consumer and simulator/emulator evidence: PASS.**
- **Component-release readiness: BLOCKED.** Only the maintainer visual approval and the two physical-device rows remain; simulator evidence does not substitute for them.
- **Catalogue readiness: BLOCKED.** Every component review, physical-device evidence and the shared alpha gate must pass.
