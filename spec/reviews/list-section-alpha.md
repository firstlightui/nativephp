---
title: List Section alpha review evidence
description: Component-release evidence, showcase audit and remaining device and approval gates for Firstlight List Section.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/workflows/testing.md
  - spec/workflows/showcase-and-screenshots.md
  - spec/screenshots.json
  - docs/components/list-section.md
  - spec/components/list-section.md
  - spec/reviews/list-section-development.md
  - nativephp.json
  - src/Components/ListSection.php
  - src/Elements/ListSection.php
  - tests/Feature/ListSectionElementTest.php
---

# List Section Alpha Review Evidence

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-10-25 |
| Review mode | Component-release (`bin/check-component ListSection` without `--development`) |
| Component | `ListSection` / `<firstlight:list-section>` |
| State class | Action/display; grouped section consumed inline by the delegated list renderer |
| Implementation | Firstlight adapter over the official `nativephp/mobile-ui` `list_section` renderers |
| Package revision captured | `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` (`v1.0.0-RC6`), clean |
| Package revision tested | `461335df2bcb51e87d092bbdf3a90e6b2a4e7114` on `release/component-reviews`, clean; later commits on the branch change only reviews and documentation |
| Showcase revision | `c7ed28ef489e1caae305fc84961af9f549eb409d` (`firstlightui/showcase` `main`), clean; its lock installs `firstlightui/nativephp` at `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` |
| iOS simulator | Firstlight iPhone 17 Pro iOS 26.5 (`758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC`); XCTest also on Firstlight iPhone 17 Pro iOS 27.0 (`731CC3F2-1651-4522-A5A3-A781710EE166`) |
| Android emulator | `emulator-5554`, AVD Medium_Phone, Android 17 (API 37), `google_apis_playstore_ps16k` (not a Pixel 9 Pro) |
| Visual reviewer | Pending maintainer approval (row below) |
| Physical devices | None used; both device rows remain BLOCKED |

## Release capture

