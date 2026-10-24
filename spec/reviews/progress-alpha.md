---
title: Progress alpha review evidence
description: Dated contract, adapter, test, consumer, platform-baseline, visual, and release-readiness evidence for Firstlight Progress.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/components/progress.md
  - docs/components/progress.md
  - nativephp.json
  - src/Components/Progress.php
  - src/Elements/Progress.php
  - tests/Feature/ProgressElementTest.php
  - vendor/nativephp/mobile-ui/src/Components/ProgressBar.php
  - vendor/nativephp/mobile-ui/src/Elements/ProgressBar.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIProgressBarRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/ProgressBarRenderer.kt
---

# Progress Alpha Review Evidence

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-08-04 |
| Component | `Progress` / `<firstlight:progress>` |
| State class | Action/display; PHP publishes work state and native code owns presentation or indeterminate animation |
| Implementation | Thin Firstlight adapter over the official `nativephp/mobile-ui` Progress Bar renderers |
| Package revision reviewed | Dirty working tree based on `9c8f0120e17fdf7e8df692053b4986080f70158f` |
| Showcase revision reviewed | Dirty working tree based on `e53978fc245e7041e94103f78fa35633827a5184` |
| iOS test target | iPhone 17 Pro, iOS 26.5, `EB44C64E-1579-4C13-A1F9-C44FBD496763`; XCTest only |
| Android test target | JVM/Paparazzi with JDK 21; no emulator used |
| Visual targets | iPhone 17 Pro `EB44C64E-1579-4C13-A1F9-C44FBD496763` and Android `emulator-5554`; guarded development capture attempted, rejected, and removed |
| Visual reviewer | Maintainer explicitly directed screenshots to be skipped on 2026-08-04; no visual approval recorded |

Progress retains a public `firstlight.progress` Element Tree type while
delegating to Mobile UI 0.3.0's genuine SwiftUI and Material 3 progress
renderers. Firstlight owns strict numeric values, reliable indeterminate
defaults, contradictory-state diagnostics, system-first styling, and the
mandatory accessible name. It exposes no second `<native:progress-bar>` API.

This is development evidence from dirty package and showcase trees. It is not
a clean release capture or a component-release review.

## Automated and consumer evidence

| Result | Command and evidence |
| --- | --- |
| PASS | Focused package tests — 48 passed, 97 assertions across Progress and manifest registration. |
| PASS | `composer test` — 191 passed, 615 assertions; five model evals skipped by default. |
| PASS | `bin/check-component Progress --development` — adapter structure, exact dependency renderer mappings, and documentation gate passed. |
| PASS | `bin/build-docs-artifacts` and `bin/check-docs --development` — generated public artefacts and development documentation checks passed. |
| PASS | Focused Progress showcase tests — 3 passed, 38 assertions across gallery, programmatic publication, accessibility, and deterministic capture fixture. |
| PASS | Showcase catalogue, appearance, and Progress integration tests — 14 passed, 221 assertions. |
| PASS | Showcase `composer test` — 37 passed, 503 assertions. |
| PASS | `php artisan native:plugin:validate <package-root>` — manifest valid; expected warning that this UI-only plugin declares no bridge functions. |
| PASS | `xcodebuild -quiet -scheme FirstlightIOSControls -destination 'platform=iOS Simulator,id=EB44C64E-1579-4C13-A1F9-C44FBD496763' test` — passed; existing Text Field and Segmented snapshot helpers emitted iOS 17 deprecation warnings. |
| PASS | `JAVA_HOME=<jdk-21> tests/android/gradlew -p tests/android testDebugUnitTest verifyPaparazziDebug --rerun-tasks` — BUILD SUCCESSFUL, 35 tasks executed; 309 tests in 87 classes, 0 failures, 0 errors. |
| PASS | `git diff --check` in both repositories. |
| PASS | The guarded capture workflow built, installed, and launched both showcase hosts for the reviewed dirty package revision. |
| BLOCKED | Guarded visual attempts were rejected for launch/loading frames and an animated fixture that could not stabilise. The fixture is now deterministic, but final recapture was cancelled and screenshots were explicitly skipped; all rejected PNGs were removed. |
| BLOCKED | Manual VoiceOver, TalkBack, Increased Contrast, Reduced Motion, RTL, and physical-device checks are not recorded. |

Composer mirrored the exact local package revision into the showcase
successfully, and the final dependency refresh reported no known security
advisories. It did not change the installed NativePHP or Mobile UI versions.

