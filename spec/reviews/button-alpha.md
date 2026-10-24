---
title: Button alpha review evidence
description: Dated contract, adapter, test, consumer, platform-baseline, visual, and release-readiness evidence for Firstlight Button.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/components/button.md
  - docs/components/button.md
  - nativephp.json
  - src/Components/Button.php
  - src/Elements/Button.php
  - tests/Feature/ButtonElementTest.php
  - vendor/nativephp/mobile-ui/src/Components/Button.php
  - vendor/nativephp/mobile-ui/src/Elements/Button.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIButtonRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/ButtonRenderer.kt
---

# Button Alpha Review Evidence

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-08-04 |
| Component | `Button` / `<firstlight:button>` |
| State class | Action/display; PHP owns outcomes and native code owns transient press feedback |
| Implementation | Firstlight API adapter over the official `nativephp/mobile-ui` Button renderers |
| Package revision reviewed | `5ee6817f51c8a8477c93228dd5316c27359c6e2c` |
| Showcase revision reviewed | `8f5ab2edbd1db3d43544d8db405cc570e2457a38` |
| iOS simulator | iPhone 17 Pro, iOS 26.5, `EB44C64E-1579-4C13-A1F9-C44FBD496763` |
| Android emulator | Pixel 9 Pro AVD, `emulator-5554` |
| Visual reviewer | Not approved; attempted captures opened the unrelated Text Field screen and were deleted |

Button exists in Firstlight because consumers reasonably expect a UI library to expose its foundational action control under the same namespace and semantic conventions as the rest of the catalogue. The current component deliberately wraps the adequate official Mobile UI primitive. Its public Element Tree type remains `firstlight.button`, preserving a migration seam for package-owned renderers if a later durable cross-platform requirement outgrows Mobile UI.

This evidence update follows the reviewed package runtime commit. It changes documentation only; the showcase lock resolves `firstlightui/nativephp` to the package revision above.

## Automated and consumer evidence

| Result | Command and evidence |
| --- | --- |
| PASS | Package `composer test` — 144 passed, 519 assertions; five model evals intentionally skipped. |
| PASS | `bin/check-component Button --development` — adapter structure and exact dependency renderer mappings passed. |
| PASS | `bin/check-docs --development` — development documentation checks passed. |
| PASS | `bin/build-docs-artifacts` — generated documentation artefacts rebuilt successfully. |
| PASS | Showcase `composer test` — 23 passed, 282 assertions, including the Button gallery, capture fixture, and press round-trip. |
| PASS | `php artisan native:plugin:validate <package-root>` — manifest valid; expected UI-only warning that no bridge functions are declared. |
| PASS | `xcodebuild -scheme FirstlightIOSControls -destination 'platform=iOS Simulator,id=EB44C64E-1579-4C13-A1F9-C44FBD496763' test` — 35 existing package-native tests passed. Button delegates the installed Mobile UI renderer and therefore adds no duplicate Firstlight Swift test target. |
| PASS | Android `testDebugUnitTest` with JDK 21 — build successful. Button delegates the installed Mobile UI renderer and therefore adds no duplicate Firstlight Kotlin test target. |
| BLOCKED | The guarded Button screenshot command built and launched both hosts, but all four images showed the unrelated in-flight Text Field screen. The images were rejected and removed; this is not visual evidence for Button. |

## Constitution review

- **Article I — PASS.** The Firstlight element publishes through the official EDGE component and maps to the official Mobile UI native renderers; no parallel bridge or WebView exists.
- **Article II — PASS.** The API is limited to a visible label, semantic variant and size, disabled and loading state, leading or trailing icon, accessibility metadata, press, and external layout.
- **Article III — PASS.** Press emits immediately, transient feedback remains native, and PHP owns any subsequent loading, disabled, or result state. Disabled and loading buttons do not emit.
- **Article IV — BLOCKED for development evidence.** The dependency renderer mapping is exact and both package platform baselines pass, but the requested Button route has not yet been visually exercised in the named consumer hosts.
- **Article V — PASS by adapter audit.** The dependency uses SwiftUI Button on iOS and the Material 3 Button family on Android without Firstlight forcing pixel parity.
- **Article VI — BLOCKED.** Source and contract evidence covers labels, roles, disabled state, loading context, screen-reader metadata, scaling, and native target sizing, but the component-specific simulator matrix and manual accessibility review are missing.
- **Article VII — PASS.** The adapter consumes the official Mobile UI semantic theme implementation and exposes no custom colour or platform styling escape props.
- **Article VIII — PASS.** The maintained contract records why the adequate official primitive is wrapped and defines the cross-platform threshold for a later bespoke renderer.
- **Article IX — BLOCKED.** PHP, documentation, manifest, consumer tests, and platform baselines pass, but Button-specific native visual evidence and approval do not.
- **Article X — BLOCKED for catalogue readiness.** The approved public-alpha catalogue is not complete.
- **Article XI — PASS.** Component, platform, documentation, screenshot, and review workflows were applied; failed visual evidence is recorded as a blocker instead of being accepted.

