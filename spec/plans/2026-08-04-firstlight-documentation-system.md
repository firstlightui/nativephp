---
title: Firstlight documentation system implementation plan
description: Task-by-task delivery plan for public docs, maintained specs, LLM artefacts, skills, and showcase screenshots.
status: historical
sources:
  - spec/designs/2026-08-04-firstlight-documentation-system-design.md
---

# Firstlight Documentation System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a source-backed documentation system with separate public `docs/` and maintained `spec/` trees, generated LLM artefacts, focused repository skills, and paired showcase screenshots.

**Architecture:** Public Markdown under `docs/` is the only source for `llms.txt` and `llms-full.txt`; maintained contributor and agent contracts live under `spec/`. Small PHP CLI tools parse a deliberately constrained frontmatter contract, generate artefacts deterministically, and validate structural invariants. The sibling showcase supplies stable native capture fixtures, while a package-side orchestrator captures iOS and Android light/dark matrices from explicit emulator targets.

**Tech Stack:** PHP 8.4, Pest 5, Symfony Process, Markdown with constrained YAML-style frontmatter, JSON manifests, NativePHP Mobile 4, Xcode `simctl`, Android `adb`, repository-owned Agent Skills.

## Global Constraints

- Preserve the active package-identity migration and every unrelated user change; stage only files named by the current task.
- If the identity migration is still uncommitted when execution starts, finish or isolate it before documentation work so commits do not mix concerns.
- Public identity is `firstlightui/nativephp`, `FirstlightUI`, `dev.firstlightui`, `github.com/firstlightui/nativephp`, and `firstlightui.dev`; do not introduce a legacy organisation identity.
- Root `Constitution.md` outranks `spec/documentation-constitution.md`; current code, tests, `composer.json`, and `nativephp.json` remain factual evidence.
- `docs/` is public and current-only. `spec/` contains maintained contributor/agent contracts plus explicitly historical dated designs and plans.
- `llms.txt` and `llms-full.txt` consume only pages indexed by `docs/index.md`.
- Every public visual component requires iOS and Android screenshots in light and dark modes from the sibling showcase.
- Do not add tests for prose, static Markdown, or skill text solely for coverage. Add tests only for executable behaviour where the regression protection is concrete.
- Use Context7 again immediately before implementing NativePHP CLI, Xcode, or Android command details.
- Never edit generated `nativephp/` platform trees to implement package or screenshot behaviour.
- Screenshot automation accepts only explicitly named simulators/emulators and never falls back to a physical device.
- Release-mode evidence requires clean package and showcase revisions. Development mode reports dirty state and deferred release evidence.

---

### Task 1: Establish documentation governance and indexes

**Files:**

- Create: `spec/documentation-constitution.md`
- Create: `spec/documentation.json`
- Create: `spec/screenshots.json`
- Create: `docs/index.md`
- Create: `spec/index.md`

**Interfaces:**

- Consumes: root `Constitution.md`, `composer.json`, `nativephp.json`, and the approved design.
- Produces: the binding content rules, public generation order, maintained-specification index, site metadata, and screenshot manifest consumed by every later task.

- [ ] **Step 1: Write the documentation constitution**

Create `spec/documentation-constitution.md` with `status: current` frontmatter and these exact top-level sections:

```markdown
# Firstlight Documentation Constitution

## Authority and scope
## Public and maintained boundaries
## Audience
## Current-only version policy
## Voice and terminology
## Page metadata
## Diataxis page contracts
## Component reference contract
## Code example evidence
## Screenshot contract
## LLM artefacts
## README contract
## Change and audit rules
## Historical records
## Prohibited content
```

Encode the global constraints verbatim where applicable. State that `docs/` is published, `spec/` is repository-only but not confidential, and `spec/designs/` plus `spec/plans/` are non-maintained historical records.

- [ ] **Step 2: Add deterministic documentation configuration**

Create `spec/documentation.json` with this shape and current values:

```json
{
    "name": "Firstlight UI",
    "site_url": "https://firstlightui.dev",
    "repository_url": "https://github.com/firstlightui/nativephp",
    "package": "firstlightui/nativephp",
    "versioning": "current"
}
```

- [ ] **Step 3: Add the screenshot manifest contract**

Create `spec/screenshots.json` with a Segmented entry that declares the future capture route, focused showcase test, and all four output paths:

```json
{
    "components": {
        "segmented": {
            "route": "/captures/segmented",
            "test": "php artisan test tests/Feature/SegmentedCaptureTest.php",
            "outputs": {
                "ios-light": "docs/screenshots/segmented/ios-light.png",
                "ios-dark": "docs/screenshots/segmented/ios-dark.png",
                "android-light": "docs/screenshots/segmented/android-light.png",
                "android-dark": "docs/screenshots/segmented/android-dark.png"
            }
        }
    }
}
```

- [ ] **Step 4: Write both indexes**

Make `docs/index.md` link only the six initial public pages in this order: installation, first component, SuperNative components, server-authoritative state, Segmented, compatibility. Make `spec/index.md` link only current maintained specifications: package architecture, adding components, testing, showcase/screenshots, and repository layout. Give each link a one-sentence retrieval-friendly summary.

