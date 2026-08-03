---
name: firstlight-create-component
description: Use when adding a new cross-platform native control to the Firstlight NativePHP UI package.
---

# Create a Firstlight Component

## Overview

Define one stable public contract, then implement genuine platform controls behind the official SuperNative seams. `Constitution.md` is the release authority.

## Inputs

- StudlyCase component name and user-facing purpose
- Stable values, null behavior, events, disabled behavior, field metadata, and accessibility semantics
- The closest Apple and Material 3 native controls

## Workflow

1. Read `Constitution.md` and the implemented Segmented contract. Resolve API ambiguity before scaffolding.
2. Write failing PHP contract examples for every public value and callback shape.
3. Run `bin/scaffold-component Name`. Stop if it reports existing files; inspect them instead of overwriting.
4. Replace every generated failing stub. Register the PHP, iOS, and Android mappings in `nativephp.json` only after the contract is implemented.
5. **REQUIRED SUB-SKILL:** Use `firstlight-ios-component` for the Apple implementation and evidence.
6. **REQUIRED SUB-SKILL:** Use `firstlight-android-component` for the Material implementation and evidence.
7. Add `docs/components/<slug>.md`, paired platform screenshots, release evidence, and exhaustive states to the showcase app.
8. **REQUIRED SUB-SKILL:** Use `firstlight-review-component` before calling the component complete.

## Evidence

Record the package commit, focused and full test commands, simulator/emulator builds, accessibility checks, and physical-device results. State any assumed NativePHP publication fix explicitly.

## Stop Conditions

Stop and report the blocker when the public contract is unresolved, either platform lacks a genuine native control, an official SuperNative seam is missing, a release prerequisite is unreleased, or constitutional review fails. Do not publish, tag, or relax the release gate.
