---
title: Callout development review
description: Constitutional review of the Firstlight Callout contract, paired native renderers, tests, showcase fixture, and outstanding runtime evidence.
status: blocked
audience: maintainer
sources:
  - Constitution.md
  - spec/components/callout.md
  - docs/components/callout.md
  - nativephp.json
  - src/Elements/Callout.php
  - resources/ios/CalloutControl.swift
  - resources/ios/CalloutRenderer.swift
  - resources/android/CalloutControl.kt
  - resources/android/CalloutRenderer.kt
  - tests/Feature/CalloutElementTest.php
  - tests/ios/CalloutSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/CalloutTest.kt
---

# Callout Development Review

Reviewed on 2026-08-05 against package base revision `db10c41` and showcase
base revision `4543672` on `main`. These revisions identify the parent
snapshots for the uncommitted Callout implementation reviewed here.

**Off-device implementation verdict:** PASS

**Development verdict:** BLOCKED

**Component-release verdict:** BLOCKED

The strict public contract, paired renderers, package and Android tests,
off-device iOS compilation, exact generated consumer hosts, sibling showcase,
approved documentation captures, and structural checks pass. Development
remains blocked because focused iOS XCTest has not been executed and native
action interaction and manual assistive-technology evidence remain absent.

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative first | PASS | The EDGE element and paired renderers use NativePHP's official plugin manifest, Element Tree, callback transport, and renderer lifecycle. No WebView, generated-host edit, alternate bridge, or parallel transport was introduced. |
| II. Familiar and coherent APIs | PASS | The API uses required `message`, semantic `tone`, optional `action-label`, standard `@press`, shared accessibility attributes, and external `class` layout. State, dismissal, icon, arbitrary style, and non-standard event APIs fail before publication. |
| III. Stable values and predictable state | PASS | Callout is an action/display component with no model. Stable native node identity reconciles server-published message, tone, action, and accessibility metadata without emitting events; only completed optional-action activation can emit `@press`. |
| IV. Equal platform quality | PASS | SwiftUI and Material 3 implementations expose the same message, five tones, renderer-owned semantic icons, optional action, accessibility metadata, malformed-data fallback, minimum action target, and reconciliation contract. Both production sources, test targets, and exact generated showcase hosts compile; the approved matrix shows the unchanged fixture on both platforms. |
| V. Native expression over pixel parity | PASS | The generated SwiftUI and Material 3 hosts were launched on the approved targets. The approved matrix shows native platform typography, iconography, system chrome, surface geometry, light/dark theming, wrapping, and distinct platform expression without forced pixel parity. |
| VI. Accessibility is correctness | BLOCKED | Distinct tone symbols, generated tone-prefixed names, label and hint overrides, decorative icons, separate native action buttons, 44-point iOS and 48-dp Android target baselines, large-text render cases, and runtime dark appearance are implemented. VoiceOver, TalkBack, focus order, Dynamic Type, font scaling, increased contrast, RTL, and reduced-motion behaviour have not been manually observed. |
| VII. System-first theming | PASS | Both renderers reuse NativePHP semantic theme tokens and platform-native typography and controls. Public styling is limited to external EDGE layout; there are no colour, shape, elevation, typography, animation, or platform escape props. |
| VIII. Small, proven expansion | PASS | The installed `nativephp/mobile-ui` 0.3.0 manifest and renderer sources contain no callout, banner, alert, or snackbar element. The added surface is limited to one persistent semantic message and one optional standard action. |
| IX. Evidence-based quality | BLOCKED | Red-first PHP and Android evidence, focused and full package tests, Android unit/render tests, off-device iOS production and XCTest compilation, docs checks, exact local Composer installation, focused and full showcase tests, plugin validation, both generated host builds, exact-route launches, deterministic capture checks, and the approved four-image matrix pass. Executed iOS XCTest, native action interaction, and manual accessibility evidence remain incomplete. |
| X. Public alpha stewardship | BLOCKED | No tag, publication, roadmap closure, component-release claim, or public-alpha claim is authorized while interaction, accessibility, executed iOS-test, and physical-device evidence remain incomplete. |
| XI. Skills enforce the constitution | PASS | The creation, iOS, Android, review, documentation, screenshot, Context7, test-first, and verification workflows were applied. Device targeting began only after the exact iOS and Android targets were explicitly approved. |
| XII. Amendment | PASS | Callout stays within the existing EDGE, API, state, parity, accessibility, theming, and evidence principles and requires no constitutional amendment. |

## Passing evidence

