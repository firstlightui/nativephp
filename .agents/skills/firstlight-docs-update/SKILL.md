---
name: firstlight-docs-update
description: Use when a Firstlight code, API, renderer, compatibility, or workflow change may make existing documentation or specifications stale.
---

# Update Firstlight Documentation

Map a confirmed change to the smallest accurate documentation update and preserve unaffected content.

## Workflow

1. Read `Constitution.md`, `spec/documentation-constitution.md`, both indexes, and the named change or Git range. Inspect current source and tests; do not infer behaviour from a diff alone.
2. Map changed paths through each page's `sources` metadata. Check ripples into component references, concepts, compatibility, installation, README, both indexes, `spec/documentation.json`, `spec/screenshots.json`, and docs skills. For icon-bearing APIs, read `spec/reference/icons.md` and recheck every shared fallback, platform override, variant, action label, and conflict rule.
3. Classify each candidate page as update, create, remove, or unaffected. Identify whether the change affects public identity, examples, platform parity, accessibility, screenshot appearance/state, or generated LLM content.
4. Present the evidence-backed scope and obtain confirmation before editing. Page deletion and removal from an index require explicit confirmation.
5. Make surgical edits. Preserve unaffected prose, examples, ordering, links, and images. Do not opportunistically rewrite a whole page or modify actively owned component/spec work.
6. Verify new claims against current source and focused tests. Add tests only for a concrete executable documentation failure.
7. For a visual change, update the screenshot manifest if needed and hand off to `firstlight-docs-screenshots`. Preserve existing approved images until replacements pass visual review.
8. Recheck `firstlightui/nativephp`, `FirstlightUI`, `dev.firstlightui`, `github.com/firstlightui/nativephp`, and `firstlightui.dev` wherever identity is affected.
9. Regenerate and validate:

```bash
bin/build-docs-artifacts
bin/check-docs --development
git diff --check
```

## Report

List updated, created, removed, and explicitly unaffected pages. Name source evidence, commands/results, regenerated artefacts, screenshot handoff, skill staleness, and remaining development/release gaps.

Stop when the change is ambiguous, implementation and tests disagree, scope is unconfirmed, a deletion lacks confirmation, or required screenshot evidence is unavailable. Report the exact conflict rather than broadening or guessing.
