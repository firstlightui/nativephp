---
title: Date Picker development review
description: Constitutional review of the off-device Firstlight Date Picker implementation and its remaining controller-owned evidence.
status: blocked
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - spec/components/date-picker.md
  - docs/components/date-picker.md
  - nativephp.json
  - src/Elements/DatePicker.php
  - resources/ios/DatePickerControl.swift
  - resources/ios/DatePickerRenderer.swift
  - resources/android/DatePickerControl.kt
  - resources/android/DatePickerRenderer.kt
---

# Date Picker Development Review

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-08-04 |
| Component | `DatePicker` / `<firstlight:date-picker>` |
| State class | Discrete, server-authoritative nullable date |
| Implementation path | Paired SwiftUI and Material 3 renderers |
| Package revision reviewed | `e1d2caff03863cf680372b57e685090ee9c18821` |
| Showcase revision reviewed | `d51ba56ef968c19c1dc0ab58971961d760029c3c` |
| Permitted device execution | Controller only; this implementation agent used no simulator, emulator, physical device, or runtime screenshot capture |

**Off-device implementation verdict:** PASS

**Development verdict:** BLOCKED

**Component-release verdict:** BLOCKED

The public contract, strict PHP/EDGE element, paired production renderer
source, native state contracts, production Android compilation, exact-revision
showcase, documentation, structural gate, plugin validation, and source parsing
pass. Exact iOS XCTest/typechecking, runtime platform review, host builds,
screenshots, and physical-device accessibility evidence remain controller-owned.
The user's screenshot exception permits capture failures to be bypassed; it
does not turn absent visual or device evidence into a pass.

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative first | PASS | Date Picker is an ordinary EDGE element registered in `nativephp.json`. Both renderers consume `NativeUINode`, observe official current-tree publications, and send the standard select-change event. No WebView, JSON bridge, generated-tree edit, or alternate transport exists. |
| II. Familiar and coherent APIs | PASS | The narrow API uses nullable `value`/`native:model`, inclusive `min`/`max`, field metadata, BCP-47 locale, IANA timezone, accessibility metadata, external layout `class`, and `@change`. It rejects modes, times, styles, custom labels/icons, range, clear, submit, press, and visual escape props. |
| III. Stable values and predictable state | PASS | PHP publishes an explicit `has_value`/`value` pair. Native renderers keep an isolated presentation draft, emit only on explicit changed confirmation, never optimistically alter the accepted trigger, have no pending latch, and dismiss a live presentation when accepted value, bounds, locale, timezone, or disabled state changes. |
| IV. Equal platform quality | BLOCKED | SwiftUI and Compose sources implement the same contract, Android production and state tests compile, and Swift source parses. The exact iOS XCTest target still requires controller-owned simulator access. |
| V. Native expression over pixel parity | BLOCKED | iOS source uses a genuine graphical SwiftUI `DatePicker` in an adaptive popover/sheet; Android uses Material 3 `DatePickerDialog`, `DatePicker`, and `SelectableDates`. Runtime native-feel review remains pending. |
| VI. Accessibility correctness | BLOCKED | Strict visible/explicit naming, hints, required metadata, helper/error feedback, disabled semantics, accepted value, and 44-point/48-dp targets are implemented. VoiceOver, TalkBack, font scaling, contrast, RTL, and physical-device rows remain pending. |
| VII. System-first theming | PASS | Both platforms use native picker presentation and NativePHP semantic tokens for the field context. Public `class` is external EDGE layout only; no arbitrary style, radius, colour, animation, or platform escape prop is exposed. |
| VIII. Small, proven expansion | PASS | The installed Mobile UI primitive was audited before scaffolding. Its broader date/time/datetime and style API and optimistic accepted display conflict with this narrower explicit-confirmation contract, justifying paired renderers. |
| IX. Evidence-based quality | BLOCKED | Focused PHP, structural/docs, Android state, Android production compilation, Swift parsing, exact-lock showcase, plugin validation, Composer validation, and diff checks pass. iOS execution, runtime captures, host builds, and dated device evidence remain pending. |
| X. Public alpha stewardship | PASS | The change is additive and documented without release, publication, tag, or alpha-readiness claims. |
| XI. Skills enforce the constitution | PASS | The create, iOS, Android, and review skills were followed. The authored Pest contract failed first, the non-overwriting scaffold ran exactly once through the approved temporary-test workaround, and device access remained with the controller. |
| XII. Amendment | PASS | Date Picker requires no constitutional amendment. |

## Contract and native expression

The accepted value is either a canonical proleptic-Gregorian `YYYY-MM-DD`
string for years `0001` through `9999`, or `null`. Omission and explicit null
publish the same `has_value = false`, empty-string wire pair. PHP rejects
coercion, whitespace, timestamps, `DateTimeInterface`, impossible dates,
reversed bounds, values outside inclusive bounds, malformed locale tags,
unknown timezones, deferred sync modes, and excluded props or events.

The timezone defines today, null draft seeding, and Swift date mapping only; it
never shifts the accepted wire date. Android deliberately maps Material's
`selectedDateMillis` through UTC midnight because it represents the selected
calendar cell rather than a local instant. Locale is applied explicitly to
trigger formatting and the native calendar display without modifying the wire
string.

An open presentation owns only a draft. Cancel discards it. Confirm sends one
canonical proposal when it differs from PHP's accepted value, then dismisses
without updating accepted state. Reopening immediately rebuilds from the still
accepted tree, so no pending latch or acknowledgement heuristic exists.

