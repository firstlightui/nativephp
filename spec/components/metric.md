---
title: Metric component contract
description: Static label, tabular value, detail, and grouped native accessibility.
status: current
audience: maintainer
sources:
  - src/Elements/Metric.php
  - src/Components/Metric.php
  - resources/ios/MetricControl.swift
  - resources/ios/MetricRenderer.swift
  - resources/android/MetricControl.kt
  - resources/android/MetricRenderer.kt
  - tests/Feature/MetricElementTest.php
  - tests/ios/MetricSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/MetricTest.kt
  - nativephp.json
---

# Metric

Metric is one static semantic measurement, not a generic text/column/card wrapper.
Mobile UI has text and layout primitives but does not group this domain value into
one accessible element with tabular figures and large-text wrapping. Paired native
renderers own that narrow contract; no layout API or optional press mode is added.

Require nonempty string `label` and formatted string `value`. Optional string
`detail` defaults empty; tone is neutral/info/success/warning/danger. No model,
events, children, selection, disabled or field state. PHP derives `a11y_label` as
`label: value[, detail]`, unless explicitly overridden. Support standard a11y
names/aliases and hint. Reject invalid types, blank required or explicit accessible
text, unsupported tones and action/field props before publication.

iOS uses SwiftUI text styles, `monospacedDigit()`, unbounded vertical text and
`accessibilityElement(children: .ignore)`. Android uses Material text styles with
`tnum`, wrapping and `clearAndSetSemantics`. Both expose one static description;
icons, button roles and callbacks are absent. Tone colours only the value. Text
content remains the primary communication, not colour. Renderers consume current
nodes directly; there is no native draft to reconcile and no event to echo.

Evidence requires PHP publication/diagnostics and actual consumer compilation,
Android merged semantics with no repeated fragments, iOS largest-text growth,
light/dark native showcase captures and largest accessibility-size captures.
VoiceOver/TalkBack swipe and physical-device release checks must be recorded
separately, never inferred from a screenshot or a decoder assertion.
