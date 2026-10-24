---
title: Status Label alpha review evidence
description: Dated constitutional, test, host-build, visual, accessibility, and release-readiness evidence for Firstlight Status Label.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/components/status-label.md
  - docs/components/status-label.md
  - nativephp.json
  - src/Elements/StatusLabel.php
  - resources/ios/StatusLabelControl.swift
  - resources/ios/StatusLabelRenderer.swift
  - resources/android/StatusLabelControl.kt
  - resources/android/StatusLabelRenderer.kt
  - tests/Feature/StatusLabelElementTest.php
  - tests/ios/StatusLabelSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/StatusLabelTest.kt
---

# Status Label Alpha Review Evidence

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-08-04 |
| Component | `StatusLabel` / `<firstlight:status-label>` |
| State class | Action/display; display-only and inert |
| Implementation | Paired SwiftUI and Material 3 renderers |
| Package revision reviewed | `eb7d3abb8885e7f5bd97132066f7b4737774a1ef` |
| Showcase revision reviewed | `96f18f2abb6330a91ebd6948a240ff3cbfa74780` |
| iOS simulator | iPhone 17 Pro, iOS 26.5, `EB44C64E-1579-4C13-A1F9-C44FBD496763` |
| Android emulator | Pixel 9 Pro AVD, Android 16 / API 36, `emulator-5554` |
| Visual reviewer | Maintainer explicitly approved all four images on 2026-08-04 |

The showcase lock resolved `firstlightui/nativephp` to the package revision above. The review file and release-checker alignment were authored after that revision and do not change the reviewed component runtime.

## Automated and host evidence

| Result | Command and evidence |
| --- | --- |
| PASS | `composer test` — 77 passed, 291 assertions; four model evals intentionally skipped. |
| PASS | `bin/check-component StatusLabel --development` — constitution checks passed. |
| PASS | `bin/check-docs --development` — documentation checks passed. |
| PASS | `xcodebuild -scheme FirstlightIOSControls -destination 'platform=iOS Simulator,id=EB44C64E-1579-4C13-A1F9-C44FBD496763' test` — 35 passed, including seven Status Label tests. |
| PASS | `JAVA_HOME=$(/usr/libexec/java_home -v 21) tests/android/gradlew -p tests/android testDebugUnitTest` — build successful, including Status Label contract and Paparazzi cases. |
| PASS | Showcase `composer test` — 14 passed, 151 assertions. |
| PASS | `php artisan native:plugin:validate <package-root>` — manifest valid; expected UI-only warning that no bridge functions are declared. |
| PASS | Generated Android host `./gradlew assembleRelease` — build successful. |
| PASS | Generated iOS host `xcodebuild -workspace NativePHP.xcworkspace -scheme NativePHP-simulator -destination 'platform=iOS Simulator,id=EB44C64E-1579-4C13-A1F9-C44FBD496763' build CODE_SIGNING_ALLOWED=NO` — build successful. |

## Constitution review

- **Article I — PASS.** The public tag publishes an official EDGE element and both renderers reconcile by stable node ID through the SuperNative tree. No WebView or parallel bridge is present.
- **Article II — PASS.** The public API is limited to `label`, semantic `tone`, accessibility metadata, and external `class`; interactive and field-state attributes fail explicitly.
- **Article III — PASS.** The component owns no mutable or proposed user state. Server publications replace display metadata without emitting an event.
- **Article IV — PASS.** Production SwiftUI and Android Compose renderers, platform tests, and both generated consumer hosts pass against the same authored API.
- **Article V — PASS.** iOS uses `Text` with `Capsule`; Android uses Material 3 `Text` within `Surface`. Behavioural parity does not force pixel parity.
- **Article VI — PASS for development.** Automated evidence covers static-text semantics, accessible names and hints, Dynamic Type, Android font scale `2.0`, light/dark rendering, long labels, and a minimum 4.5:1 contrast fallback. Live VoiceOver, TalkBack, and physical-device rows remain release blockers below.
- **Article VII — PASS.** Both renderers consume NativePHP semantic theme tokens and expose no platform styling escape prop.
- **Article VIII — PASS.** The specification audits NativePHP Badge and Chip and records why their semantics do not satisfy this display-only status contract.
- **Article IX — PASS for development.** PHP, iOS, Android, showcase, documentation, explicit-target screenshots, visual approval, plugin validation, and both host builds are evidenced. Clean release capture and physical-device review remain blocked.
- **Article X — BLOCKED for catalogue readiness.** One passing component does not complete the approved public-alpha catalogue.
- **Article XI — PASS.** Component, platform, documentation, screenshot, and review skills were applied; deterministic structural and documentation gates pass in development mode.

