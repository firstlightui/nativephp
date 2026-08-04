---
name: firstlight-ios-component
description: Use when implementing or reviewing the iOS expression of a public Firstlight control.
---

# Build a Firstlight iOS Component

## Overview

Express the contract with genuine Apple controls or idiomatic native composition. Reuse an adequate official control first.

## Canonical Identity and Authorities

- Package: `firstlightui/nativephp`
- Showcase: `firstlightui/showcase`
- Swift scheme: `FirstlightIOSControls`

Read `Constitution.md`, the catalogue design, component contract, manifests, current NativePHP source/docs, and Apple docs. For icons, read `spec/reference/icons.md`. Manifests own renderer identifiers.

## Implementation Decision

| Path | Requirement |
| --- | --- |
| Adapter | Keep the Firstlight tag while reusing or adapting an adequate official primitive. |
| Renderer | Add `resources/ios/<Name>Control.swift` and `<Name>Renderer.swift` only when core cannot meet the contract. |

Use genuine UIKit/SwiftUI controls. Otherwise compose native primitives; do not imitate Android or draw neutral chrome.

PHP resolves icon overrides through `IosSymbol`. Swift consumes resolved props, never authored `-ios` / `-android` choices. Decorative images hide from VoiceOver; actions use explicit labels and 44-point targets.

## State Rules

- **Discrete:** emit once; visible state remains server-authoritative; reconcile without echo.
- **Focused text:** preserve buffer, focus, cursor, selection, and marked text. `live`, `blur`, and `debounce` control `@change`; reconciliation must not jump keyboard or cursor.
- **Continuous:** update the gesture on the UI thread; standard modifiers control change frequency and release behaviour.
- **Action/display:** use only the contract's standard `@press` or no event.

## Workflow

1. Write failing XCTest behaviour, accessibility, and snapshot cases appropriate to the state class before implementation.
2. Prove adapter mapping; otherwise implement the minimum renderer through the official SuperNative seam.
3. Use NativePHP semantic theme tokens, Apple-native typography and motion, and targets of at least 44 points.
4. Run:

```bash
xcodebuild -scheme FirstlightIOSControls -destination 'platform=iOS Simulator,id=<fixed-id>' test
bin/check-component <Name> --development
```

5. Verify VoiceOver, Dynamic Type, Increased Contrast, Reduced Motion, themes, rapid input, and reconciliation.
6. Build `firstlightui/showcase` at the exact commit; capture simulator evidence and a dated physical device pass for release.

## Common Mistakes

- Copying Segmented state into unrelated controls.
- Creating wrappers without proving adaptation is needed.
- Treating snapshots as interaction evidence.
- Excluding production renderers from tests.

## Stop Conditions

Stop for no idiomatic composition, inadequate seams, warnings, harmful reconciliation, inaccessible output, failed showcase, or missing evidence. Physical device evidence blocks release, not development. Never add WebViews, ad hoc bridges, generated-tree edits, or escape props.