- [ ] **Step 5: Review boundaries without adding tests**

Run:

```bash
rg -n "https?://|composer require|namespace" docs spec
git diff --check -- docs spec
```

Expected: every identity-bearing result uses the approved Firstlight identity and the whitespace check passes. Inspect both indexes manually and confirm no historical plan/design is presented as current.

- [ ] **Step 6: Commit the governance foundation**

```bash
git add docs/index.md spec/index.md spec/documentation-constitution.md spec/documentation.json spec/screenshots.json
git commit -m "docs: establish documentation governance"
```

---

### Task 2: Publish the initial consumer documentation

**Files:**

- Create: `docs/getting-started/installation.md`
- Create: `docs/getting-started/first-component.md`
- Create: `docs/components/segmented.md`
- Create: `docs/concepts/supernative-components.md`
- Create: `docs/concepts/server-authoritative-state.md`
- Create: `docs/reference/compatibility.md`
- Modify: `README.md`

**Interfaces:**

- Consumes: page metadata and component-reference contracts from Task 1; current PHP, iOS, Android, tests, manifests, and Composer constraints.
- Produces: the complete initial public corpus consumed by artifact generation and public developers.

- [ ] **Step 1: Write installation and first-component pages**

Use `type: how-to` for installation and `type: tutorial` for the first component. Both use `audience: consumer` and source `composer.json`, `nativephp.json`, and the relevant provider/component files. Installation must use:

```bash
composer require firstlightui/nativephp
```

It must explain plugin registration and the required native rebuild without promising unsupported automatic registration. The tutorial must show one complete property/options definition and one `<firstlight:segmented>` example.

- [ ] **Step 2: Write the two explanation pages**

`docs/concepts/supernative-components.md` explains the EDGE/Element Tree/native-renderer/event lifecycle without contributor internals. `docs/concepts/server-authoritative-state.md` explains immediate semantic events, PHP acceptance, unchanged-value rejection, reconciliation without event echo, repeated rejected attempts, `null`, and programmatic updates.

Use `type: explanation`, `audience: consumer`, explicit headings, and direct source paths to the element, renderers, and reconciliation tests.

- [ ] **Step 3: Migrate the Segmented contract from README**

Create `docs/components/segmented.md` as `type: reference`. Preserve the current working examples and cover every required component-reference section from the constitution. Use the actual public identifiers and source-backed accepted types. Add the four image references with meaningful alt text; missing Android files remain a release gap rather than invented content.

The screenshot block uses:

```markdown
| Platform | Light | Dark |
| --- | --- | --- |
| iOS | ![Segmented on iOS in light mode](../screenshots/segmented/ios-light.png) | ![Segmented on iOS in dark mode](../screenshots/segmented/ios-dark.png) |
| Android | ![Segmented on Android in light mode](../screenshots/segmented/android-light.png) | ![Segmented on Android in dark mode](../screenshots/segmented/android-dark.png) |
```

- [ ] **Step 4: Write current compatibility reference**

Create `docs/reference/compatibility.md` from `composer.json`, `nativephp.json`, and `Package.swift`. State PHP `^8.4`, NativePHP Mobile `^4.0`, Mobile UI `^0.3`, iOS minimum `18.0`, Android minimum API `29`, and the actual Swift platform floor. Do not infer future support.

- [ ] **Step 5: Reduce README to a landing page**

Keep the package description, public links, installation command, one minimal Segmented example, status, constitution link, documentation index link, and licence. Remove detailed option rules, state timing, and prop tables now owned by `docs/components/segmented.md`.

- [ ] **Step 6: Verify examples against existing behaviour**

Run existing tests; do not add documentation-specific tests:

```bash
./vendor/bin/pest tests/Feature/SegmentedElementTest.php tests/Feature/PluginManifestTest.php
git diff --check -- README.md docs
```

Expected: existing contract tests pass and the documentation diff has no whitespace errors. Compare every documented prop/event to `src/Elements/Segmented.php` and `src/Components/Segmented.php` before committing.

- [ ] **Step 7: Commit the public corpus**

```bash
git add README.md docs/getting-started docs/components docs/concepts docs/reference
git commit -m "docs: publish initial Firstlight guide"
```

---

### Task 3: Write maintained package specifications

**Files:**

- Create: `spec/architecture/package.md`
- Create: `spec/workflows/adding-components.md`
- Create: `spec/workflows/testing.md`
- Create: `spec/workflows/showcase-and-screenshots.md`
- Create: `spec/reference/repository-layout.md`

**Interfaces:**

- Consumes: root constitution, public docs, current package layout, existing component skills, and sibling showcase layout.
- Produces: current maintainer knowledge used by `firstlight-development`, documentation skills, and future component work.

- [ ] **Step 1: Document package architecture**

Describe the PHP component/precompiler/element layers, primitive Element Tree payload, Swift and Kotlin renderer/control pairs, semantic event path, server-authoritative state, manifest registration, and package/showcase boundary. Cite exact current files in frontmatter.

