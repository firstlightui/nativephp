---
title: Firstlight shared icon contract implementation plan
description: Add constitutional, maintained-specification, skill, and eval enforcement for NativePHP-compatible icon APIs.
status: historical
sources:
  - Constitution.md
  - spec/workflows/adding-components.md
  - .agents/skills/firstlight-create-component/SKILL.md
  - https://github.com/NativePHP/mobile-ui/pull/29
---

# Firstlight Shared Icon Contract Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make every Firstlight control and repository skill use NativePHP's shared icon fallback, paired iOS/Android overrides, official resolver types, Android variants, and accessible icon-action semantics.

**Architecture:** The Constitution owns durable coherence and accessibility principles. One indexed maintained reference owns exact Blade/fluent names and resolution precedence; workflows and skills consume that reference, while evals prove an agent retains the contract.

**Tech Stack:** Markdown specifications and skills, PHP 8.4, Pest 5 evals, NativePHP Mobile UI icon contracts.

## Global Constraints

- Document Blade attributes in kebab-case and accept Mobile UI's camelCase aliases internally.
- Use `leading-icon`, `leading-icon-ios`, `leading-icon-android`, `trailing-icon`, `trailing-icon-ios`, and `trailing-icon-android`.
- Use `IosSymbol|string|null` and `AndroidSymbol|string|null` platform overrides through NativePHP's `IconResolver`.
- Preserve Android filled/outlined variants and use the shared name as the unknown-platform fallback.
- Decorative icons are silent; every icon-only action has an explicit accessible label and the platform minimum target.
- Semantic affordances such as clear/reveal own their icons internally and do not redefine consumer icon naming.
- Do not add a public icon page until an implemented public Firstlight component exposes the shared contract.

---

### Task 1: Establish the shared constitutional and maintained contract

**Files:**

- Modify: `Constitution.md`
- Create: `spec/reference/icons.md`
- Modify: `spec/index.md`
- Modify: `spec/workflows/adding-components.md`

**Interfaces:**

- Produces: the indexed `spec/reference/icons.md` authority consumed by component contracts and skills.
- Produces: constitutional rules for NativePHP-compatible naming and icon accessibility.

- [x] **Step 1: Amend the Constitution**

Add Article II.7 requiring NativePHP shared fallback plus `-ios`/`-android` overrides and official resolver conventions. Add Article VI.7 requiring decorative icons to remain silent and icon-only actions to have explicit accessible names and platform-minimum targets. Update the amendment header and append the maintainer-approved rationale, affected principles, and no-migration-impact record.

- [x] **Step 2: Write the maintained icon reference**

Define the six Blade attributes, accepted camelCase aliases, fluent signatures, active-platform precedence, unknown-platform fallback, Android variant preservation, primitive wire props, decorative/action accessibility, semantic built-in affordances, validation failures, and tests required of every icon-bearing control.

- [x] **Step 3: Index and route component work through the reference**

Add `reference/icons.md` to `spec/index.md`. Add an icon-contract step to `spec/workflows/adding-components.md` immediately after the semantic contract step and name the icon reference in `sources`.

- [x] **Step 4: Validate documentation structure**

Run:

```bash
bin/check-docs --development
git diff --check
```

Expected: both commands pass; generated public documentation remains unchanged because the new page is a maintained specification.

- [x] **Step 5: Commit the shared contract**

```bash
git add Constitution.md spec/reference/icons.md spec/index.md spec/workflows/adding-components.md
git commit -m "docs: define the shared icon contract"
```

### Task 2: Enforce icon conventions through repository skills

**Files:**

- Modify: `.agents/skills/firstlight-create-component/SKILL.md`
- Modify: `.agents/skills/firstlight-ios-component/SKILL.md`
- Modify: `.agents/skills/firstlight-android-component/SKILL.md`
- Modify: `.agents/skills/firstlight-review-component/SKILL.md`
- Modify: `.agents/skills/firstlight-docs-write/SKILL.md`
- Modify: `.agents/skills/firstlight-docs-update/SKILL.md`
- Modify: `tests/Evals/FirstlightComponentSkillsTest.php`
- Modify: `tests/Feature/ComponentToolingTest.php`

**Interfaces:**

