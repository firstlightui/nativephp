---
title: Slider development review
description: Constitutional review of the component-specific off-device Slider implementation and its deferred integration and runtime evidence.
status: blocked
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - spec/components/slider.md
  - docs/components/slider.md
  - src/Elements/Slider.php
  - src/Support/FiniteNumber.php
  - resources/ios/SliderControl.swift
  - resources/ios/SliderRenderer.swift
  - resources/android/SliderControl.kt
  - resources/android/SliderRenderer.kt
---

# Slider Development Review

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-08-04 |
| Component | `Slider` / `<firstlight:slider>` |
| State class | Continuous gesture, server-authoritative Float grid |
| Implementation path | Paired SwiftUI and Material 3 renderers |
| Package revision reviewed | `0b7dd4b6c3e866314c04e0c18fea6988df52c1fe` |
| Showcase revision reviewed | `99a751078d2645e87f7d9a1439f44d6c1e620591` |
| Permitted device execution | Controller only; this implementation agent used no simulator, emulator, physical device, or runtime screenshot capture |

**Component-specific off-device verdict:** PASS

**Development verdict:** BLOCKED

**Component-release verdict:** BLOCKED

The strict PHP contract, Float/grid helper, shared registration, paired renderer
source, focused Android state execution, isolated iOS typechecking, exact-lock
showcase, capture fixture, navigation, and documentation are complete. Exact
iOS XCTest execution, the currently Select-blocked full Android suite, roadmap,
runtime capture, accessibility, host-build, and publication-epoch evidence
remain controller-owned.

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative first | PASS | Ordinary EDGE element and paired `NativeUINode` renderers use the official current tree and standard Float slider bridge event. No WebView, JSON bridge, or alternate transport exists. |
| II. Familiar and coherent APIs | PASS | Required `value`/`min`/`max`, optional `step`, familiar field metadata, three explicit sync policies, accessibility props, external layout, and one `@change` event form a narrow API. |
| III. Stable values and predictable state | BLOCKED | PHP validation and native publication reset are deterministic. Rejected identical-value reconciliation still needs a verified NativePHP publication epoch. |
| IV. Equal platform quality | BLOCKED | SwiftUI and Material sources implement the same grid and draft contract. Focused Android JVM tests pass and isolated iOS source typechecks; exact iOS XCTest and runtime rows remain pending. |
| V. Native expression over pixel parity | BLOCKED | Both controls are genuine platform sliders with native gesture lifecycle. Controller-owned runtime native-feel review remains pending. |
| VI. Accessibility correctness | BLOCKED | Names, hints, values, errors, disabled state, and native adjustable semantics are implemented. VoiceOver, TalkBack, scaling, contrast, and RTL evidence remains pending. |
| VII. System-first theming | PASS | The controls retain native system rendering and NativePHP semantic tokens. Public arbitrary colours, variants, sizes, marks, and styles are rejected. |
| VIII. Small, proven expansion | PASS | The installed Slider was audited first. Its defaults, coercion, size, and optimistic state do not satisfy the narrower strict contract, justifying paired renderers. |
| IX. Evidence-based quality | BLOCKED | Red-first Pest, 92 integrated focused tests, 744 passing full package tests, focused Android execution, isolated iOS typechecking, exact-lock showcase tests, docs/constitution gates, and diff checks pass. Full Android has one unrelated Select failure; runtime and host evidence remain pending. |
| X. Public alpha stewardship | PASS | Additive documentation makes no release or alpha-readiness claim. |
| XI. Skills enforce the constitution | PASS | Create, iOS, Android, and review skills were followed; the authored PHP contract failed first and the scaffold ran exactly once through the required temporary-test workaround. |
| XII. Amendment | PASS | Slider requires no constitutional amendment. |

## TDD and scaffold evidence

The initial focused Pest command produced 90 expected failures because Slider,
FiniteNumber, registration, and renderer identifiers did not exist. The
scaffolder correctly refused to overwrite the authored feature test. Only that
new test was temporarily removed, `bin/scaffold-component Slider` ran exactly
once, and the complete authored test was immediately restored over the
placeholder before implementation.

Before shared integration, 88 component/helper assertions passed and the only
two focused failures were the intentionally deferred precompiler and manifest
rows. After registration, the complete focused set passes 92 tests and 241
assertions.

## Current off-device evidence