| Result | Command and evidence |
| --- | --- |
| BLOCKED | Not recaptured. In `spec/screenshots.json` the List Section entry reuses `/captures/list` and Android readiness waits for the title "Firstlight List Section", which that page never shows, so a guarded `--release` capture cannot complete. The committed `docs/screenshots/list-section/*` images are unchanged from earlier commits and byte-identical to the `docs/screenshots/list/*` images at `19d12ff`, before this release recapture. A dedicated `/captures/list-section` fixture (or a corrected manifest entry) is needed before a release matrix can be captured. |
| BLOCKED | Maintainer visual approval is not recorded, and cannot be until a release matrix exists. |

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/list-section/ios-light.png` | `docs/screenshots/list-section/ios-dark.png` |
| Android | `docs/screenshots/list-section/android-light.png` | `docs/screenshots/list-section/android-dark.png` |

## Automated and consumer evidence

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
| PASS | `tests/Feature/ListSectionElementTest.php` — included in the full Pest run. |
| N/A | No package-owned Swift or Kotlin renderer: the manifest delegates to `nativephp/mobile-ui` `list_section`. The full iOS and Android package suites still ran (below). |

Focused showcase tests: `tests/Feature/ListShowcaseTest.php`, `tests/Feature/ListItemShowcaseTest.php`. They ran in the full showcase suite above and, for the capture fixture, again inside the guarded capture.

## Showcase demo audit

Audited `firstlightui/showcase` `c7ed28ef489e1caae305fc84961af9f549eb409d` against `docs/components/list-section.md`. Covered on the interactive page:

- header and footer sections in the List showcase (grouped and `plain`)
- header-only section containing swipe rows in the List Item showcase

Gaps (listed, not invented):

- There is no dedicated List Section showcase page; coverage comes from the List and List Item pages.
- Footer-only and header-less/footer-less sections are not demonstrated.
- Programmatic header/footer changes are not demonstrated.

## Constitution review

- **Article I — PASS.** `bin/check-component ListSection --development` passes the manifest, renderer-mapping and prohibited-bridge checks; no WebView or bridge call exists in component source.
- **Article II — PASS.** The public API matches `docs/components/list-section.md` and is enforced by `tests/Feature/ListSectionElementTest.php`.
- **Article III — PASS for applicable state.** State class: Action/display; grouped section consumed inline by the delegated list renderer. Contract tests cover the applicable rejection, programmatic and no-echo rules; the showcase gaps above are not counted as evidence.
- **Article IV —** BLOCKED. The official renderers built into both generated hosts, but no release capture of List Section exists (see Release capture); physical devices are also outstanding.
- **Article V — PASS by implementation audit.** Firstlight adapter over the official `nativephp/mobile-ui` `list_section` renderers; see the development review and documentation platform section. Native feel on hardware is part of the device rows.
- **Article VI — BLOCKED.** Automated accessibility semantics pass, but live VoiceOver/TalkBack, Dynamic Type/font scale, Reduce Motion, increased contrast and RTL on physical devices are not yet recorded.
- **Article VII — PASS.** Semantic theme tokens only; no style-bag or platform escape props (contract tests).
- **Article VIII — PASS.** Unchanged from the development review.
- **Article IX — BLOCKED.** PHP, iOS (26.5 and 27.0), Android, showcase, documentation and release captures pass; physical-device checks (IX.4) and maintainer visual approval are outstanding.
- **Article X — BLOCKED for catalogue readiness.** A component review does not complete the catalogue.
- **Article XI — PASS.** The review skill, structural gate and documentation gate were applied; missing evidence is recorded as BLOCKED.

Article XII is not applicable.
## Release checklist

- [ ] BLOCKED — Clean `--release` capture of a List Section matrix (needs a dedicated capture fixture; see Release capture).
- [x] Full package suites (Pest, iOS 26.5 and 27.0 XCTest, Android unit/Paparazzi), `bin/check-component ListSection --development`, `bin/check-docs --development`, showcase suite and plugin validation pass at `461335df2bcb51e87d092bbdf3a90e6b2a4e7114`.
- [x] Showcase demo audited against every documented state; gaps listed above.
- [ ] BLOCKED — Maintainer visual approval of the four release images listed above (record reviewer, date and verdict per image).
- [ ] BLOCKED — Physical iOS device row:
  - [ ] Device: record model, iOS version, date and tester. Install the showcase built from `461335df2bcb51e87d092bbdf3a90e6b2a4e7114` or later and open **List Section** from the showcase home (`/components/list`).
  - [ ] Collection List: Preferences shows header and footer in grouped style; toggle Plain list for sticky flat headers.
  - [ ] List Item: Grouped rows section shows a header and swipe actions work inside it.
  - [ ] VoiceOver: section header announced as a heading before its rows; footer text reachable after the last row.
  - [ ] Dynamic Type at the largest accessibility size (AX5): headers and footers wrap.
  - [ ] Reduce Motion on: sticky header behaviour respects Reduce Motion / removed animations.
  - [ ] Increase Contrast on (and Smart Invert off): grouped card backgrounds and header text remain legible.
  - [ ] Right-to-left (device language Arabic or Hebrew, or the Right-to-Left pseudolanguage): header and footer alignment mirror.
  - [ ] Light and dark appearance both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.
- [ ] BLOCKED — Physical Android device row:
  - [ ] Device: record model, Android version, date and tester. Install the showcase built from `461335df2bcb51e87d092bbdf3a90e6b2a4e7114` or later and open **List Section** from the showcase home.
  - [ ] Collection List: Preferences shows header and footer in grouped style; toggle Plain list for sticky flat headers.
  - [ ] List Item: Grouped rows section shows a header and swipe actions work inside it.
  - [ ] TalkBack: section header announced as a heading before its rows; footer text reachable after the last row.
  - [ ] Font size at maximum plus Display size largest (font scale 2.0 where available): headers and footers wrap.
  - [ ] Remove animations on (Accessibility) or animator scale 0: sticky header behaviour respects Reduce Motion / removed animations.
  - [ ] High-contrast text / contrast level high: grouped card backgrounds and header text remain legible.
  - [ ] Right-to-left (Arabic or Hebrew locale, or Developer options → Force RTL layout direction): header and footer alignment mirror.
  - [ ] Light and dark theme both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.

## Verdicts

- **Automated, consumer and simulator/emulator evidence: PASS.**
- **Component-release readiness: BLOCKED.** A release capture, the maintainer visual approval and the two physical-device rows remain; simulator evidence does not substitute for them.
- **Catalogue readiness: BLOCKED.** Every component review, physical-device evidence and the shared alpha gate must pass.
