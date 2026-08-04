---
title: Firstlight documentation constitution
description: Binding rules for Firstlight public documentation, maintained specifications, generated LLM artefacts, and visual evidence.
status: current
sources:
  - Constitution.md
  - composer.json
  - nativephp.json
  - spec/designs/2026-08-04-firstlight-documentation-system-design.md
---

# Firstlight Documentation Constitution

## Authority and scope

This constitution governs Firstlight documentation, maintained specifications, generated LLM artefacts, and documentation evidence. The root `Constitution.md` remains the engineering authority and outranks this document. Current code, tests, `composer.json`, and `nativephp.json` are the factual evidence for technical claims.

## Public and maintained boundaries

`docs/` contains published, developer-facing documentation. `spec/` contains current maintainer and agent contracts that remain in the repository but are not confidential. Public pages may link to public source files, but must not depend on repository-only specifications to be understandable.

## Audience

Every public page declares `audience: consumer`. Maintained specifications declare the people or agents that act on them. Write for a developer who knows Laravel and NativePHP without assuming knowledge of Firstlight internals.

## Current-only version policy

The documentation describes only the currently supported package. Do not retain version branches, stale alternatives, or promises about future support in current pages. Put migration guidance in release notes when an intentional breaking change requires it.

## Voice and terminology

Use direct, precise language and complete examples. Name Firstlight UI, NativePHP, EDGE, SwiftUI, Jetpack Compose, and Material 3 accurately. Use the public identities `firstlightui/nativephp`, `FirstlightUI`, `dev.firstlightui`, `github.com/firstlightui/nativephp`, and `firstlightui.dev`. Do not use superseded organisation names or clinical terminology.

## Page metadata

Every indexed public page begins with constrained YAML-style frontmatter containing `title`, `description`, `type`, `audience`, and `sources`. Every maintained specification contains `title`, `description`, `status: current`, `audience`, and `sources`. Sources are repository-relative paths that substantiate the page.

## Diataxis page contracts

- Tutorials lead a learner through one complete working result and explain only what is necessary for that result.
- How-to guides solve a defined task through direct, ordered actions.
- Reference pages state the exact public contract in a structure suited to retrieval.
- Explanation pages build the mental model behind behaviour and trade-offs without becoming an implementation procedure.

Each page declares exactly one of `tutorial`, `how-to`, `reference`, or `explanation` as its `type`.

## Component reference contract

Every public component reference documents purpose, a complete example, props, events, accepted value types, option or child structure, state timing, disabled behaviour, accessibility, validation and failure behaviour, platform behaviour, compatibility, and the screenshot matrix. Identical authored examples must work on iOS and Android.

## Code example evidence

Examples use only current public APIs and must agree with the component, element, manifest, renderers, and contract tests named in `sources`. Do not invent registration steps, implicit defaults, unsupported modifiers, platform capabilities, or output. Prefer one executable example over several fragments.

## Screenshot contract

Every visual component has iOS and Android screenshots in both light and dark modes, captured from a stable authored route in the sibling showcase. Captures use explicitly named simulator and emulator IDs and never fall back to physical devices. Release evidence requires clean package and showcase revisions, a passing focused showcase test, atomic output publication, restored device appearance, and explicit visual approval. Development checks may report missing images or review evidence as deferred gaps.

## LLM artefacts

Root `llms.txt` and `llms-full.txt` are generated files. Their only content source is the ordered set of public pages linked by `docs/index.md`; maintained specifications, designs, plans, and unindexed Markdown are excluded. Generation must be deterministic and atomic. Manual edits to either artefact are prohibited.

## README contract

`README.md` is a compact package landing page. It contains the package purpose, public links, installation command, one minimal example, status, links to the root constitution and public documentation index, and licence. Detailed contracts belong in `docs/`.

## Change and audit rules

Code, API, renderer, compatibility, workflow, or evidence changes must be checked for documentation drift. New component work includes public reference documentation and the screenshot manifest entry. A release audit verifies sources, examples, links, generated artefacts, the complete screenshot matrix, and visual review evidence. Tests are required for executable documentation tooling or a concrete regression risk, not for prose or static Markdown solely to increase coverage.

## Historical records

Current maintained knowledge is indexed from `spec/index.md`. Dated records under `spec/designs/` and `spec/plans/` are historical, non-maintained context and must not be presented as current instructions.

## Prohibited content

Do not publish confidential material, secrets, unsupported claims, future commitments, stale versions, hidden platform divergence, legacy identities, manual edits to generated artefacts, screenshots from unstable fixtures, or evidence that cannot be traced to current source. Do not describe a component as release-ready while required platform, accessibility, test, showcase, screenshot, or review evidence is absent.