```text
composer test
PASS — 999 tests, 2,852 assertions; 5 model-backed evals skipped by design

vendor/bin/pest tests/Feature/CalloutElementTest.php tests/Feature/PluginManifestTest.php
PASS — 40 tests, 94 assertions

composer validate --strict
PASS in package and showcase repositories

bin/check-component Callout --development
PASS

bin/check-docs --development
PASS

bin/build-docs-artifacts
PASS

git diff --check
PASS in package and showcase repositories

JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android testDebugUnitTest --tests '*Callout*'
PASS

JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android testDebugUnitTest
PASS

swift build --build-tests --triple arm64-apple-ios18.0-simulator \
  --sdk /Applications/Xcode.app/Contents/Developer/Platforms/iPhoneSimulator.platform/Developer/SDKs/iPhoneSimulator26.5.sdk
PASS — production Callout and XCTest sources compile off-device

php artisan test in firstlight-showcase
PASS — 112 tests, 1,850 assertions

php artisan native:plugin:validate /Users/wojt/Code/clinically-au/firstlight-ui
PASS with expected UI-only warning — no bridge_functions are declared

bin/capture-doc-screenshots Callout \
  --showcase=../firstlight-showcase \
  --ios=1EB9538D-1622-432A-AD54-0654EACCEA13 \
  --android=emulator-5554
PASS — exact iOS and Android hosts built and launched; four stable captures published; target appearance and animation settings restored
```

## Red test evidence

The generated focused PHP contract failed before implementation because
Callout was only a scaffold placeholder with no EDGE, prop, callback,
validation, manifest, precompiler, or renderer contract. The completed
implementation made the focused contract pass without weakening its
assertions.

The focused Android test compilation then failed with unresolved Callout
control and renderer types before the Material 3 implementation existed. The
same test task passed after the production implementation was added.

An ordinary Xcode build command at the repository root is not applicable to
this Swift package because there is no Xcode project or workspace. The
explicit iOS Simulator SDK cross-build compiled production and test targets;
it did not execute XCTest and is not runtime evidence.

## Primitive audit

The installed `nativephp/mobile-ui` 0.3.0 plugin manifest and both platform
renderer registries expose no callout, banner, alert, or snackbar EDGE
primitive. Existing layout surfaces could imitate the appearance but could not
provide one semantic component, renderer-owned tone meaning, a single action
contract, or the required accessibility behaviour through an unchanged
official element. A paired renderer through the official component seam is
therefore the smallest coherent extension.

## Showcase and documentation evidence

The sibling showcase contains an interactive `/callout` gallery, isolated
`/captures/callout` fixture, navigation entry, all five tones, message-only and
action states, accessibility overrides, long copy, and callback feedback.
Focused and full showcase tests prove tree publication and callback routing.
The exact local package tree at `3e8cd1b` with the staged Callout changes was
installed before generated host compilation, launch, and capture.

The guarded development capture used iPhone 16 Pro Simulator
`1EB9538D-1622-432A-AD54-0654EACCEA13` on iOS 18.6 and Android `Pixel_9_Pro`
as `emulator-5554`. It published a stable light/dark matrix and restored both
targets' prior appearance and animation settings:

- `docs/screenshots/callout/ios-light.png`
- `docs/screenshots/callout/ios-dark.png`
- `docs/screenshots/callout/android-light.png`
- `docs/screenshots/callout/android-dark.png`

The maintainer approved all four images on 2026-08-05 after visual inspection
for native expression, crop, labels, wrapping, system chrome, light/dark
differentiation, and accidental data.

## Warnings and upstream assumptions

- Plugin validation reports the expected UI-only warning because this plugin
  declares no `bridge_functions`.
- SwiftPM reports existing unhandled snapshot-image resources and deprecated
  trait APIs in unrelated test files; Callout production and XCTest sources
  still compile successfully.
- SwiftUI and Material 3 own native activation feedback and focus behaviour.
  The generated-host matrix verifies presentation and text layout, while
  interactive and assistive-technology checks remain outstanding.

## Remaining evidence

- Execute the focused Callout XCTest suite on an explicitly authorized fixed
  iOS simulator.
- Exercise action activation, duplicate suppression, server reconciliation,
  malformed native fallback, and long copy on both platforms.
- Manually verify VoiceOver and TalkBack names, roles, hints, focus order, and
  one-event action behaviour.
- Observe Dynamic Type/font scaling, RTL, increased contrast/high contrast,
  dark appearance, and reduced motion on both platforms.
- Rerun package and consumer gates from clean committed checkouts before
  release review.
- Complete dated physical-device evidence required for component release.

## Catalogue readiness

This passing off-device component implementation does not establish catalogue
or alpha readiness. The complete catalogue, every component review, both
showcase runtime hosts, documentation screenshots, accessibility coverage,
physical-device rows, and upstream installation dependencies remain governed
by the shared alpha gate.

## Honest milestone

Callout is implemented, documented, installed in the sibling showcase, green
in the available source and host-build lanes, and represented by an approved
development screenshot matrix. It is not development-complete,
component-release-ready, or public-alpha-ready until the executed iOS-test,
native interaction, accessibility, and physical-device gates above are closed.
