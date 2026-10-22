---
title: Tipoff gaps development review
description: Catalogue decisions, off-device evidence, and remaining native gates for the second Tipoff brief.
status: blocked
sources:
  - Constitution.md
  - spec/designs/2026-10-22-tipoff-gaps-design.md
  - spec/plans/2026-10-22-metric-plan.md
  - spec/components/metric.md
  - spec/components/status-label.md
  - spec/components/transient-feedback.md
---

# Tipoff gaps development review

Reviewed 2026-10-22 in the Linux orb. Work is local and uncommitted; native runner
selection and simulator/emulator permission have been requested. No package tag,
release, deployment or upstream issue has been created.

| Brief item | Decision and current result |
| --- | --- |
| 1. Accessible tappable surface | Upstream core ownership. No duplicate generic container tag. Design note identifies released collector and both renderer paths; Blade preserves labels, native generic renderers do not consume the requested semantics. Consumer docs name safe Button/List Item alternatives without claiming arbitrary banners are solved. |
| 2. Status Label icon | Implemented off-device with shared icon resolution, typed/platform overrides, decorative semantics and tone foreground. Native captures and iOS execution remain blocked. Existing text-only docs PNGs are preserved and explicitly labelled as old evidence. |
| 3. Metric | New static semantic display; design then plan, scaffold, failing PHP tests then implementation. One grouped accessible description, formatted string value, tabular figures and wrapping. Optional press deferred to core; no tile/panel/layout API. Showcase/capture routes and docs added. Native capture and iOS execution remain blocked. |
| 4. Status history | Design only in the Tipoff gaps design. Fits as semantic outcome history, not generic bars. Pending/empty/count/time semantics and non-colour cues recorded; no tag or implementation. |
| 5. Feedback screen events | Laravel events retained, plus synchronous child-to-screen `emit()` with named action/dismiss payloads. Real screen-lifecycle Undo test proves republished state. Current-screen versus originating-screen/navigation limits and complete Undo example documented. |

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative | PASS | Ordinary elements/manifest/renderers and existing `emit()`; no custom bridge, vendor patch or WebView. |
| II. Familiar APIs | PASS | `icon`/`icon-ios`/`icon-android`, standard accessibility names, label/value/detail; no new Feedback tag. |
| III. State | PASS | Metric is inert; Status Label remains display-only. Feedback removes before handlers, preserves action-before-dismiss order and consumes a semantic record once. |
| IV. Equal platforms | BLOCKED | Both sources exist and Android compiles; iOS tests and both native consumer builds still required. |
| V. Native expression | BLOCKED | SwiftUI text/SF Symbols and Material text/icons are implemented; native icon-bearing light/dark inspection remains pending. |
| VI. Accessibility | BLOCKED | Android Metric semantics prove one description with no repeated fragments; Status Label decorative-icon semantics pass. iOS static grouping is implemented, but VoiceOver/TalkBack runtime checks and native largest-text evidence are pending. |
| VII. Theme | PASS | Semantic NativePHP tokens and package warning colours; no arbitrary style bag. |
| VIII. Small expansion | PASS | Only Metric adds a semantic control; generic pressable/panels remain upstream; history is design-only. |
| IX. Evidence | BLOCKED | Package/consumer/JVM checks pass; native host builds/captures and physical-device evidence are absent. |
| X. Stewardship | PASS | Additive APIs; no version bump, tag or release-readiness claim. |
| XI. Process | PASS | Create, platform, docs and review skills applied; Metric design/plan and public/maintained docs, manifest and showcase coverage exist. |

## Executed checks

- Package full `vendor/bin/pest --compact`: 1,426 passed, 5 skipped, 4 warnings,
  1 deprecated, 5,705 assertions at the combined checkpoint. The subsequent
  selected-state rejection extends the focused Metric contract.
- Focused Metric/FeedbackCenter/StatusLabel suite after that extension: 63 passed,
  266 assertions.
- Full showcase `php artisan test --compact`: 172 passed, 3,334 assertions.
- Full Android `testDebugUnitTest`: BUILD SUCCESSFUL; XML reports 290 tests,
  0 failures, 0 errors, 12 skipped (278 executed).
- `bin/check-component Metric --development` and `StatusLabel --development`:
  passed.
- `bin/build-docs-artifacts`, `bin/check-docs --development`, both repository
  `git diff --check`: passed.
- Showcase `native:plugin:validate`: valid with the expected no-bridge-functions
  warning for this UI-only plugin.
- Inspected Metric Paparazzi light, dark and font-scale 2.0 production-renderer
  development renders: all values visible; long value wraps in full. These use
  the JVM harness and are **not** showcase emulator documentation captures.

## Remaining gates and handoff

Choose the connected Mac runner and explicitly authorize the already-open native
targets before any target listing, build/capture or appearance changes. Install
the exact local package/showcase files there via file transfer, not a remote main
checkout (this orb's work is unpushed). Preserve unrelated local edits. Use the
guarded batch capture for StatusLabel and Metric; add largest text evidence on
both platforms, restore appearances, and inspect every new image. Only approved
native documentation PNGs belong under `docs/screenshots/`.

Android's official `MaterialIcon` helper does not take a variant-font argument;
the icon variant is preserved in wire/configuration, while glyph drawing follows
the official helper. Do not treat JVM icon shims as native glyph evidence.

Overall development and release verdict: **BLOCKED on native evidence**. Finish
those gates before the authorized direct-to-main landing; then replace this
provisional delivery/evidence status with actual commit links and capture paths.
