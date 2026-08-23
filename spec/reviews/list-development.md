---
title: List development review
description: Constitutional review of the Firstlight List adapter contract, delegated native renderers, tests, showcase fixture, and outstanding runtime evidence.
status: blocked
audience: maintainer
sources:
  - Constitution.md
  - spec/components/list.md
  - docs/components/list.md
  - nativephp.json
  - src/Elements/ListContainer.php
  - src/Components/ListContainer.php
  - vendor/nativephp/mobile-ui/src/Elements/NativeList.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIListRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/ListRenderer.kt
  - tests/Feature/ListContainerElementTest.php
  - docs/screenshots/list/ios-light.png
  - docs/screenshots/list/ios-dark.png
  - docs/screenshots/list/android-light.png
  - docs/screenshots/list/android-dark.png
---

# List Development Review

Reviewed on 2026-08-23 against package revision
`4c53bedc02ac1cce5b9b560a698b0c28ba56ddf4` and showcase revision
`6b68bf87d9f6e328ef7303a8848e8658b9bcb4a4` on `main`.

**Implementation verdict:** PASS

**Development verdict:** BLOCKED

**Component-release verdict:** BLOCKED

The public contract, Mobile UI adapter mapping, PHP validation, manifest
registration, precompiler support, package tests, docs checks, sibling showcase
fixtures, and approved documentation matrix pass. Development remains blocked
because manual refresh/end-reached, pull-to-reach pagination, grouped versus
plain section chrome, VoiceOver/TalkBack, and physical-device evidence have
not been recorded.

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative first | PASS | The EDGE element and delegated upstream `list` renderers use the official plugin manifest, Element Tree, callbacks, and renderer lifecycle. No WebView, alternate bridge, generated-host edit, or parallel transport was introduced. |
| II. Familiar and coherent APIs | PASS | The API exposes `separator`, `plain`, `shows-indicators`, optional `@refresh` and `@end-reached`, and external-layout-only `class`. Children are limited to List Item and List Section. Horizontal layout, selection, reorder, embedded controls, and field bindings fail before publication. |
| III. Stable values and predictable state | PASS | List is an action/display container. PHP owns refresh and end-reached callbacks while native code owns scrolling, separators, grouped section chrome, and pull-to-refresh presentation. |
| IV. Equal platform quality | BLOCKED | The adapter delegates to paired upstream iOS and Android list renderers with the same public contract. Automated PHP evidence, generated showcase host builds during capture, native launches, and the approved light/dark matrix pass. Manual `@refresh`, `@end-reached`, grouped/plain section observation, and iOS direct-runtime rows remain open. |
| V. Native expression over pixel parity | PASS | The approved matrix shows native SwiftUI and Material list presentation, grouped section headers and footers, separators, and platform system chrome without forced pixel parity. |
| VI. Accessibility is correctness | BLOCKED | List Item rows retain their own accessibility contract inside the collection. Manual VoiceOver, TalkBack, focus order, Dynamic Type, RTL, and refresh/end-reached announcement behaviour on the collection surface remain open. |
| VII. System-first theming | PASS | The delegated renderers use platform-native list chrome and NativePHP theme tokens. The public API exposes no arbitrary colour, elevation, typography, or per-platform geometry controls. |
| VIII. Small, proven expansion | PASS | List composes existing List Item and List Section contracts and does not pre-empt navigation, selection, reorder, or virtualization APIs beyond the documented finite collection boundary. |
| IX. Evidence-based quality | BLOCKED | Red-first PHP contract evidence, focused package tests, docs checks, showcase tests, guarded screenshot capture, and structural checks pass. Manual interaction, accessibility, and physical-device evidence remain incomplete. |
| X. Public alpha stewardship | BLOCKED | No tag, publication, release-readiness claim, or public-alpha claim is authorized while runtime, accessibility, and physical-device evidence remain incomplete. |
| XI. Skills enforce the constitution | PASS | The component, documentation, screenshot, review, and verification workflows were applied. Capture used the approved iPhone 17 Pro simulator and Pixel 9 Pro AVD; appearance, motion, and installed start-route state were restored. |

Article XII is not applicable because List requires no constitutional amendment.

## Passing evidence

```text
vendor/bin/pest tests/Feature/ListContainerElementTest.php tests/Feature/ListSectionElementTest.php tests/Feature/PluginManifestTest.php
PASS — 26 tests, 82 assertions

bin/check-component ListContainer --development
PASS

bin/check-component ListSection --development
PASS

bin/check-docs --development --component=ListContainer
PASS

bin/check-docs --development --component=ListSection
PASS

bin/build-docs-artifacts
PASS

php artisan test tests/Feature/ListCaptureTest.php tests/Feature/ListShowcaseTest.php
PASS in firstlight-showcase — 3 tests, 36 assertions

bin/capture-doc-screenshots List \
  --showcase=../firstlight-showcase \
  --ios=EB44C64E-1579-4C13-A1F9-C44FBD496763 \
  --android=emulator-5554
PASS — both hosts built, installed, launched, foreground/readiness checked,
and produced a complete restored four-image matrix
```

## Primitive audit

Mobile UI 0.4 `list` already provides the scroll surface, grouped section
consumption, separators, pull-to-refresh, and end-reached hooks that match the
Firstlight contract. Firstlight adds validation, public `<firstlight:list>`
authoring, child composition rules, and documentation over the upstream
primitive rather than paired renderers.

## Showcase and documentation evidence

The sibling showcase contains an interactive `/list` gallery labelled
**Collection List**, an isolated `/captures/list` fixture, navigation entry,
row press and refresh callbacks, grouped List Section content, and a footer
outside the list container. The capture fixture is stable and registered for
four documentation outputs in `spec/screenshots.json`.

The maintainer authorized the iPhone 17 Pro simulator on iOS 26.5,
`EB44C64E-1579-4C13-A1F9-C44FBD496763`, and the Pixel 9 Pro AVD running
Android 16/API 36 as `emulator-5554`. The guarded workflow built, installed,
launched, and readiness-checked both showcase hosts at the stable
`/captures/list` route, then restored appearance, motion, and the installed
start route.

The complete matrix is:

- `docs/screenshots/list/ios-light.png`
- `docs/screenshots/list/ios-dark.png`
- `docs/screenshots/list/android-light.png`
- `docs/screenshots/list/android-dark.png`

All four images were inspected for full viewport, light/dark appearance,
platform-native list expression, Account and Preferences section grouping,
separator presentation, row hierarchy, clipping, truncation, and accidental
data. The maintainer approved the complete matrix on 2026-08-23.

## Remaining evidence

- Manually verify `@refresh` pull-to-refresh and `@end-reached` pagination on
  both platforms.
- Observe grouped versus `plain` section chrome with nested List Section rows.
- Manually verify VoiceOver and TalkBack for section headers, footers, and row
  focus order inside the collection.
- Complete dated physical-device evidence required for component release.

## Honest milestone

List is implemented, documented, represented by an approved documentation matrix,
and green in the automated PHP and capture lanes. It is not development-complete,
component-release-ready, or public-alpha-ready until the manual interaction,
accessibility, and physical-device gates above are closed.
