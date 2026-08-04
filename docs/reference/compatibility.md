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
| NativePHP Mobile | `^4.0` |
| NativePHP Mobile UI | `^0.3` |
| iOS application target | iOS 18.0 or later |
| Android application target | API 29 or later |
| Firstlight Swift package | iOS 18 or later |
| Swift tools | Swift 6.2 |

These are the package's current declared floors. Firstlight does not promise support for earlier versions. Both the iOS and Android renderers are required for a public component; the same documented EDGE example works on both platforms.

## Pre-alpha runtime limitation

The `^4.0` Composer constraint expresses package and source compatibility; it
does not currently mean that NativePHP Mobile 4.0.1 passes Firstlight's public
release gate. Its bundled PHP Element Runtime can suppress a byte-identical
tree publication before either platform renderer receives it. An identical
publication still matters when PHP rejects an event or deliberately keeps the
current value.

Known consequences include a rejected Segmented selection remaining visible,
or a server-authoritative Switch or Pill Group remaining pending and ignoring
later input. Firstlight development therefore uses an unreleased mobile-air
branch for Android publication revisions and a guarded, Android-only binary
proof in the showcase. Neither is a supported release dependency, and the
patched PHP binary must not be distributed.

The public-alpha runtime gate remains blocked until an official NativePHP
release includes a content-independent publication acknowledgement in its
bundled PHP runtime. Track the upstream
[runtime issue](https://github.com/NativePHP/mobile-air/issues/279) and
[implementation work](https://github.com/NativePHP/mobile-air/pull/280).
