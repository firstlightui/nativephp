---
title: List Section development review
description: Constitutional review of the Firstlight List Section adapter contract, delegated native renderers, tests, showcase fixture, and outstanding runtime evidence.
status: blocked
audience: maintainer
sources:
  - Constitution.md
  - spec/components/list-section.md
  - docs/components/list-section.md
  - nativephp.json
  - src/Elements/ListSection.php
  - src/Components/ListSection.php
  - vendor/nativephp/mobile-ui/src/Elements/ListSection.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIListRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/ListRenderer.kt
  - tests/Feature/ListSectionElementTest.php
  - docs/screenshots/list-section/ios-light.png
  - docs/screenshots/list-section/ios-dark.png
  - docs/screenshots/list-section/android-light.png
  - docs/screenshots/list-section/android-dark.png
---

# List Section Development Review

Reviewed on 2026-08-23 against package revision
`4c53bedc02ac1cce5b9b560a698b0c28ba56ddf4` and showcase revision
`6b68bf87d9f6e328ef7303a8848e8658b9bcb4a4` on `main`.

**Implementation verdict:** PASS

**Development verdict:** BLOCKED

**Component-release verdict:** BLOCKED

The public contract, upstream `list_section` wire compatibility, PHP validation,
manifest registration, precompiler support, package tests, docs checks, nested
showcase fixtures, and approved documentation matrix pass. Development remains
blocked because standalone section misuse, grouped versus plain chrome, and
manual VoiceOver/TalkBack section semantics have not been recorded on device.

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative first | PASS | The EDGE element publishes the upstream `list_section` wire type consumed by the delegated parent list renderer. No WebView, alternate bridge, generated-host edit, or parallel transport was introduced. |
| II. Familiar and coherent APIs | PASS | The API exposes optional non-empty `header` and `footer` and accepts only List Item children. At least one row is required. Unsupported child types and section-level action props fail before publication. |
| III. Stable values and predictable state | PASS | List Section is a structural action/display child. PHP owns section copy while native code owns grouped or plain section chrome inside the parent List. |
| IV. Equal platform quality | BLOCKED | The adapter delegates section chrome to paired upstream iOS and Android list renderers through the parent List. Automated PHP evidence and the shared `/captures/list` documentation matrix pass. Manual grouped/plain observation and direct-runtime rows remain open. |
| V. Native expression over pixel parity | PASS | The approved matrix shows native section headers, grouped card rows, footers, and platform typography without forced pixel parity. |
| VI. Accessibility is correctness | BLOCKED | Headers and footers are associated with their rows through platform list semantics in source and capture evidence. Manual VoiceOver and TalkBack observation of section boundaries remains open. |
| VII. System-first theming | PASS | Section chrome uses platform-native list styling through the delegated renderers. The public API exposes no arbitrary styling controls. |
| VIII. Small, proven expansion | PASS | List Section composes existing List Item rows and does not introduce a standalone scroll surface or navigation primitive. |
| IX. Evidence-based quality | BLOCKED | Red-first PHP contract evidence, focused package tests, docs checks, showcase tests, guarded screenshot capture, and structural checks pass. Manual accessibility and physical-device evidence remain incomplete. |
| X. Public alpha stewardship | BLOCKED | No tag, publication, release-readiness claim, or public-alpha claim is authorized while runtime, accessibility, and physical-device evidence remain incomplete. |
| XI. Skills enforce the constitution | PASS | The component, documentation, screenshot, review, and verification workflows were applied. Capture reused the approved `/captures/list` fixture and restored host appearance state. |

Article XII is not applicable because List Section requires no constitutional
amendment.

## Passing evidence

```text
vendor/bin/pest tests/Feature/ListSectionElementTest.php tests/Feature/ListContainerElementTest.php tests/Feature/PluginManifestTest.php
PASS — 26 tests, 82 assertions

bin/check-component ListSection --development
PASS

bin/check-docs --development --component=ListSection
PASS

php artisan test tests/Feature/ListCaptureTest.php
PASS in firstlight-showcase — capture fixture includes grouped List Section rows
```

List Section shares the List capture route and matrix documented in
[`list-development.md`](list-development.md):

- `docs/screenshots/list-section/ios-light.png`
- `docs/screenshots/list-section/ios-dark.png`
- `docs/screenshots/list-section/android-light.png`
- `docs/screenshots/list-section/android-dark.png`

These images were copied from the single approved `/captures/list` capture
because both manifest entries describe the same stable fixture.

## Primitive audit

Mobile UI 0.4 `list_section` is a structural collector consumed only by
`list`. Firstlight exposes `<firstlight:list-section>` while preserving the
upstream wire type so iOS `NativeUIListRenderer` and Android `ListRenderer`
recognize grouped sections.

## Remaining evidence

- Verify a section authored outside List fails as documented and does not
  render application content.
- Observe grouped inset cards versus parent `plain` flat rows with sticky
  headers on both platforms.
- Manually verify VoiceOver and TalkBack announce section headers and footers
  distinctly from row content.
- Complete dated physical-device evidence required for component release.

## Honest milestone

List Section is implemented, documented, nested correctly inside List in the
showcase, and represented by the approved shared capture matrix. It is not
development-complete, component-release-ready, or public-alpha-ready until the
manual interaction, accessibility, and physical-device gates above are closed.
