---
title: Compatibility
description: Current language, framework, platform, and Swift package requirements for Firstlight UI.
type: reference
audience: consumer
sources:
  - composer.json
  - nativephp.json
  - Package.swift
  - resources/android/SegmentedRenderer.kt
  - resources/android/SwitchRenderer.kt
  - resources/android/PillGroupRenderer.kt
---

# Compatibility

| Dependency or platform | Current requirement |
| --- | --- |
| PHP | `^8.4` |
| NativePHP Mobile | `^4.2` |
| NativePHP Mobile UI | `^0.4` |
| iOS application target | iOS 18.0 or later |
| Android application target | API 29 or later |
| Firstlight Swift package | iOS 18 or later |
| Swift tools | Swift 6.2 |

These are the package's current declared floors. Firstlight does not promise support for earlier versions. Both the iOS and Android renderers are required for a public component; the same documented EDGE example works on both platforms.

After changing NativePHP versions, run `composer update` and `php artisan native:install --force` so bundled PHP binaries, native project files, and plugin copies match the resolved release.

## Pre-alpha runtime limitation

The Composer constraints express package and source compatibility; they do not
currently mean that NativePHP Mobile 4.2.0 passes Firstlight's public release
gate. The bundled PHP Element Runtime can still suppress a byte-identical tree
publication before either platform renderer receives it. An identical
publication still matters when PHP rejects an event or deliberately keeps the
current value.

Known consequences include a rejected Segmented selection remaining visible,
or a server-authoritative Switch or Pill Group remaining pending and ignoring
later input.

NativePHP Mobile 4.1.0 shipped Android `treePublicationId` ([#280](https://github.com/NativePHP/mobile-air/pull/280)), which lets plugin renderers observe equal-tree republications **after** Kotlin has received and posted a tree. That fixes Compose equal-assignment suppression only. It does not help when the shared C runtime returns early on an identical encoded frame and never calls either platform reader — the cross-platform gap tracked in the [publication acknowledgement issue](https://github.com/NativePHP/mobile-air/issues/365).

Firstlight development may still use unreleased mobile-air branches or guarded binary proofs in the showcase while validating reconciliation behaviour. Those patches must not ship as supported release dependencies.

The public-alpha runtime gate remains blocked until an official NativePHP release includes a content-independent publication acknowledgement in its bundled PHP runtime (C `nphp_element_publish()` / `nphp_frame_end()` and both platform readers). See also the closed Android-only report ([#279](https://github.com/NativePHP/mobile-air/issues/279)) and the partial Android fix ([#280](https://github.com/NativePHP/mobile-air/pull/280)) for context.
