---
title: Stepper development review
description: Constitutional review of the exact-number server-authoritative Stepper implementation and its remaining runtime evidence.
status: blocked
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - spec/components/stepper.md
  - docs/components/stepper.md
  - spec/screenshots.json
  - src/Elements/Stepper.php
  - resources/ios/StepperControl.swift
  - resources/ios/StepperRenderer.swift
  - resources/android/StepperControl.kt
  - resources/android/StepperRenderer.kt
---

# Stepper Development Review

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-08-04 |
| Component | `Stepper` / `<firstlight:stepper>` |
| State class | Discrete numeric proposal; server-authoritative accepted value |
| Implementation path | Paired SwiftUI Stepper and Material 3 minus/value/plus composition |
| Package revision reviewed | `b7cb3f917cec9b9f3b39b713b0503776fa0e9df7` |
| Showcase revision reviewed | `a07da05cba6dd1c2cd553d6420e604696093f509` |
| Permitted device execution | Controller only; this implementation agent used no simulator, emulator, physical device, or runtime screenshot capture |

**Component-specific off-device verdict:** PASS

**Development verdict:** BLOCKED

**Component-release verdict:** BLOCKED

The exact PHP integer/float contract, shared registration, paired production
renderers, focused and full Android execution, isolated iOS typechecking,
exact-lock showcase, capture fixture, navigation, documentation, and generated
documentation artefacts are complete. Exact iOS XCTest execution, host builds,
runtime native-feel and accessibility review, accepted screenshots or the
permitted capture-failure bypass record, physical-device evidence, and
identical-publication delivery remain open.

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative first | PASS | Stepper is an ordinary EDGE element. Both renderers consume `NativeUINode`, observe the official current tree, and send standard NativePHP `PRESS` events at PHP-authored private callback IDs. No WebView, JSON bridge, generated-tree edit, or parallel renderer exists. |
| II. Familiar and coherent APIs | PASS | Required `value`/`min`/`max`, optional `step`, familiar field and accessibility metadata, plain/live `native:model`, and public `@change` form a narrow cross-platform API. Private decrement/increment callback IDs preserve the one public change event. |
| III. Stable values and predictable state | BLOCKED | PHP precomputes exact bounded neighbours, native display remains accepted and suppresses stale taps, and reconciliation emits nothing. Rejected identical-value acknowledgement still requires a verified NativePHP publication epoch. |
| IV. Equal platform quality | BLOCKED | SwiftUI and Material sources implement the same accepted-display, bounds, stale-tap, and publication contract. Android tests pass and isolated iOS production source typechecks; exact iOS XCTest and runtime rows remain pending. |
| V. Native expression over pixel parity | BLOCKED | iOS uses genuine SwiftUI `Stepper`; Android uses the catalogue-approved idiomatic Material 3 `IconButton` composition. Controller-owned runtime native-feel review remains pending. |
| VI. Accessibility correctness | BLOCKED | Visible or explicit names, accepted values, hints, helper/error, disabled state, named direction actions, 44-point SwiftUI controls, and 48-dp Material targets are implemented. VoiceOver, TalkBack, scaling, contrast, RTL, and physical-device checks remain pending. |
| VII. System-first theming | PASS | Native geometry and state layers are retained; field text uses NativePHP semantic tokens. Public icons, colours, variants, sizes, formatters, and style escape props are rejected. |
| VIII. Small, proven expansion | PASS | The installed `nativephp/mobile-ui` package was audited before scaffolding and contains no Stepper element or renderer. SwiftUI supplies the genuine primitive; Material supplies the documented composition primitives, justifying paired renderers. |
| IX. Evidence-based quality | BLOCKED | Red-first PHP proof, 77 focused package tests, 748 passing full package tests, all 131 Android tests with seven evidence skips, isolated iOS typechecking, exact-lock consumer tests, structural/docs gates, plugin validation, and diff checks pass. Runtime, host, screenshot, iOS XCTest, and device evidence remain pending. |
| X. Public alpha stewardship | PASS | The additive contract and docs make no release or alpha-readiness claim and explicitly record the upstream blocker. |
| XI. Skills enforce the constitution | PASS | Create, iOS, Android, and review skills were followed. The public class proof failed first, the scaffold ran exactly once, official docs were audited, and device ownership remained with the controller. |
| XII. Amendment | PASS | Stepper requires no constitutional amendment. |