## TDD and scaffold evidence

The initial `DatePickerElementTest` was authored before implementation and
failed 74 cases because the class, precompiler entry, and manifest row did not
exist. The scaffolder correctly refused to overwrite that authored test. Under
the controller-approved Badge precedent, only the new test was temporarily
removed, `bin/scaffold-component DatePicker` ran exactly once, and the complete
authored test was immediately restored over its placeholder before
implementation. No existing component file was overwritten.

## Passing off-device evidence

```text
vendor/bin/pest tests/Feature/DatePickerElementTest.php \
  tests/Feature/PluginManifestTest.php --compact
PASS — 76 tests, 216 assertions

bin/build-docs-artifacts
bin/check-docs --component=DatePicker --development
bin/check-component DatePicker --development
PASS

composer validate --strict
PASS in package and showcase

xcrun swiftc -parse resources/ios/DatePickerControl.swift \
  resources/ios/DatePickerRenderer.swift \
  tests/ios/DatePickerSnapshotTests.swift
PASS — syntax parsing only; not XCTest or platform typechecking

JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android testDebugUnitTest \
  --tests dev.firstlightui.plugins.firstlight_ui.ui.DatePickerTest
PASS — BUILD SUCCESSFUL before concurrent placeholder tests appeared

JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android compileDebugKotlin
PASS — production renderer compilation after the final trigger/accessibility changes

php artisan test tests/Feature/DatePickerShowcaseTest.php \
  tests/Feature/DatePickerCaptureTest.php \
  tests/Feature/ShowcaseNavigationTest.php
PASS — 18 tests, 242 assertions

composer test
PASS in firstlight-showcase — 68 tests, 1,051 assertions

php artisan native:plugin:validate /Users/wojt/Code/clinically-au/firstlight-ui
PASS with the expected UI-only warning — no bridge_functions are declared

git show --check e1d2caf
git show --check d51ba56
PASS
```

The showcase lock resolves `firstlightui/nativephp` exactly to
`e1d2caff03863cf680372b57e685090ee9c18821`. Its interactive page dogfoods
omitted/null, accepted, bounded, required/error, disabled, locale/timezone,
server-rejected, and programmatic publication states. The isolated
`/captures/date-picker` route and capture test are present even though runtime
screenshots remain outside this agent's permitted lane.

## Concurrent full-suite status

The controller's latest full package rerun completed **550 passing tests and
1,569 assertions**, with five model evaluations skipped and one failure. That
single failure is Choice Group's intentionally unregistered precompiler
assertion while its parallel implementation is still component-specific. Date
Picker and Time Picker tests pass in that run.

The complete Android JVM task was also attempted but test compilation was
blocked by concurrent Choice Group and Time Picker scaffold placeholders. Date
Picker's focused Android state suite had already passed, and its final
production sources compile independently. The controller will rerun both full
package and Android gates after those parallel components integrate; this
review does not relabel the concurrent red commands as passing.

## Pending controller evidence

- Run `DatePickerSnapshotTests` on the exact permitted iOS simulator target to
  typecheck production Swift and execute the state contract.
- Rerun the complete Android JVM suite after Choice Group and Time Picker
  replace their scaffold test placeholders.
- Attempt the `/captures/date-picker` light/dark matrix on the permitted iOS
  and Android targets. If capture fails, record the user's explicit screenshot
  bypass and continue without representing absent images as passing evidence.
- Build both generated showcase hosts at the exact package/showcase revisions.
- Perform VoiceOver, TalkBack, Dynamic Type/font scale, increased contrast,
  RTL, Reduced Motion, locale/calendar, min/max, cancel/confirm, rapid reopen,
  server rejection, programmatic publication, and offline checks.
- Complete dated physical-device evidence before component release.

## Honest milestone

Date Picker is implemented, documented, and consumer-proven off-device at
exact package and showcase revisions. It is not development-complete,
component-release-ready, or alpha-ready until the blocked iOS, runtime, host,
full-suite, screenshot-or-waiver, and physical-device rows close.

## Controller runtime evidence — 2026-08-04

The final showcase lock at `a07da05` resolved the complete package at
`b7cb3f9`. Android debug built, installed, and clean-launched on Pixel 9 Pro;
the same lock passed `assembleRelease`. iOS debug reported `BUILD SUCCEEDED`,
clean-installed, and launched the catalogue on iPhone 17 Pro. The final clean
full-suite results recorded below supersede the earlier concurrent failures,
and these host runs close the generated-host rows.

On Android, Date Picker rendered its nullable, bounded, helper/error, disabled,
and reconciliation fixtures with authored accessibility descriptions. Tapping
the active field opened the genuine Material date dialog with Cancel and
Confirm actions. Direct iOS component routing and canonical capture were
waived; this is not iOS component-runtime or screenshot evidence. Exact iOS
XCTest, VoiceOver, full TalkBack, calendar/locale matrices, bounds,
cancel/confirm reconciliation, RTL, appearance/scaling, offline, and
physical-device evidence remain release requirements.

Final shared gates passed: package 748 tests/2,160 assertions with five model
evaluations skipped; Android 131 tests with zero failures/errors and seven
controller-gated skips; showcase 93 tests/1,467 assertions; both strict
Composer validations; docs build/check; all ten development component checks;
plugin validation with the expected UI-only no-bridge-functions warning; and
both repository diff checks.
