---
title: Firstlight package architecture
description: Current boundaries and lifecycle from a Firstlight Blade tag through the Element Tree to paired native renderers and semantic events.
status: current
audience: maintainer
sources:
  - Constitution.md
  - composer.json
  - nativephp.json
  - Package.swift
  - src/FirstlightServiceProvider.php
  - src/FirstlightTagPrecompiler.php
  - src/Concerns/ValidatesFields.php
  - src/Validation/FieldErrorBinder.php
  - spec/reference/field-validation.md
  - spec/reference/catalogue-boundary.md
  - src/Components/Button.php
  - src/Elements/Button.php
  - src/Components/Progress.php
  - src/Elements/Progress.php
  - src/Components/Segmented.php
  - src/Elements/Segmented.php
  - resources/ios/SegmentedRenderer.swift
  - resources/android/SegmentedRenderer.kt
---

# Firstlight Package Architecture

## Boundary

Firstlight is a NativePHP UI plugin, not an application renderer or a web-to-native translation layer. The package owns the public `<firstlight:...>` API, its EDGE element contract, and any native implementation that cannot be expressed by an adequate official `nativephp/mobile-ui` primitive. NativePHP owns compilation, the Element Tree, host integration, tree publication, and the official event seams.

The canonical identities are:

- Composer package: `firstlightui/nativephp`
- PHP namespace: `FirstlightUI`
- Blade prefix: `firstlight`
- plugin namespace: `Firstlight`
- Element Tree types: `firstlight.<slug>`

Read component and renderer identifiers from `nativephp.json`. Do not infer them from the Composer name or copy an identifier from generated host code.

## Authored tag to native control

The current renderer-backed path is:

1. An application authors a self-closing Firstlight tag such as `<firstlight:segmented>` in an EDGE view.
2. `FirstlightServiceProvider` registers `FirstlightTagPrecompiler` with Blade. While native tag compilation is active, the precompiler hands the tag to NativePHP's native precompiler and maps it to the package's native Blade component.
3. The class under `src/Components/` identifies the public Element Tree type. `Segmented` publishes `firstlight.segmented`.
4. `nativephp.json` binds that type to the PHP element class and the iOS and Android renderer identifiers.
5. The class under `src/Elements/` validates authored values, normalizes them to primitive props, registers semantic callbacks, and publishes the node through the official Element Tree.
6. The host resolves the renderer declared in the manifest. The Swift or Kotlin renderer decodes primitive node props, applies NativePHP semantic theme tokens, and renders a genuine platform control or idiomatic native composition.
7. User interaction travels through the official NativePHP event seam. PHP accepts or rejects the proposed change and publishes the next tree.
8. The native renderer finds its node in that publication and reconciles visible state without emitting another user event.

An adapter-backed component keeps the same public Firstlight tag and `firstlight.<slug>` Element Tree type but delegates to an adequate official primitive. Its manifest entry identifies the official package and type, while its renderer identifiers match that dependency's manifest. This gives consumers a coherent Firstlight catalogue without duplicating mature native code or leaking a second public API. Button and Progress are current adapter-backed examples.

If a later durable, cross-platform adapter requirement cannot be expressed by the official primitive, Firstlight can replace the delegated renderer identifiers with package-owned renderers without changing consumer markup. Platform-only novelty or visual preference does not justify that migration.

## Element contract

Element props are a serialization boundary. Publish stable primitives and lists that both renderers can decode; do not expose SwiftUI, UIKit, Compose, or Material implementation types.

Segmented demonstrates the current boundary:

- public string or integer option values are normalized to wire strings;
- `value_type` preserves the public value type;
- `has_selection` distinguishes `null` from an empty string;
- option labels, values, and enabled flags are parallel lists;
- field metadata and accessibility props remain semantic;
- string changes use the select-change callback path;
- integer choices use registered per-option callbacks so PHP receives the original integer.

Validation belongs before publication. Unsupported types, mixed value types, duplicates, malformed options, and mismatched selected values fail with actionable PHP exceptions rather than becoming renderer-specific behaviour.

Laravel user-input validation is a separate PHP extension. `ValidatesFields` and `FieldErrorBinder` publish `MessageBag` text into existing field `error` slots; they do not add a native rule engine. See [Field validation](../reference/field-validation.md). Catalogue membership versus PHP extensions is defined in [Catalogue boundary](../reference/catalogue-boundary.md).

## State ownership

The public contract determines the state model. Discrete controls such as Segmented remain server-authoritative: a tap proposes a value, while the last PHP publication owns visible selection. A rejected change, programmatic update, or reset therefore reconciles from the tree without an echo event.

Focused text, continuous values, actions, and display-only controls have different native-state needs. Use the state classifications in the component skills; do not copy Segmented's selection state machine into every component.

## Platform expression

`resources/ios/` contains Swift production sources and `resources/android/` contains Kotlin production sources. Both implement the same semantic contract, but their control geometry, motion, state layers, and presentation remain native to their platform. `Package.swift`, `nativephp.json`, and Android build sources own platform names and compatibility floors.

Production sources may use only official NativePHP extension seams. A WebView, ad hoc JSON bridge, generated-host edit, or platform-specific public escape prop crosses the package boundary and is prohibited.

## Package and showcase

The sibling `firstlightui/showcase` repository is a real consumer. It installs the package through Composer, registers the plugin through normal NativePHP discovery, and owns authored application routes and state fixtures. It does not own package contracts or renderer source.

For evidence, the showcase must install the exact package commit under review. Generated `nativephp/ios` and `nativephp/android` hosts are build products: create them with NativePHP commands when required, never make generated-tree edits the durable fix.
