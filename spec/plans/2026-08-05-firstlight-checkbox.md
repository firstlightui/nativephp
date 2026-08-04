---
title: Firstlight Checkbox implementation plan
description: Test-first delivery plan for the strict Checkbox contract, paired renderers, documentation, showcase, and constitutional evidence.
status: current
sources:
  - spec/designs/2026-08-05-firstlight-checkbox-design.md
  - spec/components/checkbox.md
  - Constitution.md
  - roadmap-v2.md
---

# Firstlight Checkbox Implementation Plan

**Goal:** Deliver a development-proven `<firstlight:checkbox>` with strict
Boolean authoring, field metadata, server-authoritative rejection, idiomatic
Apple and Material expressions, documentation, showcase coverage, and an
evidence-backed constitutional review.

## Constraints

- Use `firstlight.checkbox`, `FirstlightUI\Elements\Checkbox`,
  `FirstlightUI\Components\Checkbox`, `CheckboxRenderer`, and the plugin's
  canonical Android renderer namespace.
- Publish strict `value`, `label`, `helper`, `error`, `required`, `disabled`,
  accessibility metadata, and optional standard `on_change` only.
- Default to unchecked; never accept null, strings, numeric truthiness, or an
  indeterminate state.
- Keep accepted state server-authoritative and deduplicate proposals until any
  matching node publication clears pending state.
- Use NativePHP's existing checkbox-change transport, not a new bridge.
- Do not run device targets or record screenshots without explicit permission.
- Preserve unrelated package and showcase work; do not publish or tag.
- Keep `roadmap-v2.md` and `roadmap-v3.md` untracked and untouched unless the
  maintainer separately asks to commit them.

## Delivery sequence

### 1. PHP and EDGE contract

- [ ] Write a public-tag Pest test before scaffolding and verify it fails
  because the Checkbox class does not exist.
- [ ] Run `bin/scaffold-component Checkbox` once and never overwrite.
- [ ] Expand the failing suite for strict booleans, default false, metadata,
  required/disabled validation, callback registration, live-only binding,
  unsupported APIs, external layout, web no-op compilation, and exact paired
  manifest identifiers.
- [ ] Implement the minimum Blade component, element, precompiler entry, and
  manifest registration; verify focused Pest green.

### 2. iOS renderer

- [ ] Write failing XCTest state, accessibility-helper, control-target, and
  guarded light/dark/disabled/error/large-text snapshot cases.
- [ ] Add the package shim for NativePHP's checkbox-change event only if the
  production renderer requires it.
- [ ] Implement an idiomatic SwiftUI checkmarked row with accepted-state
  rendering, proposal deduplication, full metadata reconciliation, native
  semantic tokens, one toggle accessibility element, and 44-point target.
- [ ] Type-check all production Swift sources and compile the generic iOS
  package without launching a simulator.

### 3. Android renderer

- [ ] Write failing Kotlin state, semantics, and Paparazzi cases before
  implementation.
- [ ] Implement a Material 3 Checkbox row whose outer toggleable owns
  `Role.Checkbox`, accepted state, metadata, error semantics, and 48-dp target;
  keep the inner Checkbox non-interactive and silent to TalkBack.
- [ ] Run focused and complete Android unit/Paparazzi suites with JDK 21.

### 4. Documentation and showcase

- [ ] Add the public guide, docs index entry, generated navigation/search
  artefacts, and component structural expectations.
- [ ] Inspect the sibling showcase status, extend `ShowcaseScreen`, register
  the exact tag and summary in `ShowcaseHome`, add the interactive page and
  isolated `/captures/checkbox` fixture, and add focused tests.
- [ ] Install the exact package revision when commits are requested; otherwise
  use the current path package without pretending an uncommitted hash is exact.
- [ ] Run focused and full showcase tests and plugin validation without devices.

### 5. Verification and review

- [ ] Run focused and full package tests, generic iOS compilation, complete
  Android tests, component/docs gates, strict Composer validation, and both
  repository diff checks.
- [ ] Record simulator/emulator, screenshot, VoiceOver, TalkBack, contrast,
  scaling, RTL, motion, offline, rapid-input, and physical-device rows as
  pending unless separately authorized and performed.
- [ ] Write `spec/reviews/checkbox-development.md` with article-by-article
  PASS/BLOCKED verdicts and separate component, release, and catalogue status.
- [ ] Update `roadmap-v2.md` only when its stated delivery rule is truthfully
  satisfied; otherwise leave Checkbox's status and blockers explicit.
