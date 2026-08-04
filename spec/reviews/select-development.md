---
title: Select development review
description: Constitutional review of the component-specific off-device Select implementation and its deferred integration and runtime evidence.
status: blocked
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - spec/components/select.md
  - docs/components/select.md
  - src/Elements/Select.php
  - src/Support/OptionNormalizer.php
  - resources/ios/SelectControl.swift
  - resources/ios/SelectRenderer.swift
  - resources/android/SelectControl.kt
  - resources/android/SelectRenderer.kt
---

# Select Development Review

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-08-04 |
| Component | `Select` / `<firstlight:select>` |
| State class | Discrete single selection, server-authoritative stable scalar |
| Implementation path | Paired SwiftUI and Material 3 renderers |
| Package revision reviewed | `363d6e4244d158f99e55d3f8adc47227bb49483f` |
| Exact-lock showcase revision reviewed | `e36588b7b6c8f0d8ee111945c2db00f3aa601f90` |
| Permitted device execution | Controller only; this implementation agent used no simulator, emulator, physical device, or runtime screenshot capture |

**Component-specific off-device verdict:** PASS

**Development verdict:** BLOCKED

**Component-release verdict:** BLOCKED

The strict PHP contract, shared option normalization, collision-safe paired
renderer registration, pure native state contracts, production Android
compilation, Swift parsing, generated documentation, capture manifest, and
exact-lock showcase are complete. Exact native test execution, runtime
capture, accessibility, host-build, and publication-epoch evidence remain
controller-owned.

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative first | PASS | One EDGE element and paired `NativeUINode` renderers use the official current tree and standard press bridge event. No WebView, JSON bridge, or alternate transport exists. |
| II. Familiar and coherent APIs | PASS | Shared `options`, stable scalar `value`, one `searchable` override, familiar field metadata, immediate sync, accessibility, external layout, and one `@change` event form a narrow API. |
| III. Stable values and predictable state | BLOCKED | String and integer identity, strict option matching, non-optimistic rendering, and stale-proposal suppression are deterministic. Rejected identical-value reconciliation still needs a verified NativePHP publication epoch. |
| IV. Equal platform quality | BLOCKED | SwiftUI and Material sources implement the same compact/search threshold and selection contract. Android production source compiles, Swift source parses, and the Android class is collision-safe beside NativePHP's installed `SelectRenderer`; exact test/runtime rows remain pending. |
| V. Native expression over pixel parity | BLOCKED | iOS uses Menu or a searchable sheet; Android uses an exposed dropdown or searchable dialog. Controller-owned runtime native-feel review remains pending. |
| VI. Accessibility correctness | BLOCKED | Accessible names, hints, accepted state, errors, required and disabled metadata, selected rows, native search, and minimum targets are implemented. VoiceOver, TalkBack, scaling, contrast, and RTL evidence remains pending. |
| VII. System-first theming | PASS | Controls retain native structures and NativePHP semantic tokens. Public arbitrary colours, variants, modes, and styles are rejected. |
| VIII. Small, proven expansion | PASS | The installed Select was audited first. Its string-only, optimistic, non-searchable contract cannot preserve Firstlight's richer stable-value and publication rules, justifying paired renderers. |
| IX. Evidence-based quality | BLOCKED | Red-first Pest, 114 passing focused package assertions, passing component/docs/collision gates, 1,274 passing full-showcase assertions, Android production compilation, pure renderer state tests, and Swift parsing exist. Exact native execution, a concurrency-clean full package run, runtime, and host evidence are pending. |
| X. Public alpha stewardship | PASS | Additive documentation makes no release or alpha-readiness claim. |
| XI. Skills enforce the constitution | PASS | Create, iOS, Android, and review skills were followed; the authored PHP contract failed first and the scaffold ran exactly once through the required temporary-test workaround. |
| XII. Amendment | PASS | Select requires no constitutional amendment. |

## TDD and scaffold evidence

The first focused Pest run produced the expected failures because Select did
not exist. The scaffolder correctly refused to overwrite the authored feature
test. Only that new test was temporarily removed, `bin/scaffold-component
Select` ran exactly once, and the complete authored test was immediately
restored over the placeholder before implementation.

After shared integration, 37 focused tests with 114 assertions pass across
Select, the shared option normalizer, manifest registration, and Android
renderer collision protection.

## Current off-device evidence

