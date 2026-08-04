# Firstlight Documentation System Design

Date: 2026-08-04

Status: approved design

## Context

Firstlight UI is a developer package with a shared Laravel and EDGE API backed by genuine native iOS and Android controls. Its documentation must serve two distinct readers:

- package consumers building NativePHP applications;
- contributors maintaining Firstlight's PHP contract, native renderers, tests, showcase, and releases.

The package also needs first-class LLM artefacts and repository-owned skills that help agents document and work on Firstlight without duplicating facts that can drift.

Stream's documentation skills provide a useful lifecycle split, but their assumptions are consumer-app focused. Firstlight needs package-specific evidence: public API contracts, copyable examples, paired native implementations, compatibility constraints, accessibility, showcase fixtures, and iOS and Android screenshots in light and dark modes.

## Goals

1. Make repository Markdown the canonical documentation source.
2. Establish a documentation constitution distinct from the existing engineering constitution.
3. Support consumer and contributor documentation without mixing their concerns.
4. Generate current-only `llms.txt` and `llms-full.txt` artefacts deterministically.
5. Add focused skills for writing, updating, auditing, and capturing documentation screenshots.
6. Add a small `firstlight-development` entrypoint skill for agents working on the package.
7. Capture documentation screenshots from the installed package in the Firstlight showcase on both platforms and appearances.
8. Detect documentation drift mechanically where possible and require evidence-backed judgment where automation is insufficient.

## Non-goals

- Versioned documentation is deferred until the public API needs to be locked across releases.
- Building or hosting the documentation website is not part of this work.
- Purchasing or configuring domains is not part of this work.
- Renaming or transferring the GitHub repository is not part of this work.
- Renaming the Composer package is not part of this work.
- Documentation generation will not replace prose authoring with a YAML or JSON component schema.
- Screenshot tests do not replace runtime documentation screenshots from the showcase.

## Public identity

The intended canonical documentation URL is `https://firstlightui.dev`. `https://firstlightui.com` will redirect to the canonical `.dev` host. The intended GitHub organisation is `firstlightui`, but repository migration is a separate future change.

Until that migration occurs, generated configuration must describe the repository's actual current URL. It must not publish a future repository URL as if it already exists.

## Authority and precedence

Documentation follows this precedence order:

1. Current code, tests, `composer.json`, `nativephp.json`, and supported dependency constraints establish factual behaviour.
2. Root `Constitution.md` establishes product and engineering principles.
3. `docs/constitution.md` establishes documentation policy.
4. Canonical Markdown pages under `docs/` describe the package.
5. `README.md`, `llms.txt`, `llms-full.txt`, and repository skills consume or point to canonical documentation and must not establish conflicting contracts.

If code and tests disagree, documentation work stops and reports the ambiguity. If the two constitutions conflict, root `Constitution.md` wins.

## Canonical content model

The initial documentation tree is:

```text
docs/
  constitution.md
  config.json
  index.md
  getting-started/
  components/
  concepts/
  contributing/
  reference/
  screenshots/
    manifest.json
    <component>/
      ios-light.png
      ios-dark.png
      android-light.png
      android-dark.png
```

`docs/index.md` is the canonical navigation and generation order. A page absent from the index is not published and is reported as an orphan unless the constitution explicitly excludes it.

The constitution explicitly excludes `docs/superpowers/` from publication, orphan checks, and LLM generation. Files there are historical design specifications and implementation plans, not current package documentation.

The initial published page set is:

```text
docs/index.md
docs/getting-started/installation.md
docs/getting-started/first-component.md
docs/components/segmented.md
docs/concepts/supernative-components.md
docs/concepts/server-authoritative-state.md
docs/contributing/architecture.md
docs/contributing/adding-components.md
docs/contributing/testing.md
docs/contributing/showcase-and-screenshots.md
docs/reference/compatibility.md
```

`docs/config.json` contains output configuration, not package contracts. Its initial fields cover the site name, canonical documentation URL, actual repository URL, and current-only versioning mode.

Every canonical page begins with compact frontmatter:

