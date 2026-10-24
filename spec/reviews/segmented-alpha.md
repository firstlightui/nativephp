---
title: Segmented alpha review evidence
description: Dated development screenshot evidence and remaining release gates for Firstlight Segmented.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/documentation-constitution.md
  - spec/screenshots.json
  - docs/components/segmented.md
  - nativephp.json
  - src/Elements/Segmented.php
  - resources/ios/SegmentedControl.swift
  - resources/ios/SegmentedRenderer.swift
  - resources/android/SegmentedControl.kt
  - resources/android/SegmentedRenderer.kt
  - tests/Feature/SegmentedElementTest.php
  - bin/support/DocumentationScreenshotCapture.php
  - tests/Feature/DocumentationScreenshotCaptureTest.php
---

# Segmented Alpha Review Evidence

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-08-04 |
| Component | `Segmented` / `<firstlight:segmented>` |
| Package revision captured | Dirty working tree based on `c2de840560003ea7321ef25868e58e3baaa899f4` |
| Showcase revision captured | Dirty working tree based on `a07da05cba6dd1c2cd553d6420e604696093f509` |
| iOS simulator | iPhone 17 Pro, iOS 26.5, `EB44C64E-1579-4C13-A1F9-C44FBD496763` |
| Android emulator | Pixel 9 Pro AVD, `emulator-5554` |
| Visual reviewer | Maintainer explicitly approved all four images on 2026-08-04 |

This is development screenshot evidence. It is not clean release evidence and
does not replace physical-device or assistive-technology review.

## Capture evidence

| Result | Command and evidence |
| --- | --- |
| PASS | `bin/capture-doc-screenshots Segmented --showcase=../firstlight-showcase --ios=EB44C64E-1579-4C13-A1F9-C44FBD496763 --android=emulator-5554` published one complete matrix after the focused showcase fixture passed. |
| PASS | The capture guard forced fresh NativePHP development bundles for the requested start URL, waited for the exact Android capture title, restored the original appearances, and rejected neither platform's light/dark pair as identical. |
| PASS | `php artisan test tests/Feature/DocumentationScreenshotCaptureTest.php` — 11 passed, 48 assertions. |
| PASS | Maintainer review accepted the selected, disabled, helper, and error states; light/dark differentiation; native platform presentation; crop; labels; and truncation across all four images. |
| BLOCKED | The package and showcase working trees were dirty, so this matrix was not captured with `--release`. |
| BLOCKED | Physical iOS and Android device, VoiceOver, TalkBack, font scaling, increased contrast, Reduced Motion, RTL, offline behaviour, and host-stability rows are not recorded here. |