- Consumes: `spec/reference/icons.md` from Task 1.
- Produces: skill behavior that retains exact icon names, resolution, variants, semantics, and documentation evidence.

- [x] **Step 1: Add failing skill regressions**

Add a component-skill eval scenario that asks for an icon-bearing control and asserts the response contains `leading-icon`, `leading-icon-ios`, `leading-icon-android`, `trailing-icon`, `trailing-icon-ios`, `trailing-icon-android`, `IosSymbol`, `AndroidSymbol`, and `trailing-a11y-label`. Assert it rejects invented `ios-icon` and `android-icon` names. Extend the structural skill test so each applicable skill must name `spec/reference/icons.md` and its platform-specific responsibility.

- [x] **Step 2: Run the focused tests and verify RED**

Run:

```bash
vendor/bin/pest tests/Evals/FirstlightComponentSkillsTest.php --filter='icon contract'
vendor/bin/pest tests/Feature/ComponentToolingTest.php --filter='ships four concise skills'
```

Expected: failure because the skills do not yet cite or reproduce the maintained icon contract.

- [x] **Step 3: Update the coordinating and platform skills**

Make `firstlight-create-component` require the icon reference whenever icons apply. Make the iOS skill require resolved SF Symbol props without shared platform leakage. Make the Android skill require resolved Material names plus filled/outlined variants. Keep built-in semantic affordance icons internal.

- [x] **Step 4: Update review and documentation skills**

Make component review verify exact attributes, fluent argument order/types, fallback precedence, platform variants, decorative semantics, action labels/targets, and conflict diagnostics. Make docs write/update inspect the icon reference and include all applicable shared/platform props without inventing names.

- [x] **Step 5: Run focused tests and verify GREEN**

Run:

```bash
vendor/bin/pest tests/Evals/FirstlightComponentSkillsTest.php --filter='icon contract'
vendor/bin/pest tests/Feature/ComponentToolingTest.php --filter='ships four concise skills'
```

Expected: deterministic focused checks pass. Model-backed evals skip unless
explicitly enabled with `--evals`.

- [x] **Step 6: Commit skill enforcement**

```bash
git add .agents/skills tests/Evals/FirstlightComponentSkillsTest.php tests/Feature/ComponentToolingTest.php
git commit -m "test: enforce icon conventions in component skills"
```

### Task 3: Align the Text Field contract and run the complete governance gate

**Files:**

- Modify: `spec/designs/2026-08-04-firstlight-text-field-design.md`
- Modify: `spec/plans/2026-08-04-firstlight-icon-contract.md`

**Interfaces:**

- Consumes: `spec/reference/icons.md` and the updated skills.
- Produces: an approved Text Field design that references, rather than redefines inconsistently, the shared contract.

- [x] **Step 1: Cross-check the Text Field design**

Keep its complete six-attribute table and exact fluent signatures, cite `spec/reference/icons.md`, use `trailing-a11y-label`, and make the `clearable`/`revealable` extensions own their native icons without altering consumer naming.

- [x] **Step 2: Self-review for drift and placeholders**

Run:

```bash
rg -n 'TBD|TODO|ios-icon|android-icon|trailing-action-label' Constitution.md spec .agents/skills tests/Evals
rg -n 'leading-icon-ios|leading-icon-android|trailing-icon-ios|trailing-icon-android|trailing-a11y-label' Constitution.md spec .agents/skills tests/Evals
```

Expected: the first command contains only explicit rejection examples and the
plan command itself, with no authored contract using those names. The second
shows the maintained reference, Text Field design, skills, and eval coverage.

- [x] **Step 3: Run complete applicable gates**

Run:

```bash
vendor/bin/pest tests/Feature/ComponentToolingTest.php tests/Evals/FirstlightComponentSkillsTest.php
bin/build-docs-artifacts
bin/check-docs --development
git diff --check
```

Expected: all tests and documentation checks pass; generated public artifacts are byte-identical unless another indexed public page changed.

- [x] **Step 4: Commit the aligned design and plan record**

```bash
git add spec/designs/2026-08-04-firstlight-text-field-design.md spec/plans/2026-08-04-firstlight-icon-contract.md
git commit -m "docs: align Text Field with icon conventions"
```