Article XII is not applicable because this adapter introduces no constitutional amendment.

## Release checklist

- [x] Public API, defaults, failure behaviour, state ownership, and event semantics match PHP source and contract tests.
- [x] The manifest declares the adapter dependency and exact official renderer identifiers.
- [x] The package is reinstalled into the showcase and focused plus full consumer tests pass.
- [x] Plugin validation and both existing platform package suites pass.
- [x] Public documentation explicitly records why the expected UI-library control currently wraps Mobile UI and how a later bespoke renderer can replace it without changing markup.
- [ ] Capture and obtain approval for the iOS and Android light/dark Button matrix from the actual `/captures/button` route.
- [ ] Re-run guarded capture in `--release` mode from clean package and showcase commits.
- [ ] Record dated physical iOS and Android device/accessibility rows.

## Verdicts

- **Implementation and non-visual contract: PASS.** The Firstlight API adapter, strict boundary, dependency renderer mapping, consumer interaction, documentation, and package baselines pass.
- **Development readiness: BLOCKED.** The shared generated hosts did not display the requested Button capture route, so there is no accepted component-specific native visual evidence.
- **Component-release readiness: BLOCKED.** Clean release capture, visual approval, and physical-device/accessibility evidence are missing.
- **Catalogue readiness: BLOCKED.** The complete alpha catalogue and every component's shared release evidence are not complete.

## Warning

The screenshot commands exited successfully and produced differentiated files, but process success is not visual correctness. Every image showed a page titled `Firstlight Text Field`; none showed the Button fixture. The four files were deleted and must not be cited as Button evidence.

## Development screenshot evidence update — 2026-08-05

Button's current four-image development matrix is present and visually approved
in the [alpha catalogue screenshot evidence](2026-08-05-alpha-screenshot-evidence.md).
This supersedes the earlier rejected Text Field captures and only the review's
pending or absent screenshot statements. Clean release capture, physical-device,
assistive-technology, and component-specific blocked rows remain unchanged.

## Component-release review — 2026-10-25

This section supersedes the capture and release-readiness statements above. Earlier rows are kept as history.

### Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-10-25 |
| Review mode | Component-release (`bin/check-component Button` without `--development`) |
| Component | `Button` / `<firstlight:button>` |
| State class | Action/display; PHP owns outcomes and native code owns transient press feedback |
| Implementation | Firstlight adapter over the official `nativephp/mobile-ui` `button` renderers |
| Package revision captured | `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` (`v1.0.0-RC6`), clean |
| Package revision tested | `461335df2bcb51e87d092bbdf3a90e6b2a4e7114` on `release/component-reviews`, clean; later commits on the branch change only reviews and documentation |
| Showcase revision | `c7ed28ef489e1caae305fc84961af9f549eb409d` (`firstlightui/showcase` `main`), clean; its lock installs `firstlightui/nativephp` at `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` |
| iOS simulator | Firstlight iPhone 17 Pro iOS 26.5 (`758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC`); XCTest also on Firstlight iPhone 17 Pro iOS 27.0 (`731CC3F2-1651-4522-A5A3-A781710EE166`) |
| Android emulator | `emulator-5554`, AVD Medium_Phone, Android 17 (API 37), `google_apis_playstore_ps16k` (not a Pixel 9 Pro) |
| Visual reviewer | Pending maintainer approval (row below) |
| Physical devices | None used; both device rows remain BLOCKED |

### Release capture