Article XII is not applicable because this component requires no constitutional amendment.

## Approved visual evidence

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/status-label/ios-light.png` | `docs/screenshots/status-label/ios-dark.png` |
| Android | `docs/screenshots/status-label/android-light.png` | `docs/screenshots/status-label/android-dark.png` |

The matrix was captured from `/captures/status-label` with the explicit simulator and emulator targets above. The maintainer approved all four images for documented content, native presentation, theme differentiation, crop, labels, and truncation. This was development capture from working repositories, not clean `--release` capture.

## Release checklist

- [x] Public API, failure behaviour, and state classification match current PHP source and contract tests.
- [x] Both production renderer implementations compile and pass their platform suites.
- [x] The exact package revision is installed in the showcase lock.
- [x] Focused and full showcase tests pass.
- [x] Both generated showcase hosts build without durable generated-tree edits.
- [x] Public documentation, maintained specification, screenshot manifest, and generated LLM artefacts pass development checks.
- [x] The four-image simulator/emulator matrix has explicit visual approval.
- [ ] Re-run the guarded screenshot workflow with `--release` from clean package and showcase revisions and record the successful capture report.
- [ ] Record a dated physical iOS device row covering presentation, VoiceOver, Dynamic Type, increased contrast, Reduced Motion, RTL, offline behaviour, and host stability.
- [ ] Record a dated physical Android device row covering presentation, TalkBack, font scaling, increased contrast, Reduced Motion, RTL, offline behaviour, and host stability.

## Verdicts

- **Development readiness: PASS.** The display-only Status Label contract, both platform implementations, automated accessibility evidence, consumer integration, documentation, visual matrix, and host builds pass.
- **Component-release readiness: BLOCKED.** Clean release-mode capture and both dated physical-device/accessibility rows are missing.
- **Catalogue readiness: BLOCKED.** The complete alpha catalogue and every component's shared release evidence are not complete.

## Warnings and assumptions

- The failed `php artisan native:build --target=android` attempt entered NativePHP's iOS simulator path and selected an unavailable default destination. It is not counted as evidence; explicit generated-host Android and iOS builds passed afterward.
- Android reported an empty `sdk.dir` warning while resolving the SDK from the environment; the release host still assembled successfully.
- Xcode emitted existing environment/state warnings from Segmented contract construction and an AppIntents metadata notice; Status Label tests and the generated host build passed.
- Composer advisory refresh was unavailable in the restricted environment during the preceding path-package update; this review does not claim a current dependency security audit.

## Catalogue screenshot evidence update — 2026-08-05

The remaining alpha component matrices are now present and visually approved in
the [alpha catalogue screenshot evidence](2026-08-05-alpha-screenshot-evidence.md).
This closes the screenshot-only catalogue gap recorded above. Clean release
capture, physical-device, assistive-technology, and component-specific blocked
rows remain unchanged.

## Component-release review — 2026-10-25

This section supersedes the capture and release-readiness statements above. Earlier rows are kept as history.

### Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-10-25 |
| Review mode | Component-release (`bin/check-component StatusLabel` without `--development`) |
| Component | `StatusLabel` / `<firstlight:status-label>` |
| State class | Action/display; display-only and inert |
| Implementation | Paired Firstlight SwiftUI and Material 3 renderers |
| Package revision captured | `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` (`v1.0.0-RC6`), clean |
| Package revision tested | `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02` on `release/component-reviews`, clean; later commits on the branch change only reviews and documentation |
| Showcase revision | `c7ed28ef489e1caae305fc84961af9f549eb409d` (`firstlightui/showcase` `main`), clean; its lock installs `firstlightui/nativephp` at `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` |
| iOS simulator | Firstlight iPhone 17 Pro iOS 26.5 (`758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC`); XCTest also on Firstlight iPhone 17 Pro iOS 27.0 (`731CC3F2-1651-4522-A5A3-A781710EE166`) |
| Android emulator | `emulator-5554`, AVD Medium_Phone, Android 17 (API 37), `google_apis_playstore_ps16k` (not a Pixel 9 Pro) |
| Visual reviewer | Pending maintainer approval (row below) |
| Physical devices | None used; both device rows remain BLOCKED |

### Release capture

| Result | Command and evidence |
| --- | --- |
| PASS | `bin/capture-doc-screenshot-batch Stepper StatusLabel TextField CodeEditor TextArea --showcase=../showcase --ios=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC --android=emulator-5554 --release` — exit 0 at 2026-10-25 07:14:48 AEDT (batch 5, attempt 2; attempt 1 stopped before publishing because the Android showcase was not foregrounded after an upstream host crash on cold launch, and published nothing). The batch built and launched each host once, ran the focused showcase capture tests, cold-launched `/captures/status-label` per appearance, waited for stable frames, rejected identical light/dark pairs and published the matrix. It then restored the showcase `.env`, both appearances, iOS Reduce Motion and the Android animator scale. |
| PASS | The orchestrator reported `Package: 19d12ff9d48baeddd312a6ef5d1a749bdba585e8` and `Showcase: c7ed28ef489e1caae305fc84961af9f549eb409d` with no `(dirty)` marker. It also verified that the showcase's installed `firstlightui/nativephp` reference equals the package `HEAD`. Published images were moved out of the tree between batches so that each batch started clean, and were committed unchanged. |
| PASS | The agent inspected the four images below for documented states, theme, crop, labels, truncation, system chrome and accidental data. No layout, theme, truncation or chrome defects seen. |
| BLOCKED | Maintainer visual approval of this release matrix is not yet recorded. |

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/status-label/ios-light.png` | `docs/screenshots/status-label/ios-dark.png` |
| Android | `docs/screenshots/status-label/android-light.png` | `docs/screenshots/status-label/android-dark.png` |