## Approved visual evidence

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/segmented/ios-light.png` | `docs/screenshots/segmented/ios-dark.png` |
| Android | `docs/screenshots/segmented/android-light.png` | `docs/screenshots/segmented/android-dark.png` |

The four files were captured from the stable `/captures/segmented` showcase
fixture using the explicit simulator and emulator targets above. No image was
edited to conceal a rendering defect.

## Release checklist

- [x] The screenshot manifest declares the stable route, focused showcase test,
  and four output paths.
- [x] The focused showcase fixture passed against the exact local package
  revision installed by the showcase.
- [x] A complete light/dark matrix was published atomically for iOS and Android.
- [x] The maintainer explicitly approved all four development images.
- [ ] Re-run the guarded workflow with `--release` from clean package and
  showcase revisions and record its report.
- [ ] Record dated physical iOS and Android device and accessibility rows.

## Verdicts

- **Development screenshot readiness: PASS.** The current four-image matrix is
  complete and explicitly approved.
- **Component-release readiness: BLOCKED.** Clean release capture and dated
  physical-device and accessibility evidence remain missing.
- **Catalogue readiness: BLOCKED.** The remaining component matrices and their
  visual approvals are incomplete.

## Catalogue screenshot evidence update — 2026-08-05

The remaining alpha component matrices are now present and visually approved in
the [alpha catalogue screenshot evidence](2026-08-05-alpha-screenshot-evidence.md).
This closes the screenshot-only catalogue gap recorded above. Clean release
capture, physical-device, assistive-technology, and component-specific blocked
rows remain unchanged.

## Component-release review — 2026-10-25

This section supersedes the capture and release-readiness statements above. Earlier rows are kept as history.

### Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-10-25 |
| Review mode | Component-release (`bin/check-component Segmented` without `--development`) |
| Component | `Segmented` / `<firstlight:segmented>` |
| State class | Discrete; server-authoritative single selection |
| Implementation | Paired Firstlight SwiftUI and Material 3 renderers |
| Package revision captured | `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` (`v1.0.0-RC6`), clean |
| Package revision tested | `42e875a91949981b324cd20854e591a292b9475f` on `release/component-reviews`, clean; later commits on the branch change only reviews and documentation |
| Showcase revision | `c7ed28ef489e1caae305fc84961af9f549eb409d` (`firstlightui/showcase` `main`), clean; its lock installs `firstlightui/nativephp` at `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` |
| iOS simulator | Firstlight iPhone 17 Pro iOS 26.5 (`758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC`); XCTest also on Firstlight iPhone 17 Pro iOS 27.0 (`731CC3F2-1651-4522-A5A3-A781710EE166`) |
| Android emulator | `emulator-5554`, AVD Medium_Phone, Android 17 (API 37), `google_apis_playstore_ps16k` (not a Pixel 9 Pro) |
| Visual reviewer | Pending maintainer approval (row below) |
| Physical devices | None used; both device rows remain BLOCKED |

### Release capture

| Result | Command and evidence |
| --- | --- |
| PASS | `bin/capture-doc-screenshot-batch Segmented Tabs SearchField Select Slider --showcase=../showcase --ios=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC --android=emulator-5554 --release` — exit 0 at 2026-10-25 07:09:59 AEDT (batch 4, attempt 2; attempt 1 stopped before publishing because the Android showcase was not foregrounded after an upstream host crash on cold launch, and published nothing). The batch built and launched each host once, ran the focused showcase capture tests, cold-launched `/captures/segmented` per appearance, waited for stable frames, rejected identical light/dark pairs and published the matrix. It then restored the showcase `.env`, both appearances, iOS Reduce Motion and the Android animator scale. |
| PASS | The orchestrator reported `Package: 19d12ff9d48baeddd312a6ef5d1a749bdba585e8` and `Showcase: c7ed28ef489e1caae305fc84961af9f549eb409d` with no `(dirty)` marker. It also verified that the showcase's installed `firstlightui/nativephp` reference equals the package `HEAD`. Published images were moved out of the tree between batches so that each batch started clean, and were committed unchanged. |
| PASS | The agent inspected the four images below for documented states, theme, crop, labels, truncation, system chrome and accidental data. No layout, theme, truncation or chrome defects seen. |
| BLOCKED | Maintainer visual approval of this release matrix is not yet recorded. |

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/segmented/ios-light.png` | `docs/screenshots/segmented/ios-dark.png` |
| Android | `docs/screenshots/segmented/android-light.png` | `docs/screenshots/segmented/android-dark.png` |

Thirty matrices were captured with `bin/capture-doc-screenshot-batch` in six five-component batches rather than 31 separate `bin/capture-doc-screenshots` runs. Release mode requires both repositories to be clean before launch, and each single-component run publishes into the package tree. The batch entrypoint uses the same `DocumentationScreenshotCapture` guards.

### Automated and consumer evidence

| Result | Command and evidence |
| --- | --- |
| PASS | `vendor/bin/pest` at `42e875a91949981b324cd20854e591a292b9475f` — 1470 passed, 5 skipped, 1 deprecation, 4 warnings, exit 0 (48.4 s). |
| PASS | `xcodebuild -scheme FirstlightIOSControls -destination 'platform=iOS Simulator,id=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC' -resultBundlePath ../evidence/run2/ios26.xcresult test` (iPhone 17 Pro, iOS 26.5) — TEST SUCCEEDED: 304 tests, 298 passed, 0 failed, 6 skipped. |
| PASS | Same command on `id=731CC3F2-1651-4522-A5A3-A781710EE166` (iPhone 17 Pro, iOS 27.0) — TEST SUCCEEDED: 304 tests, 298 passed, 0 failed, 6 skipped. |
| PASS | `JAVA_HOME=<jdk-21> tests/android/gradlew -p tests/android testDebugUnitTest verifyPaparazziDebug --rerun-tasks` — BUILD SUCCESSFUL, 35 tasks executed; 309 tests in 87 classes, 0 failures, 0 errors. |
| PASS | `bin/check-docs --development` — "Documentation checks passed.", exit 0. |
| PASS | `bin/check-component <Name> --development` for all 31 manifest components — exit 0 each. |
| PASS | Showcase `php artisan test` at `acc4dc1` (local branch `release/component-review-fixtures` on `c7ed28e`) — 206 passed (4206 assertions). |
| PASS | Showcase `php artisan native:plugin:validate ../nativephp` — exit 0, with one warning: "No bridge_functions defined in manifest". |