- [ ] **Step 2: Document the component workflow**

Write the maintained workflow from contract decision through PHP tests, scaffolding, paired platform implementations, showcase fixture, public docs, screenshots, development review, and release review. State every stop condition from root `Constitution.md` without copying platform implementation details already owned by focused skills.

- [ ] **Step 3: Document useful testing expectations**

Separate package contract tests, iOS tests/snapshots, Android tests/Paparazzi, showcase tests, physical-device evidence, and documentation-tool tests. Include the user-approved rule that prose and static docs do not receive tests unless they protect against a concrete failure.

- [ ] **Step 4: Document showcase and screenshot operations**

Specify the sibling path contract, exact package revision requirement, the initial `/captures/segmented` route and future component-specific capture routes, stable authored fixtures, explicit target IDs, four-image matrix, development/release modes, visual approval, and appearance restoration.

- [ ] **Step 5: Document the repository map**

Create a concise path-to-purpose table for `src/`, `resources/ios/`, `resources/android/`, `tests/`, `docs/`, `spec/`, `.agents/skills/`, `bin/`, and the sibling showcase. Mark `spec/designs/` and `spec/plans/` historical.

- [ ] **Step 6: Validate and commit without prose tests**

```bash
git diff --check -- spec
rg -n "status: current|sources:" spec/architecture spec/workflows spec/reference
git add spec/architecture spec/workflows spec/reference
git commit -m "docs: specify Firstlight contributor workflows"
```

Expected: each maintained page has current-status frontmatter and real source paths.

---

### Task 4: Implement deterministic LLM generation and documentation checks

**Files:**

- Create: `bin/support/DocumentationPage.php`
- Create: `bin/support/DocumentationRepository.php`
- Create: `bin/support/DocumentationArtifactBuilder.php`
- Create: `bin/support/DocumentationValidator.php`
- Create: `bin/build-docs-artifacts`
- Create: `bin/check-docs`
- Create: `tests/Feature/DocumentationToolingTest.php`
- Modify: `composer.json`

**Interfaces:**

- Produces: immutable `DocumentationPage` fields `path`, `title`, `description`, `type`, `audience`, `status`, `sources`, and `body`; `DocumentationPage::fromFile(string $root, string $relativePath): DocumentationPage`; `DocumentationRepository::__construct(string $root)`; `DocumentationRepository::publicPages(): list<DocumentationPage>`; `DocumentationRepository::currentSpecifications(): list<DocumentationPage>`; `DocumentationArtifactBuilder::__construct(DocumentationRepository $repository)`; `DocumentationArtifactBuilder::outputs(): array{'llms.txt': string, 'llms-full.txt': string}`; `DocumentationValidator::__construct(string $root, DocumentationRepository $repository, DocumentationArtifactBuilder $builder)`; `DocumentationValidator::errors(bool $development): list<string>`.
- Consumes: `docs/index.md`, `spec/index.md`, frontmatter contracts, `spec/documentation.json`, `spec/screenshots.json`, `nativephp.json`, README, skills, and screenshot files.

- [ ] **Step 1: Write one focused generation test**

In `tests/Feature/DocumentationToolingTest.php`, create a temporary fixture repository with two indexed public pages in reverse lexical order. Assert that `DocumentationArtifactBuilder::outputs()` is byte-identical across two calls, `llms-full.txt` follows index order, and no `spec/` body appears.

The key assertion is:

```php
$first = $builder->outputs();
$second = $builder->outputs();

expect($second)->toBe($first)
    ->and(strpos($first['llms-full.txt'], '# Install'))
    ->toBeLessThan(strpos($first['llms-full.txt'], '# Segmented'))
    ->and($first['llms-full.txt'])->not->toContain('Internal architecture');
```

- [ ] **Step 2: Run the focused test and verify the missing-class failure**

```bash
./vendor/bin/pest tests/Feature/DocumentationToolingTest.php --filter="builds deterministic"
```

Expected: failure because the documentation support classes do not exist.

- [ ] **Step 3: Implement constrained frontmatter and index parsing**

Implement only the approved frontmatter subset: scalar `title`, `description`, `type`, `audience`, `status`, and a list of `sources`. Reject duplicate keys, missing closing delimiters, unsupported nesting, and empty required values. Parse Markdown links from the two indexes in document order and resolve them relative to the index file.

- [ ] **Step 4: Implement deterministic artefact generation**

`llms.txt` reads identity from `spec/documentation.json` and emits concise links/summaries. Map `docs/getting-started/installation.md` to `https://firstlightui.dev/getting-started/installation`: remove the `docs/` prefix and `.md` suffix, then join the remaining path to `site_url`. Map `docs/index.md` to the site root. `llms-full.txt` emits this boundary before each public page:

```text
--- Source: docs/components/segmented.md ---
```

Strip frontmatter, normalise line endings to LF, preserve authored Markdown bodies, and finish each file with one newline.

- [ ] **Step 5: Make the generation test pass**

```bash
./vendor/bin/pest tests/Feature/DocumentationToolingTest.php --filter="builds deterministic"
```

Expected: one passing test.

