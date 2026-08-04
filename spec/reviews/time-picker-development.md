---
title: Time Picker development review
description: Constitutional review of the off-device Firstlight Time Picker implementation and its remaining controller-owned evidence.
status: blocked
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - spec/components/time-picker.md
  - docs/components/time-picker.md
  - src/Elements/TimePicker.php
  - resources/ios/TimePickerControl.swift
  - resources/ios/TimePickerRenderer.swift
  - resources/android/TimePickerControl.kt
  - resources/android/TimePickerRenderer.kt
---

# Time Picker Development Review

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-08-04 |
| Component | `TimePicker` / `<firstlight:time-picker>` |
| State class | Discrete, server-authoritative nullable wall-clock time |
| Implementation path | Paired SwiftUI and Material 3 renderers |
| Package revision reviewed | `d53b588c8e048b48d22d3e813753dcd10cb20363` |
| Showcase revision reviewed | `adf49b76db1290f35b7e71bfd165e5d803046cf1` |
| Permitted device execution | Controller only; this implementation agent used no simulator, emulator, physical device, or runtime screenshot capture |

**Off-device implementation verdict:** PASS

**Development verdict:** BLOCKED

**Component-release verdict:** BLOCKED

The narrow contract, strict PHP/EDGE element, exact manifest/precompiler and
documentation integration, paired production renderer sources, focused PHP,
Android production compilation, state/Paparazzi coverage, structural gates,
and exact-revision showcase dogfooding pass. Exact iOS XCTest execution,
runtime review, host builds, screenshots, and physical-device accessibility
evidence remain controller-owned. Concurrent Slider scaffolding prevents an
honest green claim for the package-wide PHP and Android commands. The user's
screenshot exception permits failed capture to be bypassed; it does not turn
absent evidence into a pass.

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative first | PASS | Time Picker is an ordinary EDGE element. Both renderers consume `NativeUINode`, reconcile official current-tree publications, and emit the standard select-change event. There is no WebView, JSON bridge, generated-tree edit, or alternate transport. |
| II. Familiar and coherent APIs | PASS | The API uses nullable `value`/`native:model`, field metadata, BCP-47 locale, IANA timezone, accessibility metadata, layout-only `class`, and `@change`. Hour-format, bounds, range, step, modes, styles, clear/read-only affordances, icons, custom presentation labels, press/submit events, and visual escape props are excluded. |
| III. Stable values and predictable state | PASS | PHP publishes explicit `has_value`/`value`. Native renderers keep presentation-only draft state, emit only a changed explicit confirmation, never optimistically alter the trigger, have no pending latch, and dismiss an open presentation on relevant server publication. |
| IV. Equal platform quality | BLOCKED | SwiftUI and Compose production sources implement the same contract and both compiled during platform checks. Exact iOS XCTest still requires the controller-owned simulator lane. |
| V. Native expression over pixel parity | BLOCKED | iOS uses a genuine `.hourAndMinute` SwiftUI `DatePicker` in adaptive native presentation. Android uses Material 3 `TimePicker` in a Material dialog surface. Runtime native-feel review remains pending. |
| VI. Accessibility correctness | BLOCKED | Strict naming, hint, required metadata, helper/error feedback, disabled semantics, accepted value, 44-point/48-dp targets, dark/error/disabled views, and font scale `2.0` are implemented. VoiceOver, TalkBack, contrast, RTL, Reduced Motion, and physical-device rows remain pending. |
| VII. System-first theming | PASS | Both controls retain platform-native geometry and use NativePHP semantic tokens for field context. `class` controls external layout; no visual escape API is published. |
| VIII. Small, proven expansion | PASS | Mobile UI's combined date/time/datetime primitive was audited first. Its broader normalization, mode/style/hour-format API, and optimistic local accepted display conflict with the narrower explicit-confirmation contract, justifying paired renderers. |
| IX. Evidence-based quality | BLOCKED | Focused PHP, docs/structural gates, Android state/Paparazzi, production source compilation, exact-lock showcase tests, full consumer tests, and plugin validation pass. A generic iOS build compiled both Time Picker sources before failing elsewhere. Package-wide commands are temporarily red only on concurrent Slider work; runtime, host, screenshot-or-waiver, and device evidence remain pending. |
| X. Public alpha stewardship | PASS | The additive work makes no release, tag, publication, or alpha-readiness claim. |
| XI. Skills enforce the constitution | PASS | Create, iOS, Android, and review skills were applied. The missing PHP class failed first, the non-overwriting scaffold ran exactly once, placeholders were replaced, and device access remained with the controller. |
| XII. Amendment | PASS | Time Picker requires no constitutional amendment. |

## Contract and native expression

The accepted value is exact 24-hour wire notation `HH:mm` from `00:00`
through `23:59`, or `null`. PHP rejects trimming, coercion, seconds, unpadded
values, timestamps, offsets, non-strings, malformed locales, non-IANA
timezones, deferred sync modes, and excluded props/events.

Locale affects trigger and native-picker display only. An explicit Android
locale chooses its locale hour cycle; omission respects the system clock
preference. Timezone determines only the current wall-clock minute used to seed
a null draft. It never shifts an accepted value.

Cancel discards the draft. Confirm sends one canonical proposal only when it
differs from the accepted tree, then dismisses without updating accepted
state. Immediate reopening starts from the still accepted publication.

## TDD and scaffold evidence

