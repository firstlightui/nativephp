---
name: firstlight-docs-write
description: Use when creating new public documentation or maintained specifications for the Firstlight UI package.
---

# Write Firstlight Documentation

Create source-backed, current-only documentation without inventing package behaviour.

## Inputs

Collect the requested capability, intended audience, page type, current source and tests, and any screenshot requirement. Read `Constitution.md`, `spec/documentation-constitution.md`, `docs/index.md`, and `spec/index.md` before proposing content.

Authority is: root constitution; documentation constitution; current code, tests, `composer.json`, and `nativephp.json`; current docs/specs; historical designs/plans.

## Workflow

1. Inventory the exact public API, behaviour, compatibility, examples, and evidence from current source. Mark uncertainty; never convert it into a claim.
   For icon-bearing APIs, read `spec/reference/icons.md` and document every applicable shared fallback, `-ios` / `-android` platform override, accessible action label, and conflict rule exactly as implemented.
2. Choose the destination:
   - `docs/` for published consumer material.
   - `spec/` for current maintainer/agent contracts that are repository-only but not confidential.
   - `spec/designs/` or `spec/plans/` only for a dated historical record.
3. Present the proposed page, Diataxis type, sources, index placement, and affected pages. Obtain approval before writing.
4. Write constrained frontmatter. Public pages require `title`, `description`, `type`, `audience: consumer`, and `sources`. Current specs require `title`, `description`, `status: current`, `audience`, and `sources`.
5. Follow the page contract in `spec/documentation-constitution.md`. A component reference must cover its full API, values/options, events, state timing, disabled and failure behaviour, accessibility, both platforms, compatibility, and screenshots.
6. Verify every example against source and focused tests. Add tests only for a concrete executable regression, never for prose coverage.
7. For a visual component, add or update `spec/screenshots.json`, then use `firstlight-docs-screenshots`. Do not invent or substitute images.
8. Update the relevant index and README only when their landing-page contracts require it.
9. Run:

```bash
bin/build-docs-artifacts
bin/check-docs --development
git diff --check
```

## Report

List created pages, indexed location, evidence inspected, commands/results, screenshot state, and remaining development or release gaps.

Stop when authority conflicts, behaviour is unimplemented, sources are unavailable, platform claims diverge, approval is absent, or required visual evidence cannot be obtained. Report the blocker without weakening the contract.
