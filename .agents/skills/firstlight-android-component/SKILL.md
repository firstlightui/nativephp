---
name: firstlight-android-component
description: Use when implementing or reviewing the Android expression of a public Firstlight control.
---

# Build a Firstlight Android Component

## Overview

Express the contract with genuine Material 3 controls or idiomatic Compose. Reuse an adequate official control first.

## Canonical Identity and Authorities

- Package: `firstlightui/nativephp`
- Showcase: `firstlightui/showcase`
- Android minimum: API 29

Read `Constitution.md`, the catalogue design, component contract, manifests, current NativePHP source/docs, and Material 3 docs. For icons, read `spec/reference/icons.md`. Manifests own renderer identifiers.

## Implementation Decision

| Path | Requirement |
| --- | --- |
| Adapter | Keep the Firstlight tag while reusing or adapting an adequate official primitive. |
| Renderer | Add `resources/android/<Name>Control.kt` and `<Name>Renderer.kt` only when core cannot meet the contract. |

Use genuine Material 3 controls. Otherwise compose Material primitives without imitating iOS or drawing neutral chrome.

PHP resolves overrides through `AndroidSymbol`, preserving `filled` or `outlined`. Compose consumes resolved icon and variant props. Decorative icons hide from TalkBack; actions use explicit labels and 48-dp targets.

## State Rules

- **Discrete:** emit once; visible state remains server-authoritative; reconcile without echo.
- **Focused text:** preserve buffer, focus, cursor, selection, and IME composition. `live`, `blur`, and `debounce` control `@change`; reconciliation must not disturb editing.
- **Continuous:** update gestures on the UI thread; standard modifiers control change frequency and release behaviour.
- **Action/display:** use only the contract's standard `@press` or no event.

## Workflow

1. Write failing Kotlin behaviour, Compose semantics, and Paparazzi cases appropriate to the state class before implementation.
2. Prove adapter mapping; otherwise implement the minimum renderer through the official SuperNative seam.
3. Use NativePHP semantic theme tokens, Material-native typography/motion/state layers, and targets of at least 48 dp.
4. Run:

```bash
JAVA_HOME=<jdk-21> tests/android/gradlew -p tests/android testDebugUnitTest
bin/check-component <Name> --development
```

5. Verify TalkBack, Compose semantics, font scaling at `2.0`, contrast, themes, rapid input, and reconciliation.
6. Build `firstlightui/showcase` at the exact commit; capture emulator evidence and a dated physical device pass for release.

## Common Mistakes

- Treating composition as failure.
- Copying Segmented state into unrelated controls.
- Creating wrappers without proving adaptation is needed.
- Excluding production renderers or publication lookup from tests.

## Stop Conditions

Stop for no idiomatic composition, inadequate seams, warnings, harmful reconciliation, an unavailable publication fix, inaccessible output, failed showcase/dependency, or missing evidence. Physical device evidence blocks release, not development. Never add WebViews, ad hoc bridges, generated-tree edits, or escape props.