```text
vendor/bin/pest tests/Unit/Support/FiniteNumberTest.php \
  tests/Feature/SliderElementTest.php \
  tests/Feature/PluginManifestTest.php --compact
PASS — 92 tests, 241 assertions

composer test -- --compact
PASS — 744 tests, 2,126 assertions; 5 model evaluations skipped

bin/check-docs --component=Slider --development
bin/check-component Slider --development
PASS

xcrun swiftc -parse resources/ios/SliderRenderer.swift \
  resources/ios/SliderControl.swift tests/ios/SliderSnapshotTests.swift
PASS — source and test syntax

xcrun swiftc -typecheck -target arm64-apple-ios18.0-simulator \
  -D SWIFT_PACKAGE resources/ios/NativePHPTestShims.swift \
  resources/ios/SliderRenderer.swift resources/ios/SliderControl.swift
PASS — isolated component production source typechecks; no simulator was launched

JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android testDebugUnitTest \
  --tests dev.firstlightui.plugins.firstlight_ui.ui.SliderTest
PASS — BUILD SUCCESSFUL

php artisan test tests/Feature/SliderShowcaseTest.php \
  tests/Feature/SliderCaptureTest.php \
  tests/Feature/ShowcaseNavigationTest.php --compact
PASS — 22 tests, 303 assertions

composer test -- --compact
PASS in firstlight-showcase — 88 tests, 1,373 assertions

composer validate --strict
PASS in package and showcase

php artisan native:plugin:validate /Users/wojt/Code/clinically-au/firstlight-ui
PASS with the expected UI-only warning — no bridge_functions are declared

git show --check 0b7dd4b
git show --check 99a7510
PASS
```

The showcase lock resolves `firstlightui/nativephp` exactly to
`0b7dd4b6c3e866314c04e0c18fea6988df52c1fe`. It dogfoods integral,
fractional-negative, live, blur, debounce, error, disabled, rejection, custom
accessible value, and programmatic-publication states. The isolated
`/captures/slider` route and capture test are present even though runtime
screenshots remain outside this agent's permitted lane.

The complete Android JVM task compiled production and test sources and ran 131
tests, with 130 passing and seven skipped. Its sole failure is Select's
`configuration decodes complete field and option contract` assertion at
`SelectTest.kt:42`; focused Slider tests pass. The SwiftPM retry reached package
compilation but the existing macOS invocation cannot import UIKit. A
compiler-only iOS typecheck passed; no simulator test execution was attempted
because device access is reserved for the controller.

## Pending controller evidence

- Run the exact iOS test target on the controller-chosen simulator.
- Resolve or revalidate the unrelated Select assertion, then rerun the complete Android JVM suite.
- Advance the roadmap independently without changing this component contract.
- Verify that NativePHP emits an observable publication epoch for identical accepted trees; keep component release blocked on Mobile 4.0.1 until proven or fixed upstream.
- Attempt light, dark, error, disabled, accessibility-size, and RTL captures on the permitted targets. If capture fails, record the explicit screenshot bypass.
- Complete host builds, VoiceOver, TalkBack, contrast, motion, offline, rapid gesture, rejection, and programmatic publication evidence.

## Honest milestone

Slider is implemented, registered, documented, and consumer-proven off-device
at exact package and showcase revisions. It is not development-complete,
component-release-ready, or alpha-ready until the pending iOS, full Android,
runtime, accessibility, host, roadmap, and publication-epoch rows close.

## Controller runtime evidence — 2026-08-04

The final showcase lock at `a07da05` resolved the complete package at
`b7cb3f9`. Android debug built, installed, and clean-launched on Pixel 9 Pro;
the same lock passed `assembleRelease`. iOS debug reported `BUILD SUCCEEDED`,
clean-installed, and launched the catalogue on iPhone 17 Pro. The final clean
Android and package results recorded below supersede the earlier Select failure,
and these host runs close the generated-host rows.

On Android, live, blur, debounce, error, disabled, rejected, and programmatic
Slider fixtures rendered as native seek bars with their authored descriptions.
A real gesture on Live dose moved the exposed thumb bounds from
`[568,541][712,685]` to `[793,541][937,685]`. Direct iOS component routing and
canonical capture were waived; this is not iOS component-runtime or screenshot
evidence. Exact iOS XCTest, VoiceOver, full TalkBack, RTL,
appearance/scaling, rejection/rapid gestures, offline, and physical-device
evidence remain required. Release also remains blocked until NativePHP exposes
the identical-publication epoch in a supported release.

Final shared gates passed: package 748 tests/2,160 assertions with five model
evaluations skipped; Android 131 tests with zero failures/errors and seven
controller-gated skips; showcase 93 tests/1,467 assertions; both strict
Composer validations; docs build/check; all ten development component checks;
plugin validation with the expected UI-only no-bridge-functions warning; and
both repository diff checks.

## Development screenshot evidence update — 2026-08-05

Slider's current four-image development matrix is present and visually
approved in the [alpha catalogue screenshot evidence](2026-08-05-alpha-screenshot-evidence.md).
This supersedes only earlier pending or absent screenshot statements. Clean
release capture, physical-device, assistive-technology, and component-specific
blocked rows remain unchanged.
