---
title: Firstlight haptics
description: Current haptic vocabulary, its iOS and Android patterns, component defaults, interaction rules and settings.
status: current
audience: maintainer
sources:
  - src/Haptics/Haptic.php
  - src/Haptics/HapticsManager.php
  - src/Haptics/HapticsFake.php
  - src/Facades/Haptics.php
  - src/Elements/Concerns/AcceptsHaptic.php
  - src/Feedback/FeedbackTone.php
  - src/Feedback/PendingFeedback.php
  - src/Concerns/ValidatesFields.php
  - resources/android/FirstlightHaptics.kt
  - resources/android/FirstlightHapticsFunctions.kt
  - resources/ios/FirstlightHaptics.swift
  - resources/ios/FirstlightUIInit.swift
  - nativephp.json
  - tests/Feature/HapticsTest.php
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/HapticsTest.kt
  - tests/ios/HapticsTests.swift
  - docs/how-to/play-haptics.md
---

# Firstlight Haptics

Firstlight owns a semantic haptic vocabulary, a native player on each OS, and
the defaults its controls, Feedback and validation play. NativePHP core's
`Device::vibrate()` is left alone and never called. Decided 13 Nov 2026: all
of this lives in Firstlight core, with no separate plugin and no upstream
change for now.

## Vocabulary and patterns

Android plays `View.performHapticFeedback` constants, which honour Touch
feedback and need no permission. Constants newer than the device fall back as
androidx's `HapticFeedbackConstantsCompat` does (minSdk 29). iOS uses UIKit
feedback generators, and SwiftUI sensory feedback through a root host for the
kinds UIKit lacks. Both follow System Haptics.

| `Haptic` | iOS | Android (API; fallback) |
| --- | --- | --- |
| `success` | Notification success | `CONFIRM` (30; `VIRTUAL_KEY`) |
| `warning` | Notification warning | `REJECT` (30; `LONG_PRESS`). Android has no warning constant |
| `error` | Notification error | `REJECT` (30; `LONG_PRESS`) |
| `selection` | Selection | `SEGMENT_TICK` (34; `CONTEXT_CLICK`) |
| `tick` | Selection | `SEGMENT_FREQUENT_TICK` (34; `CLOCK_TICK`) |
| `toggle-on` / `toggle-off` | Selection | `TOGGLE_ON` / `TOGGLE_OFF` (34; `CONTEXT_CLICK` / `CLOCK_TICK`) |
| `press` | Light impact | `VIRTUAL_KEY` |
| `keypress` | Light impact | `KEYBOARD_TAP` |
| `long-press` | Medium impact | `LONG_PRESS` |
| `threshold-activate` / `threshold-deactivate` | Light / soft impact | `GESTURE_THRESHOLD_ACTIVATE` / `_DEACTIVATE` (34; `CONTEXT_CLICK` / `CLOCK_TICK`) |
| `drag-start` | Medium impact | `DRAG_START` (34; `LONG_PRESS`) |
| `alignment`, `increase`, `decrease`, `level-change` | SwiftUI `.alignment`, `.increase`, `.decrease`, `.levelChange` | `SEGMENT_TICK` (34; `CONTEXT_CLICK`) |
| `start` / `stop` | SwiftUI `.start` / `.stop` | `GESTURE_START` / `GESTURE_END` (30; `VIRTUAL_KEY` / `CONTEXT_CLICK`) |
| `path-complete` | SwiftUI `.pathComplete` | `CONFIRM` (30; `VIRTUAL_KEY`) |
| `none` | Nothing | Nothing |

`Haptics::impact($weight, $intensity)` plays `UIImpactFeedbackGenerator` with
intensity on iOS. On Android it composes the vibrator's TICK, CLICK, LOW_TICK or
THUD primitive at that scale with touch usage, falling back to the predefined
tick, click or heavy click.

## Defaults

| Component | iOS | Android |
| --- | --- | --- |
| Button, Icon Button | Silent; `haptic` plays as the tap fires | Silent; `haptic` plays on touch down |
| Switch, Checkbox | System Switch haptic; Checkbox silent | `toggle-on` / `toggle-off` on the local proposal |
| Pill Group | Silent | `toggle-on` / `toggle-off` |
| Segmented, Tabs | UIKit's own | `selection` |
| Stepper | Silent | `selection` per step |
| Slider | System | `tick` per snapped step |
| Date, Time, Select, menus | System | System |
| Feedback | `success` / `warning` / `danger` play their outcome when sent during an interaction | Same |
| Validation alert | `error` on a press or submit | Same |

Mobile UI draws pull-to-refresh, list swipe actions and `native:pressable`, so
Firstlight adds nothing to them. On Android they stay silent.

## Interaction rules

`ValidatesFields` brackets each `dispatchUiEvent()` and `__invokeInteraction()`
with `HapticsManager::beginInteraction()` and `endInteraction()`. Inside one,
outcomes are held and only the strongest plays when it ends (error, then
warning, then success). Other haptics play at once. Feedback plays its tone
only inside an interaction, unless sent `withoutHaptic()`. Bridge failures are
swallowed: a haptic never breaks the interaction that played it.

## Settings

`Haptics::setEnabled(bool)` stores the app's switch natively, in
`SharedPreferences` (`firstlight_haptics`) on Android and `UserDefaults`
(`firstlight.haptics.enabled`) on iOS. Renderers and bridge calls read it
before playing. The system setting always applies as well.