Component-specific tests within those runs:

| Result | Test file and evidence |
| --- | --- |
| PASS | `tests/Feature/SegmentedElementTest.php` — included in the full Pest run. |
| PASS | `tests/ios/SegmentedControlSnapshotTests.swift` — iOS 26.5: 19 passed, 0 failed; iOS 27.0: 19 passed, 0 failed. |
| PASS | `tests/ios/SegmentedRendererContractTests.swift` — iOS 26.5: 10 passed, 0 failed; iOS 27.0: 10 passed, 0 failed. |
| PASS | `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SegmentedControlScreenshotTest.kt` — 4 tests, 0 failed, 0 skipped (`testDebugUnitTest` / Paparazzi verify). |
| PASS | `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SegmentedRendererContractTest.kt` — 8 tests, 0 failed, 0 skipped (`testDebugUnitTest` / Paparazzi verify). |
| PASS | `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SegmentedSelectionStateTest.kt` — 5 tests, 0 failed, 0 skipped (`testDebugUnitTest` / Paparazzi verify). |

Focused showcase tests: `tests/Feature/SegmentedCaptureTest.php`, `tests/Feature/SegmentedShowcaseTest.php`. They ran in the full showcase suite above and, for the capture fixture, again inside the guarded capture.

### Showcase demo audit

Audited `firstlightui/showcase` `c7ed28ef489e1caae305fc84961af9f549eb409d` against `docs/components/segmented.md`. Covered on the interactive page:

- stable integer values with a disabled rich option, helper and hint
- server reconciliation with a reset button (programmatic publication)
- unchanged-value rejection with a visible attempt counter
- simple strings and string map; two controls bound to the same model must agree
- `null` (no selection) and an empty inert option set
- whole-control `disabled`, static error, `required` with helper
- long labels

Gaps (listed, not invented):

- A control named only by `a11y-label` is not demonstrated.
- A same-typed value absent from `options` (must stay unselected) is not demonstrated.
- `ValidatesFields` auto-bound error and `native:model.live` are not demonstrated on this page.
- Rapid taps on an accepting control have no emission counter; only the rejecting control counts attempts.

### Constitution review

- **Article I — PASS.** `bin/check-component Segmented --development` passes the manifest, renderer-mapping and prohibited-bridge checks; no WebView or bridge call exists in component source.
- **Article II — PASS.** The public API matches `docs/components/segmented.md` and is enforced by `tests/Feature/SegmentedElementTest.php`.
- **Article III — PASS for applicable state.** State class: Discrete; server-authoritative single selection. Contract tests cover the applicable rejection, programmatic and no-echo rules; the showcase gaps above are not counted as evidence.
- **Article IV —** PASS for simulator/emulator evidence; BLOCKED for physical devices. The production renderers built into the generated hosts and rendered `/captures/segmented` on iOS 26.5 and Android API 37 in the clean release capture.
- **Article V — PASS by implementation audit.** Paired Firstlight SwiftUI and Material 3 renderers; see the development review and documentation platform section. Native feel on hardware is part of the device rows.
- **Article VI — BLOCKED.** Automated accessibility semantics pass, but live VoiceOver/TalkBack, Dynamic Type/font scale, Reduce Motion, increased contrast and RTL on physical devices are not yet recorded.
- **Article VII — PASS.** Semantic theme tokens only; no style-bag or platform escape props (contract tests).
- **Article VIII — PASS.** Unchanged from the development review.
- **Article IX — BLOCKED.** PHP, iOS (26.5 and 27.0), Android, showcase, documentation and release captures pass; physical-device checks (IX.4) and maintainer visual approval are outstanding.
- **Article X — BLOCKED for catalogue readiness.** A component review does not complete the catalogue.
- **Article XI — PASS.** The review skill, structural gate and documentation gate were applied; missing evidence is recorded as BLOCKED.

Article XII is not applicable.

### Release checklist — 2026-10-25

