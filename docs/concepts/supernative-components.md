---
title: SuperNative components
description: How Firstlight turns shared EDGE markup into genuine iOS and Android controls.
type: explanation
audience: consumer
sources:
  - src/FirstlightTagPrecompiler.php
  - src/Components/Segmented.php
  - src/Elements/Segmented.php
  - nativephp.json
  - resources/ios/SegmentedRenderer.swift
  - resources/android/SegmentedRenderer.kt
---

# SuperNative Components

Firstlight is a NativePHP SuperNative UI plugin. It participates in the same EDGE, Element Tree, native renderer, and wire-event lifecycle as NativePHP's own native UI components.

## From EDGE to an Element Tree

The Firstlight precompiler maps an authored tag such as `<firstlight:segmented>` to its Blade component during a native render. The component declares the `firstlight.segmented` element type. Its PHP element validates authored input, normalises options, and publishes a primitive Element Tree payload.

The payload carries stable values, labels, enabled state, field metadata, accessibility text, and callback identifiers. It does not carry SwiftUI or Compose objects and does not introduce a separate JSON bridge.

## Genuine platform renderers

The plugin manifest maps the same element type to `SegmentedRenderer` on iOS and `dev.firstlightui.plugins.firstlight_ui.ui.SegmentedRenderer` on Android. The iOS renderer creates a native SwiftUI/UIKit control. The Android renderer creates a Material 3 Jetpack Compose control.

The shared API guarantees the component's meaning and behaviour. Each platform retains its own geometry, materials, typography, state layers, motion, and accessibility conventions.

## Semantic events and reconciliation

A native interaction sends a semantic change event with the stable public value, not a renderer index or display label. PHP processes that event and publishes the next Element Tree. The renderer reconciles its control from the newly published value.

That lifecycle keeps application decisions in PHP while ordinary native interaction and presentation remain on the platform UI thread. Read [server-authoritative state](server-authoritative-state.md) for the selection timing contract.