```yaml
---
title: Segmented
description: Render a native segmented selection control.
type: reference
audience: consumer
sources:
  - src/Components/Segmented.php
  - src/Elements/Segmented.php
  - resources/ios/SegmentedControl.swift
  - resources/android/SegmentedControl.kt
---
```

Allowed `type` values are `tutorial`, `how-to`, `reference`, and `explanation`. Allowed `audience` values are `consumer`, `contributor`, and `both`. Every source path must exist and must materially support the page's claims.

`README.md` remains a concise repository landing page. It may repeat the minimum installation command, but detailed component contracts live only in canonical pages. Documentation checks validate its package name, compatibility summary, canonical links, and absence of a second detailed API reference.

## Documentation constitution

`docs/constitution.md` is written as binding instructions to future maintainers and agents. It includes the following rules.

### Audience and scope

- Write consumer pages for Laravel developers who may be new to NativePHP and SuperNative concepts.
- Write contributor pages for maintainers working across PHP, Swift, Kotlin, the showcase, tests, and releases.
- Keep contributor implementation detail out of consumer API pages unless it changes observable behaviour.
- Document the current supported release or default branch only.

### Voice and terminology

- Use direct Australian English, active voice, and present tense.
- Address the reader as `you` in procedural guidance.
- Preserve exact code identifiers, prop names, event names, commands, and platform terminology.
- Define Firstlight-specific and SuperNative terms on first use.
- Use one canonical term per concept.
- Avoid marketing filler, vague promises, unsupported superlatives, and context-dependent phrases such as "as above".

### Page design

- Give each page one Diataxis purpose.
- Use explicit headings that retain meaning when retrieved independently.
- Declare languages on every code fence.
- Use regular tables for API contracts.
- State prerequisites before steps and observable outcomes after steps.
- Use relative links between repository pages.
- Keep examples complete, copyable, and backed by a package test or showcase fixture.
- Do not document planned components or unreleased upstream fixes as available behaviour.

### Component reference contract

Every public visual component reference contains:

1. purpose and current availability;
2. a minimal PHP and Blade example;
3. props, events, accepted types, defaults, and invalid input;
4. state ownership and reconciliation behaviour;
5. null, disabled, missing-value, and failure behaviour where applicable;
6. accessibility semantics and requirements;
7. iOS implementation and platform-specific behaviour;
8. Android implementation and platform-specific behaviour;
9. iOS and Android screenshots in light and dark modes;
10. errors, troubleshooting, sources, and related links.

Behavioural parity is shared. Visual explanations and screenshots remain platform-specific and must not imply pixel parity.

### Screenshot contract

- Capture public component screenshots from deterministic fixtures in the sibling Firstlight showcase.
- Require the four-image iOS/Android by light/dark matrix for every public visual component.
- Use fixed simulator and emulator identifiers supplied explicitly to the capture command.
- Capture the installed package, not a hand-built approximation or package-unit-test fixture.
- Use stable authored data and states. Never capture transient loading, developer overlays, secrets, notifications, or accidental local data.
- Use predictable paths: `docs/screenshots/<component>/<platform>-<appearance>.png`.
- Use alt text that names the component, platform, appearance, and meaningful state.
- Refresh the matrix when renderer output, layout, theme behaviour, platform presentation, or showcase fixtures change materially.
- Require visual review of all four images before treating them as release evidence.

`docs/screenshots/manifest.json` maps each component to its showcase route, focused showcase test, and four expected output paths. Device identifiers remain invocation inputs because machine-specific targets do not belong in the repository.

## Generated LLM artefacts

`bin/build-docs-artifacts` generates both root artefacts from `docs/index.md`.

### `llms.txt`

The concise file contains:

- package name and one-sentence purpose;
- canonical documentation and repository URLs;
- current installation identifier;
- links and summaries for getting started, concepts, component references, compatibility, and contribution guidance;
- an explicit current-only statement.

### `llms-full.txt`

The full file concatenates canonical pages in index order. It excludes `docs/constitution.md`, generated files, historical design documents, and screenshots. Each page is preceded by a stable source-path boundary so a retrieved passage retains provenance.

