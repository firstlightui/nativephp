---
name: firstlight-docs-audit
description: Use when assessing Firstlight documentation or specifications for accuracy, completeness, drift, broken evidence, or release readiness.
---

# Audit Firstlight Documentation

Audit read-only. Do not edit files, regenerate artefacts, capture screenshots, or apply fixes. A fix is a separate explicit request.

## Audit

1. Read `Constitution.md`, `spec/documentation-constitution.md`, both indexes, `spec/documentation.json`, `spec/screenshots.json`, `composer.json`, and `nativephp.json`.
2. Inventory indexed public pages, indexed current specifications, manifest components, generated LLM artefacts, docs skills, screenshot outputs, review records, and sibling-showcase fixtures.
3. Run the read-only check:

```bash
bin/check-docs --development
```

Use the release mode only when assessing release readiness. Never run `bin/build-docs-artifacts` during an audit.
4. Trace page `sources` to current code and tests. Verify public identifiers, accepted types, props/events, state timing, errors, accessibility, platform behaviour, compatibility floors, and examples. Treat current implementation/test disagreement as a finding, not permission to choose one silently.
5. Check constitution violations, stale or unsupported claims, missing public API coverage, orphan public pages, unindexed current specs, broken links/sources, identity drift, missing screenshot variants, unstable or missing showcase fixtures, generated artefact drift, and docs skills that encode stale paths or workflow.
6. Distinguish development gaps from release blockers. A unit snapshot is not a showcase screenshot; simulator evidence is not a physical-device row; file existence is not visual approval.

## Report

Rank each finding `critical`, `warning`, `gap`, or `informational`. Include exact path and line, contradicted source/test or command evidence, impact, and the smallest correction. End with counts by severity plus separate development and release verdicts.

If evidence is inaccessible, current work is changing underneath the audit, or authority conflicts, mark the item blocked or uncertain. Do not speculate, mutate the repository, or soften missing evidence.
