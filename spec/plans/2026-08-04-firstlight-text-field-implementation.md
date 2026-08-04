---
title: Firstlight Text Field implementation plan
description: Build and verify the approved cross-platform Text Field contract.
status: complete
sources:
  - spec/designs/2026-08-04-firstlight-text-field-design.md
  - spec/reference/icons.md
  - .agents/skills/firstlight-create-component/SKILL.md
---

# Firstlight Text Field Implementation Plan

**Goal:** Ship `<firstlight:text-field>` with strict PHP validation, focused native editing, NativePHP-compatible icons, semantic clear/reveal extensions, and genuine Apple and Material 3 presentation.

**Architecture:** A Firstlight EDGE element publishes primitive field configuration and callbacks. Paired renderers keep a node-keyed local editing buffer, dispatch through official SuperNative text/submit/press events, and reconcile PHP publications without clobbering focused edits.

## Task 1: Establish the PHP contract with failing tests

- [x] Add `tests/Feature/TextFieldElementTest.php` covering strict values, field metadata, enums, sync modes, icons, callbacks, affordance conflicts, and diagnostics.
- [x] Register the test element and confirm the focused suite fails because Text Field is absent.
- [x] Run `bin/scaffold-component TextField`; it correctly refused to overwrite the authored failing test, so remaining files were added individually.

## Task 2: Implement the PHP and manifest surface

- [x] Implement the self-closing Blade component and strict `FirstlightUI\Elements\TextField` builder.
- [x] Resolve platform icons in PHP, preserve Android variants, register change/submit callbacks, and use the node's standard press callback for authored trailing actions.
- [x] Extend the tag precompiler and plugin manifest.
- [x] Run the focused Pest suite to green.

## Task 3: Implement the genuine Apple renderer

- [x] Add failing XCTest contracts for configuration decoding, focused draft reconciliation, sync/submit ordering, and clear/reveal behaviour.
- [x] Implement an Apple-native SwiftUI field composition with semantic icons, keyboard/content hints, autocapitalization, autocorrection, submit labels, native focus, and 44-point actions.
- [x] Run the fixed-simulator Swift suite.

## Task 4: Implement the Material 3 renderer

- [x] Add failing Kotlin tests for configuration, focused draft reconciliation, dispatch policy, clear/reveal semantics, icon variants, accessibility, and font scaling.
- [x] Implement Material 3 `OutlinedTextField` with native keyboard/IME/content hints, autofill semantics, semantic icons, 48-dp actions, and official bridge events.
- [x] Run `testDebugUnitTest` and record representative Paparazzi baselines.

## Task 5: Documentation, showcase, and evidence

- [x] Write `docs/components/text-field.md`, index it, and add complete showcase fixtures without editing generated native trees.
- [x] Add representative iOS/Android snapshots and accessibility states on deterministic hosts.
- [x] Record development review evidence and the remaining physical-device and clean-release-capture blockers.

## Task 6: Complete verification

- [x] Run focused and full Pest, Swift, and Android suites.
- [x] Run `bin/check-component TextField --development`, docs build/check, plugin manifest validation, and both consumer builds.
- [x] Review the diff requirement-by-requirement against the Constitution and approved design; commit the completed development build.
