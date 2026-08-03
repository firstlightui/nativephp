---
name: firstlight-ios-component
description: Use when implementing or reviewing the iOS half of a Firstlight native control.
---

# Build a Firstlight iOS Component

## Overview

Wrap the genuine Apple control and keep the renderer a thin SuperNative adapter. PHP remains server-authoritative for visible state.

## Inputs and Files

- Read `Constitution.md`, `nativephp.json`, and the component's PHP contract.
- Implement `resources/ios/<Name>Control.swift` and `resources/ios/<Name>Renderer.swift`.
- Add behavior and snapshot coverage under `tests/ios/`.

## Workflow

1. Write failing XCTest contract and snapshot cases before implementation. Cover null, selected, disabled, error, light, dark, and accessibility states.
2. Use the genuine Apple UIKit or SwiftUI control. Do not recreate an iPhone-looking control from generic shapes.
3. Decode only primitive Element Tree props in the SuperNative renderer. Route semantic events through the official bridge.
4. On touch, emit once but keep visible selection server-authoritative. Reconcile accepted PHP publications without replaying events. A rejected value must never remain displayed; repeated rejected attempts must remain possible.
5. Use semantic NativePHP theme tokens. Verify contrast and at least 44-point targets.
6. Run:

```bash
xcodebuild -scheme FirstlightIOSControls -destination 'platform=iOS Simulator,id=<fixed-id>' test
bin/check-component <Name> --development
```

7. Verify VoiceOver labels, values, traits, and order; Dynamic Type at a large accessibility size; Increased Contrast; Reduced Motion; light and dark modes; and rapid taps.
8. Capture deterministic simulator snapshots. Record a separate physical device pass with model, iOS version, package commit, tester, and date.

## Evidence

Report exact test counts, snapshot paths, the consumer build result, accessibility findings, and unresolved warnings. Simulator evidence does not replace the physical device row.

## Stop Conditions

Stop when no genuine Apple control fits, the official SuperNative seam cannot express the contract, Swift warnings remain, accessibility truncates meaning, the consumer host does not compile, or physical-device evidence is absent for release. Do not add web-rendered or ad hoc bridge substitutes.
