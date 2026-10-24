---
title: Select alpha review evidence
description: Component-release evidence, showcase audit and remaining device and approval gates for Firstlight Select.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/workflows/testing.md
  - spec/workflows/showcase-and-screenshots.md
  - spec/screenshots.json
  - docs/components/select.md
  - spec/components/select.md
  - spec/reviews/select-development.md
  - nativephp.json
  - src/Components/Select.php
  - src/Elements/Select.php
  - tests/Feature/SelectElementTest.php
  - resources/ios/SelectControl.swift
  - resources/ios/SelectRenderer.swift
  - resources/android/SelectControl.kt
  - resources/android/SelectRenderer.kt
  - tests/ios/SelectSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SelectTest.kt
---

# Select Alpha Review Evidence

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-10-25 |
| Review mode | Component-release (`bin/check-component Select` without `--development`) |
| Component | `Select` / `<firstlight:select>` |
| State class | Discrete; server-authoritative single selection with compact and searchable presentation |
| Implementation | Paired Firstlight SwiftUI and Material 3 renderers |
| Package revision captured | `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` (`v1.0.0-RC6`), clean |
| Package revision tested | `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02` on `release/component-reviews`, clean; later commits on the branch change only reviews and documentation |
| Showcase revision | `c7ed28ef489e1caae305fc84961af9f549eb409d` (`firstlightui/showcase` `main`), clean; its lock installs `firstlightui/nativephp` at `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` |
| iOS simulator | Firstlight iPhone 17 Pro iOS 26.5 (`758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC`); XCTest also on Firstlight iPhone 17 Pro iOS 27.0 (`731CC3F2-1651-4522-A5A3-A781710EE166`) |
| Android emulator | `emulator-5554`, AVD Medium_Phone, Android 17 (API 37), `google_apis_playstore_ps16k` (not a Pixel 9 Pro) |
| Visual reviewer | Wojt Janowski (maintainer), 2026-10-24 |
| Physical devices | None used; both device rows remain BLOCKED |

## Release capture