## Constitution review

- **Article I — PASS.** The public element uses the official EDGE and manifest
  adapter seams, and indeterminate animation remains inside the native control.
  There is no WebView, bridge function, timer, or parallel renderer.
- **Article II — PASS.** The API is limited to a familiar progress fraction,
  explicit or inferred indeterminate mode, mandatory accessibility name, and
  external layout. The public Firstlight tag is retained.
- **Article III — PASS for applicable state.** Progress emits no event and owns
  no optimistic state. Programmatic PHP publications update presentation only;
  zero remains distinct from missing progress, and invalid values fail.
- **Article IV — BLOCKED for development evidence.** Exact official iOS and
  Android renderer mappings, both platform test baselines, and showcase host
  builds pass, but final visual evidence was explicitly skipped.
- **Article V — PASS by adapter audit.** The dependency uses SwiftUI
  `ProgressView` and Material 3 `LinearProgressIndicator` without forcing pixel
  parity or exposing platform geometry.
- **Article VI — BLOCKED.** A non-empty `a11y-label` is enforced and native
  progress value semantics are retained, but simulator screen-reader,
  contrast, motion, and physical-device evidence is absent. The ignored
  upstream `a11y-hint` prop is explicitly rejected.
- **Article VII — PASS.** The adapter inherits Mobile UI semantic theme colours
  and rejects arbitrary fill and Android-only track colour overrides.
- **Article VIII — PASS.** The official primitive was audited first and reused;
  Firstlight adds only the semantic wrapper, strict contract, docs, tests, and
  consumer fixtures.
- **Article IX — BLOCKED.** Package, platform baseline, documentation,
  accessibility-tree, consumer tests, and native host builds pass, but
  screenshots, visual approval, and device checks are missing.
- **Article X — BLOCKED for catalogue readiness.** The complete approved alpha
  catalogue and clean component-release evidence are not complete.
- **Article XI — PASS.** Component creation, iOS adapter, Android adapter,
  documentation, screenshot, and constitutional-review skills were applied;
  rejected evidence was not retained after the maintainer skipped screenshots.

Article XII is not applicable because Progress requires no constitutional
amendment.

## Release checklist

- [x] Public API, defaults, mode inference, values, diagnostics, accessibility,
  and eventless state agree across source, tests, specification, and docs.
- [x] The manifest declares the official package, `progress_bar` adapter type,
  and exact installed iOS and Android renderer identifiers.
- [x] Focused and full package and showcase tests pass against the mirrored
  development package.
- [x] Existing iOS and Android platform suites pass without adding placeholder
  Firstlight renderer files.
- [x] Build both showcase hosts for the exact Progress package revision.
- [ ] Capture and obtain approval for the iOS and Android light/dark matrix from
  `/captures/progress` using explicitly approved targets.
- [ ] Re-run guarded capture in release mode from clean package and showcase
  commits.
- [ ] Record dated physical iOS and Android device and accessibility rows.

## Verdicts

- **Implementation and non-visual contract: PASS.** The thin wrapper, strict
  semantic boundary, tests, docs, and installed consumer fixtures are coherent.
- **Development readiness: BLOCKED.** Native screenshot evidence was explicitly
  skipped and remains unavailable.
- **Component-release readiness: BLOCKED.** Clean capture, visual approval, and
  physical-device and accessibility evidence are missing.
- **Catalogue readiness: BLOCKED.** The complete alpha catalogue and every
  component's release evidence are incomplete.

## Development screenshot evidence update — 2026-08-05

Progress's current four-image development matrix is present and visually
approved in the [alpha catalogue screenshot evidence](2026-08-05-alpha-screenshot-evidence.md).
This supersedes only earlier pending or absent screenshot statements. Clean
release capture, physical-device, assistive-technology, and component-specific
blocked rows remain unchanged.

## Component-release review — 2026-10-25

This section supersedes the capture and release-readiness statements above. Earlier rows are kept as history.

### Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-10-25 |
| Review mode | Component-release (`bin/check-component Progress` without `--development`) |
| Component | `Progress` / `<firstlight:progress>` |
| State class | Action/display; PHP publishes work state, native code owns indeterminate animation |
| Implementation | Firstlight adapter over the official `nativephp/mobile-ui` `progress_bar` renderers |
| Package revision captured | `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` (`v1.0.0-RC6`), clean |
| Package revision tested | `42e875a91949981b324cd20854e591a292b9475f` on `release/component-reviews`, clean; later commits on the branch change only reviews and documentation |
| Showcase revision | `c7ed28ef489e1caae305fc84961af9f549eb409d` (`firstlightui/showcase` `main`), clean; its lock installs `firstlightui/nativephp` at `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` |
| iOS simulator | Firstlight iPhone 17 Pro iOS 26.5 (`758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC`); XCTest also on Firstlight iPhone 17 Pro iOS 27.0 (`731CC3F2-1651-4522-A5A3-A781710EE166`) |
| Android emulator | `emulator-5554`, AVD Medium_Phone, Android 17 (API 37), `google_apis_playstore_ps16k` (not a Pixel 9 Pro) |
| Visual reviewer | Pending maintainer approval (row below) |
| Physical devices | None used; both device rows remain BLOCKED |

### Release capture