Thirty matrices were captured with `bin/capture-doc-screenshot-batch` in six five-component batches rather than 31 separate `bin/capture-doc-screenshots` runs. Release mode requires both repositories to be clean before launch, and each single-component run publishes into the package tree. The batch entrypoint uses the same `DocumentationScreenshotCapture` guards.

### Automated and consumer evidence

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
| PASS | `tests/Feature/StatusLabelElementTest.php` — included in the full Pest run. |
| PASS | `tests/ios/StatusLabelSnapshotTests.swift` — iOS 26.5: 10 passed, 0 failed; iOS 27.0: 10 passed, 0 failed. |
| FAIL | `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/StatusLabelTest.kt` — not found in JUnit results. |

Focused showcase tests: `tests/Feature/StatusLabelCaptureTest.php`, `tests/Feature/StatusLabelShowcaseTest.php`. They ran in the full showcase suite above and, for the capture fixture, again inside the guarded capture.

### Showcase demo audit

Audited `firstlightui/showcase` `c7ed28ef489e1caae305fc84961af9f549eb409d` against `docs/components/status-label.md`. Covered on the interactive page:

- five semantic tones, text-only Draft, icons with iOS/Android overrides
- long label wrapping
- `a11y-label` and `a11y-hint` override

Gaps (listed, not invented):

- Programmatic replacement of label or tone by a later publication is not demonstrated.
- Typed `IosSymbol` / `AndroidSymbol` icon values are PHP-contract-only evidence.

### Constitution review