| Result | Command and evidence |
| --- | --- |
| PASS | `bin/capture-doc-screenshot-batch Segmented Tabs SearchField Select Slider --showcase=../showcase --ios=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC --android=emulator-5554 --release` — exit 0 at 2026-10-25 07:09:59 AEDT (batch 4, attempt 2; attempt 1 stopped before publishing because the Android showcase was not foregrounded after an upstream host crash on cold launch, and published nothing). The batch built and launched each host once, ran the focused showcase capture tests, cold-launched `/captures/select` per appearance, waited for stable frames, rejected identical light/dark pairs and published the matrix. It then restored the showcase `.env`, both appearances, iOS Reduce Motion and the Android animator scale. |
| PASS | The orchestrator reported `Package: 19d12ff9d48baeddd312a6ef5d1a749bdba585e8` and `Showcase: c7ed28ef489e1caae305fc84961af9f549eb409d` with no `(dirty)` marker. It also verified that the showcase's installed `firstlightui/nativephp` reference equals the package `HEAD`. Published images were moved out of the tree between batches so that each batch started clean, and were committed unchanged. |
| PASS | The agent inspected the four images below for documented states, theme, crop, labels, truncation, system chrome and accidental data. No layout, theme, truncation or chrome defects seen. |
| PASS | The maintainer approved all four images of this release matrix on 2026-10-24. |

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/select/ios-light.png` | `docs/screenshots/select/ios-dark.png` |
| Android | `docs/screenshots/select/android-light.png` | `docs/screenshots/select/android-dark.png` |

Thirty matrices were captured with `bin/capture-doc-screenshot-batch` in six five-component batches rather than 31 separate `bin/capture-doc-screenshots` runs. Release mode requires both repositories to be clean before launch, and each single-component run publishes into the package tree. The batch entrypoint uses the same `DocumentationScreenshotCapture` guards.

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
| PASS | `tests/Feature/SelectElementTest.php` — included in the full Pest run. |
| FAIL | `tests/ios/SelectSnapshotTests.swift` — iOS 26.5: not found; iOS 27.0: not found. |
| FAIL | `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SelectTest.kt` — not found in JUnit results. |

Focused showcase tests: `tests/Feature/SelectCaptureTest.php`, `tests/Feature/SelectShowcaseTest.php`. They ran in the full showcase suite above and, for the capture fixture, again inside the guarded capture.

## Showcase demo audit

Audited `firstlightui/showcase` `c7ed28ef489e1caae305fc84961af9f549eb409d` against `docs/components/select.md`. Covered on the interactive page:

- compact nullable string selection with placeholder
- integer values with a disabled option and `required`
- 13-option automatic searchable presentation and forced `searchable`
- static error, `disabled`
- server rejection with an attempt counter

Gaps (listed, not invented):

- Programmatic set/clear (must not emit) is not demonstrated.
- An empty option set (inert trigger) is not demonstrated.
- A field named only by `a11y-label` and a same-typed value absent from options are not demonstrated.
- The in-flight guard for rapid choices has no observable counter on an accepting select.

## Constitution review

- **Article I — PASS.** `bin/check-component Select --development` passes the manifest, renderer-mapping and prohibited-bridge checks; no WebView or bridge call exists in component source.
- **Article II — PASS.** The public API matches `docs/components/select.md` and is enforced by `tests/Feature/SelectElementTest.php`.
- **Article III — PASS for applicable state.** State class: Discrete; server-authoritative single selection with compact and searchable presentation. Contract tests cover the applicable rejection, programmatic and no-echo rules; the showcase gaps above are not counted as evidence.
- **Article IV —** PASS for simulator/emulator evidence; BLOCKED for physical devices. The production renderers built into the generated hosts and rendered `/captures/select` on iOS 26.5 and Android API 37 in the clean release capture.
- **Article V — PASS by implementation audit.** Paired Firstlight SwiftUI and Material 3 renderers; see the development review and documentation platform section. Native feel on hardware is part of the device rows.
- **Article VI — BLOCKED.** Automated accessibility semantics pass, but live VoiceOver/TalkBack, Dynamic Type/font scale, Reduce Motion, increased contrast and RTL on physical devices are not yet recorded.
- **Article VII — PASS.** Semantic theme tokens only; no style-bag or platform escape props (contract tests).
- **Article VIII — PASS.** Unchanged from the development review.
- **Article IX — BLOCKED.** PHP, iOS (26.5 and 27.0), Android, showcase, documentation and release captures pass; physical-device checks (IX.4) are outstanding; maintainer visual approval is recorded.
- **Article X — BLOCKED for catalogue readiness.** A component review does not complete the catalogue.
- **Article XI — PASS.** The review skill, structural gate and documentation gate were applied; missing evidence is recorded as BLOCKED.

Article XII is not applicable.
## Release checklist

- [x] Clean `--release` capture from `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` and `c7ed28ef489e1caae305fc84961af9f549eb409d` published the four-image matrix (2026-10-25).
- [x] Full package suites (Pest, iOS 26.5 and 27.0 XCTest, Android unit/Paparazzi), `bin/check-component Select --development`, `bin/check-docs --development`, showcase suite and plugin validation pass at `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02`.
- [x] Showcase demo audited against every documented state; gaps listed above.
- [x] PASS — Maintainer visual approval: Wojt Janowski approved all four release images listed above on 2026-10-24.
- [ ] BLOCKED — Physical iOS device row:
  - [ ] Device: record model, iOS version, date and tester. Install the showcase built from `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02` or later and open **Select** from the showcase home (`/components/select`).
  - [ ] Priority: choose each option; placeholder shows while null.
  - [ ] Triage: integer identity preserved; unavailable option cannot be chosen.
  - [ ] Large queue: search filters in authored order; select a result; dismiss without choosing leaves PHP unchanged.
  - [ ] Forced searchable queue opens the searchable presentation for two options.
  - [ ] Server-approved queue stays All and counts attempts.
  - [ ] Rapid input: choose two options in quick succession; the trigger shows only the accepted value.
  - [ ] VoiceOver: trigger announces label, value or placeholder, hint, required, disabled, error; option rows announce selection and disabled state; search field is editable text.
  - [ ] Dynamic Type at the largest accessibility size (AX5): trigger and option labels wrap; sheet/dialog rows keep platform targets.
  - [ ] Reduce Motion on: menu/sheet/dialog presentation respects Reduce Motion / removed animations.
  - [ ] Increase Contrast on (and Smart Invert off): checkmarks/radios and disabled options remain distinguishable.
  - [ ] Right-to-left (device language Arabic or Hebrew, or the Right-to-Left pseudolanguage): trigger chevron, checkmarks and radio rows mirror.
  - [ ] Light and dark appearance both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.
- [ ] BLOCKED — Physical Android device row:
  - [ ] Device: record model, Android version, date and tester. Install the showcase built from `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02` or later and open **Select** from the showcase home.
  - [ ] Priority: choose each option; placeholder shows while null.
  - [ ] Triage: integer identity preserved; unavailable option cannot be chosen.
  - [ ] Large queue: search filters in authored order; select a result; dismiss without choosing leaves PHP unchanged.
  - [ ] Forced searchable queue opens the searchable presentation for two options.
  - [ ] Server-approved queue stays All and counts attempts.
  - [ ] Rapid input: choose two options in quick succession; the trigger shows only the accepted value.
  - [ ] TalkBack: trigger announces label, value or placeholder, hint, required, disabled, error; option rows announce selection and disabled state; search field is editable text.
  - [ ] Font size at maximum plus Display size largest (font scale 2.0 where available): trigger and option labels wrap; sheet/dialog rows keep platform targets.
  - [ ] Remove animations on (Accessibility) or animator scale 0: menu/sheet/dialog presentation respects Reduce Motion / removed animations.
  - [ ] High-contrast text / contrast level high: checkmarks/radios and disabled options remain distinguishable.
  - [ ] Right-to-left (Arabic or Hebrew locale, or Developer options → Force RTL layout direction): trigger chevron, checkmarks and radio rows mirror.
  - [ ] Light and dark theme both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.

## Verdicts

- **Automated, consumer and simulator/emulator evidence: PASS.**
- **Component-release readiness: BLOCKED.** Only the two physical-device rows remain; simulator evidence does not substitute for them.
- **Catalogue readiness: BLOCKED.** Every component review, physical-device evidence and the shared alpha gate must pass.