- [x] Clean `--release` capture from `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` and `c7ed28ef489e1caae305fc84961af9f549eb409d` published the four-image matrix (2026-10-25).
- [x] Full package suites (Pest, iOS 26.5 and 27.0 XCTest, Android unit/Paparazzi), `bin/check-component Segmented --development`, `bin/check-docs --development`, showcase suite and plugin validation pass at `42e875a91949981b324cd20854e591a292b9475f`.
- [x] Showcase demo audited against every documented state; gaps listed above.
- [ ] BLOCKED — Maintainer visual approval of the four release images listed above (record reviewer, date and verdict per image).
- [ ] BLOCKED — Physical iOS device row:
  - [ ] Device: record model, iOS version, date and tester. Install the showcase built from `42e875a91949981b324cd20854e591a292b9475f` or later and open **Segmented** from the showcase home (`/components/segmented`).
  - [ ] Priority: Urgent is unavailable; Routine stays selected.
  - [ ] Queue: choose All, confirm Stable queue agrees, press Reset selections; both return to Mine without a change event or replayed animation.
  - [ ] Rejected queue: tap All repeatedly; Mine stays selected and attempts rise by one per tap.
  - [ ] No initial selection starts with no segment selected; No available options and Disabled group stay inert.
  - [ ] Rapid input: alternate Mine/All on Queue as fast as possible; the indicator settles on the last PHP-accepted value and the two Queue controls agree.
  - [ ] Long labels at the largest text size: segment titles may truncate (documented) but VoiceOver/TalkBack read the full name.
  - [ ] VoiceOver: the control announces its label, each segment name, selected state, position, disabled state, required, helper and error text.
  - [ ] Dynamic Type at the largest accessibility size (AX5): field label, helper and error wrap; segment titles stop growing at accessibility sizes and may truncate while the spoken name stays complete.
  - [ ] Reduce Motion on: the iOS selection slide and Material segment transition respect Reduce Motion / removed animations and do not replay on server reconciliation.
  - [ ] Increase Contrast on (and Smart Invert off): selected segment remains distinguishable; iOS Increased Contrast uses the contrast-checked selected text colour.
  - [ ] Right-to-left (device language Arabic or Hebrew, or the Right-to-Left pseudolanguage): segment order mirrors with the layout direction while values stay stable.
  - [ ] Light and dark appearance both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.
- [ ] BLOCKED — Physical Android device row:
  - [ ] Device: record model, Android version, date and tester. Install the showcase built from `42e875a91949981b324cd20854e591a292b9475f` or later and open **Segmented** from the showcase home.
  - [ ] Priority: Urgent is unavailable; Routine stays selected.
  - [ ] Queue: choose All, confirm Stable queue agrees, press Reset selections; both return to Mine without a change event or replayed animation.
  - [ ] Rejected queue: tap All repeatedly; Mine stays selected and attempts rise by one per tap.
  - [ ] No initial selection starts with no segment selected; No available options and Disabled group stay inert.
  - [ ] Rapid input: alternate Mine/All on Queue as fast as possible; the indicator settles on the last PHP-accepted value and the two Queue controls agree.
  - [ ] Long labels at the largest text size: segment titles may truncate (documented) but VoiceOver/TalkBack read the full name.
  - [ ] TalkBack: the control announces its label, each segment name, selected state, position, disabled state, required, helper and error text.
  - [ ] Font size at maximum plus Display size largest (font scale 2.0 where available): field label, helper and error wrap; segment titles stop growing at accessibility sizes and may truncate while the spoken name stays complete.
  - [ ] Remove animations on (Accessibility) or animator scale 0: the iOS selection slide and Material segment transition respect Reduce Motion / removed animations and do not replay on server reconciliation.
  - [ ] High-contrast text / contrast level high: selected segment remains distinguishable; iOS Increased Contrast uses the contrast-checked selected text colour.
  - [ ] Right-to-left (Arabic or Hebrew locale, or Developer options → Force RTL layout direction): segment order mirrors with the layout direction while values stay stable.
  - [ ] Light and dark theme both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.

### Verdicts — 2026-10-25

- **Automated, consumer and simulator/emulator evidence: PASS.**
- **Component-release readiness: BLOCKED.** Only the maintainer visual approval and the two physical-device rows remain; simulator evidence does not substitute for them.
- **Catalogue readiness: BLOCKED.** Every component review, physical-device evidence and the shared alpha gate must pass.
