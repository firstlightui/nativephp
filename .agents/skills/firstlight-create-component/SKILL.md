---
name: firstlight-create-component
description: Use when adding or changing a public cross-platform control in the Firstlight NativePHP package.
---

# Create a Firstlight Component

## Overview

Use official SuperNative seams: adapt `nativephp/mobile-ui` or add paired renderers, exposing `<firstlight:...>`.

## Canonical Identity

- Package: `firstlightui/nativephp`
- Showcase: `firstlightui/showcase`
- PHP namespace: `FirstlightUI`
- Blade prefix: `firstlight`
- Plugin namespace: `Firstlight`

## Required Authorities

Read `Constitution.md`, `spec/designs/2026-08-04-firstlight-alpha-component-system-design.md`, [Catalogue boundary](../../../spec/reference/catalogue-boundary.md), the closest contract, and official NativePHP/platform documentation. For icons, read `spec/reference/icons.md`. Resolve API ambiguity before scaffolding. Laravel-shaped PHP services are not catalogue controls.

## State Classification

| Kind | Required model |
| --- | --- |
| Discrete choice | Stable values; visible state remains server-authoritative. |
| Focused text | Native editing buffer; standard `live`, `blur`, and `debounce` sync modes. |
| Continuous value | Native UI-thread gesture; standard binding modifiers control `@change` frequency. |
| Action or display | No invented model or change event. |

Use standard `@change`, `@submit`, and `@press`, not new bridge vocabulary.

## Icon Contract

Retain each base icon prop with `-ios` and `-android` overrides. Document kebab-case Blade names, accept camelCase aliases, and resolve `(name, IosSymbol|string|null, AndroidSymbol|string|null)` through `IconResolver`. Preserve Android variants and shared fallback. Decorative icons are silent; icon-only actions require the established label prop, such as `trailing-a11y-label`. Semantic affordances own their icons.

## Workflow

1. Specify purpose, values, empty behaviour, events, states, metadata, accessibility, and platform expression.
2. Audit the `nativephp/mobile-ui` primitive. Choose:
   - **Adapter:** retain the Firstlight tag; reuse or thinly adapt an adequate official primitive.
   - **Renderer:** add an EDGE element and paired renderers only when the primitive cannot meet the contract.
3. Write failing Pest 5 contracts. Renderer: run `bin/scaffold-component Name`; never overwrite. Adapter: implement only the failing contract.
4. **REQUIRED SUB-SKILL:** Use `firstlight-ios-component` for Apple implementation or adapter evidence.
5. **REQUIRED SUB-SKILL:** Use `firstlight-android-component` for Material implementation or adapter evidence.
6. Add `docs/components/<slug>.md`. In the sibling showcase, inspect status and preserve adjacent work. Extend `ShowcaseScreen`, register the exact tag and summary in `ShowcaseHome`, and add `/captures/<slug>` separately. Keep `NATIVEPHP_START_URL=/`; navigation and appearance belong to `ShowcaseLayout`. Run focused and full consumer tests without devices. Any simulator or emulator action requires explicit permission for the exact target.
7. **REQUIRED SUB-SKILL:** Run `firstlight-review-component` before completion.

## Evidence

Record commit, path decision, tests, consumer builds, screenshots, accessibility, devices, and prerequisites.

## Common Mistakes

- Replacing a Firstlight API with a `<native:...>` recipe.
- Copying Segmented state into unrelated controls.
- Scaffolding before auditing the official primitive.
- Inventing `ios-icon` or `android-icon` instead of applying `spec/reference/icons.md`.
- Replacing showcase chrome/start route or concurrent fixtures.
- Adding layout primitives, navigation chrome, input masks, or a Filament-style schema builder to the Firstlight catalogue.
- Making validation, authorization, or pagination native widgets instead of PHP extensions over existing `error`, `loading`, list, confirmation, and Feedback APIs.

## Stop Conditions

Stop for unresolved contracts, inadequate official seams, unequal platforms, failed tests/builds, missing accessibility, unreleased prerequisites, or failed review. Never publish, tag, edit generated trees, or weaken gates.
