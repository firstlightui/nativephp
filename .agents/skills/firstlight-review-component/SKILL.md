---
name: firstlight-review-component
description: Use when auditing a Firstlight component before a development milestone, alpha release, tag, or publication.
---

# Review a Firstlight Component

## Overview

Return an evidence-backed, requirement-by-requirement verdict. `Constitution.md` is authoritative; a passing unit suite alone is not release approval.

## Inputs

- StudlyCase component name
- Review mode: development or release
- Package root, showcase root, fixed simulator/emulator targets, and package commit

## Workflow

1. Read `Constitution.md`, the public component docs, manifest entry, PHP contract, both renderers, tests, and release-evidence checklist.
2. Run the structural gate:

```bash
bin/check-component <Name> --development
# Omit --development for a release review.
```

3. Run the package gates from a clean diff:

```bash
composer test
xcodebuild -scheme FirstlightIOSControls -destination 'platform=iOS Simulator,id=<fixed-id>' test
JAVA_HOME=<jdk-21> tests/android/gradlew -p tests/android testDebugUnitTest
```

4. In the showcase app, install the exact package commit, run focused and full consumer tests, then run `php artisan native:plugin:validate <package-root>`. Build both native hosts without editing generated trees.
5. Exercise accepted selection, rejected unchanged selection, repeated rejected attempts, server reset, disabled state, null state, dark mode, large text, and rapid taps where applicable.
6. For release mode, verify paired iOS/Android light/dark screenshots and filled physical-device rows for VoiceOver, TalkBack, Dynamic Type, font scaling, contrast, Reduced Motion, and offline behavior.

## Report Shape

List each relevant Constitution article as `PASS`, `FAIL`, or `BLOCKED`, followed by the exact file, command, screenshot, or device row. Then list warnings, assumed NativePHP publication fix/version, and an overall development or release verdict.

## Stop Conditions

Stop with a failed or blocked verdict when any required path, renderer, test, official seam, consumer build, accessibility check, device row, or publication prerequisite is missing. Do not tag, publish, soften failures, or treat development evidence as release evidence.