- [ ] **Step 6: Write one table-driven validation test**

Use a Pest dataset to prove only materially useful failures: missing indexed file, missing declared source, broken relative link, undocumented `nativephp.json` component, incomplete screenshot manifest, and stale generated output. Assert that development mode permits absent PNG/review evidence but release mode reports it.

- [ ] **Step 7: Run the validation test and verify failure**

```bash
./vendor/bin/pest tests/Feature/DocumentationToolingTest.php --filter="reports documentation contract failures"
```

Expected: failure because `DocumentationValidator` is not implemented.

- [ ] **Step 8: Implement validation and executable wrappers**

`bin/build-docs-artifacts` writes each output to a same-directory temporary file and renames it only after both outputs are complete. `bin/check-docs` never mutates files and accepts only optional `--development`. It aggregates actionable relative-path errors, exits `0` on success, `1` on contract failure, and `2` on invalid invocation.

Add executable modes and Composer scripts:

```json
"docs:build": "bin/build-docs-artifacts",
"docs:check": "bin/check-docs --development"
```

- [ ] **Step 9: Verify focused and repository behaviour**

```bash
./vendor/bin/pest tests/Feature/DocumentationToolingTest.php
bin/build-docs-artifacts
bin/check-docs --development
git diff --check
```

Expected: focused tests pass and generated artefacts are created. Until Tasks 5–11 are complete, the repository development check exits `1` with only exact missing-skill or deferred-evidence findings; treat any unrelated error as a Task 4 defect.

- [ ] **Step 10: Commit executable documentation tooling**

```bash
git add composer.json bin/build-docs-artifacts bin/check-docs bin/support tests/Feature/DocumentationToolingTest.php llms.txt llms-full.txt
git commit -m "feat: generate and validate documentation artefacts"
```

---

### Task 5: Create the documentation-writing skill

**Files:**

- Create: `.agents/skills/firstlight-docs-write/SKILL.md`
- Create: `.agents/skills/firstlight-docs-write/agents/openai.yaml`

**Interfaces:**

- Consumes: both constitutions, both indexes, public page contracts, package source, tests, and showcase fixtures.
- Produces: a gated workflow for new public pages and maintained specifications that finishes with artefact generation and development checks.

- [ ] **Step 1: Initialise the skill through the standard generator**

```bash
python3 /Users/wojt/.codex/skills/.system/skill-creator/scripts/init_skill.py firstlight-docs-write --path .agents/skills --interface 'display_name=Firstlight Docs Write' --interface 'short_description=Write source-backed Firstlight documentation' --interface 'default_prompt=Use $firstlight-docs-write to document a Firstlight package capability.'
```

- [ ] **Step 2: Replace the template with the approved workflow**

Use this trigger-only description:

```yaml
description: Use when creating new public documentation or maintained specifications for the Firstlight UI package.
```

The body must cover inputs, authority order, source inventory, public-versus-spec selection, approval before writing, exact page contracts, example evidence, screenshot handoff, `bin/build-docs-artifacts`, `bin/check-docs --development`, report shape, and stop conditions. Keep it under 500 words and do not duplicate component facts.

- [ ] **Step 3: Validate the skill structure**

```bash
python3 /Users/wojt/.codex/skills/.system/skill-creator/scripts/quick_validate.py .agents/skills/firstlight-docs-write
git diff --check -- .agents/skills/firstlight-docs-write
```

Expected: validation passes. Do not add a skill-content test unless a realistic no-skill control demonstrates a concrete writing failure that this workflow must prevent.

- [ ] **Step 4: Commit the writing skill**

```bash
git add .agents/skills/firstlight-docs-write
git commit -m "feat: add Firstlight documentation writing skill"
```

---

### Task 6: Create the surgical documentation-update skill

**Files:**

- Create: `.agents/skills/firstlight-docs-update/SKILL.md`
- Create: `.agents/skills/firstlight-docs-update/agents/openai.yaml`

**Interfaces:**

- Consumes: a named change or Git range, `sources` metadata, current docs/specs, and screenshot impact rules.
- Produces: confirmed affected scope, surgical edits, updated indexes when necessary, regenerated artefacts, and a ripple-effect report.

- [ ] **Step 1: Initialise the skill**

```bash
python3 /Users/wojt/.codex/skills/.system/skill-creator/scripts/init_skill.py firstlight-docs-update --path .agents/skills --interface 'display_name=Firstlight Docs Update' --interface 'short_description=Update Firstlight docs after code changes' --interface 'default_prompt=Use $firstlight-docs-update to update Firstlight documentation for this change.'
```

- [ ] **Step 2: Encode the surgical update contract**

Use:

```yaml
description: Use when a Firstlight code, API, renderer, compatibility, or workflow change may make existing documentation or specifications stale.
```

Require change detection, mapping through `sources`, a user-confirmed scope, exact ripple checks, preservation of unaffected prose/images, screenshot handoff for visual changes, identity checks, skill-staleness checks, artefact regeneration, and a new/updated/removed-page report. Deletions require explicit confirmation.

- [ ] **Step 3: Validate and inspect for rewrite loopholes**

