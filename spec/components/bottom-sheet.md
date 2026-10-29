---
title: Bottom Sheet component contract
description: Server-controlled sheet presentation, dismissal, content-fitted height, and paired native renderer contract for Firstlight Bottom Sheet.
status: current
audience: maintainer
sources:
  - Constitution.md
  - nativephp.json
  - vendor/nativephp/mobile-ui/src/Elements/BottomSheet.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIBottomSheetRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/BottomSheetRenderer.kt
  - resources/ios/BottomSheetControl.swift
  - resources/ios/BottomSheetRenderer.swift
  - resources/android/BottomSheetControl.kt
  - resources/android/BottomSheetRenderer.kt
  - tests/ios/BottomSheetRendererTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/BottomSheetTest.kt
  - spec/components/modal.md
---

# Bottom Sheet Component Contract

## Purpose and state class

`<firstlight:bottom-sheet>` is a panel that slides up from the bottom of the
screen to present authored content. It is an action/display presentation: PHP
owns `visible` and the dismiss callback; native code owns sheet chrome, drag
to dismiss, tap outside, and sizing the sheet to its content.

Bottom Sheet is not a Modal, an Alert Dialog, a Confirmation Dialog, a modelled field, or a
shared detent geometry API. Modal remains the full-screen cover/dialog
contract. Alert Dialog remains the one-action acknowledgement contract.
Confirmation Dialog remains the confirm/cancel decision contract.

## Public API

```blade
<firstlight:bottom-sheet
    :visible="$showingFilters"
    a11y-label="Filters"
    @dismiss="closeFilters"
>
    <firstlight:status-label label="Filters" />
</firstlight:bottom-sheet>
```

| Prop or event | Contract |
| --- | --- |
| `visible` | Server-published presentation request. Defaults to unpublished/`false` in the native renderer. |
| `pinned-footer` | Strict bool, default false. When true, at least two direct children are required; the final direct child stays outside the scrolling body. CamelCase alias and `pinnedFooter()` are supported. |
| `a11y-label` | Optional non-empty accessible name for the sheet. The `a11yLabel` alias is accepted. |
| `a11y-hint` | Optional non-empty supplementary guidance. The `a11yHint` alias is accepted. |
| `@dismiss` | Required NativePHP dismissal callback. The handler sets the server property to `false`. |
| `class` | External EDGE layout only; it does not style native sheet chrome. |

There is no `value`, `native:model`, `dismissible`, `detents`, or overlay
`@press`. Height is not authored: on both platforms the sheet opens at the
height of its content, capped at the full-height sheet, and follows the
content as it changes. Short sheets stay short; tall sheets open fully so
every authored control is reachable without a drag. Content taller than the
full-height sheet scrolls on both platforms. With `pinned-footer`, all earlier
children scroll and the final direct child stays visible. Multiple footer
controls use an existing Mobile UI column; no new layout tag is introduced.
The footer should remain compact enough to leave a scrollable body viewport.

Child content is authored inside the paired tag. Sheets are always
user-dismissible through native drag and outside/back dismissal.

## Empty, action, and failure behaviour

An unpublished or `visible=false` sheet occupies no presentation. Drag down,
outside tap, or system back emits `@dismiss` once. Handlers must set
`visible` to `false`. Programmatic publication of `visible=false` is the
accepted closed state.

`detents`, `dismissible`, field bindings, and overlay `@press` fail with
actionable `InvalidArgumentException` messages before publication. `visible`
requires a real boolean when authored. Accessibility strings must be
non-empty when authored.

## Accessibility

Provide `a11y-label` when the sheet has no single visible heading that the
host can use as the surface name; it names the sheet container without
replacing the children's own labels. Drag indicators and dismiss gestures use
native sheet semantics. The sheet height follows Dynamic Type and Android font
scaling because it is measured from the rendered content.

## Platform expression

- iOS presents a SwiftUI `.sheet` with a visible drag indicator and one
  `.height` presentation detent measured from the content (system-capped at
  the large detent). Content sits in a `ScrollView` that only bounces when it
  overflows. Swipe down and taps on the dimmed background dismiss it.
  A pinned footer uses a bottom safe-area inset and contributes to the
  measured content-height detent.
- Android renders a Firstlight Material 3 `ModalBottomSheet` with
  theme-sourced surface and scrim. The sheet is sized to its content and
  skips the partially expanded stop, so content taller than half the screen
  opens fully expanded and every authored control is reachable without a
  drag. Short content opens at its own height. A bounded, vertically scrolling
  body sits above the unscrolled footer, within the native sheet insets.

## Renderer decision

Firstlight adds validation, a required `@dismiss` seam, and the public
`<firstlight:bottom-sheet>` tag. The PHP element still builds on Mobile UI's
`BottomSheet` element, but the public Element Tree type
`firstlight.bottom-sheet` has paired Firstlight renderers: iOS
`BottomSheetRenderer` and Android `FirstlightBottomSheetRenderer`. There is no
manifest adapter.

Mobile UI 0.8's renderers cannot meet the content-height contract: with no
`detents` prop both fall back to `medium,large`. iOS then opens every sheet at
the medium detent. Android keeps Material's partially expanded stop and fills
the content column to full height, so tall content opens at the half-height
stop with its lower controls off-screen. Shared detent props stay rejected so
the catalogue does not promise authored geometry.

## Evidence boundary

Development evidence requires Pest contracts, manifest registration, showcase
fixtures, public documentation, and constitutional review. iOS XCTest covers
presentation state, the single dismiss event, and the measured detent. Android
Robolectric tests check that tall content opens fully expanded and short
content keeps its own height.
Screenshot capture, VoiceOver/TalkBack, and physical-device rows remain
release evidence.
