---
name: firstlight-android-component
description: Use when implementing or reviewing the Android expression of a public Firstlight control.
---

# Build a Firstlight Android Component

## Overview

Express the shared contract with genuine Material 3 controls or idiomatic Compose composition. Reuse an adequate official NativePHP control before adding a renderer.

## Canonical Identity and Authorities

- Package: `firstlightui/nativephp`
- Showcase: `firstlightui/showcase`
- Android minimum: API 29

Read `Constitution.md`, `spec/designs/2026-08-04-firstlight-alpha-component-system-design.md`, the component contract, `nativephp.json`, current NativePHP source/docs, and Material 3 docs. Manifest and Gradle sources—not Composer-name inference—own renderer identifiers.

## Implementation Decision

| Path | Requirement |
| --- | --- |
| Adapter | Keep the Firstlight tag while reusing or thinly adapting an adequate official primitive. Do not create Kotlin files merely to satisfy a template. |
| Renderer | Add `resources/android/<Name>Control.kt` and `<Name>Renderer.kt` only when core cannot meet the contract. Compile production sources against current official APIs. |

Use a genuine Material 3 control when available. When Material has no named stock control, compose the intent from genuine Compose/Material primitives—for example Stepper as minus button, value, and plus button—without imitating iOS or drawing platform-neutral chrome.

## State Rules

- **Discrete:** emit once per user action; keep accepted visible state server-authoritative; reconcile every publication without echo or replay.
- **Focused text:** preserve the native editing buffer, focus, cursor, selection, and IME composition. Standard `live`, `blur`, and `debounce` modes control `@change`; reconciliation must not disturb keyboard or editing state.
- **Continuous:** update gestures on the UI thread; standard modifiers control change frequency and release behaviour.
- **Action/display:** use only the contract's standard `@press` or no event.

## Workflow

1. Write failing Kotlin behaviour, Compose semantics, and Paparazzi cases appropriate to the state class before implementation.
2. Prove an adapter maps every Firstlight prop/event/state. If it cannot, implement the minimum renderer, decode primitive Element Tree props, and use only the official SuperNative event seam.
3. Use NativePHP semantic theme tokens, Material-native typography/motion/state layers, and targets of at least 48 dp.
4. Run:

```bash
JAVA_HOME=<jdk-21> tests/android/gradlew -p tests/android testDebugUnitTest
bin/check-component <Name> --development
```

5. Verify TalkBack names, roles, values, states and order; Compose semantics; font scaling at `2.0`; high contrast; light/dark modes; rapid input; and state-class-specific reconciliation.
6. Prove `firstlightui/showcase` builds against the exact package commit and capture deterministic emulator evidence. Record a separate dated physical-device pass for release.

## Common Mistakes

- Treating “no single Material control” as failure when an idiomatic native composition exists.
- Copying Segmented's state machine or selected/error fixtures into unrelated controls.
- Creating wrapper files without proving an official primitive needs adaptation.
- Testing shims while excluding production renderers or real publication lookup.

## Stop Conditions

Stop when neither a genuine control nor idiomatic native composition fits, the official seam cannot express the contract, warnings remain, reconciliation harms native interaction, accessibility truncates meaning, the showcase host fails, an official dependency is unavailable, or required evidence is absent. Missing physical-device evidence blocks release, not development. Do not add WebViews, ad hoc bridges, generated-tree edits, or platform escape props.
