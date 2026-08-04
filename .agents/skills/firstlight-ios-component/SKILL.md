---
name: firstlight-ios-component
description: Use when implementing or reviewing the iOS expression of a public Firstlight control.
---

# Build a Firstlight iOS Component

## Overview

Express the shared contract with genuine Apple controls or an idiomatic composition of native primitives. Reuse an adequate official NativePHP control before adding a custom renderer.

## Canonical Identity and Authorities

- Package: `firstlightui/nativephp`
- Showcase: `firstlightui/showcase`
- Swift scheme: `FirstlightIOSControls`

Read `Constitution.md`, `spec/designs/2026-08-04-firstlight-alpha-component-system-design.md`, the component contract, `nativephp.json`, `Package.swift`, current NativePHP source/docs, and current Apple documentation. Manifest and Swift package files—not Composer-name inference—own renderer identifiers.

## Implementation Decision

| Path | Requirement |
| --- | --- |
| Adapter | Keep the public Firstlight tag while reusing or thinly adapting an adequate official primitive. Do not create Swift files merely to satisfy a template. |
| Renderer | Add `resources/ios/<Name>Control.swift` and `<Name>Renderer.swift` only when core cannot meet the contract. Compile the production renderer against current official APIs. |

Use a genuine UIKit/SwiftUI control when available. When Apple has no named stock control, compose the intent from native SwiftUI/UIKit primitives; do not imitate Android or draw platform-neutral chrome.

## State Rules

- **Discrete:** emit once per user action; keep accepted visible state server-authoritative; reconcile every publication without echo or replay.
- **Focused text:** preserve the native editing buffer, focus, cursor, selection, and marked-text composition. Standard `live`, `blur`, and `debounce` modes control `@change`; server reconciliation must not cause keyboard or cursor jumps.
- **Continuous:** update the gesture on the UI thread; standard modifiers control change frequency and release behaviour.
- **Action/display:** use only the contract's standard `@press` or no event.

## Workflow

1. Write failing XCTest behaviour, accessibility, and snapshot cases appropriate to the state class before implementation.
2. Prove an adapter maps every Firstlight prop/event/state. If it cannot, implement the minimum renderer, decode primitive Element Tree props, and use only the official SuperNative event seam.
3. Use NativePHP semantic theme tokens, Apple-native typography and motion, and targets of at least 44 points.
4. Run:

```bash
xcodebuild -scheme FirstlightIOSControls -destination 'platform=iOS Simulator,id=<fixed-id>' test
bin/check-component <Name> --development
```

5. Verify VoiceOver names, values, roles, states, order, Dynamic Type at accessibility sizes, Increased Contrast, Reduced Motion, light/dark modes, rapid input, and the state-class-specific reconciliation cases.
6. Prove `firstlightui/showcase` builds against the exact package commit and capture deterministic simulator evidence. Record a separate dated physical-device pass for release.

## Common Mistakes

- Copying Segmented's selection state machine into text or continuous controls.
- Creating wrapper files without proving an official primitive needs adaptation.
- Treating snapshots as evidence for focus, animation, VoiceOver, or touch behaviour.
- Testing a shim while excluding the production renderer from compilation.

## Stop Conditions

Stop when neither a genuine control nor idiomatic native composition fits, the official seam cannot express the contract, warnings remain, state reconciliation harms native interaction, accessibility truncates meaning, the showcase host fails, or required evidence is absent. Missing physical-device evidence blocks release, not development. Do not add WebViews, ad hoc bridges, generated-tree edits, or platform escape props.
