---
name: firstlight-review-component
description: Use when auditing a Firstlight component before a development milestone, alpha release, tag, or publication.
---

# Review a Firstlight Component

## Overview

Return an evidence-backed, requirement-by-requirement verdict. `Constitution.md` is authoritative; passing tests are not approval.

## Inputs

- Public component name and tag
- Review mode: development or component-release
- Package root, showcase root, package commit, and permitted device targets

## Workflow

1. Read `Constitution.md`, `spec/designs/2026-08-04-firstlight-alpha-component-system-design.md`, the component specification, public docs, example API, manifest entry, PHP contract, tests, and evidence checklist. For icon-bearing controls, also read `spec/reference/icons.md`.
2. Identify its implementation path. For an adapter, verify the official NativePHP primitive was audited and every Firstlight prop, event, state, accessibility semantic, and diagnostic maps without leaking a second public API. For a custom renderer, inspect both production platform implementations. Never require placeholder renderer files for an adapter.
3. Classify its state and review the matching rules:
   - discrete: server-authoritative; rejection, reset, and reconciliation never echo events;
   - focused text: preserve the native buffer, selection, cursor, marked text, focus, keyboard, and `live`/`blur`/`debounce` semantics;
   - continuous: native-thread interaction with binding-controlled publication frequency;
   - action or display: loading, duplicate activation, semantics, or inert behaviour as applicable.
4. When icons apply, verify the established base prop plus `-ios` and `-android` overrides, fluent `IosSymbol` / `AndroidSymbol` types, shared fallback, Android variant preservation, decorative semantics, conflict diagnostics, and explicit labels such as `trailing-a11y-label` for icon-only actions.
5. Run the structural gate:

```bash
bin/check-component <Name> --development
# Omit --development for component-release review.
```

6. Run package gates from a clean diff. Device-targeting commands require explicit permission for each exact simulator or emulator; target IDs alone are not permission.

```bash
composer test
xcodebuild -scheme FirstlightIOSControls -destination 'platform=iOS Simulator,id=<fixed-id>' test
JAVA_HOME=<jdk-21> tests/android/gradlew -p tests/android testDebugUnitTest
```

7. In `firstlightui/showcase`, preserve adjacent work. Interactive pages extend `ShowcaseScreen` and appear in `ShowcaseHome`; capture routes remain isolated from shared chrome. Install the exact package, run focused and full consumer tests, then `php artisan native:plugin:validate <package-root>`. Never edit generated trees.
8. Verify every applicable documented state, accessibility mode, edge case, rapid interaction, rejection, and programmatic change.
9. For component-release mode, verify permitted Simulator/emulator evidence and completed physical-device rows for both platforms.

## Report Shape

List each relevant Constitution article as `PASS`, `FAIL`, or `BLOCKED`, followed by the exact file, command, screenshot, or device row. Then list warnings, upstream assumptions, and an overall development or component-release verdict.

Report catalogue readiness separately. A passing component review does not authorize an alpha claim: the complete catalogue, every component review, both showcase host builds, documentation, accessibility coverage, physical-device evidence, and upstream installation dependencies must pass the shared alpha gate.

## Stop Conditions

Stop with a failed or blocked verdict when any applicable adapter mapping or renderer, test, official seam, showcase state, consumer build, accessibility check, device row, or dependency is missing. Do not tag, publish, soften failures, treat development evidence as release evidence, or present one passing component as alpha readiness.