## TDD, audit, and scaffold evidence

The installed NativePHP package was searched before implementation. It contains
no Stepper element or native renderer, so no adapter path exists. Current
NativePHP documentation confirmed ordinary model/change publication, SwiftUI
documentation confirmed the Stepper initializer with custom increment and
decrement closures, and Material 3 documentation confirmed enabled icon-button
semantics for the approved Android composition. No public-contract variation
was required.

The first focused proof asserted the absent public Stepper class and failed as
expected. That temporary proof was removed, `bin/scaffold-component Stepper`
ran exactly once, and the authored contract replaced every scaffold marker.
The subsequent focused suite exposed one integer-precision defect above
`2^53`; exact signed integer grid arithmetic replaced the float conversion and
the regression now covers both large positive values and a range spanning
`PHP_INT_MIN` without overflow.

## Current off-device evidence

```text
vendor/bin/pest tests/Feature/StepperElementTest.php \
  tests/Feature/PluginManifestTest.php
PASS — 77 tests, 280 assertions

composer test
PASS — 748 tests, 2,160 assertions; 5 model evaluations skipped

bin/check-component Stepper --development
composer docs:check
PASS

xcrun --sdk iphonesimulator swiftc -parse \
  resources/ios/StepperRenderer.swift resources/ios/StepperControl.swift \
  tests/ios/StepperSnapshotTests.swift
PASS — syntax only; no simulator launched

xcrun --sdk iphonesimulator swiftc -typecheck -D SWIFT_PACKAGE \
  -target arm64-apple-ios18.0-simulator \
  resources/ios/NativePHPTestShims.swift \
  resources/ios/StepperRenderer.swift resources/ios/StepperControl.swift
PASS — isolated production source typechecks; no simulator launched

JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android testDebugUnitTest
PASS — 131 tests, 0 failures, 7 controller-gated evidence skips

php artisan test tests/Feature/StepperShowcaseTest.php \
  tests/Feature/StepperCaptureTest.php \
  tests/Feature/ShowcaseNavigationTest.php
PASS — 23 tests, 312 assertions

composer test
PASS in firstlight-showcase — 93 tests, 1,467 assertions

composer validate --strict
PASS in package and showcase

php artisan native:plugin:validate /Users/wojt/Code/clinically-au/firstlight-ui
PASS with the expected UI-only warning — no bridge_functions are declared

git show --check b7cb3f9
git show --check a07da05
PASS
```

SwiftPM was also attempted without a device. Its macOS build stops package-wide
at the existing `UIKit` import in `BadgeRenderer.swift`, before Stepper XCTest
execution. The isolated iOS simulator-target typecheck above proves the Stepper
production sources but is not represented as XCTest or runtime evidence.

The showcase lock resolves `firstlightui/nativephp` exactly to
`b7cb3f917cec9b9f3b39b713b0503776fa0e9df7`. It dogfoods integer and float
models, lower and upper bounds, error, disabled, server rejection, rapid-tap
guard inputs, callback typing, and programmatic publication. The isolated
`/captures/stepper` route and screenshot inventory declare four stable paths.
No capture was attempted because the user explicitly permits capture issues to
be bypassed and emulator/device access remains controller-owned; absent images
are not claimed as passing evidence.

## Pending controller evidence

- Run the exact iOS Stepper XCTest target on the controller-chosen simulator.
- Verify NativePHP emits an observable publication for identical accepted trees; keep component release blocked on Mobile 4.0.1 until proven or fixed upstream.
- Review native feel, rapid interaction, rejection, reset, and programmatic publication on both controller-chosen runtime targets.
- Attempt the light/dark capture matrix if useful; if capture fails, record the user's explicit bypass without claiming image evidence.
- Complete iOS and Android host builds plus VoiceOver, TalkBack, Dynamic Type/font scale, contrast, RTL, Reduced Motion, offline, and physical-device rows.

## Honest milestone

Stepper is implemented, registered, documented, exact-lock consumer-proven,
and component-specific off-device ready at the reviewed revisions. It is not
development-complete, component-release-ready, or alpha-ready until the
pending iOS, runtime, accessibility, host, visual or bypass, physical-device,
and identical-publication rows close.