Generated files contain a warning header. Generation is deterministic: identical canonical inputs produce byte-identical output. `bin/check-docs` fails when regeneration creates a diff.

## Skill architecture

Repository-owned skills live under `.agents/skills/` and include matching `agents/openai.yaml` metadata.

### `firstlight-docs-write`

Use when creating or expanding canonical Firstlight documentation.

1. Read both constitutions and the current documentation index.
2. Inventory the public PHP and EDGE API, both native implementations, tests, compatibility constraints, and showcase fixtures.
3. Propose new pages and source mappings before writing.
4. Write one approved page at a time using the appropriate page contract.
5. Require a tested or showcased basis for examples.
6. Invoke `firstlight-docs-screenshots` for new public visual component pages.
7. Update the index, build LLM artefacts, and run documentation checks.

### `firstlight-docs-update`

Use when code changes may affect existing documentation.

1. Accept a user-named change or Git range.
2. Map changed files to pages through `sources` metadata and semantic searches.
3. Detect ripple effects including renamed props, events, labels, state semantics, platform constraints, examples, accessibility, and compatibility.
4. Confirm the affected scope.
5. Update only changed material.
6. Invoke screenshot capture for visual or theme-affecting changes.
7. Update the index when necessary, regenerate LLM artefacts, and run documentation checks.
8. Check whether `firstlight-development` or component workflow skills also became stale.

### `firstlight-docs-audit`

Use when assessing documentation completeness, accuracy, or drift.

The audit is read-only by default. It reports:

- factual claims that conflict with code or tests;
- public components, props, events, or compatibility constraints missing documentation;
- iOS or Android claims without paired evidence;
- malformed metadata, missing sources, broken links, and orphan pages;
- README or LLM artefact drift;
- incomplete or stale screenshot matrices and missing showcase fixtures;
- documentation-constitution violations;
- stale instructions in `firstlight-development` or existing component skills.

Findings are ranked `critical`, `warning`, `gap`, or `informational` and cite exact evidence. Applying fixes requires a separate explicit request.

### `firstlight-docs-screenshots`

Use when creating, refreshing, or verifying documentation screenshots.

1. Read both constitutions, the component page, and `docs/screenshots/manifest.json`.
2. Verify a deterministic showcase fixture exists for each documented state.
3. Record package and showcase revisions and dirty state.
4. In release mode, require clean exact revisions. Development mode may proceed with dirty sources but must report them.
5. Refresh the package installed in the showcase and verify that installed sources match the requested package state.
6. Run the showcase's focused fixture contract tests.
7. Require explicit iOS Simulator UDID and Android emulator serial values and reject physical devices.
8. Launch the showcase directly into the fixture using current NativePHP v4 commands.
9. Set light appearance, wait for stable rendering, and capture each platform.
10. Repeat in dark appearance.
11. Restore the original target appearances.
12. Verify PNG type, dimensions, naming, and non-identical light and dark files.
13. Present all four images for visual review and report revisions, targets, commands, and output paths.

The capture implementation uses current NativePHP v4 device targeting and platform-native screenshot tooling. It does not edit generated native trees. Platform commands are isolated behind adapters so orchestration can be tested without live devices.

### `firstlight-development`

Use as the entrypoint for agents implementing, debugging, reviewing, or documenting Firstlight.

The skill stays small and procedural:

1. Read both constitutions.
2. Identify the requested layer and inspect canonical documentation plus current source.
3. Route component creation, iOS work, Android work, and constitutional review to the existing focused skills.
4. Route documentation work to the appropriate documentation skill.
5. Require same-change documentation updates and regenerated LLM artefacts when public behaviour changes.
6. Report exact evidence and stop conditions.

It does not duplicate component prop tables, compatibility facts, or renderer contracts.

## Existing skill integration

The current component skills remain focused on their implementation layers. Their completion workflows are amended to require:

