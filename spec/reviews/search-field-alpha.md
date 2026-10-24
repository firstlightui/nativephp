---
title: Search Field alpha review evidence
description: Component-release evidence, showcase audit and remaining device and approval gates for Firstlight Search Field.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/workflows/testing.md
  - spec/workflows/showcase-and-screenshots.md
  - spec/screenshots.json
  - docs/components/search-field.md
  - spec/reviews/search-field-development.md
  - nativephp.json
  - src/Components/SearchField.php
  - src/Elements/SearchField.php
  - tests/Feature/SearchFieldElementTest.php
  - resources/ios/SearchFieldControl.swift
  - resources/ios/SearchFieldRenderer.swift
  - resources/android/SearchFieldControl.kt
  - resources/android/SearchFieldRenderer.kt
  - tests/ios/SearchFieldRendererContractTests.swift
  - tests/ios/SearchFieldSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SearchFieldTest.kt
---

# Search Field Alpha Review Evidence

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-10-25 |
| Review mode | Component-release (`bin/check-component SearchField` without `--development`) |
| Component | `SearchField` / `<firstlight:search-field>` |
| State class | Focused text; native search buffer with live/blur/debounce publication and submit |
| Implementation | Paired Firstlight SwiftUI and Material 3 renderers |
| Package revision captured | `8008f508f62eaf1ff92fbb5fc720eac0f3f6cb88` on `release/component-reviews`, clean (recaptured after fixes) |
| Package revision tested | `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02` on `release/component-reviews`, clean; later commits on the branch change only reviews and documentation |
| Showcase revision | `f48dbb7ea0c1666984a3b500241d46488ae24ccf` (`firstlightui/showcase` branch `release/component-review-fixtures`, on `c7ed28e`), clean; its lock installs `firstlightui/nativephp` at `8008f508f62eaf1ff92fbb5fc720eac0f3f6cb88` |
| iOS simulator | Firstlight iPhone 17 Pro iOS 26.5 (`758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC`); XCTest also on Firstlight iPhone 17 Pro iOS 27.0 (`731CC3F2-1651-4522-A5A3-A781710EE166`) |
| Android emulator | `emulator-5554`, AVD Medium_Phone, Android 17 (API 37), `google_apis_playstore_ps16k` (not a Pixel 9 Pro) |
| Visual reviewer | Wojt Janowski (maintainer), 2026-10-24 |
| Physical devices | None used; both device rows remain BLOCKED |

## Release capture

