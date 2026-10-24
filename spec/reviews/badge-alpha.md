---
title: Badge alpha review evidence
description: Component-release evidence, showcase audit and remaining device and approval gates for Firstlight Badge.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/workflows/testing.md
  - spec/workflows/showcase-and-screenshots.md
  - spec/screenshots.json
  - docs/components/badge.md
  - spec/components/badge.md
  - spec/reviews/badge-development.md
  - nativephp.json
  - src/Components/Badge.php
  - src/Elements/Badge.php
  - tests/Feature/BadgeElementTest.php
  - resources/ios/BadgeControl.swift
  - resources/ios/BadgeRenderer.swift
  - resources/android/BadgeControl.kt
  - resources/android/BadgeRenderer.kt
  - tests/ios/BadgeSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/BadgeTest.kt
---

# Badge Alpha Review Evidence

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-10-25 |
| Review mode | Component-release (`bin/check-component Badge` without `--development`) |
| Component | `Badge` / `<firstlight:badge>` |
| State class | Action/display; display-only and inert |
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
| PASS | `bin/capture-doc-screenshot-batch Metric ActivityIndicator Badge Button Callout --showcase=../showcase --ios=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC --android=emulator-5554 --release` — exit 0 at 2026-10-25 07:00:33 AEDT (batch 1). The batch built and launched each host once, ran the focused showcase capture tests, cold-launched `/captures/badge` per appearance, waited for stable frames, rejected identical light/dark pairs and published the matrix. It then restored the showcase `.env`, both appearances, iOS Reduce Motion and the Android animator scale. |
| PASS | The orchestrator reported `Package: 19d12ff9d48baeddd312a6ef5d1a749bdba585e8` and `Showcase: c7ed28ef489e1caae305fc84961af9f549eb409d` with no `(dirty)` marker. It also verified that the showcase's installed `firstlightui/nativephp` reference equals the package `HEAD`. Published images were moved out of the tree between batches so that each batch started clean, and were committed unchanged. |
| PASS | The agent inspected the four images below for documented states, theme, crop, labels, truncation, system chrome and accidental data. No layout, theme, truncation or chrome defects seen. |
| PASS | The maintainer approved all four images of this release matrix on 2026-10-24. |

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/badge/ios-light.png` | `docs/screenshots/badge/ios-dark.png` |
| Android | `docs/screenshots/badge/android-light.png` | `docs/screenshots/badge/android-dark.png` |

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
| PASS | `tests/Feature/BadgeElementTest.php` — included in the full Pest run. |
| PASS | `tests/ios/BadgeSnapshotTests.swift` — iOS 26.5: 5 passed, 0 failed; iOS 27.0: 5 passed, 0 failed. |
| FAIL | `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/BadgeTest.kt` — not found in JUnit results. |

Focused showcase tests: `tests/Feature/BadgeCaptureTest.php`, `tests/Feature/BadgeShowcaseTest.php`. They ran in the full showcase suite above and, for the capture fixture, again inside the guarded capture.

## Showcase demo audit

Audited `firstlightui/showcase` `c7ed28ef489e1caae305fc84961af9f549eb409d` against `docs/components/badge.md`. Covered on the interactive page:

- counts 0 (hidden), 1, 9, 99 and 100 (`99+`)
- five tones on text badges
- `a11y-label` and `a11y-hint` override

Gaps (listed, not invented):

- Programmatic count or label updates are not demonstrated.
- Refreshing custom warning colours after `Theme::merge()` is not demonstrated.

## Constitution review

- **Article I — PASS.** `bin/check-component Badge --development` passes the manifest, renderer-mapping and prohibited-bridge checks; no WebView or bridge call exists in component source.
- **Article II — PASS.** The public API matches `docs/components/badge.md` and is enforced by `tests/Feature/BadgeElementTest.php`.
- **Article III — PASS for applicable state.** State class: Action/display; display-only and inert. Contract tests cover the applicable rejection, programmatic and no-echo rules; the showcase gaps above are not counted as evidence.
- **Article IV —** PASS for simulator/emulator evidence; BLOCKED for physical devices. The production renderers built into the generated hosts and rendered `/captures/badge` on iOS 26.5 and Android API 37 in the clean release capture.
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
- [x] Full package suites (Pest, iOS 26.5 and 27.0 XCTest, Android unit/Paparazzi), `bin/check-component Badge --development`, `bin/check-docs --development`, showcase suite and plugin validation pass at `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02`.
- [x] Showcase demo audited against every documented state; gaps listed above.
- [x] PASS — Maintainer visual approval: Wojt Janowski approved all four release images listed above on 2026-10-24.
- [ ] BLOCKED — Physical iOS device row:
  - [ ] Device: record model, iOS version, date and tester. Install the showcase built from `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02` or later and open **Badge** from the showcase home (`/components/badge`).
  - [ ] Zero renders nothing; 100 shows `99+`.
  - [ ] All tones in light and dark; nothing is tappable.
  - [ ] VoiceOver: each badge is static text; count badges read their contextual label ("100 pending items"), the hidden zero badge exposes nothing; Rx reads "Prescription ready" with its hint.
  - [ ] Dynamic Type at the largest accessibility size (AX5): badges grow with text size without clipping.
  - [ ] Reduce Motion on: no animation is expected.
  - [ ] Increase Contrast on (and Smart Invert off): tone foreground keeps at least 4.5:1; warning uses the warning/on-warning pair.
  - [ ] Right-to-left (device language Arabic or Hebrew, or the Right-to-Left pseudolanguage): badges align to the leading edge.
  - [ ] Light and dark appearance both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.
- [ ] BLOCKED — Physical Android device row:
  - [ ] Device: record model, Android version, date and tester. Install the showcase built from `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02` or later and open **Badge** from the showcase home.
  - [ ] Zero renders nothing; 100 shows `99+`.
  - [ ] All tones in light and dark; nothing is tappable.
  - [ ] TalkBack: each badge is static text; count badges read their contextual label ("100 pending items"), the hidden zero badge exposes nothing; Rx reads "Prescription ready" with its hint.
  - [ ] Font size at maximum plus Display size largest (font scale 2.0 where available): badges grow with text size without clipping.
  - [ ] Remove animations on (Accessibility) or animator scale 0: no animation is expected.
  - [ ] High-contrast text / contrast level high: tone foreground keeps at least 4.5:1; warning uses the warning/on-warning pair.
  - [ ] Right-to-left (Arabic or Hebrew locale, or Developer options → Force RTL layout direction): badges align to the leading edge.
  - [ ] Light and dark theme both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.

## Verdicts

- **Automated, consumer and simulator/emulator evidence: PASS.**
- **Component-release readiness: BLOCKED.** Only the two physical-device rows remain; simulator evidence does not substitute for them.
- **Catalogue readiness: BLOCKED.** Every component review, physical-device evidence and the shared alpha gate must pass.
