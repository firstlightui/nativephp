---
title: Activity Indicator development review
description: Constitutional review of the off-device Firstlight Activity Indicator implementation and its remaining runtime evidence.
status: blocked
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-05-firstlight-activity-indicator-design.md
  - spec/components/activity-indicator.md
  - docs/components/activity-indicator.md
  - nativephp.json
  - src/Elements/ActivityIndicator.php
  - resources/ios/ActivityIndicatorRenderer.swift
  - resources/android/ActivityIndicatorRenderer.kt
  - tests/Feature/ActivityIndicatorElementTest.php
  - tests/ios/ActivityIndicatorSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/ActivityIndicatorTest.kt
---

# Activity Indicator Development Review

Reviewed on 2026-08-05 against package revision
`bf0a3468ae5c6f792f0655914746080d11c634a3` and showcase revision
`cc6e3461a7d304fc1123772f762698af0f3caaff` on `main`.

**Off-device implementation verdict:** PASS

**Development verdict:** BLOCKED

**Component-release verdict:** BLOCKED

The PHP contract, paired native renderer sources, Android tests and goldens,
documentation, exact-revision showcase, and structural gates pass. The iOS
production target cross-compiles for the installed simulator SDK, but the full
iOS test target is blocked by an unrelated existing Swift 6 closure-capture
error in `IconButtonSnapshotTests`. No simulator, emulator, assistive-
technology, or physical-device evidence is claimed.

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative first | PASS | The EDGE element and paired renderers use the NativePHP manifest and `NativeUINode` tree. No WebView, JSON bridge, generated-host edit, or alternate transport was introduced. |
| II. Familiar and coherent APIs | PASS | The API is deliberately narrow: required `a11y-label`, `sm\|md\|lg` size with `md` default, and external layout `class`. Presence means active, so ordinary Blade or Livewire conditional rendering controls the lifecycle without duplicating state through `loading`, `active`, `visible`, or `wire:loading` props. |
| III. Stable values and predictable state | PASS | The indicator has no application value, proposal, or callback. A stable native node reconciles configuration without repeating the iOS announcement; removing and remounting the node creates a new appearance and announcement lifecycle. |
| IV. Equal platform quality | BLOCKED | SwiftUI and Material 3 implementations consume the same size and accessibility contract. Android compiles and verifies its tests and goldens, and iOS production sources cross-compile, but exact iOS XCTest execution and runtime parity evidence remain absent. |
| V. Native expression over pixel parity | PASS | iOS uses SwiftUI `ProgressView` and platform control sizes; Android uses Material 3 `CircularProgressIndicator` with platform-appropriate 20/32/48 dp geometry. Shared semantics do not force pixel-identical drawing. |
| VI. Accessibility is correctness | BLOCKED | The contract requires a non-empty accessible name. Android exposes a polite live region and content description; iOS posts one polite announcement per mount without stealing focus. Automated source and contract checks pass, but VoiceOver, TalkBack, focus, reduced-motion, RTL, contrast, and physical-device rows have not been performed. |
| VII. System-first theming | PASS | Both renderers use the current theme primary colour and native control families. No public colour, stroke, animation, or arbitrary style escape hatch exists. |
| VIII. Small, proven expansion | PASS | Activity Indicator is separate from determinate Progress and is restricted to indeterminate busy feedback. The paired renderers are justified by the installed platform primitives' different announcement capabilities. |
| IX. Evidence-based quality | BLOCKED | TDD, PHP, Android, docs, manifest, structural checks, showcase tests, and off-device iOS production compilation pass. Full iOS test compilation stops in an unrelated Icon Button test, while generated host builds and runtime checks require explicit target permission. |
| X. Public alpha stewardship | BLOCKED | No tag, publication, release-readiness claim, or roadmap completion is authorized. Runtime, screenshot, assistive-technology, and physical-device evidence remain incomplete. |
| XI. Skills enforce the constitution | PASS | The component, platform, documentation, verification, and review workflows were applied. No simulator or emulator was booted, switched, reset, stopped, or otherwise used without explicit permission. |

Article XII is not applicable because Activity Indicator requires no
constitutional amendment.

## Passing evidence