| Result | Command and evidence |
| --- | --- |
| PASS | `bin/capture-doc-screenshot-batch Modal BottomSheet Checkbox Progress SearchField --showcase=../showcase --ios=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC --android=emulator-5554 --release` — exit 0 at 2026-10-25 09:04:53 AEDT (recapture after the heading, Checkbox, Progress and Search Field fixes; attempt 1). The batch built and launched each host once, ran the focused showcase capture tests, cold-launched `/captures/search-field` per appearance, waited for stable frames, rejected identical light/dark pairs and published the matrix. It then restored the showcase `.env`, both appearances, iOS Reduce Motion and the Android animator scale. |
| PASS | The orchestrator reported `Package: 8008f508f62eaf1ff92fbb5fc720eac0f3f6cb88` and `Showcase: f48dbb7ea0c1666984a3b500241d46488ae24ccf` with no `(dirty)` marker. It also verified that the showcase's installed `firstlightui/nativephp` reference equals the package `HEAD`. The published images were committed unchanged. |
| PASS | The agent inspected the four images below for documented states, theme, crop, labels, truncation, system chrome and accidental data. Fixed: the iOS disabled field now dims its text and search glyph, matching Android. |
| PASS | The maintainer approved all four images of this release matrix on 2026-10-24. |

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/search-field/ios-light.png` | `docs/screenshots/search-field/ios-dark.png` |
| Android | `docs/screenshots/search-field/android-light.png` | `docs/screenshots/search-field/android-dark.png` |

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
| PASS | `tests/Feature/SearchFieldElementTest.php` — included in the full Pest run. |
| PASS | `tests/ios/SearchFieldRendererContractTests.swift` — iOS 26.5: 7 passed, 0 failed; iOS 27.0: 7 passed, 0 failed. |
| PASS | `tests/ios/SearchFieldSnapshotTests.swift` — iOS 26.5: 0 passed, 0 failed; iOS 27.0: 0 passed, 0 failed. |
| PASS | `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SearchFieldTest.kt` — 5 tests, 0 failed, 1 skipped (`testDebugUnitTest` / Paparazzi verify). |

Focused showcase tests: `tests/Feature/SearchFieldCaptureTest.php`, `tests/Feature/SearchFieldShowcaseTest.php`. They ran in the full showcase suite above and, for the capture fixture, again inside the guarded capture.

## Showcase demo audit

Audited `firstlightui/showcase` `c7ed28ef489e1caae305fc84961af9f549eb409d` against `docs/components/search-field.md`. Covered on the interactive page:

- live query with native clear and submit counter (including empty submit)
- programmatic reset
- debounced (500 ms) and blur synchronisation
- `disabled`
- `a11y-label` and `a11y-hint`

Gaps (listed, not invented):

- `.lazy` is not demonstrated.
- Server rejection of a query is not demonstrated.

## Constitution review

- **Article I — PASS.** `bin/check-component SearchField --development` passes the manifest, renderer-mapping and prohibited-bridge checks; no WebView or bridge call exists in component source.
- **Article II — PASS.** The public API matches `docs/components/search-field.md` and is enforced by `tests/Feature/SearchFieldElementTest.php`.
- **Article III — PASS for applicable state.** State class: Focused text; native search buffer with live/blur/debounce publication and submit. Contract tests cover the applicable rejection, programmatic and no-echo rules; the showcase gaps above are not counted as evidence.
- **Article IV —** PASS for simulator/emulator evidence; BLOCKED for physical devices. The production renderers built into the generated hosts and rendered `/captures/search-field` on iOS 26.5 and Android API 37 in the clean release capture.
- **Article V — PASS by implementation audit.** Paired Firstlight SwiftUI and Material 3 renderers; see the development review and documentation platform section. Native feel on hardware is part of the device rows.
- **Article VI — BLOCKED.** Automated accessibility semantics pass, but live VoiceOver/TalkBack, Dynamic Type/font scale, Reduce Motion, increased contrast and RTL on physical devices are not yet recorded.
- **Article VII — PASS.** Semantic theme tokens only; no style-bag or platform escape props (contract tests).
- **Article VIII — PASS.** Unchanged from the development review.
- **Article IX — BLOCKED.** PHP, iOS (26.5 and 27.0), Android, showcase, documentation and release captures pass; physical-device checks (IX.4) are outstanding; maintainer visual approval is recorded.
- **Article X — BLOCKED for catalogue readiness.** A component review does not complete the catalogue.
- **Article XI — PASS.** The review skill, structural gate and documentation gate were applied; missing evidence is recorded as BLOCKED.

Article XII is not applicable.
## Release checklist

- [x] Clean `--release` capture from `8008f508f62eaf1ff92fbb5fc720eac0f3f6cb88` and `f48dbb7ea0c1666984a3b500241d46488ae24ccf` published the four-image matrix (2026-10-25, recaptured after fixes).
- [x] Full package suites (Pest, iOS 26.5 and 27.0 XCTest, Android unit/Paparazzi), `bin/check-component SearchField --development`, `bin/check-docs --development`, showcase suite and plugin validation pass at `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02`.
- [x] Showcase demo audited against every documented state; gaps listed above.
- [x] PASS — Maintainer visual approval: Wojt Janowski approved all four release images listed above on 2026-10-24.
- [ ] BLOCKED — Physical iOS device row:
  - [ ] Device: record model, iOS version, date and tester. Install the showcase built from `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02` or later and open **Search Field** from the showcase home (`/components/search-field`).
  - [ ] Live: type, clear (focus stays, PHP empty), submit; last submitted and count update once per Search key.
  - [ ] Reset query from PHP while unfocused emits nothing.
  - [ ] Debounced and blur fields publish only after quiet / focus loss; submit flushes first.
  - [ ] Disabled search cannot focus, clear or submit.
  - [ ] Rapid input: type and submit repeatedly; each submit counts once with the current text.
  - [ ] IME composition is preserved while focused.
  - [ ] VoiceOver: search field named by `a11y-label`, value, hint, disabled; search icon silent; clear button named by the localized `clear_search` label.
  - [ ] Dynamic Type at the largest accessibility size (AX5): text grows; clear target stays at least 44 pt / 48 dp.
  - [ ] Reduce Motion on: clear button appearance respects Reduce Motion / removed animations.
  - [ ] Increase Contrast on (and Smart Invert off): field background, placeholder and icons remain legible.
  - [ ] Right-to-left (device language Arabic or Hebrew, or the Right-to-Left pseudolanguage): search icon and clear button swap edges.
  - [ ] Light and dark appearance both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.
- [ ] BLOCKED — Physical Android device row:
  - [ ] Device: record model, Android version, date and tester. Install the showcase built from `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02` or later and open **Search Field** from the showcase home.
  - [ ] Live: type, clear (focus stays, PHP empty), submit; last submitted and count update once per Search key.
  - [ ] Reset query from PHP while unfocused emits nothing.
  - [ ] Debounced and blur fields publish only after quiet / focus loss; submit flushes first.
  - [ ] Disabled search cannot focus, clear or submit.
  - [ ] Rapid input: type and submit repeatedly; each submit counts once with the current text.
  - [ ] IME composition is preserved while focused.
  - [ ] TalkBack: search field named by `a11y-label`, value, hint, disabled; search icon silent; clear button named by the localized `clear_search` label.
  - [ ] Font size at maximum plus Display size largest (font scale 2.0 where available): text grows; clear target stays at least 44 pt / 48 dp.
  - [ ] Remove animations on (Accessibility) or animator scale 0: clear button appearance respects Reduce Motion / removed animations.
  - [ ] High-contrast text / contrast level high: field background, placeholder and icons remain legible.
  - [ ] Right-to-left (Arabic or Hebrew locale, or Developer options → Force RTL layout direction): search icon and clear button swap edges.
  - [ ] Light and dark theme both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.

## Verdicts

- **Automated, consumer and simulator/emulator evidence: PASS.**
- **Component-release readiness: BLOCKED.** Only the two physical-device rows remain; simulator evidence does not substitute for them.
- **Catalogue readiness: BLOCKED.** Every component review, physical-device evidence and the shared alpha gate must pass.