```bash
python3 /Users/wojt/.codex/skills/.system/skill-creator/scripts/quick_validate.py .agents/skills/firstlight-docs-update
rg -n "surgical|unaffected|confirm|screenshots|build-docs-artifacts|check-docs" .agents/skills/firstlight-docs-update/SKILL.md
```

Expected: validation passes and every required guard is present. Add a focused agent scenario only if a no-skill control rewrites unaffected pages or skips a demonstrated ripple effect.

- [ ] **Step 4: Commit the update skill**

```bash
git add .agents/skills/firstlight-docs-update
git commit -m "feat: add Firstlight documentation update skill"
```

---

### Task 7: Create the read-only documentation-audit skill

**Files:**

- Create: `.agents/skills/firstlight-docs-audit/SKILL.md`
- Create: `.agents/skills/firstlight-docs-audit/agents/openai.yaml`

**Interfaces:**

- Consumes: both constitutions, both indexes, public and current-spec inventories, package source/tests, generated artefacts, skills, screenshot manifest, and showcase fixtures.
- Produces: an evidence-backed read-only report ranked critical, warning, gap, or informational.

- [ ] **Step 1: Run a useful read-only baseline if agent capacity is available**

Give a fresh agent a copy of the repository plus: “Audit Firstlight documentation accuracy and report findings.” Record whether it modifies files or reports unsupported claims. If the control stays read-only and evidence-backed, do not manufacture more scenarios.

- [ ] **Step 2: Initialise the skill**

```bash
python3 /Users/wojt/.codex/skills/.system/skill-creator/scripts/init_skill.py firstlight-docs-audit --path .agents/skills --interface 'display_name=Firstlight Docs Audit' --interface 'short_description=Audit Firstlight documentation against source' --interface 'default_prompt=Use $firstlight-docs-audit to audit Firstlight documentation for drift and gaps.'
```

- [ ] **Step 3: Encode the audit and report contracts**

Use:

```yaml
description: Use when assessing Firstlight documentation or specifications for accuracy, completeness, drift, broken evidence, or release readiness.
```

Make read-only behaviour explicit. Cover constitution violations, stale claims, missing public API coverage, orphan public pages, unindexed current specs, broken links/sources, identity drift, incomplete screenshot matrices, showcase drift, generated artefact drift, and stale skills. Require exact paths/lines/evidence and a summary count. Applying fixes is a separate explicit request.

- [ ] **Step 4: Validate and, only if Step 1 failed, forward-test read-only behaviour**

```bash
python3 /Users/wojt/.codex/skills/.system/skill-creator/scripts/quick_validate.py .agents/skills/firstlight-docs-audit
git diff --check -- .agents/skills/firstlight-docs-audit
```

If the baseline mutated files, repeat the same scenario with the skill and confirm no filesystem change. Otherwise stop at structural validation.

- [ ] **Step 5: Commit the audit skill**

```bash
git add .agents/skills/firstlight-docs-audit
git commit -m "feat: add Firstlight documentation audit skill"
```

---

### Task 8: Add the Firstlight development entrypoint and integrate existing skills

**Files:**

- Create: `.agents/skills/firstlight-development/SKILL.md`
- Create: `.agents/skills/firstlight-development/agents/openai.yaml`
- Modify: `.agents/skills/firstlight-create-component/SKILL.md`
- Modify: `.agents/skills/firstlight-ios-component/SKILL.md`
- Modify: `.agents/skills/firstlight-android-component/SKILL.md`
- Modify: `.agents/skills/firstlight-review-component/SKILL.md`
- Modify: `bin/scaffold-component`
- Modify: `bin/check-component`
- Modify: `tests/Feature/ComponentToolingTest.php`

**Interfaces:**

- Consumes: all current focused component skills plus the three documentation skills.
- Produces: one small routing skill; component scaffolding with valid public-doc metadata; component release checks using `spec/reviews/`; same-change documentation integration.

- [ ] **Step 1: Update the existing useful component-tooling test**

Change the release-gate assertion from `docs/review/segmented-alpha.md` to `spec/reviews/segmented-alpha.md`. Add assertions that a scaffolded component doc begins with the approved frontmatter keys and that `bin/check-component` reports `bin/check-docs` release failure when documentation evidence is incomplete. Do not add separate tests for skill prose.

- [ ] **Step 2: Run the focused test and verify expected failures**

```bash
./vendor/bin/pest tests/Feature/ComponentToolingTest.php
```

Expected: failures identify the old review path and scaffold template.

- [ ] **Step 3: Update component tooling**

Make `bin/scaffold-component` create a public component page with `title`, `description`, `type: reference`, `audience: consumer`, and all generated source paths. Keep the explicit failing-stub marker used by the scaffold test.

Make `bin/check-component` derive the component slug and require `spec/reviews/segmented-alpha.md` for Segmented (and `spec/reviews/{$slug}-alpha.md` generally) in release mode. Run `bin/check-docs` with the same development/release intent. Preserve exact missing-path reporting.

- [ ] **Step 4: Initialise and write `firstlight-development`**

