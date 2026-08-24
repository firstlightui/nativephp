---
title: Firstlight repository layout
description: Path-to-purpose reference for package source, native implementations, tests, documentation, tooling, skills, evidence, and the sibling showcase.
status: current
audience: maintainer
sources:
  - composer.json
  - nativephp.json
  - Package.swift
  - docs/index.md
  - spec/index.md
  - spec/screenshots.json
  - bin/check-component
  - bin/check-docs
  - .agents/skills/firstlight-create-component/SKILL.md
  - .agents/skills/firstlight-docs-write/SKILL.md
---

# Firstlight Repository Layout

## Package checkout

| Path | Responsibility | Authored or generated |
| --- | --- | --- |
| `Constitution.md` | Highest project authority for component design, parity, accessibility, evidence, and release | Authored and deliberately amended |
| `composer.json` | Composer identity, PHP dependencies, Laravel provider, and package scripts | Authored |
| `nativephp.json` | NativePHP plugin identity, component registration, renderer identifiers, platforms, and minimum OS versions | Authored; registration authority |
| `Package.swift` | Swift package, scheme, iOS floor, source target, and iOS test target | Authored; Apple build authority |
| `src/Components/` | Public NativePHP Blade component adapters and Element Tree type selection | Authored |
| `src/Elements/` | Public value validation, primitive props, callback registration, and Element Tree contracts | Authored |
| `src/Concerns/` | Screen-level PHP extensions such as Laravel field validation, form submission, and action authorization | Authored |
| `src/Validation/` | MessageBag request-scope binding onto field `error` slots | Authored |
| `src/Authorization/` | Gate evaluation used by action authorization | Authored |
| `src/NativeComponent.php` | NativePHP screen base that includes `ValidatesFields`, `SubmitsForms`, and `AuthorizesActions` | Authored |
| `src/Support/` | Shared normalization and callback helpers used by element contracts | Authored |
| `src/Exceptions/` | Actionable public-contract diagnostics | Authored |
| `src/FirstlightServiceProvider.php` | Laravel/Blade package boot integration | Authored |
| `src/FirstlightTagPrecompiler.php` | Mapping from `<firstlight:...>` authoring to NativePHP native components | Authored |
| `resources/ios/` | Production Swift controls, renderer adapters, and package-local compile shims | Authored production source except explicitly named test shims |
| `resources/android/` | Production Kotlin controls and renderer adapters | Authored production source |
| `tests/Feature/` | PHP element, manifest, tooling, documentation, and cross-file contract tests | Authored tests |
| `tests/Unit/` | Narrow PHP support behaviour | Authored tests |
| `tests/ios/` | XCTest behaviour and snapshot coverage for production Swift sources | Authored tests and reviewed snapshots |
| `tests/android/` | Isolated Gradle project, Kotlin behaviour tests, Compose/Paparazzi tests, and reviewed snapshots | Authored tests, build fixture, and snapshots |
| `tests/Evals/` | Focused model-based checks for consequential repository-skill decisions | Authored evaluation tests |
| `bin/scaffold-component` | Non-overwriting renderer-path skeleton generator | Authored executable tooling |
| `bin/check-component` | Deterministic component structural and evidence gate | Authored executable tooling |
| `bin/build-docs-artifacts` | Atomic generator for root LLM artefacts | Authored executable tooling |
| `bin/check-docs` | Documentation, source, link, manifest, screenshot, skill, and generated-output validator | Authored executable tooling |
| `bin/capture-doc-screenshots` | Guarded iOS/Android light/dark capture entrypoint | Authored executable tooling |
| `bin/capture-doc-screenshot-batch` | Guarded appearance-batched capture for multiple component matrices with one build per platform | Authored executable tooling |
| `bin/support/` | PHP implementations shared by documentation commands | Authored executable support code |
| `docs/` | Published, current-only developer documentation and approved screenshot files | Authored Markdown and reviewed capture output |
| `docs/index.md` | Public navigation and the only page order used to generate LLM artefacts | Authored index |
| `spec/` | Current maintainer and agent contracts plus explicitly historical records | Authored Markdown and JSON contracts |
| `spec/index.md` | Index of current maintained specifications | Authored index |
| `spec/designs/` | Dated historical design decisions; not current instructions | Historical authored records |
| `spec/plans/` | Dated historical implementation plans; not current instructions | Historical authored records |
| `spec/reviews/` | Factual dated release-review evidence when it exists | Authored evidence; never inferred |
| `spec/documentation.json` | Canonical documentation identity and current-only policy | Authored JSON contract |
| `spec/screenshots.json` | Component capture routes, focused tests, and four output paths | Authored JSON contract |
| `.agents/skills/` | Repository-owned component, platform, review, documentation, and screenshot workflows | Authored operational contracts |
| `llms.txt`, `llms-full.txt` | Public LLM artefacts generated exclusively from `docs/index.md` pages | Generated; never edit manually |

Build output and dependency directories such as `.build/` and `vendor/` are local products. They are not sources of package truth and should not be indexed as documentation authority.

## Sibling showcase checkout

The canonical showcase is `firstlightui/showcase`; in the adjacent local layout it is `../firstlight-showcase`.

| Showcase path | Responsibility |
| --- | --- |
| `app/NativeComponents/` | Application state and NativePHP route components used to dogfood Firstlight |
| `app/NativeComponents/Captures/` | Stable documentation-capture component state |
| `resources/views/native/` | Authored consumer examples and capture views |
| `routes/native.php` | Native routes, including `/captures/<slug>` fixtures |
| `tests/Feature/` | Consumer tree, accessibility, and fixture contracts |
| `composer.json`, `composer.lock` | Real package installation and the exact installed path-package reference |
| `nativephp/ios`, `nativephp/android` | Generated host projects used for builds and runs; never the durable source of a fix |

The showcase must not define package renderer code or a competing public API. Package work must not move application-only fixture state into the package.

## Placement rules

- Put consumer guidance in `docs/` and make it independently understandable.
- Put current maintainer or agent contracts in an indexed `spec/` page.
- Put a dated design or plan in its historical directory and do not present it as current truth.
- Treat Laravel-shaped behaviour as a PHP SuperNative extension unless [Catalogue boundary](catalogue-boundary.md) requires a new control.
- Add a component's public page to `docs/index.md`, its registration to `nativephp.json`, and its capture contract to `spec/screenshots.json`.
- Add or update a repository skill only when a repeated operational workflow has a stable boundary.
- Do not hand-edit LLM artefacts, generated native hosts, dependency trees, build directories, or captured images.
