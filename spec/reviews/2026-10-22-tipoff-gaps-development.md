---
title: Tipoff gaps development review
description: Catalogue decisions, approved native evidence, and outstanding release qualification for the second Tipoff brief.
status: current
sources:
  - Constitution.md
  - spec/designs/2026-10-22-tipoff-gaps-design.md
  - spec/plans/2026-10-22-metric-plan.md
  - spec/components/metric.md
  - spec/components/status-label.md
  - spec/components/transient-feedback.md
---

# Tipoff gaps development review

Reviewed 2026-10-22–23 in the Linux orb and on the user-authorized mini.local runner.
Implementation commits:
[package](https://github.com/firstlightui/nativephp/commit/6267f6c),
[showcase](https://github.com/firstlightui/showcase/commit/f424aa5), and
[showcase build metadata fix](https://github.com/firstlightui/showcase/commit/646a40d).
The maintainer approved all twelve screenshots and explicitly deferred the
upstream theme race. No stable/alpha qualification is claimed.

| Brief item | Decision and current result |
| --- | --- |
| 1. Accessible tappable surface | Upstream core ownership. No duplicate generic container tag. Design note identifies released collector and both renderer paths; Blade preserves labels, native generic renderers do not consume the requested semantics. Consumer docs name safe Button/List Item alternatives without claiming arbitrary banners are solved. |
| 2. Status Label icon | Implemented with shared icon resolution, typed/platform overrides, decorative semantics and tone foreground. Both platform tests and consumer builds pass. Replacement native matrix and largest-text captures pass agent inspection and have explicit maintainer visual approval. |
| 3. Metric | New static semantic display; design then plan, scaffold, failing PHP tests then implementation. One grouped accessible description, formatted string value, tabular figures and wrapping. Optional press deferred to core; no tile/panel/layout API. Both platform tests and consumer builds pass; complete native matrix and largest-text evidence pass agent inspection. |
| 4. Status history | Design only in the Tipoff gaps design. Fits as semantic outcome history, not generic bars. Pending/empty/count/time semantics and non-colour cues recorded; no tag or implementation. |
| 5. Feedback screen events | Laravel events retained, plus synchronous child-to-screen `emit()` with named action/dismiss payloads. Real screen-lifecycle Undo test proves republished state. Current-screen versus originating-screen/navigation limits and complete Undo example documented. |

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative | PASS | Ordinary elements/manifest/renderers and existing `emit()`; no custom bridge, vendor patch or WebView. |
| II. Familiar APIs | PASS | `icon`/`icon-ios`/`icon-android`, standard accessibility names, label/value/detail; no new Feedback tag. |
| III. State | PASS | Metric is inert; Status Label remains display-only. Feedback removes before handlers, preserves action-before-dismiss order and consumes a semantic record once. |
| IV. Equal platforms | PASS (development) | Both platform test suites and native consumer builds pass. Physical-device release qualification remains separate. |
| V. Native expression | PASS (development) | SwiftUI text/SF Symbols and Material text/icons pass native light/dark inspection on both targets. |
| VI. Accessibility | BLOCKED (release) | Platform semantics tests and native largest-text wrapping pass. Android Metric has five combined descriptions without child fragments; Android Status Label has descriptions and text nodes but no icon-name nodes. iOS expanded trees include grouped labels and descendants. Spoken traversal remains unverified. |
| VII. Theme | PASS | Semantic NativePHP tokens and package warning colours; no arbitrary style bag. |
| VIII. Small expansion | PASS | Only Metric adds a semantic control; generic pressable/panels remain upstream; history is design-only. |
| IX. Evidence | PASS (development), BLOCKED (release) | Package/consumer/platform checks, native host builds, capture review and explicit maintainer visual approval pass. Physical-device, spoken AT, soak/performance and broader app qualification remain absent. |
| X. Stewardship | PASS | Additive APIs; no version bump, tag or release-readiness claim. |
| XI. Process | PASS | Create, platform, docs and review skills applied; Metric design/plan and public/maintained docs, manifest and showcase coverage exist. |

## Executed checks

- Package full `vendor/bin/pest --compact`: 1,427 passed, 5 skipped, 4 warnings,
  1 deprecated, 5,706 assertions; reproduced on mini.local.
- Focused Metric/FeedbackCenter/StatusLabel suite after that extension: 63 passed,
  266 assertions.
- Full showcase `php artisan test --compact`: 172 passed, 3,334 assertions.
- Full Android `testDebugUnitTest`: BUILD SUCCESSFUL; XML reports 290 tests,
  0 failures, 0 errors, 12 skipped (278 executed).
- iOS package tests: TEST SUCCEEDED, exit 0; 282 tests, 6 skipped, 0 failures.
  Diagnostic collection separately emitted a 600-second timeout warning after
  the tests completed; this does not represent a failed test.
- `bin/check-component Metric --development` and `StatusLabel --development`:
  passed.
- `bin/build-docs-artifacts`, `bin/check-docs --development`, both repository
  `git diff --check`: passed.
- Showcase `native:plugin:validate`: valid with the expected no-bridge-functions
  warning for this UI-only plugin.
- Inspected Metric Paparazzi light, dark and font-scale 2.0 production-renderer
  development renders: all values visible; long value wraps in full. These use
  the JVM harness and are **not** showcase emulator documentation captures.
- Guarded native batch: eight default captures (two components, two platforms,
  light/dark) pass agent inspection. Real Material/SF Symbol glyphs are visible;
  Draft intentionally covers the optional text-only Status Label presentation.
- Maximum iOS accessibility text and Android font scale 2.0: long Status Label
  text and the Metric value `1,234.50 milliseconds` remain complete. iOS content
  is scrollable; separate scrolled captures show the full long labels/values
  clear of the floating showcase footer. No ellipsis or internal truncation.

## Remaining gates and handoff

The user authorized the available mini.local simulators and emulators. Exact
package/showcase files were transferred over matching bases, preserving unrelated
untracked directories. Native targets are iPhone 17 Pro / iOS 26.5
(`EB44C64E-1579-4C13-A1F9-C44FBD496763`) and Pixel_9_Pro / Android 16 API 36
(`emulator-5554`). The guarded batch completed for StatusLabel and Metric.
The approved matrix and largest-text PNGs are committed under
`docs/screenshots/{status-label,metric}/`: each directory contains `ios-light.png`,
`ios-dark.png`, `android-light.png`, `android-dark.png`, `ios-largest-text.png` and
`android-largest-text.png`. Approval: maintainer `wojt`, in the
[source thread](https://ampcode.com/threads/T-01a164ab-17b5-7197-997c-db446686336b).

Final evidence archive: `firstlight-gaps-mini-final.tar.gz`, SHA-256
`030646513e9d9209660dcc2ce0d853a0392d3a534ccd55ef43160efc4eae83af`.
Transfer checksum and eight real default PNG entries were verified in the orb;
the default PNGs byte-match the inspected and approved review images.

Restoration verified: iOS remains booted with original dark appearance, large
content size and Reduce Motion enabled. Android night mode, font scale and
animator setting were restored; only the AVD started for this task was shut down.
Both installed start routes are `/`, both preview caches are light, and the host
showcase `.env` hash is unchanged. Build-induced lockfile changes were restored;
preexisting adjacent untracked work was preserved.

The first iOS Metric captures were rejected: they displayed Status Label because
cold launches re-extracted the first bundled route. Installed bundle metadata
proved that the environment build number was serialized as a string; iOS's
numeric-only version reader fell back to zero and mismatched the installed build.
Casting showcase's `version_code` to an integer produces numeric JSON and fixes
the mismatch without a vendor patch. A string environment fixture `"17"` emitted
`{"version_code":"17"}` before and `{"version_code":17}` after; the rebuilt native
bundle contains numeric `1`. Showcase's full suite still passes after the fix.
Rejected captures are not documentation evidence.

An additional interactive-preview check exposed an existing upstream theme race,
not a Metric or Status Label styling change. With system-dark and preview-light,
simulator stdout records both light palettes at lines 100–141, the first tree
publication at 142–143, then configured light/dark palettes from line 149 onward.
Timestamped stderr records the queue worker and four async TSRM contexts booting
after dispatch. Each context boots Mobile UI's service provider, whose
`NativeUIServiceProvider.php:65–66` calls `Theme::load()`; the iOS bridge writes the
shared global store (`NativeUIThemeFunctions.swift:15–18`). Static tree background
props retain the preview palette while native renderers resolve the overwritten
global theme against system appearance, producing unreadable text. All four
OS/preview combinations were executed on both platforms: matched themes pass;
opposite themes fail cold. Warm-toggle reapplication restores Metric text but not
all toggle/chrome/warning contrast, so it is not a qualified workaround. Android
reproduces the visual failure; the detailed worker-bootstrap trace is iOS-only.
The maintainer explicitly deferred this upstream issue. No vendor patch or
parallel theme system is added.

Android's official `MaterialIcon` helper does not take a variant-font argument;
the icon variant is preserved in wire/configuration, while glyph drawing follows
the official helper. Do not treat JVM icon shims as native glyph evidence.

Overall development verdict: **PASS with the explicitly deferred upstream preview
race**. Release qualification remains **BLOCKED** on physical-device, spoken
screen-reader and broader runtime gates; no stable/alpha-ready claim is made here.
Pushing directly to main is authorized. The user requested the next RC when ready;
the separate question about retaining RC1's disclosed qualification limits remains
unanswered and is not inferred from screenshot approval or theme-race deferral.
