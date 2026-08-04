---
name: firstlight-docs-screenshots
description: Use when creating, refreshing, or verifying Firstlight component screenshots from the native showcase on iOS and Android.
---

# Capture Firstlight Documentation Screenshots

Capture paired native evidence from a stable consumer fixture. Never substitute unit snapshots, a WebView, or a physical device.

## Inputs

Require a StudlyCase component name, `../firstlight-showcase`, one explicit iOS Simulator UDID, one explicit Android emulator serial, and development or release mode. Read `Constitution.md`, `spec/documentation-constitution.md`, and the component entry in `spec/screenshots.json`.

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

The command verifies targets, requires generated iOS and Android hosts (`php artisan native:install both` in the showcase), rejects physical Android devices, runs the focused fixture test, launches the exact route, verifies the configured Android application is installed and foregrounded, waits for stable frames, restores original appearances, rejects identical light/dark output, and publishes only a complete matrix.
4. Present all four absolute PNG paths as an iOS/Android by light/dark matrix. Inspect selected/disabled/error states, theme, native presentation, crop, labels, truncation, system chrome, and accidental data.
5. Obtain explicit visual approval. Re-capture rejected images; do not edit an image to conceal a rendering defect.
6. Regenerate LLM artefacts and run `bin/check-docs --development`. For release mode, record factual revisions, devices/OS, commands, four paths, reviewer/date, and only accessibility or physical-device evidence actually performed under `spec/reviews/`.

## Report

Return package/showcase revisions and dirty markers, exact target IDs, test/capture results, four output paths, visual verdict, and separate remaining development/release gates.

Stop on an unstable fixture, mismatched installed revision, ambiguous target, physical device, capture failure, appearance-restoration failure, visual rejection, or missing release evidence. Do not weaken the matrix or claim approval.