- canonical component documentation through `firstlight-docs-write` or `firstlight-docs-update`;
- showcase fixture coverage;
- the complete documentation screenshot matrix;
- successful `bin/check-docs` and LLM artefact generation;
- `firstlight-review-component` evidence before release completion.

## Deterministic tooling

### `bin/check-docs`

The checker validates:

- required page metadata and allowed enum values;
- unique indexed pages and orphan detection;
- existence of every declared source;
- internal Markdown links and screenshot references;
- a documented page for every public component in `nativephp.json`;
- required component-reference sections;
- the four-image screenshot matrix and manifest entry;
- README package identity and canonical links;
- generated artefact freshness;
- repository skill presence and required integration markers.

Judgment-heavy factual accuracy remains the audit skill's responsibility.

### `bin/capture-doc-screenshots`

The orchestrator accepts an explicit component, showcase path, iOS UDID, Android serial, and development or release mode. It performs preflight checks, calls platform adapters, writes only the declared output matrix, and produces an evidence summary.

The command refuses ambiguous targets, real devices, mismatched installed sources, failed showcase tests, dirty release inputs, missing fixtures, and partial capture sets.

## End-to-end flows

### New component documentation

```text
public contract and paired implementations
  -> showcase fixtures and focused tests
  -> canonical component page
  -> four runtime screenshots
  -> index update
  -> LLM artefact generation
  -> documentation checks
  -> constitutional component review
```

### Existing component change

```text
Git range or named change
  -> affected-page mapping
  -> surgical canonical updates
  -> screenshot refresh when visual behaviour changed
  -> skill consistency check
  -> regenerated LLM artefacts
  -> documentation checks
```

### Documentation audit

```text
constitutions + code + tests + manifest + showcase + docs + generated outputs
  -> evidence-backed findings
  -> prioritised read-only report
  -> separately authorised fixes
```

## Testing strategy

Deterministic tooling is developed test-first with Pest. Tests use temporary fixture repositories and fake platform adapters. Coverage includes:

- stable index ordering and byte-identical generation;
- malformed or missing frontmatter;
- missing or irrelevant source paths;
- duplicate, unindexed, and orphan pages;
- broken internal links and media references;
- stale `llms.txt` and `llms-full.txt`;
- undocumented manifest components;
- incomplete screenshot matrices;
- target validation and physical-device rejection;
- partial capture cleanup and appearance restoration;
- installed-package mismatch and dirty release rejection.

Each skill is developed independently using the skill-writing RED-GREEN-REFACTOR workflow:

1. run realistic baseline scenarios without the skill;
2. record concrete omissions, wrong output shapes, and rationalisations;
3. write the smallest skill that corrects those failures;
4. forward-test the same scenarios with the skill;
5. close discovered gaps before creating the next skill;
6. validate every skill folder and its `agents/openai.yaml` metadata.

## Failure handling

Documentation work stops and reports a blocker when:

- the public contract is ambiguous;
- code and tests disagree;
- a claim lacks current evidence;
- a required source or showcase fixture is missing;
- the showcase package does not match the requested package state;
- required focused tests fail;
- simulator or emulator targets are missing or ambiguous;
- release captures use dirty inputs;
- a platform or appearance image is absent or invalid;
- generated artefacts are stale after regeneration.

Generation writes complete artefacts atomically and does not leave partial outputs. Screenshot capture restores appearance on failure where the platform permits it and never falls back from a simulator or emulator to a physical device.

## Initial delivery

The first implementation includes:

1. `docs/constitution.md` and `docs/config.json`;
2. canonical index and initial consumer/contributor structure;
3. migration of the current Segmented contract from `README.md` into canonical pages;
4. a concise README linked to canonical documentation;
5. generated `llms.txt` and `llms-full.txt`;
6. the five repository-owned skills and metadata;
7. deterministic build, check, and screenshot orchestration commands;
8. test coverage for deterministic tooling;
9. integration updates to the existing component skills;
10. the complete Segmented showcase screenshot matrix, subject to available fixed simulator and emulator targets and visual approval.

Website hosting, DNS, redirects, repository transfer, Composer package renaming, and versioned documentation remain follow-up work.
