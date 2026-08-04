---
title: Testing Firstlight
description: Useful verification layers for package contracts, native implementations, consumer fixtures, documentation tooling, accessibility, and release evidence.
status: current
audience: maintainer
sources:
  - Constitution.md
  - composer.json
  - Package.swift
  - bin/check-component
  - bin/check-docs
  - tests/Feature/SegmentedElementTest.php
  - tests/Feature/ComponentToolingTest.php
  - tests/Feature/DocumentationToolingTest.php
  - tests/Feature/DocumentationScreenshotCaptureTest.php
  - tests/ios/SegmentedRendererContractTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SegmentedRendererContractTest.kt
  - .agents/skills/firstlight-review-component/SKILL.md
---

# Testing Firstlight

## Principle

Tests prove Firstlight-owned behaviour at the narrowest useful layer, then consumer and device evidence prove that the layers integrate. A test that exercises only framework behaviour, a snapshot that cannot prove interaction, or a file-existence check presented as product quality is insufficient.

Prose and static Markdown do not need tests solely to increase coverage. Add a documentation test only when executable tooling or a concrete regression risk needs protection.

## Verification layers

| Layer | Proves | Does not prove |
| --- | --- | --- |
| PHP contract tests | authored API, normalization, primitive Element Tree props, callbacks, diagnostics, state metadata, manifest integration | native layout, motion, accessibility runtime |
| Component tooling tests | non-overwriting scaffolding and deterministic structural gate failures | semantic correctness or native feel |
| iOS behaviour and snapshot tests | production Swift configuration, reconciliation, theme/state rendering, selected accessibility cases | live VoiceOver, touch, motion, focus, or physical-device behaviour |
| Android behaviour and Paparazzi tests | production Kotlin configuration, reconciliation, Material rendering, theme and font-scale cases | live TalkBack, gesture timing, or physical-device behaviour |
| Showcase tests | real consumer discovery, authored route tree, documented states, accessibility-tree integration | visual quality unless paired with native capture and review |
| Simulator/emulator builds and screenshots | both hosts build, the route renders natively, light/dark and representative visual states | physical-device performance, accessibility interaction, offline behaviour |
| Physical-device review | interaction, motion, presentation, accessibility services, offline behaviour, reconciliation, rapid input | nothing beyond the recorded device, OS, scenario, and date |
| Documentation tooling tests | indexes, source paths, generated artefacts, manifests, links, screenshot workflow failure handling | truth of a claim unless its declared source is also inspected |
| Skill evals | high-value agent decisions encoded by repository skills | production code behaviour |

## Package commands

Run focused tests during implementation, then the full applicable gates before review.

```bash
composer test

xcodebuild \
  -scheme FirstlightIOSControls \
  -destination 'platform=iOS Simulator,id=<fixed-id>' \
  test

JAVA_HOME=<jdk-21> tests/android/gradlew \
  -p tests/android \
  testDebugUnitTest

bin/check-component <Name> --development
bin/check-docs --development
```

Omit `--development` from `bin/check-component` only for component-release review. The release mode requires evidence that development mode deliberately defers. Do not make a failing release gate pass by deleting a required row or substituting simulator evidence for a physical device.

Before relying on the current component-release command, resolve its review-path mismatch with the documentation gate: `bin/check-component` checks `docs/review/`, while `bin/check-docs` checks the maintained `spec/reviews/` boundary. A duplicated review file is not a fix.

Pest evals under `tests/Evals/` launch an isolated Codex process and are evidence for skill decisions. Keep them focused on consequential choices; do not use them as a substitute for deterministic structural tests.

## Component contract expectations

Select cases from the component's state class rather than copying Segmented's suite:

- **Discrete:** stable values, `null`, disabled choices, rejection, reset, programmatic publication, rapid selection, and no echo events.
- **Focused text:** native buffer, cursor, selection, marked text or IME composition, focus, keyboard, and `live`/`blur`/`debounce` timing.
- **Continuous:** UI-thread gesture continuity, publication frequency, release behaviour, rejection, and programmatic updates.
- **Action/display:** loading or duplicate activation, semantics, inert behaviour, and programmatic updates as applicable.

Every renderer path compiles and tests the production renderer, not a shim that bypasses it. Snapshot assertions complement behaviour tests; they do not replace them.

## Showcase verification

The sibling showcase must install the exact package commit being reviewed. Run its focused fixture tests, full consumer suite, plugin validation, and both native host builds. A package-only pass is not consumer evidence.

Showcase fixtures cover each applicable documented state with fixed, neutral data. Interactive, accessibility, scaling, RTL, rejection, and rapid-input scenarios remain separate evidence even when a compact documentation fixture shows only representative states.

## Failure policy

Retain the original failing command and evidence. Decide whether the failure is in the package, official dependency, generated host, environment, or test before editing. Never weaken an assertion, exclude production source, bless a new snapshot, or broaden a timeout solely to obtain green output.

When implementation and a documented contract disagree, stop and resolve authority. Tests are evidence of current behaviour, not permission to silently redefine the public API.
