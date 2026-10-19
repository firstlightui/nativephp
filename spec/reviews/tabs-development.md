---
title: Tabs development review
description: Constitution review and current automated evidence for the Firstlight Tabs build.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/components/tabs.md
  - spec/reference/catalogue-boundary.md
  - docs/components/tabs.md
  - nativephp.json
  - spec/screenshots.json
  - docs/screenshots/tabs
  - src/Elements/Tabs.php
  - resources/ios/TabsControl.swift
  - resources/ios/TabsRenderer.swift
  - resources/android/TabsControl.kt
  - resources/android/TabsRenderer.kt
  - tests/Feature/TabsElementTest.php
  - tests/ios/TabsSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/TabsTest.kt
---

# Tabs Development Review

**Implementation verdict:** PASS

**Development milestone verdict:** PASS

**Component-release verdict:** BLOCKED

Reviewed on 2026-10-19. Automated evidence was first gathered on package `09eb460` and showcase `918cfa9`, and the implementation landed as package `19c69bf` and showcase `fb5f544`. The screenshot matrix was captured from package `19c69bf` plus the uncommitted iOS horizontal-margin change, with the showcase on `fb5f544`.

## Requirement review

| Requirement | Verdict | Evidence |
| --- | --- | --- |
| Catalogue boundary | PASS | Tabs selects sections of one object and owns no route, pane, pager, or bar. The boundary reference records this distinction. Pinning under a top bar remains layout. |
| Official primitive decision | PASS | NativePHP `tab-row` binds an integer index, selects optimistically, has no badges, and draws a custom iOS strip. See the contract's primitive decision. |
| Public EDGE contract | PASS | `tests/Feature/TabsElementTest.php`: 32 tests cover map and rich tabs, badges, `null`, missing values, empty and disabled groups, 2–5 count limits, diagnostics, sync modes, the unnamed-group warning, Blade `native:model` compilation, and the manifest entry. |
| Server-authoritative state | PASS | Swift `TabsRendererContractTests` and Kotlin `TabsRendererContractTest` prove one proposal per tap, no optimistic selection, rejection by republishing the same value, nested-node reconciliation, and no emission for selected, disabled, out-of-range, or unbound taps. The UIKit coordinator restores the accepted segment before proposing. |
| iOS implementation | PASS | Native `UISegmentedControl` with content-sized segments and a 44-point minimum height. Compiled with no warnings in `resources/ios/Tabs*.swift` on Xcode 27.1. 11 Tabs tests pass on an iPhone 17 Pro simulator (iOS 26.5). |
| Android implementation | PASS | Material 3 `PrimaryTabRow` with a moving `PrimaryIndicator` and `Badge`. When a label does not fit, the row switches to `PrimaryScrollableTabRow`. The full Android JVM suite passes (248 tests). |
| Accessibility structure | PASS (automated) | Robolectric proves the Tab role, selected and disabled state, badge text in the description, a 48 dp height, and collection position semantics. On iOS, an accessibility representation exposes a tab-bar container with a selectable button per segment. Showcase `assertAccessible()` passes. |
| Large text | PASS (with note) | Android switches to the scrollable row at font scale 2.0. iOS caps segment titles at 24 pt like Segmented, so AX5 titles truncate and VoiceOver exposes the full names. |
| Public documentation | PASS | `bin/check-component Tabs --development` passes. The docs page, indexes, LLM artefacts, and reserved screenshot manifest paths are present. |
| Exact-package showcase | PASS | `/tabs` and `/captures/tabs` are added. The full showcase suite passes (168 tests). `native:plugin:validate` passes; its only warning is the intentional absence of bridge functions. |
| iOS snapshot visuals | PASS (with note) | Layer snapshots on iOS 26 do not draw the segmented selection pill, as with the existing Segmented snapshots. The simulator capture confirms the themed pill: red with white text in light, orange with dark text in dark. |
| Runtime screenshot matrix | PASS | `bin/capture-doc-screenshots Tabs` captured `/captures/tabs` on an iPhone 17 Pro simulator (iOS 26.5, `EB44C64E-1579-4C13-A1F9-C44FBD496763`) and the `emulator-5554` Pixel 9 Pro emulator (Android 16). The first capture showed the iOS control edge to edge; the renderer now insets it by the system margin, and the recapture is published. The Android dark selected label uses `onSurface` because the theme accent fails text contrast there, while the indicator keeps the accent. |
| VoiceOver and TalkBack announcements | BLOCKED | Announcement wording, including "Tab 2 of 4", requires a runtime screen-reader pass on both platforms. |
| Physical-device interaction | BLOCKED | Motion, Reduced Motion, increased contrast, RTL, rapid input, and reconciliation have not been reviewed on physical devices. |

## Constitution review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I SuperNative first | PASS | Tabs is an ordinary EDGE element with paired renderers and standard `SELECT_CHANGE` events. It has no WebView or bridge function. |
| II Familiar APIs | PASS | Tabs uses `value`, `native:model`, `label`, `disabled`, `a11y-label`, `a11y-hint`, and `@change`, and exposes no platform vocabulary. Icons are deferred rather than invented. |
| III Stable values | PASS | Values are non-empty strings, and `null` means no selection. Missing values stay unselected. Malformed input fails loudly. |
| IV Equal platforms | PASS | Both renderers implement the same contract. Per-tab icons are deferred under IV.5 because iOS segments cannot show a title and an image together. |
| V Native expression | PASS | iOS uses `UISegmentedControl`, which the HIG permits for view switching. Android uses Material 3 primary tabs and badges. |
| VI Accessibility | PASS (automated), BLOCKED (runtime) | Roles, state, names, position, and targets are covered by automated checks. Screen-reader runtime checks are outstanding. |
| VII Theming | PASS | Both renderers use NativePHP tokens with contrast-safe selected and badge colours. |
| VIII Small expansion | PASS | One control with a concrete consumer (Forge Control hubs). Tabs adds no layout, pager, or pinning. |
| IX Evidence | PASS (development), BLOCKED (release) | Automated evidence and the simulator and emulator screenshot matrix pass. Screen-reader and physical-device rows are missing. |
