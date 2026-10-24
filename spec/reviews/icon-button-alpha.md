---
title: Icon Button alpha review evidence
description: Component-release evidence, showcase audit and remaining device and approval gates for Firstlight Icon Button.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/workflows/testing.md
  - spec/workflows/showcase-and-screenshots.md
  - spec/screenshots.json
  - docs/components/icon-button.md
  - spec/components/icon-button.md
  - spec/reviews/icon-button-development.md
  - nativephp.json
  - src/Components/IconButton.php
  - src/Elements/IconButton.php
  - tests/Feature/IconButtonElementTest.php
  - resources/ios/IconButtonControl.swift
  - resources/ios/IconButtonRenderer.swift
  - resources/android/IconButtonControl.kt
  - resources/android/IconButtonRenderer.kt
  - tests/ios/IconButtonSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/IconButtonTest.kt
---

# Icon Button Alpha Review Evidence

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-10-25 |
| Review mode | Component-release (`bin/check-component IconButton` without `--development`) |
| Component | `IconButton` / `<firstlight:icon-button>` |
| State class | Action/display; icon-only action with required accessible name |
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
| PASS | `bin/capture-doc-screenshot-batch Checkbox ChoiceGroup DatePicker TimePicker IconButton --showcase=../showcase --ios=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC --android=emulator-5554 --release` — exit 0 at 2026-10-25 07:02:53 AEDT (batch 2). The batch built and launched each host once, ran the focused showcase capture tests, cold-launched `/captures/icon-button` per appearance, waited for stable frames, rejected identical light/dark pairs and published the matrix. It then restored the showcase `.env`, both appearances, iOS Reduce Motion and the Android animator scale. |
| PASS | The orchestrator reported `Package: 19d12ff9d48baeddd312a6ef5d1a749bdba585e8` and `Showcase: c7ed28ef489e1caae305fc84961af9f549eb409d` with no `(dirty)` marker. It also verified that the showcase's installed `firstlightui/nativephp` reference equals the package `HEAD`. Published images were moved out of the tree between batches so that each batch started clean, and were committed unchanged. |
| PASS | The agent inspected the four images below for documented states, theme, crop, labels, truncation, system chrome and accidental data. No layout, theme, truncation or chrome defects seen. |
| BLOCKED | Maintainer visual approval of this release matrix is not yet recorded. |

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/icon-button/ios-light.png` | `docs/screenshots/icon-button/ios-dark.png` |
| Android | `docs/screenshots/icon-button/android-light.png` | `docs/screenshots/icon-button/android-dark.png` |

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
| PASS | `tests/Feature/IconButtonElementTest.php` — included in the full Pest run. |
| PASS | `tests/ios/IconButtonSnapshotTests.swift` — iOS 26.5: 8 passed, 0 failed; iOS 27.0: 8 passed, 0 failed. |
| FAIL | `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/IconButtonTest.kt` — not found in JUnit results. |

Focused showcase tests: `tests/Feature/IconButtonCaptureTest.php`, `tests/Feature/IconButtonShowcaseTest.php`. They ran in the full showcase suite above and, for the capture fixture, again inside the guarded capture.

## Showcase demo audit

Audited `firstlightui/showcase` `c7ed28ef489e1caae305fc84961af9f549eb409d` against `docs/components/icon-button.md`. Covered on the interactive page:

- five variants, three sizes
- shared icon with iOS/Android overrides and hint
- `disabled` and `loading`
- press counter and last-action name

Gaps (listed, not invented):

- A PHP-owned `loading` or `disabled` transition after a press is not demonstrated.
- Typed `IosSymbol` / `AndroidSymbol` variant preservation is PHP-contract-only evidence.

## Constitution review

- **Article I — PASS.** `bin/check-component IconButton --development` passes the manifest, renderer-mapping and prohibited-bridge checks; no WebView or bridge call exists in component source.
- **Article II — PASS.** The public API matches `docs/components/icon-button.md` and is enforced by `tests/Feature/IconButtonElementTest.php`.
- **Article III — PASS for applicable state.** State class: Action/display; icon-only action with required accessible name. Contract tests cover the applicable rejection, programmatic and no-echo rules; the showcase gaps above are not counted as evidence.
- **Article IV —** PASS for simulator/emulator evidence; BLOCKED for physical devices. The production renderers built into the generated hosts and rendered `/captures/icon-button` on iOS 26.5 and Android API 37 in the clean release capture.
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
- [x] Full package suites (Pest, iOS 26.5 and 27.0 XCTest, Android unit/Paparazzi), `bin/check-component IconButton --development`, `bin/check-docs --development`, showcase suite and plugin validation pass at `42e875a91949981b324cd20854e591a292b9475f`.
- [x] Showcase demo audited against every documented state; gaps listed above.
- [ ] BLOCKED — Maintainer visual approval of the four release images listed above (record reviewer, date and verdict per image).
- [ ] BLOCKED — Physical iOS device row:
  - [ ] Device: record model, iOS version, date and tester. Install the showcase built from `42e875a91949981b324cd20854e591a292b9475f` or later and open **Icon Button** from the showcase home (`/components/icon-button`).
  - [ ] Tap every enabled icon; the count rises once and Last action names it.
  - [ ] Disabled and loading icons leave the result unchanged.
  - [ ] Rapid input: tap Record press repeatedly; one increment per completed press.
  - [ ] VoiceOver: one button per icon named by `a11y-label` (never the icon name), hint where authored, disabled or loading state; glyphs are silent.
  - [ ] Dynamic Type at the largest accessibility size (AX5): glyphs scale by size while the touch target stays at least 44 pt / 48 dp.
  - [ ] Reduce Motion on: press feedback and loading progress respect Reduce Motion / removed animations.
  - [ ] Increase Contrast on (and Smart Invert off): filled, tonal and ghost variants remain legible.
  - [ ] Right-to-left (device language Arabic or Hebrew, or the Right-to-Left pseudolanguage): directional glyphs follow platform mirroring rules; row order mirrors.
  - [ ] Light and dark appearance both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.
- [ ] BLOCKED — Physical Android device row:
  - [ ] Device: record model, Android version, date and tester. Install the showcase built from `42e875a91949981b324cd20854e591a292b9475f` or later and open **Icon Button** from the showcase home.
  - [ ] Tap every enabled icon; the count rises once and Last action names it.
  - [ ] Disabled and loading icons leave the result unchanged.
  - [ ] Rapid input: tap Record press repeatedly; one increment per completed press.
  - [ ] TalkBack: one button per icon named by `a11y-label` (never the icon name), hint where authored, disabled or loading state; glyphs are silent.
  - [ ] Font size at maximum plus Display size largest (font scale 2.0 where available): glyphs scale by size while the touch target stays at least 44 pt / 48 dp.
  - [ ] Remove animations on (Accessibility) or animator scale 0: press feedback and loading progress respect Reduce Motion / removed animations.
  - [ ] High-contrast text / contrast level high: filled, tonal and ghost variants remain legible.
  - [ ] Right-to-left (Arabic or Hebrew locale, or Developer options → Force RTL layout direction): directional glyphs follow platform mirroring rules; row order mirrors.
  - [ ] Light and dark theme both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.

## Verdicts

- **Automated, consumer and simulator/emulator evidence: PASS.**
- **Component-release readiness: BLOCKED.** Only the maintainer visual approval and the two physical-device rows remain; simulator evidence does not substitute for them.
- **Catalogue readiness: BLOCKED.** Every component review, physical-device evidence and the shared alpha gate must pass.
