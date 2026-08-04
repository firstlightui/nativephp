---
title: Showcase and screenshot workflow
description: Current consumer-fixture and guarded capture contract for iOS and Android documentation evidence.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/documentation-constitution.md
  - spec/screenshots.json
  - bin/capture-doc-screenshots
  - bin/capture-doc-screenshot-batch
  - bin/support/DocumentationScreenshotCapture.php
  - tests/Feature/DocumentationScreenshotCaptureTest.php
  - .agents/skills/firstlight-docs-screenshots/SKILL.md
---

# Showcase and Screenshot Workflow

## Repository boundary

The sibling `firstlightui/showcase` repository is the consumer and dogfooding application. In the standard adjacent checkout layout it is available from the package as `../firstlight-showcase`.

The showcase owns application component classes, routes, authored views, and fixture tests. The package owns the public contract, renderers, screenshot manifest, capture orchestrator, images, and documentation review gates. Generated native host trees are build products and must not contain durable fixes.

## Stable fixture contract

Each visual component has a dedicated `/captures/<slug>` route declared in `spec/screenshots.json`. The entry also declares one focused showcase test and exactly four package output paths.

A capture fixture must:

- use fixed, neutral authored values with no user, tenant, credential, or time-dependent data;
- include representative documented states such as selected, disabled, and error where applicable;
- fit in one phone viewport without interaction or scrolling to reveal required evidence;
- publish a deterministic native tree that the focused showcase test verifies;
- use the same authored example on iOS and Android.

Keep broad dogfooding and interaction scenarios in the showcase even when the compact capture route displays only representative states.

## Preconditions

Before capture:

1. Choose one explicit available iOS Simulator UDID and one explicit booted Android emulator serial. Never choose or switch targets implicitly and never substitute a physical Android device.
2. Install the exact package `HEAD` in the showcase. A branch name or semver label is not exact-revision evidence.
3. Ensure both generated NativePHP hosts exist by running `php artisan native:install both` in the showcase when needed.
4. Run the focused fixture test named in `spec/screenshots.json`.
5. Use development mode for working evidence. Release mode requires both package and showcase repositories to be clean before launch.

## Guarded capture

Run only the package orchestrator:

```bash
bin/capture-doc-screenshots Component \
  --showcase=../firstlight-showcase \
  --ios=<simulator-udid> \
  --android=<emulator-serial>
```

Add `--release` only for release evidence. Add `--keep-failed` only while diagnosing a failed temporary matrix.

For two or more components, use the guarded batch entrypoint:

```bash
bin/capture-doc-screenshot-batch Component ComponentTwo \
  --showcase=../firstlight-showcase \
  --ios=<simulator-udid> \
  --android=<emulator-serial>
```

The batch guard builds and launches each native host once, then changes only the
installed showcase runtime's start URL and cold-launches the same app for each
exact stable route. It does not invoke custom URL schemes, rebuild between
components, or modify generated host source.
It captures all iOS light routes, iOS dark routes, Android light routes, and
Android dark routes in that order. The showcase environment and both original
appearances are restored even when capture fails. The Android animator-duration
scale is temporarily set to zero so documented indeterminate states can produce
stable evidence, and iOS Reduced Motion is enabled before launch for the same
reason. Both settings are restored to their exact prior values. Route switching is
capture orchestration only; the public showcase menu continues to open its
interactive component pages.

The orchestrators verify the package revision installed by Composer, both explicit targets, emulator status, generated hosts, and every focused showcase test. They launch exact routes, confirm the configured Android application is installed and foregrounded, wait for stable frames, capture both appearances, restore original appearance settings, reject byte-identical theme pairs, and publish only complete matrices. Consecutive frames must be byte-identical or differ across no more than `0.01%` of pixels, which admits a small intentional native progress animation without admitting a transition or unstable page.

NativePHP process exit alone is not capture success. Installation, foreground state, stable frames, four distinct outputs, and appearance restoration must all pass.

## Output and review

The required matrix for `<slug>` is:

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/<slug>/ios-light.png` | `docs/screenshots/<slug>/ios-dark.png` |
| Android | `docs/screenshots/<slug>/android-light.png` | `docs/screenshots/<slug>/android-dark.png` |

Inspect all four images for documented state, native presentation, correct theme, crop, labels, truncation, system chrome, and accidental data. Obtain explicit visual approval. Re-capture a rejected image through the stable fixture; do not edit pixels to hide a renderer or fixture defect.

After approval, regenerate `llms.txt` and `llms-full.txt` and run `bin/check-docs --development`. The generated LLM files contain public pages only; the command is still required because screenshot paths and documentation contracts share the same validation pass.

## Development and release evidence

Development evidence may come from dirty repositories and may defer a physical-device row or release review record. Report those facts without presenting development evidence as release-ready.

Release evidence records the exact package and showcase commits, clean status, device names and OS versions, commands, four image paths, reviewer and date, explicit visual verdict, and only accessibility or physical-device checks actually performed. Store that factual review under `spec/reviews/<slug>-alpha.md`. Missing physical-device or accessibility evidence remains missing; a screenshot does not imply it.

On any target ambiguity, revision mismatch, unstable fixture, launch failure, foreground failure, capture failure, identical theme pair, restoration failure, or visual rejection, stop without replacing the last approved complete matrix.