```text
vendor/bin/pest tests/Feature/ActivityIndicatorElementTest.php \
  tests/Feature/PluginManifestTest.php
PASS — 41 tests, 95 assertions

composer test
PASS — 790 tests, 2,283 assertions; 5 model-backed evals skipped by design

composer validate --strict
PASS

bin/check-component ActivityIndicator --development
PASS

bin/build-docs-artifacts
PASS

bin/check-docs --development
PASS

swiftc type-check of Activity Indicator production and test sources against
the installed iOS Simulator SDK
PASS

swift build --build-tests \
  --triple arm64-apple-ios18.0-simulator \
  --sdk /Applications/Xcode.app/Contents/Developer/Platforms/iPhoneSimulator.platform/Developer/SDKs/iPhoneSimulator26.5.sdk
PARTIAL PASS — all production sources compile, including Activity Indicator;
test compilation later stops in existing IconButtonSnapshotTests.swift

JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android testDebugUnitTest
PASS — 139 tests, 7 controller-gated skips, 0 failures, 0 errors

recordPaparazziDebug and verifyPaparazziDebug for ActivityIndicatorScreenshotTest
PASS — small, medium, and large indicators recorded and verified

composer test
PASS in firstlight-showcase — 96 tests, 1,511 assertions

composer validate --strict
PASS in firstlight-showcase

php artisan native:plugin:validate /Users/wojt/Code/clinically-au/firstlight-ui
PASS with expected UI-only warning — no bridge_functions are declared

git diff --check
PASS in both repositories
```

The showcase lock resolves `firstlightui/nativephp` exactly to package revision
`bf0a3468ae5c6f792f0655914746080d11c634a3`. Its gallery, conditional-presence
example, and isolated `/captures/activity-indicator` fixture cover all three
sizes and the required accessible labels.

## Red test evidence

The focused PHP contract was authored and run before implementation; it failed
because the component class, element, precompiler registration, and manifest
entries did not exist. The Android contract likewise failed first on its
missing renderer types, and the manifest contract failed before paired
registration. The showcase contract initially failed because the gallery,
capture route, and navigation entry were absent. Those contracts now pass.

## Deferred and blocked evidence

- Exact iOS XCTest and snapshot execution. Plain `swift test` targets macOS and
  fails at the package's existing UIKit imports. The iOS-simulator cross-build
  compiles production, then stops at an existing implicit-`self` error in
  `tests/ios/IconButtonSnapshotTests.swift`.
- Generated iOS and Android showcase host builds on explicitly approved targets.
- Simulator/emulator appearance, conditional mount/remount, announcement,
  focus-retention, RTL, increased-contrast, and reduced-motion checks.
- Documentation screenshot matrix and explicit visual approval.
- Dated physical-device VoiceOver, TalkBack, offline, theme, reconciliation,
  and host-stability evidence required before component release.

## Honest milestone

Activity Indicator is implemented on `main`, documented, installed in the
showcase at the exact package revision, and proven by broad off-device coverage.
It is not development-complete, component-release-ready, or public-alpha-ready
until the applicable runtime evidence is completed. The roadmap hit-list item
must remain open.

## Approved iOS simulator evidence — 2026-08-05

The maintainer explicitly approved the running iPhone 17 Pro simulator at iOS
26.5 (`EB44C64E-1579-4C13-A1F9-C44FBD496763`) and Android emulator
`emulator-5554` for development host, runtime, accessibility, and screenshot
checks, without booting, switching, resetting, or stopping either target.

The guarded documentation capture stopped before capture because the Android
serial was not available. An exact-serial `adb get-state` probe confirmed the
same absence; no replacement emulator was selected or started. The approved
iOS simulator was already running and retained its lifecycle state.

The exact-destination iOS package gate first exposed a Swift 6 explicit-capture
compile error in the existing Icon Button snapshot fixture. Adding `self.` to
that helper call allowed the complete iOS test target to compile and execute.
The full suite ran 146 tests with five controller-gated skips; remaining suite
failures were pre-existing Badge, Icon Button, Pill Group, Search Field, and
Text Area evidence failures rather than Activity Indicator behaviour.

Activity Indicator's two missing iOS reference images were then recorded from
the approved simulator, visually inspected, and re-run in verification mode.
The focused suite passed all eight tests with zero failures, proving semantic
size decoding, non-interactive accessibility metadata, one announcement per
mount, reconciliation without repeat announcement, stable nested publication,
production renderer construction, and light/dark native snapshots at all three
sizes.

This closes Activity Indicator's exact iOS XCTest and unit-snapshot row. It does
not close generated showcase host runtime, documentation screenshots, manual
VoiceOver/TalkBack, or physical-device evidence. Development and release
verdicts therefore remain **BLOCKED** until the approved Android emulator is
available and the guarded paired workflow can complete.
