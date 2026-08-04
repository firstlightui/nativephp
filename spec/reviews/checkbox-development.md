---
title: Checkbox development review
description: Constitutional review of the exact-revision off-device Checkbox implementation and its remaining runtime evidence.
status: blocked
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - spec/components/checkbox.md
  - docs/components/checkbox.md
  - nativephp.json
  - src/Elements/Checkbox.php
  - resources/ios/CheckboxControl.swift
  - resources/ios/CheckboxRenderer.swift
  - resources/android/CheckboxControl.kt
  - resources/android/CheckboxRenderer.kt
  - tests/Feature/CheckboxElementTest.php
  - tests/ios/CheckboxSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/CheckboxTest.kt
---

# Checkbox Development Review

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-08-05 |
| Component | `Checkbox` / `<firstlight:checkbox>` |
| State class | Discrete, strict Boolean, server-authoritative |
| Implementation path | Paired SwiftUI and Material 3 renderers |
| Package revision reviewed | `9ae7fa0c28ee1d57e0f82b37306bf4e9c8e39658` |
| Showcase revision reviewed | `d034743` |
| Permitted device execution | None; no simulator, emulator, physical device, host launch, or runtime screenshot action was performed |

**Component-specific off-device verdict:** PASS

**Development verdict:** BLOCKED

**Component-release verdict:** BLOCKED

The strict PHP contract, paired renderer sources, complete Android suite, full
iOS Simulator test-target compilation, public documentation, structural gates,
and exact-lock showcase are complete. Exact iOS test execution, native host
builds, runtime reconciliation, visual evidence, assistive-technology checks,
and physical-device rows remain pending.

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative first | PASS | `firstlight.checkbox` uses the EDGE element, manifest renderer mapping, current `NativeUITree`, and NativePHP's existing checkbox-change transport. It adds no WebView, JSON bridge, generated-host edit, or alternate state channel. |
| II. Familiar and coherent APIs | PASS | Strict Boolean `value`/`native:model`, `label`, `helper`, `error`, `required`, `disabled`, accessibility metadata, external `class`, and standard `@change` form a narrow field API. Unsupported indeterminate, styling, event, and deferred-sync APIs fail explicitly. |
| III. Stable values and predictable state | BLOCKED | Both state classes retain the published value, deduplicate proposals, replace all metadata on publication, clear pending state after identical rejection, and never echo programmatic changes. Package and showcase tests pass, but identical-tree publication still requires runtime verification in the generated NativePHP hosts. |
| IV. Equal platform quality | BLOCKED | iOS and Android implement the same contract with platform-native expression. Android production and tests execute completely; the full iOS Simulator target compiles, but XCTest execution and both generated host builds were not authorized. |
| V. Native expression over pixel parity | BLOCKED | iOS uses an idiomatic SwiftUI checkmarked row and Android uses Material 3 `Checkbox`. Runtime native-feel, pressed-state, wrapping, RTL, and interaction review remains pending. |
| VI. Accessibility correctness | BLOCKED | Source and contract tests cover visible/explicit names, values, hints, required/error context, disabled state, decorative glyphs, one target, and 44-point/48-dp minimums. VoiceOver, TalkBack, font scaling at runtime, contrast, RTL, and physical-device evidence remain pending. |
| VII. System-first theming | PASS | Both controls use NativePHP semantic tokens and platform typography/control geometry. Consumer colours, icons, shapes, variants, and arbitrary style escape hatches are excluded. |
| VIII. Small, proven expansion | PASS | The installed Mobile UI Checkbox was audited first. Its optimistic local state and absent helper/error/required metadata cannot meet this form-field contract, so paired renderers are justified. |
| IX. Evidence-based quality | BLOCKED | Red-first PHP, Swift, Kotlin, and showcase contracts; full package, Android, and showcase suites; exact lock; documentation; structural checks; and plugin validation pass. Controller-owned snapshots, native host builds, runtime accessibility, and physical-device evidence are absent. |
| X. Public alpha stewardship | PASS | The addition is documented without a release, tag, publication, or alpha-readiness claim. Missing development and release evidence is explicit. |
| XI. Skills enforce the constitution | PASS | The create, iOS, Android, documentation, screenshot-boundary, TDD, and review skills were applied. No device target was inferred or used. |
| XII. Amendment | PASS | Checkbox is the roadmap's later standalone form/checklist Boolean control; it changes no constitutional principle and requires no amendment. |

## Red-first evidence

- The first PHP public contract failed because the Checkbox component did not
  exist. The expanded generated suite then failed until strict validation,
  compilation, manifest registration, and callback behaviour were implemented.
- The Android contract failed on missing `CheckboxRendererConfiguration`,
  `CheckboxRendererState`, event, and control types before production Kotlin
  was written.
- The iOS Simulator test-target build failed on missing Checkbox configuration,
  state, event, accessibility, control, and renderer types before production
  Swift was written.
- The showcase contract failed first because `/checkbox` and
  `/captures/checkbox` were not registered.

## Passing off-device evidence

```text
vendor/bin/pest tests/Feature/CheckboxElementTest.php \
  tests/Feature/PluginManifestTest.php
PASS — 44 tests, 117 assertions

composer test
PASS — 831 tests, 2,384 assertions; 5 model-backed evals skipped by design

swift build --triple arm64-apple-ios18.0-simulator \
  --sdk /Applications/Xcode.app/Contents/Developer/Platforms/iPhoneSimulator.platform/Developer/SDKs/iPhoneSimulator26.5.sdk \
  --scratch-path /private/tmp/firstlight-checkbox-swift-build \
  --build-tests --disable-sandbox
PASS — complete production and XCTest targets compiled for the iOS Simulator

JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android testDebugUnitTest
PASS — 152 tests, 12 controller-gated skips, 0 failures, 0 errors

bin/check-component Checkbox --development
bin/check-docs --development
composer validate --strict
PASS in the package

composer test
PASS in firstlight-showcase — 100 tests, 1,604 assertions

composer validate --strict
PASS in firstlight-showcase

php artisan native:plugin:validate /Users/wojt/Code/clinically-au/firstlight-ui
PASS with the expected UI-only warning — no bridge_functions are declared

git show --check 9ae7fa0
git show --check d034743
PASS
```

The showcase lock resolves `firstlightui/nativephp` exactly to
`9ae7fa0c28ee1d57e0f82b37306bf4e9c8e39658`. Its interactive gallery covers
unchecked, checked, required, disabled unchecked and checked, helper, error,
long-label, accepted, repeated rejected, and programmatic publication cases.
The isolated `/captures/checkbox` route contains fixed unchecked, checked,
disabled, required, helper, and error states and passes its accessibility-tree
contract.

## Deferred evidence

- Run the focused iOS XCTest suite on an explicitly permitted simulator.
- Build both generated showcase hosts for the exact package and showcase
  revisions.
- Run the guarded four-image documentation capture using explicit iOS and
  Android target IDs, inspect all images, and obtain visual approval.
- Verify identical-value rejection and pending-guard release in both generated
  hosts, including rapid repeated interaction.
- Complete VoiceOver, TalkBack, Dynamic Type/font scaling, Increased Contrast,
  Reduced Motion, RTL, disabled/error, offline, and physical-device rows.
- Re-run the documentation and component gates in release mode only after the
  clean screenshot and review evidence exists.

## Honest milestone

Checkbox is implemented, registered, documented, committed, and dogfooded by
the exact-revision showcase with broad off-device evidence. It is not
development-complete, component-release-ready, catalogue-ready, or
public-alpha-ready until the deferred runtime, visual, accessibility, host,
and physical-device evidence is complete. The roadmap item must remain open.
