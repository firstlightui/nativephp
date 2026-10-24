---
title: Compatibility
description: Current language, framework, platform, and Swift package requirements for Firstlight UI.
type: reference
audience: consumer
sources:
  - composer.json
  - nativephp.json
  - Package.swift
  - src/NativeComponent.php
  - tests/Feature/NativeComponentResponseTest.php
  - resources/android/SegmentedRenderer.kt
  - resources/android/SwitchRenderer.kt
  - resources/android/PillGroupRenderer.kt
---

# Compatibility

| Dependency or platform | Current requirement |
| --- | --- |
| PHP | `^8.4` |
| NativePHP Mobile | `^4.6` |
| NativePHP Mobile UI | `^0.8` |
| iOS application target | iOS 18.0 or later |
| Android application target | API 29 or later |
| Firstlight Swift package | iOS 18 or later |
| Swift tools | Swift 6.2 |

These are the package's current declared floors. Firstlight does not promise support for earlier versions. Both the iOS and Android renderers are required for a public component; the same documented EDGE example works on both platforms.

After changing NativePHP versions, run `composer update` and `php artisan native:install --force` so bundled PHP binaries, native project files, and plugin copies match the resolved release.

## Runtime response contract

### Supported response-revision contract

Extend `FirstlightUI\NativeComponent`, not the upstream EDGE base, for screens
using Firstlight controls, with NativePHP at the floors above.

The base stamps the **final chrome-wrapped root** with the integer prop
`firstlight_response_revision`. Its counter is private, process-wide, and
monotonically increasing across screen instances within the PHP process, not
component state to bind, persist, or set yourself. An otherwise unchanged full
response therefore differs from the previous frame and reaches the renderers.
The one exception is the final frame `back()` publishes before popping the
screen: it keeps the current revision, so an unchanged screen is not republished
mid-pop. On iOS such a frame would re-push the screen being left.

This deliberately bypasses whole-frame equality suppression for each completed
screen response. It trades that deduplication saving for reliable reconciliation
of unchanged values; publication cost still needs soak/performance qualification.

- Normal Blade paths are automatic: a global Laravel `View` returned from
  `render()`, `$this->view()`, and the inferred view all receive the revision.
- Programmatic Element roots must return `$this->response($element)` from
  `render()`. This adds normal screen chrome as well as the root revision.
  Use it only for raw Element content, not an already-completed `view()` or
  `fromView()` response. Custom `wrapWithChrome()` overrides must call the parent.
- Nested children are emitted untouched; partials are unstamped. Neither is an
  independent full response under this contract.
- `renderStreaming()` is final and returns `false`. Streaming bypasses this
  contract and cannot be opted into by a subclass.
- Existing concerns such as `ValidatesFields` remain usable separately, but
  adding a trait to the upstream base does not provide this publication contract.

The revision is **not a per-event acknowledgement**. It does not prevent
publication coalescing or serialize simultaneous proposals. Applications must
not infer that every event receives a separately observable response.
This is a screen-rendering contract, not a replacement for NativePHP's generic
publisher: framework-owned loading placeholders and error/dump overlays are
outside it.

### Upstream context and qualification

The October 2026 Android and iOS PHP 8.5.11 bundles still suppress byte-identical
frames in the shared C runtime. Android `treePublicationId`, shipped in Mobile
4.1.0 ([#280](https://github.com/NativePHP/mobile-air/pull/280)), only helps after
a publication reaches Kotlin. The cross-platform bug
[#365](https://github.com/NativePHP/mobile-air/issues/365) remains upstream;
the response revision makes Firstlight independent of it.

The permanent implementation was checked on October 19, 2026 with Mobile 4.6.0,
Mobile UI 0.8.0, and the official PHP 8.5.11 bundles: 14 nonstreaming phases each
on an Android emulator and iOS simulator. Each phase delivered three proposals
through a normal Blade screen using Firstlight's base, without app-authored
revision props. Repeated Switch/Pill rejection, Segmented rejection, validation,
accepted Switch changes, 500 ms rejection, and rapid same-control bursts passed.
Rapid bursts deliberately coalesce a second tap while pending; they do not
prove one response per physical tap. The earlier spike did not reproduce a
baseline Segmented failure, so these results do not establish a Segmented fix.

Package regression tests cover Blade, inferred views, hand-built responses,
remounts, nested children, and unstamped partials. This is not production
qualification. Firstlight 1.0.0 is a release candidate pending physical-device,
soak/performance, and wider app qualification, including simultaneous controls.
