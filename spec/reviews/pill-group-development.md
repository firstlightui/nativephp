---
title: Pill Group development review
description: Constitution review and current automated evidence for the Firstlight Pill Group build.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/components/pill-group.md
  - docs/components/pill-group.md
  - nativephp.json
  - spec/screenshots.json
  - src/Elements/PillGroup.php
  - resources/ios/PillGroupControl.swift
  - resources/ios/PillGroupRenderer.swift
  - resources/android/PillGroupControl.kt
  - resources/android/PillGroupRenderer.kt
  - tests/Feature/PillGroupElementTest.php
  - tests/ios/PillGroupSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/PillGroupTest.kt
---

# Pill Group Development Review

**Implementation verdict:** PASS

**Development milestone verdict:** BLOCKED

**Component-release verdict:** BLOCKED

Reviewed on 2026-08-04 against package implementation commit
`faac8e5007f6b46c744381d4503448420ef57834` and showcase commit
`379e68f`. The showcase lock and installed package both resolve to the full
package commit above. This review file is authored afterward and does not
change the reviewed runtime.

The maintainer explicitly deferred runtime documentation screenshots. The
capture manifest and showcase fixture remain ready, but no screenshot or
visual-approval claim is included in this review.

## Requirement review

| Requirement | Verdict | Evidence |
| --- | --- | --- |
| Public EDGE contract | PASS | `<firstlight:pill-group>` publishes one strict grouped element with scalar-or-list values, standard `@change`, familiar `native:model`, and explicit diagnostics. |
| Official primitive decision | PASS | The maintained contract records why NativePHP's per-chip Boolean model cannot provide one stable scalar-or-list binding, whole-value callbacks, group validation, or stale-proposal suppression. |
| Server-authoritative state | PASS | Swift and Kotlin state tests prove one immediate proposal, no optimistic selection, stable-node reconciliation, no event echo, and suppression of stale rapid taps. |
| iOS implementation | PASS | SwiftUI capsule buttons, wrapping `Layout`, semantic selection, 44-point targets, RTL flow, and large Dynamic Type snapshots compile in the complete iOS test target. |
| Android implementation | PASS | Material 3 `FilterChip` controls in `FlowRow`, 48-dp targets, semantic selection, font-scale snapshots, and contrast-safe selected label/check colours pass the full Android suite. |
| Equal authored API | PASS | The PHP option, value, field metadata, error, required, disabled, and accessibility contract is consumed by both renderers without platform-widget props. |
| Accessibility structure | PASS | Visible or explicit group labels, hints, required/error semantics, selected/disabled option semantics, non-colour checkmarks, minimum targets, wrapping, large-text snapshots, and showcase `assertAccessible()` coverage pass. |
| Public documentation | PASS | Maintained contract, consumer reference, indexes, compatibility link, generated LLM artefacts, and reserved screenshot manifest paths pass development documentation checks. |
| Exact-package showcase | PASS | The synchronized showcase installs package commit `faac8e5`; focused Pill Group and full consumer tests pass after clearing stale compiled Blade views from the preceding package revision. |
| Current generated iOS host | PASS | The regenerated showcase `NativePHP-simulator` workspace builds for a generic iOS Simulator with signing disabled. |
| Current generated Android host | BLOCKED | `native:install` leaves documented template placeholders until NativePHP's run/package preparation phase. A direct Gradle build is therefore not evidence, and no shared emulator was reserved after screenshots were deferred. The full Android library suite does compile the production renderer. |
| Runtime screenshot matrix and visual approval | BLOCKED | Explicitly deferred by the maintainer. The branch contains no documentation screenshots and makes no visual-approval claim. |
| Physical-device interaction | BLOCKED | VoiceOver, TalkBack, increased contrast, Reduced Motion, RTL interaction, offline behaviour, reconciliation, and rapid input have not been reviewed on physical devices. |
| Clean release capture | BLOCKED | Release-mode capture from clean package and showcase commits has not been run. |

## Constitution review

- **Article I — PASS.** Pill Group uses the official EDGE element tree,
  shared-memory publication, renderer registration, and standard press events;
  it introduces no WebView or parallel bridge.
- **Article II — PASS.** The public API uses familiar values, options,
  `native:model`, field metadata, and `@change`, with no platform terminology
  or styling escape hatch.
- **Article III — PASS.** Stable string or integer values, `null`, missing
  selections, disabled choices, immediate proposals, and reconciliation are
  covered by PHP and native state tests.
- **Article IV — PASS for implementation.** Both production renderers consume
  the same contract and pass their platform compile and behaviour suites.
- **Article V — PASS.** iOS retains SwiftUI capsule expression while Android
  retains Material 3 FilterChip geometry and state layers.
- **Article VI — PASS for automated structure; BLOCKED for release.** Native
  semantics, targets, scaling, themes, disabled state, errors, and non-colour
  selection are automated. Live assistive-technology and physical-device rows
  remain open.
- **Article VII — PASS.** Both renderers derive colours from NativePHP semantic
  tokens. Android maps Material's separate selected label and leading-icon
  channels to one contrast-safe selected-content token.
- **Article VIII — PASS.** The official NativePHP chip was audited before the
  paired-renderer path was selected, and the component remains deliberately
  narrower than a general chip API.
- **Article IX — BLOCKED.** Contract, platform, showcase, documentation, and
  generic iOS host evidence pass, but current prepared Android consumer-host
  evidence and the runtime screenshot matrix are deferred.
- **Article X — BLOCKED.** One implementation does not establish public-alpha
  catalogue readiness.
- **Article XI — PASS.** Repository component, platform, documentation, and
  review skills were applied; deterministic development gates pass without
  weakening screenshot or device requirements.

Article XII is not applicable; Pill Group requires no constitutional amendment.

## Verification

- `composer test` — 172 passed, 610 assertions; five model evals skipped by default.
- `JAVA_HOME=... tests/android/gradlew -p tests/android testDebugUnitTest` — build successful, including Pill Group state, colour, semantics, light/dark, error/disabled, and font-scale cases.
- `xcodebuild -quiet -scheme FirstlightIOSControls -destination 'generic/platform=iOS Simulator' build-for-testing` — complete iOS test target compiled; existing deprecated test-helper warnings remain.
- Earlier focused Pill Group simulator run — ten iOS Pill Group snapshot and renderer tests passed before the Android-only selected-icon correction and main synchronization.
- `bin/check-component PillGroup --development` — passed.
- `bin/build-docs-artifacts && bin/check-docs --development` — passed.
- Showcase focused Button and Pill Group tests — 8 passed, 77 assertions.
- Showcase `composer test` — 37 passed, 480 assertions.
- `php artisan native:plugin:validate vendor/firstlightui/nativephp` — manifest valid with the expected UI-only warning that no bridge functions are declared.
- Regenerated showcase iOS host generic Simulator build — passed.

## Deferred evidence

- Runtime outputs remain reserved at
  `docs/screenshots/pill-group/{ios,android}-{light,dark}.png` but are absent.
- The screenshot workflow must be rerun against explicit fixed targets after
  the maintainer makes them available, followed by explicit visual approval.
- A prepared exact-revision Android showcase host build and the dated physical
  iOS and Android rows remain required before component release.

Do not describe Pill Group as development-complete or release-ready until the
blocked rows are closed.

## Development screenshot evidence update — 2026-08-05

Pill Group's current four-image development matrix is present and visually
approved in the [alpha catalogue screenshot evidence](2026-08-05-alpha-screenshot-evidence.md).
This supersedes only earlier pending or absent screenshot statements. Clean
release capture, physical-device, assistive-technology, and component-specific
blocked rows remain unchanged.
