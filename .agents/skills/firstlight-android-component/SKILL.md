---
name: firstlight-android-component
description: Use when implementing or reviewing the Android half of a Firstlight native control.
---

# Build a Firstlight Android Component

## Overview

Use the genuine Material 3 control and keep the renderer a thin SuperNative adapter. PHP remains server-authoritative for visible state.

## Inputs and Files

- Read `Constitution.md`, `nativephp.json`, and the component's PHP contract.
- Implement `resources/android/<Name>Control.kt` and `resources/android/<Name>Renderer.kt`.
- Add behavior and Paparazzi coverage under `tests/android/`.

## Workflow

1. Write failing Kotlin contract and Paparazzi cases before implementation. Cover null, selected, disabled, error, light, dark, and accessibility states.
2. Use the genuine Material 3 Compose control. Do not imitate iOS or hand-draw a platform-neutral substitute.
3. Decode only primitive Element Tree props in the SuperNative renderer. Route semantic events through the official bridge.
4. On touch, emit once but keep visible selection server-authoritative. Reconcile accepted PHP publications without replaying events. A rejected value must never remain displayed; repeated rejected attempts must remain possible.
5. Use semantic NativePHP theme tokens. Verify contrast and at least 48-dp targets.
6. Run:

```bash
JAVA_HOME=<jdk-21> tests/android/gradlew -p tests/android testDebugUnitTest
bin/check-component <Name> --development
```

7. Verify TalkBack labels, roles, states, and order; font scaling at `2.0`; high contrast; light and dark modes; and rapid taps.
8. Capture deterministic emulator snapshots. Record a separate physical device pass with model, Android version, package commit, tester, and date.

## Evidence

Report exact test counts, snapshot paths, the consumer build result, accessibility findings, and unresolved warnings. Simulator evidence does not replace the physical device row. If a NativePHP publication fix is unreleased, name the required official version and keep the public alpha gate failed.

## Stop Conditions

Stop when no genuine Material 3 control fits, the official SuperNative seam cannot express the contract, compiler warnings remain, accessibility truncates meaning, the consumer host does not compile, the publication fix is not officially available for release, or physical-device evidence is absent. Do not edit generated native trees or add web-rendered or ad hoc bridge substitutes.
