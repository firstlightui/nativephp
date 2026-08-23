---
title: Modal development review
description: Constitutional review of the Firstlight Modal adapter contract, delegated native renderers, tests, showcase fixture, and outstanding runtime evidence.
status: blocked
audience: maintainer
sources:
  - Constitution.md
  - spec/components/modal.md
  - docs/components/modal.md
  - nativephp.json
  - src/Elements/Modal.php
  - src/Components/Modal.php
  - vendor/nativephp/mobile-ui/src/Elements/Modal.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIModalRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/ModalRenderer.kt
  - tests/Feature/ModalElementTest.php
  - docs/screenshots/modal/ios-light.png
  - docs/screenshots/modal/ios-dark.png
  - docs/screenshots/modal/android-light.png
  - docs/screenshots/modal/android-dark.png
---

# Modal Development Review

Reviewed on 2026-08-24 against the in-progress package working tree and
showcase working tree.

**Implementation verdict:** PASS

**Development verdict:** BLOCKED

**Component-release verdict:** BLOCKED

The public contract, Mobile UI adapter mapping, PHP validation, required
`@dismiss` seam, manifest registration, precompiler support, package tests,
docs checks, sibling showcase fixtures, and approved documentation matrix
pass. Development remains blocked because VoiceOver/TalkBack observation and
physical-device evidence have not been recorded.

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative first | PASS | The EDGE element and delegated upstream `modal` renderers use the official plugin manifest, Element Tree, callbacks, and renderer lifecycle. No WebView, alternate bridge, generated-host edit, or parallel transport was introduced. |
| II. Familiar and coherent APIs | PASS | The API exposes `visible`, optional `dismissible`, optional accessibility names, required `@dismiss`, and external-layout-only `class`. Field bindings, detents, and overlay `@press` fail before publication. |
| III. Stable values and predictable state | PASS | Modal is an action/display presentation. PHP owns visibility and dismissal; native code owns cover/dialog chrome. Dismiss handlers are documented as idempotent because iOS `.fullScreenCover` can also notify PHP when `visible=false` is published. |
| IV. Equal platform quality | BLOCKED | The adapter delegates to paired upstream iOS `.fullScreenCover` and Android full-screen `Dialog` renderers with the same public contract. Automated PHP evidence, generated showcase host builds during capture, native launches, and the approved light/dark matrix pass. Manual close, back, outside dismissal, `dismissible=false`, and iOS direct-runtime rows remain open. |
| V. Native expression over pixel parity | PASS | The approved matrix shows Apple full-screen cover and Material full-screen dialog rather than a shared geometry API. |
| VI. Accessibility is correctness | BLOCKED | `a11y-label` / `a11y-hint` map through the official a11y props. Manual VoiceOver, TalkBack, focus containment, and close-control naming remain open. |
| VII. System-first theming | PASS | Delegated renderers use NativePHP theme tokens. The public API exposes no colour, elevation, or detent controls. |
| VIII. Small, proven expansion | PASS | Modal composes existing child components and does not replace Confirmation Dialog or Bottom Sheet. |
| IX. Evidence-based quality | BLOCKED | Red-first PHP contracts, focused package tests, docs checks, showcase tests, guarded screenshot capture, and structural checks pass. Manual interaction, accessibility, and physical-device evidence remain incomplete. |
| X. Public alpha stewardship | BLOCKED | No tag, publication, or public-alpha claim is authorized. NativePHP identical-publication remains an upstream catalogue blocker. |
| XI. Skills enforce the constitution | PASS | The create, iOS/Android adapter, documentation, screenshot, review, and verification workflows were applied. Capture used the approved iPhone 17 Pro simulator and Pixel 9 Pro AVD; appearance, motion, and installed start-route state were restored. |

Article XII is not applicable because Modal requires no constitutional amendment.

## Passing evidence

```text
vendor/bin/pest tests/Feature/ModalElementTest.php tests/Feature/BottomSheetElementTest.php tests/Feature/PluginManifestTest.php
PASS — 26 tests, 79 assertions

bin/check-component Modal --development
PASS

bin/check-docs --development --component=Modal
PASS

bin/build-docs-artifacts
PASS

php artisan test tests/Feature/ModalShowcaseTest.php tests/Feature/ModalCaptureTest.php tests/Feature/BottomSheetShowcaseTest.php tests/Feature/BottomSheetCaptureTest.php tests/Feature/ShowcaseNavigationTest.php
PASS in firstlight-showcase — 34 tests, 465 assertions

bin/capture-doc-screenshots Modal \
  --showcase=../firstlight-showcase \
  --ios=EB44C64E-1579-4C13-A1F9-C44FBD496763 \
  --android=emulator-5554
PASS — complete restored four-image matrix
```

## Primitive audit

Mobile UI 0.4 `modal` already provides full-screen presentation, `visible`,
`dismissible`, `@dismiss`, and `a11y_label`. Firstlight adds boolean
validation, a required `@dismiss` handler, rejection of modelled values and
detents, and the public `<firstlight:modal>` tag.

## Showcase and documentation evidence

The sibling showcase contains an interactive `/modal` gallery, an isolated
`/captures/modal` fixture, and a navigation entry. The capture fixture is
stable and registered for four documentation outputs in
`spec/screenshots.json`.

The maintainer authorized the iPhone 17 Pro simulator on iOS 26.5,
`EB44C64E-1579-4C13-A1F9-C44FBD496763`, and the Pixel 9 Pro AVD running
Android 16/API 36 as `emulator-5554`. The guarded workflow built, installed,
launched, and readiness-checked both showcase hosts at the stable
`/captures/modal` route, then restored appearance, motion, and the installed
start route.

The complete matrix is:

- `docs/screenshots/modal/ios-light.png`
- `docs/screenshots/modal/ios-dark.png`
- `docs/screenshots/modal/android-light.png`
- `docs/screenshots/modal/android-dark.png`

All four images were inspected for full viewport, light/dark appearance,
platform-native cover and dialog chrome, close control, status label content,
clipping, truncation, and accidental data. The maintainer approved the
complete matrix on 2026-08-24.

## Remaining evidence

- Manually verify close, back, outside dismissal, and `dismissible=false`.
- Manually verify VoiceOver and TalkBack focus containment.
- Complete dated physical-device evidence required for component release.

## Honest milestone

Modal is implemented, documented, represented by an approved documentation
matrix, and green in the automated PHP and capture lanes. It is not
development-complete, component-release-ready, or public-alpha-ready until
the manual interaction, accessibility, and physical-device gates above are
closed.
