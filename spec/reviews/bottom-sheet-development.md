---
title: Bottom Sheet development review
description: Constitutional review of the Firstlight Bottom Sheet adapter contract, delegated native renderers, tests, showcase fixture, and outstanding runtime evidence.
status: blocked
audience: maintainer
sources:
  - Constitution.md
  - spec/components/bottom-sheet.md
  - docs/components/bottom-sheet.md
  - nativephp.json
  - src/Elements/BottomSheet.php
  - src/Components/BottomSheet.php
  - vendor/nativephp/mobile-ui/src/Elements/BottomSheet.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIBottomSheetRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/BottomSheetRenderer.kt
  - tests/Feature/BottomSheetElementTest.php
  - docs/screenshots/bottom-sheet/ios-light.png
  - docs/screenshots/bottom-sheet/ios-dark.png
  - docs/screenshots/bottom-sheet/android-light.png
  - docs/screenshots/bottom-sheet/android-dark.png
---

# Bottom Sheet Development Review

Reviewed on 2026-08-24 against the in-progress package working tree and
showcase working tree.

**Implementation verdict:** PASS

**Development verdict:** BLOCKED

**Component-release verdict:** BLOCKED

The public contract, Mobile UI adapter mapping, PHP validation, required
`@dismiss` seam, rejection of shared detents, manifest registration,
precompiler support, package tests, docs checks, sibling showcase fixtures,
and approved documentation matrix pass. Development remains blocked because
VoiceOver/TalkBack observation and physical-device evidence have not been
recorded.

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative first | PASS | The EDGE element and delegated upstream `bottom_sheet` renderers use the official plugin manifest, Element Tree, callbacks, and renderer lifecycle. No WebView, alternate bridge, generated-host edit, or parallel transport was introduced. |
| II. Familiar and coherent APIs | PASS | The API exposes `visible`, optional accessibility names, required `@dismiss`, and external-layout-only `class`. `detents`, `dismissible`, and field bindings fail before publication. |
| III. Stable values and predictable state | PASS | Bottom Sheet is an action/display presentation. PHP owns visibility and dismissal; native code owns sheet chrome, drag, and platform height stops. |
| IV. Equal platform quality | BLOCKED | The adapter delegates to paired upstream iOS `.sheet` and Material `ModalBottomSheet` renderers. Automated PHP evidence, generated showcase host builds during capture, native launches, and the approved light/dark matrix pass. Manual drag-to-dismiss, outside dismissal, back dismissal, and iOS direct-runtime rows remain open. |
| V. Native expression over pixel parity | PASS | The approved matrix shows native sheet chrome, grabbers, and scrims. Shared detent strings remain rejected so iOS presentation detents and Material partial/full stops are not forced into one geometry. |
| VI. Accessibility is correctness | BLOCKED | `a11y-label` / `a11y-hint` map through the official a11y props. Manual VoiceOver, TalkBack, and sheet-dismiss semantics remain open. |
| VII. System-first theming | PASS | Delegated renderers use NativePHP theme tokens for surface and scrim. The public API exposes no colour or geometry overrides. |
| VIII. Small, proven expansion | PASS | Bottom Sheet does not replace Modal or Confirmation Dialog and does not introduce virtualization or navigation chrome. |
| IX. Evidence-based quality | BLOCKED | Red-first PHP contracts, focused package tests, docs checks, showcase tests, guarded screenshot capture, and structural checks pass. Manual interaction, accessibility, and physical-device evidence remain incomplete. |
| X. Public alpha stewardship | BLOCKED | No tag, publication, or public-alpha claim is authorized. NativePHP identical-publication remains an upstream catalogue blocker. |
| XI. Skills enforce the constitution | PASS | The create, iOS/Android adapter, documentation, screenshot, review, and verification workflows were applied. Capture used the approved iPhone 17 Pro simulator and Pixel 9 Pro AVD; appearance, motion, and installed start-route state were restored. |

Article XII is not applicable because Bottom Sheet requires no constitutional amendment.

## Passing evidence

```text
vendor/bin/pest tests/Feature/ModalElementTest.php tests/Feature/BottomSheetElementTest.php tests/Feature/PluginManifestTest.php
PASS — 26 tests, 79 assertions

bin/check-component BottomSheet --development
PASS

bin/check-docs --development --component=BottomSheet
PASS

bin/build-docs-artifacts
PASS

php artisan test tests/Feature/ModalShowcaseTest.php tests/Feature/ModalCaptureTest.php tests/Feature/BottomSheetShowcaseTest.php tests/Feature/BottomSheetCaptureTest.php tests/Feature/ShowcaseNavigationTest.php
PASS in firstlight-showcase — 34 tests, 465 assertions

bin/capture-doc-screenshots BottomSheet \
  --showcase=../firstlight-showcase \
  --ios=EB44C64E-1579-4C13-A1F9-C44FBD496763 \
  --android=emulator-5554
PASS — complete restored four-image matrix
```

## Primitive audit

Mobile UI 0.4 `bottom_sheet` already provides sheet presentation, `visible`,
`@dismiss`, and `a11y_label`. Firstlight adds boolean validation, a required
`@dismiss` handler, and rejects `detents` so the shared API does not promise
identical height stops.

## Showcase and documentation evidence

The sibling showcase contains an interactive `/bottom-sheet` gallery, an
isolated `/captures/bottom-sheet` fixture, and a navigation entry. The
capture fixture is stable and registered for four documentation outputs in
`spec/screenshots.json`.

The maintainer authorized the iPhone 17 Pro simulator on iOS 26.5,
`EB44C64E-1579-4C13-A1F9-C44FBD496763`, and the Pixel 9 Pro AVD running
Android 16/API 36 as `emulator-5554`. The guarded workflow built, installed,
launched, and readiness-checked both showcase hosts at the stable
`/captures/bottom-sheet` route, then restored appearance, motion, and the
installed start route.

The complete matrix is:

- `docs/screenshots/bottom-sheet/ios-light.png`
- `docs/screenshots/bottom-sheet/ios-dark.png`
- `docs/screenshots/bottom-sheet/android-light.png`
- `docs/screenshots/bottom-sheet/android-dark.png`

All four images were inspected for full viewport, light/dark appearance,
platform-native sheet chrome, grabber, rounded corners, scrim, Filters and
Unread only content, clipping, truncation, and accidental data. The
maintainer approved the complete matrix on 2026-08-24.

## Remaining evidence

- Manually verify drag-down, outside, and back dismissal on both platforms.
- Manually verify VoiceOver and TalkBack sheet semantics.
- Complete dated physical-device evidence required for component release.

## Honest milestone

Bottom Sheet is implemented, documented, represented by an approved
documentation matrix, and green in the automated PHP and capture lanes. It
is not development-complete, component-release-ready, or public-alpha-ready
until the manual interaction, accessibility, and physical-device gates above
are closed.