`TimePickerPublicContractTest` first failed because the element class did not
exist. The temporary proof was removed, `bin/scaffold-component TimePicker`
ran exactly once, and every generated placeholder was replaced with authored
contract, renderer, and test files. No existing component file was overwritten.

## Passing off-device evidence

```text
vendor/bin/pest tests/Feature/TimePickerElementTest.php \
  tests/Feature/PluginManifestTest.php
PASS — 74 tests, 191 assertions

bin/build-docs-artifacts
bin/check-docs --component=TimePicker --development
bin/check-component TimePicker --development
PASS

JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android testDebugUnitTest --tests '*TimePicker*'
PASS — BUILD SUCCESSFUL, including state and light/dark/error/disabled/font-scale Paparazzi cases

xcrun swiftc -parse resources/ios/TimePickerControl.swift \
  resources/ios/TimePickerRenderer.swift tests/ios/TimePickerSnapshotTests.swift
PASS — syntax parsing only

xcodebuild -scheme FirstlightIOSControls -destination generic/platform=iOS \
  -derivedDataPath /private/tmp/firstlight-time-picker-derived-data \
  CODE_SIGNING_ALLOWED=NO build
PARTIAL — `TimePickerControl.swift` and `TimePickerRenderer.swift` compiled; the
package build then failed in pre-existing `IconButtonControl.swift` because
the package test shim does not define NativePHP's `getIconForName` helper.
```

The generic iOS command accessed no simulator or physical device. It proves
production Time Picker typechecking, not XCTest behavior or runtime evidence.

The showcase lock resolves `firstlightui/nativephp` exactly to
`d53b588c8e048b48d22d3e813753dcd10cb20363`. Its interactive page dogfoods
omitted/null, accepted, required/error, disabled, localized/timezone,
server-rejected, and programmatic-publication states. Its isolated
`/captures/time-picker` fixture contains four stable documentation states.

```text
php artisan test tests/Feature/TimePickerShowcaseTest.php \
  tests/Feature/TimePickerCaptureTest.php \
  tests/Feature/ShowcaseNavigationTest.php
PASS — 20 tests, 262 assertions

composer test
PASS in firstlight-showcase — 78 tests, 1,193 assertions

composer validate --strict
PASS in package and showcase

php artisan native:plugin:validate /Users/wojt/Code/clinically-au/firstlight-ui
PASS with the expected UI-only warning — no bridge_functions are declared

git show --check d53b588
git show --check adf49b7
PASS
```

## Concurrent full-suite status

The package `composer test` command completed **560 passing tests and 1,666
assertions**, with five model evaluations skipped and 90 failures. Every
failure belongs to the concurrent, uncommitted Slider implementation: its
temporary non-Element class, missing precompiler/manifest integration, and
missing `FiniteNumber` helper at the instant of the run. Time Picker and the
shared manifest catalogue pass.

The full Android task compiled all production sources, including the final
canonical `TimePickerRenderer` rename, then failed compiling Slider's generated
placeholder test. Time Picker's focused state and Paparazzi suite had already
passed before Slider entered the shared test source set. These red concurrent
commands are recorded as blocked, not relabelled as passing.

## Pending controller evidence

- Rerun full package PHP and Android JVM gates after Slider integration lands.
- Run exact iOS XCTest on the controller-permitted simulator lane.
- Attempt the light/dark capture matrix on controller-permitted targets. If
  capture fails, record the user's bypass without claiming the images passed.
- Complete VoiceOver, TalkBack, large text, contrast, RTL, Reduced Motion,
  locale/hour-cycle, timezone seed, cancel/confirm, rapid reopen, server
  rejection, programmatic publication, offline, host-build, and dated physical
  device rows before release.

## Honest milestone

Time Picker is implemented, documented, structurally green, and
consumer-proven off-device at exact package and showcase revisions. It is not
development-complete, component-release-ready, or alpha-ready until the
blocked concurrent full-suite, iOS, runtime, host, screenshot-or-waiver, and
device rows close.

## Controller runtime evidence — 2026-08-04

The final showcase lock at `a07da05` resolved the complete package at
`b7cb3f9`. Android debug built, installed, and clean-launched on Pixel 9 Pro;
the same lock passed `assembleRelease`. iOS debug reported `BUILD SUCCEEDED`,
clean-installed, and launched the catalogue on iPhone 17 Pro. The final clean
full-suite results recorded below supersede the earlier concurrent Slider
failures, and these host runs close the generated-host rows.

The first Android runtime pass exposed a real trigger defect: the text field
consumed taps before the dialog action. A red regression now requires the
full-size semantic button overlay used by Date Picker, and the corrected host
opened the genuine Material time dialog with Cancel and Confirm actions. Direct
iOS component routing and canonical capture were waived; this is not iOS
component-runtime or screenshot evidence. Exact iOS XCTest, VoiceOver, full
TalkBack, locale/hour-cycle, timezone, cancel/confirm, RTL,
appearance/scaling, offline, and physical-device evidence remain release
requirements.

Final shared gates passed: package 748 tests/2,160 assertions with five model
evaluations skipped; Android 131 tests with zero failures/errors and seven
controller-gated skips; showcase 93 tests/1,467 assertions; both strict
Composer validations; docs build/check; all ten development component checks;
plugin validation with the expected UI-only no-bridge-functions warning; and
both repository diff checks.