```bash
python3 /Users/wojt/.codex/skills/.system/skill-creator/scripts/init_skill.py firstlight-development --path .agents/skills --interface 'display_name=Firstlight Development' --interface 'short_description=Work safely across the Firstlight package' --interface 'default_prompt=Use $firstlight-development to make this Firstlight package change.'
```

Use this description:

```yaml
description: Use when implementing, debugging, reviewing, testing, or documenting work in the Firstlight UI package.
```

Keep the body under 350 words. It must read both constitutions and both indexes, inspect current source, route to the four component skills and three documentation skills, enforce current public identity, require same-change docs for public behaviour, and report evidence without duplicating API facts.

- [ ] **Step 5: Integrate documentation into the four existing skills**

Add `firstlight-docs-write`/`firstlight-docs-update` at the documentation step, require the screenshot skill once it exists, use `spec/reviews/` for release evidence, and require `bin/check-docs`. Keep each existing skill under 500 words and preserve its platform-specific stop conditions.

- [ ] **Step 6: Validate the routing skill and focused tooling**

```bash
python3 /Users/wojt/.codex/skills/.system/skill-creator/scripts/quick_validate.py .agents/skills/firstlight-development
./vendor/bin/pest tests/Feature/ComponentToolingTest.php
bin/check-component Segmented --development
```

Expected: skill validation and focused tooling tests pass; development component check succeeds or reports only a real documentation-foundation gap from an earlier unfinished task.

- [ ] **Step 7: Commit the entrypoint and integration**

```bash
git add .agents/skills/firstlight-development .agents/skills/firstlight-create-component .agents/skills/firstlight-ios-component .agents/skills/firstlight-android-component .agents/skills/firstlight-review-component bin/scaffold-component bin/check-component tests/Feature/ComponentToolingTest.php
git commit -m "feat: integrate documentation into component workflows"
```

---

### Task 9: Add a deterministic Segmented capture fixture to the showcase

**Files (sibling repository `../firstlight-showcase`):**

- Create: `app/NativeComponents/Captures/SegmentedCapture.php`
- Create: `resources/views/native/captures/segmented.blade.php`
- Create: `tests/Feature/SegmentedCaptureTest.php`
- Modify: `routes/native.php`
- Modify: `README.md`

**Interfaces:**

- Consumes: installed `firstlightui/nativephp` package and the route/test declared by `spec/screenshots.json`.
- Produces: stable `/captures/segmented` native screen with representative selected, disabled-choice, helper, and error states; a focused wire-tree contract test.

- [ ] **Step 1: Resolve the showcase's existing dirty migration first**

Run `git -C ../firstlight-showcase status --short`. Do not stage documentation-capture files until the active identity/workaround changes are committed or explicitly separated. Record the resulting clean base commit.

- [ ] **Step 2: Write the failing capture fixture test**

The test visits `/captures/segmented`, asserts the title `Firstlight Segmented`, and verifies exactly three `firstlight.segmented` nodes labelled `Queue`, `Priority`, and `Queue with error`. Assert stable values, the disabled urgent option, helper/error text, and accessibility hints.

- [ ] **Step 3: Run the focused test and verify route failure**

```bash
cd ../firstlight-showcase
php artisan test tests/Feature/SegmentedCaptureTest.php
```

Expected: failure because `/captures/segmented` is not registered.

- [ ] **Step 4: Implement the stable capture component and view**

Register:

```php
Route::native('/captures/segmented', SegmentedCapture::class)
    ->name('captures.segmented')
    ->layout(ShowcaseLayout::class);
```

Keep the fixture static and fitted to one phone viewport. Use authored healthcare-neutral labels and the showcase's existing light/dark semantic theme. Do not add controls whose state changes with time, network, or persisted data.

- [ ] **Step 5: Make the showcase test pass**

```bash
php artisan test tests/Feature/SegmentedCaptureTest.php
php artisan test tests/Feature/SegmentedShowcaseTest.php
```

Expected: both focused suites pass.

- [ ] **Step 6: Document and commit the fixture in the showcase repository**

Add README commands for the capture route and state that device identifiers are supplied by the package capture workflow.

```bash
git add app/NativeComponents/Captures/SegmentedCapture.php resources/views/native/captures/segmented.blade.php tests/Feature/SegmentedCaptureTest.php routes/native.php README.md
git commit -m "feat: add Segmented documentation capture fixture"
```

Record the showcase commit for Task 11.

---

### Task 10: Implement safe native screenshot orchestration

**Files:**

- Create: `bin/support/CaptureRequest.php`
- Create: `bin/support/CaptureReport.php`
- Create: `bin/support/CaptureCommandRunner.php`
- Create: `bin/support/DocumentationScreenshotCapture.php`
- Create: `bin/capture-doc-screenshots`
- Create: `tests/Feature/DocumentationScreenshotCaptureTest.php`

**Interfaces:**

