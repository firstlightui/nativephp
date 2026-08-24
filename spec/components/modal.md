---
title: Modal component contract
description: Server-controlled full-screen presentation, dismissal, content ownership, and Mobile UI adapter contract for Firstlight Modal.
status: current
audience: maintainer
sources:
  - Constitution.md
  - nativephp.json
  - vendor/nativephp/mobile-ui/src/Elements/Modal.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIModalRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/ModalRenderer.kt
  - spec/components/confirmation-dialog.md
---

# Modal Component Contract

## Purpose and state class

`<firstlight:modal>` is a full-screen overlay that presents authored content
above the current screen. It is an action/display presentation: PHP owns
`visible` and the dismiss callback; native code owns cover/dialog chrome,
focus containment, close controls, and motion.

Modal is not a Confirmation Dialog, an Alert Dialog, a Bottom Sheet, a modelled field, or a
layout primitive. Alert Dialog remains the contract for a one-action
acknowledgement. Confirmation Dialog remains the contract for a single
confirm/cancel decision. Bottom Sheet remains the contract for a detent-height
panel that slides up from the bottom.

## Public API

```blade
<firstlight:modal
    :visible="$showingAccount"
    a11y-label="Account details"
    @dismiss="closeAccount"
>
    <firstlight:status-label label="Account details" />
</firstlight:modal>
```

| Prop or event | Contract |
| --- | --- |
| `visible` | Server-published presentation request. Defaults to unpublished/`false` in the native renderer. |
| `dismissible` | Optional boolean. When true (the native default), the host shows a close control and allows system back, outside, or swipe dismissal. |
| `a11y-label` | Optional non-empty accessible name for the presented surface. The `a11yLabel` alias is accepted. |
| `a11y-hint` | Optional non-empty supplementary guidance. The `a11yHint` alias is accepted. |
| `@dismiss` | Required NativePHP dismissal callback. The handler sets the server property to `false`. |
| `class` | External EDGE layout only; it does not style native modal chrome. |

There is no `value`, `native:model`, `@press` on the overlay itself, loading
state, or detent/geometry API. Child content is authored inside the paired
tag. `dismissable` is accepted as an alias of `dismissible`.

## Empty, action, and failure behaviour

An unpublished or `visible=false` Modal occupies no presentation. User close,
back, outside tap, or swipe emits `@dismiss` once when the host allows it.
Handlers must set `visible` to `false`. Programmatic publication of
`visible=false` is the accepted closed state.

Unsupported field bindings, detents, overlay `@press`, and Mobile UI escape
attributes fail with actionable `InvalidArgumentException` messages before
publication. Boolean props require real booleans. Accessibility strings must
be non-empty when authored.

The delegated iOS renderer uses `.fullScreenCover`, whose `onDismiss` can also
run when PHP publishes `visible=false`. Dismiss handlers must therefore be
idempotent.

## Accessibility

Provide `a11y-label` when the presented content has no single visible heading
that the host can use as the surface name. Close controls use the native
"Close" name. VoiceOver and TalkBack focus are contained by the official
renderers.

## Platform expression

- iOS delegates to Mobile UI SwiftUI `.fullScreenCover` with theme-sourced
  background and an optional close button.
- Android delegates to a Material full-screen `Dialog` with theme-sourced
  background, back/outside dismissal when `dismissible`, and an optional close
  icon.

## Adapter decision

Mobile UI 0.4 `modal` already uses paired native presentation primitives for
this contract. Firstlight adds validation, a required `@dismiss` seam, and the
public `<firstlight:modal>` tag rather than paired renderers. The public
Element Tree type is `firstlight.modal`.

## Evidence boundary

Development evidence requires Pest contracts, manifest registration, showcase
fixtures, public documentation, and constitutional review. Adapter-backed
components do not require package-local Swift or Kotlin renderer files.
Screenshot capture, VoiceOver/TalkBack, and physical-device rows remain
release evidence.
