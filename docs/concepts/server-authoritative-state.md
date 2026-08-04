---
title: Server-authoritative state
description: How Firstlight reconciles immediate native interaction with values accepted and published by PHP.
type: explanation
audience: consumer
sources:
  - Constitution.md
  - src/Elements/Segmented.php
  - resources/ios/SegmentedControl.swift
  - resources/ios/SegmentedRenderer.swift
  - resources/android/SegmentedControl.kt
  - resources/android/SegmentedRenderer.kt
  - tests/ios/SegmentedRendererContractTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SegmentedRendererContractTest.kt
---

# Server-authoritative State

Firstlight selection controls treat PHP's published value as authoritative. Native interaction is immediate, but it does not create an independent source of truth.

## Interaction and acceptance

When a person selects an enabled option, the native renderer immediately sends the option's stable value through the component callback. PHP may accept it by publishing that value, transform it according to application rules, or reject it by retaining the existing value.

The renderer reconciles from every resulting Element Tree publication. An accepted value becomes selected. A rejected value returns to, or remains at, the last published selection. Reconciliation itself never emits another change event.

## Repeated attempts and programmatic changes

Each user attempt is semantic. If PHP rejects a choice, selecting that choice again sends another event rather than being suppressed as a duplicate of the rejected attempt.

A programmatic PHP update changes the published selection without masquerading as a user interaction. It therefore updates the native control without firing `@change` back to PHP.

## No selection

`null` means no selection. It is distinct from an authored empty-string value. A same-typed value that is not present in the current options also renders with no selected segment; Firstlight never silently selects the first option.

This timing is why Segmented accepts plain `native:model` and `native:model.live`, but rejects deferred `blur` and `debounce` sync modes.
