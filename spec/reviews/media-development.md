---
title: Media development review
description: Constitutional review of the Firstlight Media field contract, MediaValue Storage commit, paired crop sheets, tests, showcase fixture, and outstanding runtime evidence.
status: blocked
audience: maintainer
sources:
  - Constitution.md
  - spec/components/media.md
  - docs/components/media.md
  - nativephp.json
  - src/Elements/Media.php
  - src/Components/Media.php
  - src/Media/MediaValue.php
  - src/Media/MediaStorage.php
  - src/Media/MediaValidation.php
  - resources/ios/MediaControl.swift
  - resources/ios/MediaRenderer.swift
  - resources/android/MediaControl.kt
  - resources/android/MediaRenderer.kt
  - tests/Feature/MediaElementTest.php
  - tests/Feature/MediaValueTest.php
  - tests/Feature/MediaStorageTest.php
  - tests/Feature/MediaValidationTest.php
  - docs/screenshots/media/ios-light.png
  - docs/screenshots/media/ios-dark.png
  - docs/screenshots/media/android-light.png
  - docs/screenshots/media/android-dark.png
---

# Media Development Review

Reviewed on 2026-08-24 against the in-progress package working tree on
`feature/media-field` and the sibling showcase working tree.

**Implementation verdict:** PASS

**Development verdict:** BLOCKED

**Component-release verdict:** BLOCKED

The public contract, custom paired native renderers, MediaValue / Storage
commit helpers, ValidatesFields participation, PHP diagnostics, manifest
registration, precompiler support, package tests, docs checks, showcase
capture fixture, and approved documentation matrix pass. Development remains
blocked because interactive picker/crop observation, VoiceOver/TalkBack, and
physical-device evidence have not been recorded.

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative first | PASS | The EDGE element and Firstlight-owned iOS/Android Media renderers use the official plugin manifest, Element Tree, callbacks, and renderer lifecycle. No WebView, alternate bridge, generated-host edit, or parallel transport was introduced. |
| II. Familiar and coherent APIs | PASS | The API exposes required `mode` (`image`\|`document`), label/helper/error, `required`/`disabled`, `disk`/`directory`, crop/`aspect` composition, `a11y-label`/`a11y-hint`, `MediaValue`/`null` binding, `@change`/`@clear`, and external-layout-only `class`. Document mode rejects crop/aspect; unsupported attributes fail before publication. |
| III. Stable values and predictable state | PASS | Media is an action/display field with a committed value. Native code owns picker and crop chrome; PHP owns Storage commit and republishes `MediaValue` only after Storage succeeds. Empty is `null`. Preview updates only after the accepted tree republishes. |
| IV. Equal platform quality | BLOCKED | Paired Firstlight iOS and Android field + crop-sheet renderers share one public contract. Automated PHP evidence, Paparazzi/XCTest coverage in package history, native launches on the open iPhone 17 Pro simulator and `emulator-5554`, and the approved light/dark matrix pass. Manual camera/library/file-picker, crop Confirm/Skip, replace/clear, and iOS direct-runtime rows remain open. |
| V. Native expression over pixel parity | PASS | The approved matrix shows native field chrome and Material/SwiftUI expression rather than a shared geometry API. Image mode uses a Firstlight-owned crop sheet; document mode uses the system file picker. |
| VI. Accessibility is correctness | BLOCKED | `a11y-label` / `a11y-hint` map through the official a11y props. Manual VoiceOver, TalkBack, error announcement, and disabled control naming remain open. |
| VII. System-first theming | PASS | Renderers use NativePHP theme tokens. The public API exposes no colour or elevation controls. |
| VIII. Small, proven expansion | PASS | One catalogue control covers image and document modes with author-controlled crop composition instead of separate picker-only tags. |
| IX. Evidence-based quality | BLOCKED | Red-first PHP contracts, focused package tests, docs checks, showcase capture tests, structural checks, and the approved four-image matrix pass. Manual interaction, accessibility, and physical-device evidence remain incomplete. |
| X. Public alpha stewardship | BLOCKED | No tag, publication, or public-alpha claim is authorized. NativePHP identical-publication remains an upstream catalogue blocker. |
| XI. Skills enforce the constitution | PASS | The create, iOS/Android component, documentation, screenshot, and review workflows were applied. Capture used the open iPhone 17 Pro simulator (`EB44C64E-1579-4C13-A1F9-C44FBD496763`) and Android emulator (`emulator-5554`); appearance and animator settings were restored after Android capture. |

Article XII is not applicable because Media requires no constitutional amendment.

## Passing evidence

```text
vendor/bin/pest tests/Feature/MediaElementTest.php tests/Feature/MediaValueTest.php tests/Feature/MediaStorageTest.php tests/Feature/MediaValidationTest.php --compact
PASS — 28 tests, 104 assertions

bin/check-component Media --development
PASS

bin/check-docs --development
PASS

bin/build-docs-artifacts
PASS

php artisan test tests/Feature/MediaCaptureTest.php
PASS in firstlight-showcase — 48 assertions

docs/screenshots/media/{ios,android}-{light,dark}.png
PASS — visual approval 2026-08-24 on open simulator/emulator targets
```

## Outstanding development blockers

- Manual image camera/library pick, crop Confirm/Skip/aspect lock, document file picker, replace, clear, and disabled locked-field observation on both platforms
- VoiceOver and TalkBack naming, error announcement, and focus order
- Physical-device rows for both platforms

## Component-release blockers

- Complete every development blocker above
- Release-mode capture with clean package and showcase revisions
- Catalogue-wide alpha stewardship gates, including NativePHP identical-tree publication

## Overall

Media is implementation-complete for development review with an approved
documentation matrix. Development and component-release remain **BLOCKED**
until interactive, accessibility, and physical-device evidence are recorded.
No public-alpha claim is authorized.
