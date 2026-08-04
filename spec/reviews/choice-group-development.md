---
title: Choice Group development review
description: Constitutional review of the off-device Firstlight Choice Group implementation and its remaining integration, runtime, and release blockers.
status: blocked
sources:
  - Constitution.md
  - spec/components/choice-group.md
  - docs/components/choice-group.md
  - src/Elements/ChoiceGroup.php
  - resources/ios/ChoiceGroupControl.swift
  - resources/ios/ChoiceGroupRenderer.swift
  - resources/android/ChoiceGroupControl.kt
  - resources/android/ChoiceGroupRenderer.kt
---

# Choice Group Development Review

Reviewed on 2026-08-04 against package implementation commit
`5e1e491d9db7546ba67c2ac7f10556b09eb956ed` and showcase commit
`2ffc24b`. The showcase lock resolves exactly to the package implementation
commit. This review file is authored afterward and does not change the reviewed
runtime.

**Off-device component verdict:** PASS

**Development verdict:** BLOCKED

**Component-release verdict:** BLOCKED

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative first | PASS | Choice Group publishes primitive props through one EDGE element and paired renderers consume `NativeUINode`; it adds no WebView, JSON bridge, or alternate binding system. |
| II. Familiar APIs | PASS | `options`, `value`/`native:model`, `multiple`, field metadata, accessibility metadata, external `class`, and standard `@change` form the complete public API. |
| III. Predictable state | PASS | PHP and native state contracts cover stable typed values, radio versus checkbox proposals, no optimistic selection, stale-tap suppression, equal-publication unlock, reconciliation, disabled rows, and empty options. |
| IV. Equal platform quality | BLOCKED | Both production implementations exist and the complete Android suite compiles and tests Choice Group. The controller-owned iOS target run and live platform comparison remain outstanding. |
| V. Native expression | BLOCKED | Source uses Apple checkmarked option rows and Material radio/checkbox rows. Runtime visual review remains outstanding. |
| VI. Accessibility correctness | BLOCKED | Source provides one target per row, labels, hint/error/required/disabled/selection semantics, non-colour indicators, and minimum targets. VoiceOver, TalkBack, large-text screenshots, RTL, and physical-device checks remain open. |
| VII. System-first theming | PASS | Both renderers consume NativePHP semantic tokens and expose no colour, radius, density, or platform-widget styling props. |
| VIII. Small proven expansion | PASS | The official NativePHP radio and checkbox elements were audited first; they cannot represent this scalar-or-list field contract without leaking separate bindings. |
| IX. Evidence quality | BLOCKED | Package PHP, component/docs gates, complete Android, exact-lock showcase, consumer tests, plugin validation, and source parsing pass. iOS target execution, runtime capture, and devices remain pending. |
| X. Alpha stewardship | BLOCKED | The component is not eligible for release while its NativePHP identical-publication dependency is unreleased. |
| XI. Skills enforce the constitution | PASS | The create, iOS, Android, and review skills were followed; the component was scaffolded exactly once after a failing Pest contract. |

## Official primitive audit

NativePHP `radio-group` represents only one string selection. NativePHP `checkbox` represents one Boolean per element. Neither can publish one stable string-or-integer scalar or list, one complete `@change` proposal, shared helper/error/required semantics, or a pending-proposal guard. The paired renderer preserves each platform's native selection expression while keeping one authored API.

## Off-device verification

- Focused Choice Group, option-normalizer, and manifest PHP tests: 38 passed, 116 assertions.
- Complete package PHP suite: 558 passed, 1,585 assertions; five model evaluations skipped by default.
- `bin/check-component ChoiceGroup --development`: passed.
- `bin/build-docs-artifacts` and the component documentation gate: passed.
- Complete Android JVM/Paparazzi suite: build successful. Choice Group screenshot cases were skipped behind their controller-owned evidence flag.
- `swiftc -parse` over the Choice Group production and test sources: passed.
- Focused showcase, capture, and navigation tests: 19 passed, 218 assertions.
- Complete exact-lock showcase suite: 73 passed, 1,107 assertions.
- Package and showcase `composer validate --strict`: passed.
- Installed showcase lock reference: `5e1e491d9db7546ba67c2ac7f10556b09eb956ed`, exactly matching the package implementation commit.
- `php artisan native:plugin:validate vendor/firstlightui/nativephp`: passed with the expected no-bridge-functions warning for a UI-only plugin.
- `git diff --check`: passed in both repositories before their implementation commits.
- Swift production and behavior test sources are authored. SwiftPM cannot execute this iOS-only package as a macOS target because UIKit is unavailable; the controller-owned iOS target run is pending.
- Screenshot tests are explicitly guarded for controller-owned evidence capture.
- No simulator, emulator, physical device, or screenshot command was used during this implementation.

## Release blocker: identical publications

Choice Group deliberately keeps the server authoritative and suppresses additional taps while a proposal is in flight. PHP may reject a proposal by republishing an identical selection. The native renderer must observe that publication to release its guard. The local showcase can validate this against the pinned NativePHP fork, but the component must not be released until the identical-publication fix is merged and available in a supported NativePHP release.

## Pending evidence

- Run the Choice Group XCTest contract and guarded snapshot case on the controller-selected iOS target.
- Attempt controller-owned light/dark captures; record the explicit waiver if capture fails.
- Complete VoiceOver, TalkBack, large text, contrast, RTL, reduced motion, offline, rejection, rapid-tap, and physical-device checks.

Do not describe Choice Group as development-complete or release-ready until the blocked rows are closed and the upstream publication dependency is released.

## Controller runtime evidence — 2026-08-04

The final showcase lock at `a07da05` resolved the complete package at
`b7cb3f9`. Android debug built, installed, and clean-launched on Pixel 9 Pro;
the same lock passed `assembleRelease`. iOS debug reported `BUILD SUCCEEDED`,
clean-installed, and launched the catalogue on iPhone 17 Pro. These host runs
close the earlier generated-host rows.

On Android, the single-radio and multiple-checkbox fixtures rendered with
their selected, disabled, helper, error, and required semantics. Selecting
Urgent changed its radio accessibility state to checked. Direct iOS component
routing and canonical capture were waived; this is not iOS component-runtime
or screenshot evidence. Exact iOS XCTest, VoiceOver, full TalkBack, RTL,
appearance/scaling, rejection/rapid-tap, offline, and physical-device evidence
remain required. Release also remains blocked until NativePHP exposes the
identical-publication epoch in a supported release.

Final shared gates passed: package 748 tests/2,160 assertions with five model
evaluations skipped; Android 131 tests with zero failures/errors and seven
controller-gated skips; showcase 93 tests/1,467 assertions; both strict
Composer validations; docs build/check; all ten development component checks;
plugin validation with the expected UI-only no-bridge-functions warning; and
both repository diff checks.

## Development screenshot evidence update — 2026-08-05

Choice Group's current four-image development matrix is present and visually
approved in the [alpha catalogue screenshot evidence](2026-08-05-alpha-screenshot-evidence.md).
This supersedes only earlier pending or absent screenshot statements. Clean
release capture, physical-device, assistive-technology, and component-specific
blocked rows remain unchanged.