- Produces: `CaptureRequest::__construct(string $component, string $packageRoot, string $showcaseRoot, string $iosUdid, string $androidSerial, bool $release, bool $keepFailed)`; `CaptureReport::__construct(string $packageRevision, string $showcaseRevision, bool $packageDirty, bool $showcaseDirty, string $iosUdid, string $androidSerial, array $commands, array $outputs)` where both arrays are `array<string, string>`; `CaptureCommandRunner::run(list<string> $command, ?string $cwd = null): array{exitCode: int, stdout: string, stderr: string}`; `DocumentationScreenshotCapture::__construct(CaptureCommandRunner $runner, string $packageRoot)`; `DocumentationScreenshotCapture::capture(CaptureRequest $request): CaptureReport`; CLI arguments `Component --showcase=PATH --ios=UDID --android=SERIAL [--release] [--keep-failed]`.
- Consumes: `spec/screenshots.json`, explicit emulator targets, current package/showcase Git states, focused showcase test, NativePHP run commands, `simctl`, and `adb`.

- [ ] **Step 1: Refresh current platform documentation**

Use Context7 to confirm NativePHP Mobile 4 `native:run {os} {udid} --start-url=... --no-tty`. Check installed `xcrun simctl help` and `adb --help` for appearance, screenshot, and target-selection commands. Record exact supported commands in `spec/workflows/showcase-and-screenshots.md` if they differ from the design.

- [ ] **Step 2: Write one safety-focused orchestration test**

Use a fake `CaptureCommandRunner` and a temporary output root. In one table-driven test prove:

- missing target identifiers fail before any command runs;
- Android `ro.kernel.qemu != 1` is rejected;
- dirty release inputs are rejected;
- a failed third capture publishes none of the four final files;
- original iOS and Android appearances are restored in `finally`;
- a successful run publishes exactly the four manifest paths and rejects byte-identical light/dark pairs.

- [ ] **Step 3: Run the safety test and verify missing-class failure**

```bash
./vendor/bin/pest tests/Feature/DocumentationScreenshotCaptureTest.php
```

Expected: failure because capture support classes do not exist.

- [ ] **Step 4: Implement target and repository preflight**

Validate exact package/showcase paths, component manifest entry, clean release Git state, installed package identity, iOS Simulator UDID, booted Android emulator serial, and focused showcase test. Development mode records dirty state but does not hide it.

- [ ] **Step 5: Implement conditional rendering waits and atomic publication**

Launch each platform directly at the manifest route. After changing appearance, capture temporary PNGs until two consecutive hashes match or 15 seconds elapse. Store all four in a temporary directory; validate PNG dimensions and light/dark differences; rename into the four resolved manifest output paths only after the complete matrix passes.

Always restore the original appearances in `finally`. Preserve failed temporary evidence only when `--keep-failed` is explicitly supplied; otherwise remove the task-specific temporary directory.

- [ ] **Step 6: Implement the CLI and make tests pass**

The CLI prints package commit, showcase commit, dirty markers, target IDs, commands, and final paths. It exits `0` on a complete matrix, `1` on evidence/capture failure, and `2` on invalid invocation.

```bash
chmod +x bin/capture-doc-screenshots
./vendor/bin/pest tests/Feature/DocumentationScreenshotCaptureTest.php
```

Expected: safety-focused suite passes.

- [ ] **Step 7: Commit screenshot orchestration**

```bash
git add bin/capture-doc-screenshots bin/support/CaptureRequest.php bin/support/CaptureReport.php bin/support/CaptureCommandRunner.php bin/support/DocumentationScreenshotCapture.php tests/Feature/DocumentationScreenshotCaptureTest.php spec/workflows/showcase-and-screenshots.md
git commit -m "feat: automate documentation screenshot capture"
```

---

### Task 11: Create the screenshot skill and capture Segmented evidence

**Files:**

- Create: `.agents/skills/firstlight-docs-screenshots/SKILL.md`
- Create: `.agents/skills/firstlight-docs-screenshots/agents/openai.yaml`
- Create or refresh: `docs/screenshots/segmented/ios-light.png`
- Create or refresh: `docs/screenshots/segmented/ios-dark.png`
- Create: `docs/screenshots/segmented/android-light.png`
- Create: `docs/screenshots/segmented/android-dark.png`
- Create after complete evidence: `spec/reviews/segmented-alpha.md`
- Modify: `spec/index.md`

**Interfaces:**

- Consumes: screenshot orchestrator, manifest, fixed simulator/emulator identifiers, package and showcase commits, and visual approval.
- Produces: a guarded capture workflow, complete four-image matrix, and factual release-evidence record.

- [ ] **Step 1: Run a useful weak-evidence baseline if agent capacity is available**

Ask a fresh agent to “capture Segmented documentation screenshots from the showcase” without the skill. Record whether it uses ambiguous targets, accepts dirty release inputs, substitutes unit snapshots, or skips visual review. If none occurs, do not add more scenarios.

- [ ] **Step 2: Initialise and write the screenshot skill**

```bash
python3 /Users/wojt/.codex/skills/.system/skill-creator/scripts/init_skill.py firstlight-docs-screenshots --path .agents/skills --interface 'display_name=Firstlight Docs Screenshots' --interface 'short_description=Capture paired native documentation images' --interface 'default_prompt=Use $firstlight-docs-screenshots to capture Firstlight component documentation images.'
```

