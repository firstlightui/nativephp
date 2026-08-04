---
name: firstlight-docs-screenshots
description: Use when creating, refreshing, or verifying Firstlight component screenshots from the native showcase on iOS and Android.
---

# Capture Firstlight Documentation Screenshots

Capture paired native evidence from a stable consumer fixture. Never substitute unit snapshots, a WebView, or a physical device.

## Inputs

Require one or more StudlyCase component names, `../firstlight-showcase`, one explicit iOS Simulator UDID, one explicit Android emulator serial, and development or release mode. Read `Constitution.md`, `spec/documentation-constitution.md`, and every requested component entry in `spec/screenshots.json`.

Target IDs are not permission. Obtain explicit permission before listing, booting, launching, capturing, changing appearance, or stopping a simulator or emulator. If IDs are absent and listing is permitted, list read-only and ask the user to choose. Treat targets as shared resources; never select, switch, reset, or stop them implicitly.

## Workflow

1. Confirm the manifest declares a stable `/captures/<slug>` route, focused showcase test, and all four output paths. Inspect the fixture for fixed authored values, representative states, neutral sample data, and one-phone-viewport fit.
2. Ensure the showcase has installed the exact package revision. Development mode may report dirty repositories; release mode requires both package and showcase clean.
3. Run only the guarded command:

```bash
bin/capture-doc-screenshots Component \
  --showcase=../firstlight-showcase \
  --ios=<simulator-udid> \
  --android=<emulator-serial>
```

Add `--release` only for release evidence. Add `--keep-failed` only when failed temporary captures are needed for diagnosis.

For two or more components, prefer the appearance-batched guard:

```bash
bin/capture-doc-screenshot-batch Component ComponentTwo \
  --showcase=../firstlight-showcase \
  --ios=<simulator-udid> \
  --android=<emulator-serial>
```

The guards verify targets, require generated iOS and Android hosts (`php artisan native:install both` in the showcase), reject physical Android devices, run every focused fixture test, launch exact routes, verify the configured Android application is installed and foregrounded, wait for byte-identical frames or a bounded `0.01%` intentional-animation difference, restore the showcase environment and original appearances, reject identical light/dark output, and publish only complete matrices. Batch capture builds each host once, updates only the installed runtime start URL, cold-launches that same app for each fixture, then captures iOS light, iOS dark, Android light, and Android dark. It enables iOS Reduced Motion before launch and freezes Android's animator-duration scale for deterministic indeterminate states, then restores both exact prior settings and `/` as the installed start route.
4. Present all four absolute PNG paths as an iOS/Android by light/dark matrix. Inspect selected/disabled/error states, theme, native presentation, crop, labels, truncation, system chrome, and accidental data.
5. Obtain explicit visual approval. Re-capture rejected images; do not edit an image to conceal a rendering defect.
6. Regenerate LLM artefacts and run `bin/check-docs --development`. For release mode, record factual revisions, devices/OS, commands, four paths, reviewer/date, and only accessibility or physical-device evidence actually performed under `spec/reviews/`.

## Report

Return package/showcase revisions and dirty markers, exact target IDs, test/capture results, four output paths, visual verdict, and separate remaining development/release gates.

Stop on an unstable fixture, mismatched installed revision, ambiguous target, physical device, capture failure, appearance-restoration failure, visual rejection, or missing release evidence. Do not weaken the matrix or claim approval.
