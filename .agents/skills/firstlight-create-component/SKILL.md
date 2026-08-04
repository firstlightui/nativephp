---
name: firstlight-create-component
description: Use when adding or changing a public cross-platform control in the Firstlight NativePHP package.
---

# Create a Firstlight Component

## Overview

Define one semantic public contract, then satisfy it through official SuperNative seams. A Firstlight component may adapt an adequate `nativephp/mobile-ui` primitive or add paired renderers; either path still exposes a documented `<firstlight:...>` API.

## Canonical Identity

- Package: `firstlightui/nativephp`
- Showcase: `firstlightui/showcase`
- PHP namespace: `FirstlightUI`
- Blade prefix: `firstlight`
- Plugin namespace: `Firstlight`

Read renderer class/package identifiers from `nativephp.json` and `Package.swift`; do not derive them from Composer names or copy transitional identifiers.

## Required Authorities

Read `Constitution.md`, `spec/designs/2026-08-04-firstlight-alpha-component-system-design.md`, the closest implemented contract, and current official NativePHP plus platform documentation. Resolve public API ambiguity before scaffolding.

## State Classification

| Kind | Required model |
| --- | --- |
| Discrete choice | Stable values; visible state remains server-authoritative. |
| Focused text | Native editing buffer; standard `live`, `blur`, and `debounce` sync modes. |
| Continuous value | Native UI-thread gesture; standard binding modifiers control `@change` frequency. |
| Action or display | No invented model or change event. |

Use standard `@change`, `@submit`, and `@press` events. Do not invent a parallel bridge or binding vocabulary.

## Workflow

1. Write the semantic contract: purpose, values, null/empty behaviour, events, disabled/loading/error behaviour, field metadata, accessibility, and platform expression.
2. Audit the corresponding `nativephp/mobile-ui` primitive. Choose:
   - **Adapter:** retain the public Firstlight tag while reusing or thinly adapting an adequate official primitive.
   - **Renderer:** add a Firstlight EDGE element and paired native renderers only when the official primitive cannot meet the contract.
3. Write failing Pest 5 contract examples. For a renderer path, run `bin/scaffold-component Name`; stop rather than overwrite existing files. For an adapter path, add only the minimum tag/element adaptation the failing contract requires.
4. **REQUIRED SUB-SKILL:** Use `firstlight-ios-component` for Apple implementation or adapter evidence.
5. **REQUIRED SUB-SKILL:** Use `firstlight-android-component` for Material implementation or adapter evidence.
6. Add `docs/components/<slug>.md`, complete `firstlightui/showcase` fixtures, paired screenshots, and documented states.
7. **REQUIRED SUB-SKILL:** Use `firstlight-review-component` before calling the component complete.

## Evidence

Record the exact `firstlightui/nativephp` commit, adapter/renderer decision, focused and full tests, both consumer builds, screenshots, accessibility checks, device results, and unresolved upstream prerequisites.

## Common Mistakes

- Replacing an alpha-catalogue component with a `<native:...>` recipe instead of retaining its Firstlight API.
- Copying Segmented's state machine into text, continuous, action, or display controls.
- Scaffolding paired renderers before auditing the official primitive.
- Treating a custom shape as native when an idiomatic platform composition is required.

## Stop Conditions

Stop when the semantic contract is unresolved, an official seam cannot express it, platform capability or quality is unequal, tests or consumer builds fail, accessibility evidence is incomplete, an upstream prerequisite is unreleased, or constitutional review fails. Do not publish, tag, edit generated native trees, or weaken the release gate.
