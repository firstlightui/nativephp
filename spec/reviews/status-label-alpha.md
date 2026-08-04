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
