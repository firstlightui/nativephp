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
| PASS | `JAVA_HOME=<jdk-21> tests/android/gradlew -p tests/android testDebugUnitTest` — build and JVM/Paparazzi suite successful without an emulator. |
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
