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
