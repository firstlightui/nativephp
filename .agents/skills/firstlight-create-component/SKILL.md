---
name: firstlight-create-component
description: Use when adding or changing a public cross-platform control in the Firstlight NativePHP package.
---

# Create a Firstlight Component

## Overview

Define one public contract through official SuperNative seams. Adapt an adequate `nativephp/mobile-ui` primitive or add paired renderers; both expose `<firstlight:...>`.

## Canonical Identity

- Package: `firstlightui/nativephp`
- Showcase: `firstlightui/showcase`
- PHP namespace: `FirstlightUI`
- Blade prefix: `firstlight`
- Plugin namespace: `Firstlight`

## Required Authorities

Read `Constitution.md`, `spec/designs/2026-08-04-firstlight-alpha-component-system-design.md`, the closest implemented contract, and current official NativePHP plus platform documentation. When icons apply, also read `spec/reference/icons.md`. Resolve public API ambiguity before scaffolding.

## State Classification

| Kind | Required model |
| --- | --- |
| Discrete choice | Stable values; visible state remains server-authoritative. |
| Focused text | Native editing buffer; standard `live`, `blur`, and `debounce` sync modes. |
| Continuous value | Native UI-thread gesture; standard binding modifiers control `@change` frequency. |
| Action or display | No invented model or change event. |

Use standard `@change`, `@submit`, and `@press`; do not invent bridge vocabulary.

## Icon Contract

Retain each base icon prop with `-ios` and `-android` overrides. Document kebab-case Blade names, accept camelCase aliases, and resolve `(name, IosSymbol|string|null, AndroidSymbol|string|null)` through `IconResolver`. Preserve Android variants and shared fallback. Decorative icons are silent; icon-only actions require the established label prop, such as `trailing-a11y-label`. Semantic affordances own their icons.

## Workflow

1. Specify purpose, values, empty behaviour, events, states, metadata, accessibility, and platform expression.
2. Audit the corresponding `nativephp/mobile-ui` primitive. Choose:
   - **Adapter:** retain the public Firstlight tag while reusing or thinly adapting an adequate official primitive.
   - **Renderer:** add a Firstlight EDGE element and paired native renderers only when the official primitive cannot meet the contract.
3. Write failing Pest 5 contracts. For a renderer, run `bin/scaffold-component Name`; never overwrite. For an adapter, add only what the failing contract requires.
4. **REQUIRED SUB-SKILL:** Use `firstlight-ios-component` for Apple implementation or adapter evidence.
5. **REQUIRED SUB-SKILL:** Use `firstlight-android-component` for Material implementation or adapter evidence.
6. Add `docs/components/<slug>.md`, complete `firstlightui/showcase` fixtures, paired screenshots, and documented states.
7. **REQUIRED SUB-SKILL:** Use `firstlight-review-component` before calling the component complete.

## Evidence

Record the commit, path decision, tests, consumer builds, screenshots, accessibility, devices, and upstream prerequisites.

## Common Mistakes

- Replacing a Firstlight API with a `<native:...>` recipe.
- Copying Segmented state into unrelated controls.
- Scaffolding before auditing the official primitive.
- Inventing `ios-icon`, `android-icon`, or another icon vocabulary instead of applying `spec/reference/icons.md`.

## Stop Conditions

Stop for unresolved contracts, inadequate official seams, unequal platforms, failed tests/builds, missing accessibility, unreleased prerequisites, or failed review. Never publish, tag, edit generated trees, or weaken gates.
