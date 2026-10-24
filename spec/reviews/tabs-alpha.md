---
title: Tabs alpha review evidence
description: Component-release evidence, showcase audit and remaining device and approval gates for Firstlight Tabs.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/workflows/testing.md
  - spec/workflows/showcase-and-screenshots.md
  - spec/screenshots.json
  - docs/components/tabs.md
  - spec/components/tabs.md
  - spec/reviews/tabs-development.md
  - nativephp.json
  - src/Components/Tabs.php
  - src/Elements/Tabs.php
  - tests/Feature/TabsElementTest.php
  - resources/ios/TabsControl.swift
  - resources/ios/TabsRenderer.swift
  - resources/android/TabsControl.kt
  - resources/android/TabsRenderer.kt
  - tests/ios/TabsSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/TabsTest.kt
---

# Tabs Alpha Review Evidence

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-10-25 |
| Review mode | Component-release (`bin/check-component Tabs` without `--development`) |
| Component | `Tabs` / `<firstlight:tabs>` |
| State class | Discrete; server-authoritative tab selection |
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
| PASS | `bin/capture-doc-screenshot-batch Segmented Tabs SearchField Select Slider --showcase=../showcase --ios=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC --android=emulator-5554 --release` — exit 0 at 2026-10-25 07:09:59 AEDT (batch 4, attempt 2; attempt 1 stopped before publishing because the Android showcase was not foregrounded after an upstream host crash on cold launch, and published nothing). The batch built and launched each host once, ran the focused showcase capture tests, cold-launched `/captures/tabs` per appearance, waited for stable frames, rejected identical light/dark pairs and published the matrix. It then restored the showcase `.env`, both appearances, iOS Reduce Motion and the Android animator scale. |
| PASS | The orchestrator reported `Package: 19d12ff9d48baeddd312a6ef5d1a749bdba585e8` and `Showcase: c7ed28ef489e1caae305fc84961af9f549eb409d` with no `(dirty)` marker. It also verified that the showcase's installed `firstlightui/nativephp` reference equals the package `HEAD`. Published images were moved out of the tree between batches so that each batch started clean, and were committed unchanged. |
| PASS | The agent inspected the four images below for documented states, theme, crop, labels, truncation, system chrome and accidental data. No layout, theme, truncation or chrome defects seen. |
| PASS | The maintainer approved all four images of this release matrix on 2026-10-24. |

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/tabs/ios-light.png` | `docs/screenshots/tabs/ios-dark.png` |
| Android | `docs/screenshots/tabs/android-light.png` | `docs/screenshots/tabs/android-dark.png` |

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
| PASS | `tests/Feature/TabsElementTest.php` — included in the full Pest run. |
| FAIL | `tests/ios/TabsSnapshotTests.swift` — iOS 26.5: not found; iOS 27.0: not found. |
| FAIL | `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/TabsTest.kt` — not found in JUnit results. |

Focused showcase tests: `tests/Feature/TabsCaptureTest.php`, `tests/Feature/TabsShowcaseTest.php`. They ran in the full showcase suite above and, for the capture fixture, again inside the guarded capture.

## Showcase demo audit

Audited `firstlightui/showcase` `c7ed28ef489e1caae305fc84961af9f549eb409d` against `docs/components/tabs.md`. Covered on the interactive page:

- `native:model` tab group with badge counts and `badge-a11y-label`, one disabled tab (Manage)
- programmatic selection (Show Deployments) and programmatic badge changes (fail / clear)
- guarded group rejecting Manage with an attempt counter
- `null` unselected group and an empty inert group
- whole-group `disabled`
- five long labels (scrollable Android row)

Gaps (listed, not invented):

- A badge above 99 (`99+`) is not demonstrated.
- Opening a specific tab from a route parameter is not demonstrated.
- `a11y-label` overriding `label` is not demonstrated; `native:model.live` is not demonstrated.
- Rapid taps on the accepting group have no emission counter.

## Constitution review

- **Article I — PASS.** `bin/check-component Tabs --development` passes the manifest, renderer-mapping and prohibited-bridge checks; no WebView or bridge call exists in component source.
- **Article II — PASS.** The public API matches `docs/components/tabs.md` and is enforced by `tests/Feature/TabsElementTest.php`.
- **Article III — PASS for applicable state.** State class: Discrete; server-authoritative tab selection. Contract tests cover the applicable rejection, programmatic and no-echo rules; the showcase gaps above are not counted as evidence.
- **Article IV —** PASS for simulator/emulator evidence; BLOCKED for physical devices. The production renderers built into the generated hosts and rendered `/captures/tabs` on iOS 26.5 and Android API 37 in the clean release capture.
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
- [x] Full package suites (Pest, iOS 26.5 and 27.0 XCTest, Android unit/Paparazzi), `bin/check-component Tabs --development`, `bin/check-docs --development`, showcase suite and plugin validation pass at `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02`.
- [x] Showcase demo audited against every documented state; gaps listed above.
- [x] PASS — Maintainer visual approval: Wojt Janowski approved all four release images listed above on 2026-10-24.
- [ ] BLOCKED — Physical iOS device row:
  - [ ] Device: record model, iOS version, date and tester. Install the showcase built from `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02` or later and open **Tabs** from the showcase home (`/components/tabs`).
  - [ ] Switch Overview/Sites/Deployments; only the selected pane renders; tapping the selected tab emits nothing.
  - [ ] Manage is unavailable; Show Deployments selects it from PHP without a change event.
  - [ ] Fail another deployment / Clear failures update the badge; a zero count hides the badge.
  - [ ] Guarded sections: tap Manage repeatedly; Overview stays selected and attempts rise by one per tap.
  - [ ] No initial section starts unselected; No available sections and Disabled sections stay inert.
  - [ ] Rapid input: tap Sites then Deployments quickly; the indicator settles on the last accepted value with no back-and-forth.
  - [ ] Five long labels: Android scrolls; iOS sizes segments to content.
  - [ ] VoiceOver: each tab announces its label plus badge text (e.g. "Deployments, 2 failed"), tab role, selected/disabled state and position ("Tab 2 of 4").
  - [ ] Dynamic Type at the largest accessibility size (AX5): iOS segment titles stop growing at accessibility sizes and may truncate; Android switches to the scrollable tab row when labels do not fit.
  - [ ] Reduce Motion on: the Material indicator and iOS segment change respect Reduce Motion / removed animations and do not replay on reconciliation.
  - [ ] Increase Contrast on (and Smart Invert off): selected tab indicator/fill and badges remain legible.
  - [ ] Right-to-left (device language Arabic or Hebrew, or the Right-to-Left pseudolanguage): tab order and the Android indicator mirror; badges follow the label in reading order.
  - [ ] Light and dark appearance both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.
- [ ] BLOCKED — Physical Android device row:
  - [ ] Device: record model, Android version, date and tester. Install the showcase built from `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02` or later and open **Tabs** from the showcase home.
  - [ ] Switch Overview/Sites/Deployments; only the selected pane renders; tapping the selected tab emits nothing.
  - [ ] Manage is unavailable; Show Deployments selects it from PHP without a change event.
  - [ ] Fail another deployment / Clear failures update the badge; a zero count hides the badge.
  - [ ] Guarded sections: tap Manage repeatedly; Overview stays selected and attempts rise by one per tap.
  - [ ] No initial section starts unselected; No available sections and Disabled sections stay inert.
  - [ ] Rapid input: tap Sites then Deployments quickly; the indicator settles on the last accepted value with no back-and-forth.
  - [ ] Five long labels: Android scrolls; iOS sizes segments to content.
  - [ ] TalkBack: each tab announces its label plus badge text (e.g. "Deployments, 2 failed"), tab role, selected/disabled state and position ("Tab 2 of 4").
  - [ ] Font size at maximum plus Display size largest (font scale 2.0 where available): iOS segment titles stop growing at accessibility sizes and may truncate; Android switches to the scrollable tab row when labels do not fit.
  - [ ] Remove animations on (Accessibility) or animator scale 0: the Material indicator and iOS segment change respect Reduce Motion / removed animations and do not replay on reconciliation.
  - [ ] High-contrast text / contrast level high: selected tab indicator/fill and badges remain legible.
  - [ ] Right-to-left (Arabic or Hebrew locale, or Developer options → Force RTL layout direction): tab order and the Android indicator mirror; badges follow the label in reading order.
  - [ ] Light and dark theme both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.

## Verdicts

- **Automated, consumer and simulator/emulator evidence: PASS.**
- **Component-release readiness: BLOCKED.** Only the two physical-device rows remain; simulator evidence does not substitute for them.
- **Catalogue readiness: BLOCKED.** Every component review, physical-device evidence and the shared alpha gate must pass.
