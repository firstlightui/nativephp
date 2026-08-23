---
title: Bottom Sheet component contract
description: Server-controlled sheet presentation, dismissal, content ownership, and Mobile UI adapter contract for Firstlight Bottom Sheet.
status: current
audience: maintainer
sources:
  - Constitution.md
  - nativephp.json
  - vendor/nativephp/mobile-ui/src/Elements/BottomSheet.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIBottomSheetRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/BottomSheetRenderer.kt
  - spec/components/modal.md
---

# Bottom Sheet Component Contract

## Purpose and state class

`<firstlight:bottom-sheet>` is a panel that slides up from the bottom of the
screen to present authored content. It is an action/display presentation: PHP
owns `visible` and the dismiss callback; native code owns sheet chrome, drag
to dismiss, tap outside, and platform height stops.

Bottom Sheet is not a Modal, a Confirmation Dialog, a modelled field, or a
shared detent geometry API. Modal remains the full-screen cover/dialog
contract. Confirmation Dialog remains the confirm/cancel decision contract.

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
| `a11y-label` | Optional non-empty accessible name for the sheet. The `a11yLabel` alias is accepted. |
| `a11y-hint` | Optional non-empty supplementary guidance. The `a11yHint` alias is accepted. |
| `@dismiss` | Required NativePHP dismissal callback. The handler sets the server property to `false`. |
| `class` | External EDGE layout only; it does not style native sheet chrome. |

There is no `value`, `native:model`, `dismissible`, `detents`, or overlay
`@press`. Height stops remain native: iOS uses SwiftUI presentation detents
and Android uses Material 3 partial/full sheet stops. Firstlight does not
publish a shared detent string because the platforms cannot express the same
stops faithfully.

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
host can use as the surface name. Drag indicators and dismiss gestures use
native sheet semantics.

## Platform expression

- iOS delegates to Mobile UI SwiftUI `.sheet` with a visible drag indicator
  and the upstream default medium/large detents.
- Android delegates to Material 3 `ModalBottomSheet` with theme-sourced
  surface and scrim. Partial versus full expansion follows the upstream
  default detent string; Firstlight does not expose that string.

## Adapter decision

Mobile UI 0.4 `bottom_sheet` already uses paired native sheet primitives.
Firstlight adds validation, a required `@dismiss` seam, and the public
`<firstlight:bottom-sheet>` tag rather than paired renderers. The public
Element Tree type is `firstlight.bottom-sheet`. Shared detent props are
rejected so the catalogue does not promise identical geometry.

## Evidence boundary

Development evidence requires Pest contracts, manifest registration, showcase
fixtures, public documentation, and constitutional review. Adapter-backed
components do not require package-local Swift or Kotlin renderer files.
Screenshot capture, VoiceOver/TalkBack, and physical-device rows remain
release evidence.