| Result | Command and evidence |
| --- | --- |
| PASS | `bin/capture-doc-screenshot-batch Metric ActivityIndicator Badge Button Callout --showcase=../showcase --ios=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC --android=emulator-5554 --release` — exit 0 at 2026-10-25 07:00:33 AEDT (batch 1). The batch built and launched each host once, ran the focused showcase capture tests, cold-launched `/captures/button` per appearance, waited for stable frames, rejected identical light/dark pairs and published the matrix. It then restored the showcase `.env`, both appearances, iOS Reduce Motion and the Android animator scale. |
| PASS | The orchestrator reported `Package: 19d12ff9d48baeddd312a6ef5d1a749bdba585e8` and `Showcase: c7ed28ef489e1caae305fc84961af9f549eb409d` with no `(dirty)` marker. It also verified that the showcase's installed `firstlightui/nativephp` reference equals the package `HEAD`. Published images were moved out of the tree between batches so that each batch started clean, and were committed unchanged. |
| PASS | The agent inspected the four images below for documented states, theme, crop, labels, truncation, system chrome and accidental data. Defect: on iOS the `icon-trailing="chevron-right"` button renders no glyph (Android shows the chevron). The previously approved iOS image has the same defect; see the bug list. |
| BLOCKED | Maintainer visual approval of this release matrix is not yet recorded. |

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/button/ios-light.png` | `docs/screenshots/button/ios-dark.png` |
| Android | `docs/screenshots/button/android-light.png` | `docs/screenshots/button/android-dark.png` |

Thirty matrices were captured with `bin/capture-doc-screenshot-batch` in six five-component batches rather than 31 separate `bin/capture-doc-screenshots` runs. Release mode requires both repositories to be clean before launch, and each single-component run publishes into the package tree. The batch entrypoint uses the same `DocumentationScreenshotCapture` guards.

### Automated and consumer evidence

| Result | Command and evidence |
| --- | --- |
| PASS | `vendor/bin/pest --log-junit ../evidence/pest.xml` at `461335df2bcb51e87d092bbdf3a90e6b2a4e7114` — 1465 passed, 5 skipped, 1 deprecation, 4 warnings, exit 0 (49.7 s). |
| PASS | `xcodebuild -scheme FirstlightIOSControls -destination 'platform=iOS Simulator,id=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC' -resultBundlePath ../evidence/ios26.xcresult test` (iPhone 17 Pro, iOS 26.5) — TEST SUCCEEDED: 304 tests, 298 passed, 0 failed, 6 skipped. |
| PASS | Same command on `id=731CC3F2-1651-4522-A5A3-A781710EE166` (iPhone 17 Pro, iOS 27.0) — TEST SUCCEEDED: 304 tests, 298 passed, 0 failed, 6 skipped. |
| PASS | `JAVA_HOME=<jdk-21> tests/android/gradlew -p tests/android testDebugUnitTest verifyPaparazziDebug` — BUILD SUCCESSFUL; 309 tests in 87 classes, 0 failures, 0 errors. |
| PASS | `bin/check-docs --development` — "Documentation checks passed.", exit 0. |
| PASS | `bin/check-component <Name> --development` for all 31 manifest components — exit 0 each. |
| PASS | Showcase `php artisan test` at `c7ed28e` — 205 passed (4194 assertions). |
| PASS | Showcase `php artisan native:plugin:validate ../nativephp` — exit 0, with one warning: "No bridge_functions defined in manifest". |

Component-specific tests within those runs:

| Result | Test file and evidence |
| --- | --- |
| PASS | `tests/Feature/ButtonElementTest.php` — included in the full Pest run. |
| N/A | No package-owned Swift or Kotlin renderer: the manifest delegates to `nativephp/mobile-ui` `button`. The full iOS and Android package suites still ran (below). |

Focused showcase tests: `tests/Feature/ButtonCaptureTest.php`, `tests/Feature/ButtonShowcaseTest.php`. They ran in the full showcase suite above and, for the capture fixture, again inside the guarded capture.

### Showcase demo audit

Audited `firstlightui/showcase` `c7ed28ef489e1caae305fc84961af9f549eb409d` against `docs/components/button.md`. Covered on the interactive page:

- five variants, three sizes, leading and trailing icons
- `disabled` and `loading` with hint
- press counter shared by every enabled button

Gaps (listed, not invented):

- A PHP-owned transition (press → `loading` → result, or toggling `disabled`) is not demonstrated.
- `a11y-label` replacing the visible text is not demonstrated.

### Constitution review

- **Article I — PASS.** `bin/check-component Button --development` passes the manifest, renderer-mapping and prohibited-bridge checks; no WebView or bridge call exists in component source.
- **Article II — PASS.** The public API matches `docs/components/button.md` and is enforced by `tests/Feature/ButtonElementTest.php`.
- **Article III — PASS for applicable state.** State class: Action/display; PHP owns outcomes and native code owns transient press feedback. Contract tests cover the applicable rejection, programmatic and no-echo rules; the showcase gaps above are not counted as evidence.
- **Article IV —** PASS for simulator/emulator evidence; BLOCKED for physical devices. The production renderers built into the generated hosts and rendered `/captures/button` on iOS 26.5 and Android API 37 in the clean release capture.
- **Article V — PASS by implementation audit.** Firstlight adapter over the official `nativephp/mobile-ui` `button` renderers; see the development review and documentation platform section. Native feel on hardware is part of the device rows.
- **Article VI — BLOCKED.** Automated accessibility semantics pass, but live VoiceOver/TalkBack, Dynamic Type/font scale, Reduce Motion, increased contrast and RTL on physical devices are not yet recorded.
- **Article VII — PASS.** Semantic theme tokens only; no style-bag or platform escape props (contract tests).
- **Article VIII — PASS.** Unchanged from the development review.
- **Article IX — BLOCKED.** PHP, iOS (26.5 and 27.0), Android, showcase, documentation and release captures pass; physical-device checks (IX.4) and maintainer visual approval are outstanding.
- **Article X — BLOCKED for catalogue readiness.** A component review does not complete the catalogue.
- **Article XI — PASS.** The review skill, structural gate and documentation gate were applied; missing evidence is recorded as BLOCKED.

Article XII is not applicable.

### Release checklist — 2026-10-25

- [x] Clean `--release` capture from `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` and `c7ed28ef489e1caae305fc84961af9f549eb409d` published the four-image matrix (2026-10-25).
- [x] Full package suites (Pest, iOS 26.5 and 27.0 XCTest, Android unit/Paparazzi), `bin/check-component Button --development`, `bin/check-docs --development`, showcase suite and plugin validation pass at `461335df2bcb51e87d092bbdf3a90e6b2a4e7114`.
- [x] Showcase demo audited against every documented state; gaps listed above.
- [ ] BLOCKED — Maintainer visual approval of the four release images listed above (record reviewer, date and verdict per image).
- [ ] BLOCKED — Physical iOS device row:
  - [ ] Device: record model, iOS version, date and tester. Install the showcase built from `461335df2bcb51e87d092bbdf3a90e6b2a4e7114` or later and open **Button** from the showcase home (`/components/button`).
  - [ ] Tap each enabled variant, size and icon button; the count rises by exactly one per press.
  - [ ] Unavailable and Saving changes do not act.
  - [ ] Rapid input: tap Record press as fast as possible; the count equals the number of completed presses.
  - [ ] VoiceOver: button role and visible text as the name; disabled state; the loading button is announced as loading with its hint.
  - [ ] Dynamic Type at the largest accessibility size (AX5): labels grow and wrap within native button sizing; icons stay aligned.
  - [ ] Reduce Motion on: press feedback and loading progress respect Reduce Motion / removed animations.
  - [ ] Increase Contrast on (and Smart Invert off): every variant, including ghost and disabled, stays legible.
  - [ ] Right-to-left (device language Arabic or Hebrew, or the Right-to-Left pseudolanguage): leading/trailing icons mirror; chevron-right follows platform mirroring rules.
  - [ ] Light and dark appearance both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.
- [ ] BLOCKED — Physical Android device row:
  - [ ] Device: record model, Android version, date and tester. Install the showcase built from `461335df2bcb51e87d092bbdf3a90e6b2a4e7114` or later and open **Button** from the showcase home.
  - [ ] Tap each enabled variant, size and icon button; the count rises by exactly one per press.
  - [ ] Unavailable and Saving changes do not act.
  - [ ] Rapid input: tap Record press as fast as possible; the count equals the number of completed presses.
  - [ ] TalkBack: button role and visible text as the name; disabled state; the loading button is announced as loading with its hint.
  - [ ] Font size at maximum plus Display size largest (font scale 2.0 where available): labels grow and wrap within native button sizing; icons stay aligned.
  - [ ] Remove animations on (Accessibility) or animator scale 0: press feedback and loading progress respect Reduce Motion / removed animations.
  - [ ] High-contrast text / contrast level high: every variant, including ghost and disabled, stays legible.
  - [ ] Right-to-left (Arabic or Hebrew locale, or Developer options → Force RTL layout direction): leading/trailing icons mirror; chevron-right follows platform mirroring rules.
  - [ ] Light and dark theme both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.

### Verdicts — 2026-10-25

- **Automated, consumer and simulator/emulator evidence: PASS.**
- **Component-release readiness: BLOCKED.** Only the maintainer visual approval and the two physical-device rows remain; simulator evidence does not substitute for them.
- **Catalogue readiness: BLOCKED.** Every component review, physical-device evidence and the shared alpha gate must pass.