Use:

```yaml
description: Use when creating, refreshing, or verifying Firstlight component screenshots from the native showcase on iOS and Android.
```

Require the manifest, stable fixture test, explicit targets, development/release distinction, exact installed package, four-image matrix, appearance restoration, no physical devices, visual presentation, and report shape. The skill calls `bin/capture-doc-screenshots`; it does not duplicate platform command implementation.

- [ ] **Step 3: Validate the skill**

```bash
python3 /Users/wojt/.codex/skills/.system/skill-creator/scripts/quick_validate.py .agents/skills/firstlight-docs-screenshots
```

If Step 1 demonstrated a real failure, repeat the same request with the skill and verify the guard. Otherwise structural validation is sufficient.

- [ ] **Step 4: Obtain explicit targets and run development capture**

List available targets read-only, ask the user to select one fixed iOS Simulator UDID and one Android emulator serial if none are already specified, export the approved values as `FIRSTLIGHT_IOS_UDID` and `FIRSTLIGHT_ANDROID_SERIAL`, then run:

```bash
bin/capture-doc-screenshots Segmented --showcase=../firstlight-showcase --ios="$FIRSTLIGHT_IOS_UDID" --android="$FIRSTLIGHT_ANDROID_SERIAL"
```

Expected: four valid PNGs and a report naming dirty state if either repository is dirty. Do not silently switch targets.

- [ ] **Step 5: Present all four images for visual approval**

Display the four absolute image paths in a 2×2 platform/appearance matrix. Check component state, theme, crop, native presentation, labels, truncation, status-bar noise, and accidental data. Re-capture any rejected image; do not edit screenshots to conceal a rendering defect.

- [ ] **Step 6: Run release capture and write factual review evidence**

After both repositories are clean at the exact approved commits, rerun with `--release`. Create `spec/reviews/segmented-alpha.md` containing the package/showcase commits, device models and OS versions, commands, four paths, visual reviewer/date, accessibility/device evidence already available, and explicit blocked items. Never claim physical-device evidence that was not performed.

- [ ] **Step 7: Run final gates**

```bash
bin/build-docs-artifacts
bin/check-docs
bin/check-component Segmented
composer test
git diff --check
```

Expected: documentation and component release gates pass only if all required evidence exists. If physical-device or upstream publication evidence remains absent, preserve a clear blocked release verdict while allowing `bin/check-docs --development` to pass.

- [ ] **Step 8: Commit package evidence and screenshot skill**

For complete release evidence, run:

```bash
git add .agents/skills/firstlight-docs-screenshots docs/screenshots/segmented spec/reviews/segmented-alpha.md spec/index.md llms.txt llms-full.txt
git commit -m "docs: add paired Segmented showcase evidence"
```

If release evidence is legitimately blocked, do not create the review file. Commit the approved development screenshots and skill with:

```bash
git add .agents/skills/firstlight-docs-screenshots docs/screenshots/segmented llms.txt llms-full.txt
git commit -m "docs: add Segmented showcase screenshots"
```

Report the exact remaining release gate instead of weakening it.

---

### Task 12: Run the complete documentation-system audit

**Files:**

- Modify only files implicated by verified audit findings.

**Interfaces:**

- Consumes: completed docs/spec trees, generated artefacts, five documentation/development skills, component skills, package source/tests, showcase fixture, and screenshots.
- Produces: final development and release verdicts with exact evidence.

- [ ] **Step 1: Invoke the new audit skill in read-only mode**

Audit public API coverage, current specification coverage, links, sources, identity, generated artefacts, skills, showcase fixture, screenshot matrix, and component release evidence. Save no audit report unless explicitly requested; return findings in the task output.

- [ ] **Step 2: Apply only verified, authorised corrections**

Use `firstlight-docs-update` for any confirmed drift. Do not broaden prose or refactor unrelated code.

- [ ] **Step 3: Run proportional final verification**

```bash
composer test
bin/build-docs-artifacts
bin/check-docs --development
bin/check-component Segmented --development
python3 /Users/wojt/.codex/skills/.system/skill-creator/scripts/quick_validate.py .agents/skills/firstlight-docs-write
python3 /Users/wojt/.codex/skills/.system/skill-creator/scripts/quick_validate.py .agents/skills/firstlight-docs-update
python3 /Users/wojt/.codex/skills/.system/skill-creator/scripts/quick_validate.py .agents/skills/firstlight-docs-audit
python3 /Users/wojt/.codex/skills/.system/skill-creator/scripts/quick_validate.py .agents/skills/firstlight-docs-screenshots
python3 /Users/wojt/.codex/skills/.system/skill-creator/scripts/quick_validate.py .agents/skills/firstlight-development
git diff --check
```

Run default release checks additionally when all release evidence exists. Report development success separately from any blocked release requirement.

- [ ] **Step 4: Commit audit corrections if any**

Stage only files changed for verified findings and use:

```bash
git commit -m "docs: resolve documentation system audit findings"
```

Skip this commit when the audit requires no corrections.