| Result | Command and evidence |
| --- | --- |
| PASS | `bin/capture-doc-screenshot-batch ListItem List Media PillGroup Progress --showcase=../showcase --ios=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC --android=emulator-5554 --release` — exit 0 at 2026-10-25 07:05:14 AEDT (batch 3). The batch built and launched each host once, ran the focused showcase capture tests, cold-launched `/captures/progress` per appearance, waited for stable frames, rejected identical light/dark pairs and published the matrix. It then restored the showcase `.env`, both appearances, iOS Reduce Motion and the Android animator scale. |
| PASS | The orchestrator reported `Package: 19d12ff9d48baeddd312a6ef5d1a749bdba585e8` and `Showcase: c7ed28ef489e1caae305fc84961af9f549eb409d` with no `(dirty)` marker. It also verified that the showcase's installed `firstlightui/nativephp` reference equals the package `HEAD`. Published images were moved out of the tree between batches so that each batch started clean, and were committed unchanged. |
| PASS | The agent inspected the four images below for documented states, theme, crop, labels, truncation, system chrome and accidental data. Observation: the Android bar stays at the Material default width (about 240 dp) because the fixture omits `w-full`. |
| BLOCKED | Maintainer visual approval of this release matrix is not yet recorded. |

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/progress/ios-light.png` | `docs/screenshots/progress/ios-dark.png` |
| Android | `docs/screenshots/progress/android-light.png` | `docs/screenshots/progress/android-dark.png` |

Thirty matrices were captured with `bin/capture-doc-screenshot-batch` in six five-component batches rather than 31 separate `bin/capture-doc-screenshots` runs. Release mode requires both repositories to be clean before launch, and each single-component run publishes into the package tree. The batch entrypoint uses the same `DocumentationScreenshotCapture` guards.

### Automated and consumer evidence

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
| PASS | `tests/Feature/ProgressElementTest.php` — included in the full Pest run. |
| N/A | No package-owned Swift or Kotlin renderer: the manifest delegates to `nativephp/mobile-ui` `progress_bar`. The full iOS and Android package suites still ran (below). |

Focused showcase tests: `tests/Feature/ProgressCaptureTest.php`, `tests/Feature/ProgressShowcaseTest.php`. They ran in the full showcase suite above and, for the capture fixture, again inside the guarded capture.

### Showcase demo audit

Audited `firstlightui/showcase` `c7ed28ef489e1caae305fc84961af9f549eb409d` against `docs/components/progress.md`. Covered on the interactive page:

- determinate 0, 0.42, 0.87 and 1
- explicit and inferred (`null`) indeterminate
- long accessible context
- programmatic advance with a 100% ceiling and reset

Gaps (listed, not invented):

- Switching the same node between indeterminate and determinate is not demonstrated.

### Constitution review

- **Article I — PASS.** `bin/check-component Progress --development` passes the manifest, renderer-mapping and prohibited-bridge checks; no WebView or bridge call exists in component source.
- **Article II — PASS.** The public API matches `docs/components/progress.md` and is enforced by `tests/Feature/ProgressElementTest.php`.
- **Article III — PASS for applicable state.** State class: Action/display; PHP publishes work state, native code owns indeterminate animation. Contract tests cover the applicable rejection, programmatic and no-echo rules; the showcase gaps above are not counted as evidence.
- **Article IV —** PASS for simulator/emulator evidence; BLOCKED for physical devices. The production renderers built into the generated hosts and rendered `/captures/progress` on iOS 26.5 and Android API 37 in the clean release capture.
- **Article V — PASS by implementation audit.** Firstlight adapter over the official `nativephp/mobile-ui` `progress_bar` renderers; see the development review and documentation platform section. Native feel on hardware is part of the device rows.
- **Article VI — BLOCKED.** Automated accessibility semantics pass, but live VoiceOver/TalkBack, Dynamic Type/font scale, Reduce Motion, increased contrast and RTL on physical devices are not yet recorded.
- **Article VII — PASS.** Semantic theme tokens only; no style-bag or platform escape props (contract tests).
- **Article VIII — PASS.** Unchanged from the development review.
- **Article IX — BLOCKED.** PHP, iOS (26.5 and 27.0), Android, showcase, documentation and release captures pass; physical-device checks (IX.4) and maintainer visual approval are outstanding.
- **Article X — BLOCKED for catalogue readiness.** A component review does not complete the catalogue.
- **Article XI — PASS.** The review skill, structural gate and documentation gate were applied; missing evidence is recorded as BLOCKED.

Article XII is not applicable.

### Release checklist — 2026-10-25

- [x] Clean `--release` capture from `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` and `c7ed28ef489e1caae305fc84961af9f549eb409d` published the four-image matrix (2026-10-25).
- [x] Full package suites (Pest, iOS 26.5 and 27.0 XCTest, Android unit/Paparazzi), `bin/check-component Progress --development`, `bin/check-docs --development`, showcase suite and plugin validation pass at `42e875a91949981b324cd20854e591a292b9475f`.
- [x] Showcase demo audited against every documented state; gaps listed above.
- [ ] BLOCKED — Maintainer visual approval of the four release images listed above (record reviewer, date and verdict per image).
- [ ] BLOCKED — Physical iOS device row:
  - [ ] Device: record model, iOS version, date and tester. Install the showcase built from `42e875a91949981b324cd20854e591a292b9475f` or later and open **Progress** from the showcase home (`/components/progress`).
  - [ ] Compare 0%, 42%, 87%, 100% and indeterminate bars.
  - [ ] Advance progress from 25% to 100%; further presses stay at 100%; Reset returns 25% with no event.
  - [ ] Rapid input: press Advance repeatedly; the bar never exceeds 100%.
  - [ ] VoiceOver: progress role with the `a11y-label` name and the system-formatted percentage for determinate bars; indeterminate bars announce ongoing activity without a percentage.
  - [ ] Dynamic Type at the largest accessibility size (AX5): no text inside the bar; surrounding labels wrap.
  - [ ] Reduce Motion on: indeterminate animation follows Reduce Motion / removed animations policy.
  - [ ] Increase Contrast on (and Smart Invert off): track and indicator remain distinguishable.
  - [ ] Right-to-left (device language Arabic or Hebrew, or the Right-to-Left pseudolanguage): determinate fill grows from the right edge.
  - [ ] Light and dark appearance both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.
- [ ] BLOCKED — Physical Android device row:
  - [ ] Device: record model, Android version, date and tester. Install the showcase built from `42e875a91949981b324cd20854e591a292b9475f` or later and open **Progress** from the showcase home.
  - [ ] Compare 0%, 42%, 87%, 100% and indeterminate bars.
  - [ ] Advance progress from 25% to 100%; further presses stay at 100%; Reset returns 25% with no event.
  - [ ] Rapid input: press Advance repeatedly; the bar never exceeds 100%.
  - [ ] TalkBack: progress role with the `a11y-label` name and the system-formatted percentage for determinate bars; indeterminate bars announce ongoing activity without a percentage.
  - [ ] Font size at maximum plus Display size largest (font scale 2.0 where available): no text inside the bar; surrounding labels wrap.
  - [ ] Remove animations on (Accessibility) or animator scale 0: indeterminate animation follows Reduce Motion / removed animations policy.
  - [ ] High-contrast text / contrast level high: track and indicator remain distinguishable.
  - [ ] Right-to-left (Arabic or Hebrew locale, or Developer options → Force RTL layout direction): determinate fill grows from the right edge.
  - [ ] Light and dark theme both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.

### Verdicts — 2026-10-25

- **Automated, consumer and simulator/emulator evidence: PASS.**
- **Component-release readiness: BLOCKED.** Only the maintainer visual approval and the two physical-device rows remain; simulator evidence does not substitute for them.
- **Catalogue readiness: BLOCKED.** Every component review, physical-device evidence and the shared alpha gate must pass.