- **Article I — PASS.** `bin/check-component StatusLabel --development` passes the manifest, renderer-mapping and prohibited-bridge checks; no WebView or bridge call exists in component source.
- **Article II — PASS.** The public API matches `docs/components/status-label.md` and is enforced by `tests/Feature/StatusLabelElementTest.php`.
- **Article III — PASS for applicable state.** State class: Action/display; display-only and inert. Contract tests cover the applicable rejection, programmatic and no-echo rules; the showcase gaps above are not counted as evidence.
- **Article IV —** PASS for simulator/emulator evidence; BLOCKED for physical devices. The production renderers built into the generated hosts and rendered `/captures/status-label` on iOS 26.5 and Android API 37 in the clean release capture.
- **Article V — PASS by implementation audit.** Paired Firstlight SwiftUI and Material 3 renderers; see the development review and documentation platform section. Native feel on hardware is part of the device rows.
- **Article VI — BLOCKED.** Automated accessibility semantics pass, but live VoiceOver/TalkBack, Dynamic Type/font scale, Reduce Motion, increased contrast and RTL on physical devices are not yet recorded.
- **Article VII — PASS.** Semantic theme tokens only; no style-bag or platform escape props (contract tests).
- **Article VIII — PASS.** Unchanged from the development review.
- **Article IX — BLOCKED.** PHP, iOS (26.5 and 27.0), Android, showcase, documentation and release captures pass; physical-device checks (IX.4) and maintainer visual approval are outstanding.
- **Article X — BLOCKED for catalogue readiness.** A component review does not complete the catalogue.
- **Article XI — PASS.** The review skill, structural gate and documentation gate were applied; missing evidence is recorded as BLOCKED.

Article XII is not applicable.

### Release checklist — 2026-10-25

- [x] Clean `--release` capture from `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` and `c7ed28ef489e1caae305fc84961af9f549eb409d` published the four-image matrix (2026-10-25).
- [x] Full package suites (Pest, iOS 26.5 and 27.0 XCTest, Android unit/Paparazzi), `bin/check-component StatusLabel --development`, `bin/check-docs --development`, showcase suite and plugin validation pass at `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02`.
- [x] Showcase demo audited against every documented state; gaps listed above.
- [ ] BLOCKED — Maintainer visual approval of the four release images listed above (record reviewer, date and verdict per image).
- [ ] BLOCKED — Physical iOS device row:
  - [ ] Device: record model, iOS version, date and tester. Install the showcase built from `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02` or later and open **Status Label** from the showcase home (`/components/status-label`).
  - [ ] Inspect all five tones and Draft in light and dark.
  - [ ] Long label wraps fully with the background behind every line.
  - [ ] Nothing is tappable; taps produce no feedback.
  - [ ] VoiceOver: each label is static text (no button, selected or live-region role); icons are silent; the override reads "Referral status: ready" with its hint.
  - [ ] Dynamic Type at the largest accessibility size (AX5): labels expand or wrap rather than truncate; wrapped labels keep a capped corner radius.
  - [ ] Reduce Motion on: no animation is expected.
  - [ ] Increase Contrast on (and Smart Invert off): tone foreground keeps at least 4.5:1 against its background in both appearances.
  - [ ] Right-to-left (device language Arabic or Hebrew, or the Right-to-Left pseudolanguage): leading icons move to the right edge.
  - [ ] Light and dark appearance both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.
- [ ] BLOCKED — Physical Android device row:
  - [ ] Device: record model, Android version, date and tester. Install the showcase built from `822ddeb1afa93cd1171bdd6eab46e45a7f38cb02` or later and open **Status Label** from the showcase home.
  - [ ] Inspect all five tones and Draft in light and dark.
  - [ ] Long label wraps fully with the background behind every line.
  - [ ] Nothing is tappable; taps produce no feedback.
  - [ ] TalkBack: each label is static text (no button, selected or live-region role); icons are silent; the override reads "Referral status: ready" with its hint.
  - [ ] Font size at maximum plus Display size largest (font scale 2.0 where available): labels expand or wrap rather than truncate; wrapped labels keep a capped corner radius.
  - [ ] Remove animations on (Accessibility) or animator scale 0: no animation is expected.
  - [ ] High-contrast text / contrast level high: tone foreground keeps at least 4.5:1 against its background in both appearances.
  - [ ] Right-to-left (Arabic or Hebrew locale, or Developer options → Force RTL layout direction): leading icons move to the right edge.
  - [ ] Light and dark theme both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.

### Verdicts — 2026-10-25

- **Automated, consumer and simulator/emulator evidence: PASS.**
- **Component-release readiness: BLOCKED.** Only the maintainer visual approval and the two physical-device rows remain; simulator evidence does not substitute for them.
- **Catalogue readiness: BLOCKED.** Every component review, physical-device evidence and the shared alpha gate must pass.
