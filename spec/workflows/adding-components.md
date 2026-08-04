---
title: Adding Firstlight components
description: Maintained workflow for delivering one public Firstlight control through contract, implementation, consumer evidence, documentation, and review.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/documentation-constitution.md
  - nativephp.json
  - spec/reference/icons.md
  - bin/scaffold-component
  - bin/check-component
  - .agents/skills/firstlight-create-component/SKILL.md
  - .agents/skills/firstlight-ios-component/SKILL.md
  - .agents/skills/firstlight-android-component/SKILL.md
  - .agents/skills/firstlight-review-component/SKILL.md
  - .agents/skills/firstlight-docs-write/SKILL.md
  - .agents/skills/firstlight-docs-screenshots/SKILL.md
---

# Adding Firstlight Components

## Outcome

A component is complete only when one semantic Firstlight API works through an adequate official adapter or paired production renderers, is consumed by the sibling showcase, is documented with both-platform evidence, and passes constitutional review. A generated skeleton, one platform, or a passing unit test is not a completed component.

Use `firstlight-create-component` as the coordinating skill. It delegates platform work to `firstlight-ios-component` and `firstlight-android-component`, documentation to the docs skills, and the final verdict to `firstlight-review-component`.

## Workflow

1. **Define the semantic contract.** State the purpose, stable values, `null` or empty behaviour, events, field metadata, disabled/loading/error behaviour, accessibility semantics, and intended expression on both platforms. Resolve ambiguity before scaffolding.
2. **Apply the shared icon contract when applicable.** Preserve the component's established base icon prop, append `-ios` and `-android` overrides, resolve through NativePHP's typed icon resolver, preserve Android variants, and define decorative or interactive accessibility according to `spec/reference/icons.md`.
3. **Classify state.** Choose discrete, focused text, continuous value, or action/display. This determines native buffering, publication timing, reconciliation, and applicable review evidence.
4. **Audit the official primitive.** Compare the contract with the current `nativephp/mobile-ui` primitive and extension seams.
   - Choose an **adapter** when the official primitive can express every Firstlight prop, event, state, diagnostic, and accessibility semantic.
   - Choose a **renderer** only when the official primitive cannot satisfy the contract. The renderer path requires a Firstlight element and both production renderers.
5. **Write failing PHP contract tests.** Prove public parsing, primitive props, callback registration, errors, and the state contract before implementation.
6. **Create only the required files.** For a renderer path, run `bin/scaffold-component Name` once and replace every `FIRSTLIGHT_NOT_IMPLEMENTED` marker. The command refuses to overwrite existing authored work. For an adapter, add only the minimum adapter files; do not create placeholder Swift or Kotlin.
7. **Implement both platforms.** Apply the iOS and Android skills. Both must satisfy the same authored example and semantic contract through genuine native controls or idiomatic native composition.
8. **Register the contract.** Update `nativephp.json` with the public type, element, Blade component, self-closing behaviour, and real renderer identifiers where applicable. Treat the manifest as authority for registration names.
9. **Dogfood the component.** Install the exact package commit in `firstlightui/showcase`. Add stable application and capture fixtures covering every applicable documented state, then run focused and full consumer tests and build both hosts without editing generated trees.
10. **Document the public API.** Add `docs/components/<slug>.md`, index it, and source every claim from current code and tests. Add the component to `spec/screenshots.json` and capture the approved iOS/Android light/dark matrix.
11. **Run development gates.** Run focused tests while iterating, then the full applicable package, platform, showcase, component, and documentation checks.
12. **Review constitutionally.** Use `firstlight-review-component` and report every relevant Constitution article as `PASS`, `FAIL`, or `BLOCKED` with exact evidence.
13. **Add release evidence separately.** Component-release review requires clean repositories, both consumer builds, approved simulator evidence, completed physical-device rows, accessibility evidence, and no unresolved dependency or upstream prerequisite.

## Structural checker limitation

`bin/check-component` currently models the renderer path: it requires PHP component and element classes, paired Swift and Kotlin control/renderer files, platform tests, and a manifest entry. The component skills also permit a proven adapter path without placeholder native files.

When an adapter is the approved implementation and the checker demands renderer-only files, stop and update the checker to understand adapters. Do not create unused renderers, weaken the public contract, or misclassify the component merely to make the gate green.

The current release checkers also disagree on review location: `bin/check-component` looks under `docs/review/<slug>-alpha.md`, while the documentation constitution, screenshot skill, and `bin/check-docs` use `spec/reviews/<slug>-alpha.md`. `spec/reviews/` is the maintained-evidence boundary. Align the component checker before component release; do not copy one review into both paths to conceal the conflict.

## Evidence ownership

| Evidence | Owner |
| --- | --- |
| Semantic API and state classification | Component contract and `firstlight-create-component` |
| Apple expression and Apple-specific evidence | `firstlight-ios-component` |
| Material expression and Android-specific evidence | `firstlight-android-component` |
| Public page and source mapping | Documentation skills |
| Consumer route, states, and exact package installation | `firstlightui/showcase` |
| Four-image native matrix and visual approval | `firstlight-docs-screenshots` |
| Requirement-by-requirement verdict | `firstlight-review-component` |

## Stop conditions

Stop rather than describing the component as complete when the contract is unresolved, the official seam cannot express it, platforms diverge, a production renderer is excluded from tests, server reconciliation harms native interaction, accessibility evidence is incomplete, the exact package does not build in the showcase, documented states are absent, screenshots are rejected, a physical-device release row is missing, or review returns `FAIL` or `BLOCKED`.

Do not publish, tag, add WebViews or ad hoc bridges, edit generated hosts, invent parallel binding vocabulary, or turn a component-development pass into a catalogue or alpha-readiness claim.