```text
vendor/bin/pest tests/Feature/SelectElementTest.php \
  tests/Unit/Support/OptionNormalizerTest.php \
  tests/Feature/PluginManifestTest.php \
  tests/Feature/AndroidRendererCollisionTest.php
37 passed; 114 assertions

bin/check-component Select --development
PASS

composer docs:build && composer docs:check
PASS

swiftc -parse resources/ios/SelectControl.swift \
  resources/ios/SelectRenderer.swift tests/ios/SelectSnapshotTests.swift
PASS - syntax parsing only

JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android testDebugUnitTest --tests '*Select*'
Production Select Kotlin compiled; concurrent Stepper placeholder prevented unit-test compilation before Gradle filtering

php artisan test tests/Feature/SelectShowcaseTest.php \
  tests/Feature/SelectCaptureTest.php \
  tests/Feature/ShowcaseNavigationTest.php
21 passed; 271 assertions

php artisan test
83 passed; 1,274 assertions (exact-lock showcase)
```

The first full package attempt reached 740 passes and five intentional eval
skips. It found Select's initial Android renderer-name collision, which was
then corrected to `FirstlightSelectRenderer` and proven by the focused
collision and constitution gates above. Its remaining two failures came from
concurrent Slider tests before Slider's shared registration lane; they are not
Select failures.

No simulator, emulator, physical device, or screenshot command was run.
Runtime screenshot capture was explicitly bypassed under the task's permitted
capture-failure fallback.

## Pending controller evidence

- Rerun `SelectTest` after Stepper replaces its scaffold placeholder, and run the exact iOS test target on the controller-chosen simulator.
- Rerun the package-wide Pest suite after concurrent Slider and Stepper files are integrated or removed.
- Verify that NativePHP emits an observable publication epoch for identical accepted trees; keep component release blocked on Mobile 4.0.1 until proven or fixed upstream.
- If runtime capture becomes available, capture compact, searchable, light, dark, error, disabled, accessibility-size, and RTL evidence at the declared stable paths.
- Complete host builds, VoiceOver, TalkBack, contrast, offline, rapid-selection, rejection, and programmatic publication evidence.

## Honest milestone

Select is implemented, registered, documented, and exercised by an exact-lock
showcase. It is not development-complete, component-release-ready, or
alpha-ready until the pending exact native, concurrency-clean package,
runtime, accessibility, host, and publication-epoch rows close.

## Final audit variation

No public API or behaviour variation was required after the installed API
audit. The only integration variation is internal: Android exports
`FirstlightSelectRenderer` because the installed NativePHP UI plugin already
exports `SelectRenderer`. The manifest, constitution checker, and collision
test all enforce that collision-safe identifier.

## Controller runtime evidence — 2026-08-04

The final showcase lock at `a07da05` resolved the complete package at
`b7cb3f9`. Android debug built, installed, and clean-launched on Pixel 9 Pro;
the same lock passed `assembleRelease`. iOS debug reported `BUILD SUCCEEDED`,
clean-installed, and launched the catalogue on iPhone 17 Pro. The final clean
full-suite results recorded below supersede the earlier concurrent failures,
and these host runs close the generated-host rows.

On Android, compact and searchable Select fixtures rendered with helper,
error, required, disabled, and stable-value states. The compact menu opened
with Routine, Urgent, and Critical; choosing Urgent closed the menu and updated
the focused field. Direct iOS component routing and canonical capture were
waived; this is not iOS component-runtime or screenshot evidence. Exact iOS
XCTest, VoiceOver, full TalkBack, searchable-dialog input, RTL,
appearance/scaling, rejection/rapid selection, offline, and physical-device
evidence remain required. Release also remains blocked until NativePHP exposes
the identical-publication epoch in a supported release.

Final shared gates passed: package 748 tests/2,160 assertions with five model
evaluations skipped; Android 131 tests with zero failures/errors and seven
controller-gated skips; showcase 93 tests/1,467 assertions; both strict
Composer validations; docs build/check; all ten development component checks;
plugin validation with the expected UI-only no-bridge-functions warning; and
both repository diff checks.

## Development screenshot evidence update — 2026-08-05

Select's current four-image development matrix is present and visually
approved in the [alpha catalogue screenshot evidence](2026-08-05-alpha-screenshot-evidence.md).
This supersedes only earlier pending or absent screenshot statements. Clean
release capture, physical-device, assistive-technology, and component-specific
blocked rows remain unchanged.
