---
title: Alert Dialog component contract
description: Server-controlled one-action acknowledgement, dismissal, accessibility, and paired-renderer contract for Firstlight Alert Dialog.
status: current
audience: maintainer
sources:
  - Constitution.md
  - nativephp.json
  - vendor/nativephp/mobile/src/Dialog.php
  - spec/components/confirmation-dialog.md
  - spec/components/modal.md
  - spec/components/transient-feedback.md
  - src/Elements/AlertDialog.php
  - resources/ios/AlertDialogControl.swift
  - resources/android/AlertDialogControl.kt
  - tests/Feature/AlertDialogElementTest.php
---

# Alert Dialog Component Contract

## Purpose and state class

`<firstlight:alert-dialog>` presents one blocking acknowledgement. It is an
action/presentation component. PHP owns whether the presentation is requested;
the native platform owns alert chrome, the single action, focus containment,
and dismissal motion.

It is not a Confirmation Dialog, Modal, Bottom Sheet, Callout, Transient
Feedback, or a modelled field. Confirmation Dialog remains the contract for
one confirm action and one cancel action. Modal remains the contract for
authored child content. Transient Feedback remains the non-blocking outcome
queue.

## Public API

```blade
<firstlight:alert-dialog
    :visible="$showingSaved"
    title="Changes saved"
    message="Your profile was updated."
    action-label="OK"
    @dismiss="acknowledgeSaved"
/>
```

| Prop or event | Contract |
| --- | --- |
| `visible` | Server-published presentation request. Defaults to `false`. |
| `title` | Required non-empty heading and accessible name. |
| `message` | Required non-empty explanation. |
| `action-label` | Required non-empty acknowledgement label. Defaults to `OK`. |
| `@dismiss` | Required NativePHP dismissal callback for the action, back, or outside dismissal. |
| `class` | External EDGE layout only; it does not style native alert chrome. |

Blade documentation uses kebab-case. Attribute adaptation also accepts
`actionLabel`. There is no `@press`, cancel action, tone, value,
`native:model`, child slot, loading state, disabled state, icon, or platform
styling escape hatch.

## Events and reconciliation

The action button and permitted system dismissal emit `@dismiss` exactly once.
Programmatic publication of `visible=false` emits nothing. Repeated callbacks
are suppressed after the first native dismissal.

The handler sets its server property to `false`. Publishing `false` closes an
open alert. A later `false` to `true` transition opens it again. Copy-only
publications update a still-open presentation but do not reopen an alert the
user already dismissed. A missing native dismiss callback ID fails closed and
never presents an inert surface.

## Platform expression and accessibility

- iOS uses SwiftUI `alert` with a visible title, message, and one action.
  SwiftUI owns presentation style and action placement.
- Android uses Material 3 `AlertDialog` with title, supporting text, and a
  single confirm slot. Material owns surface, motion, and focus. There is no
  dismiss button.

The authored title, message, and action label provide the complete accessible
dialog structure. Native modal focus containment, VoiceOver and TalkBack
traversal, Dynamic Type or font scaling, dark appearance, contrast, reduced
motion, and right-to-left action ordering remain platform-owned. The native
action control meets its platform interaction-target baseline.

## Official primitive decision

This is a paired-renderer component. NativePHP Mobile's `Dialog::alert()` is an
imperative service rather than an EDGE element and cannot participate in the
published tree or presentation reconciliation. Mobile UI's Modal is a generic
content container. Confirmation Dialog always requires two actions. Adapting
any of those would leak a second public API or leave acknowledgement
semantics undefined.

Firstlight therefore uses the official SuperNative component seam with genuine
SwiftUI and Material 3 alerts. The dismiss event reuses NativePHP's existing
press-event transport; the public contract invents no bridge event.

## Evidence plan

Pest proves strict copy, visibility, callbacks, diagnostics, tag compilation,
layout-only classes, and manifest identifiers. XCTest and Kotlin tests prove
decoding, duplicate suppression, programmatic closure, reconciliation,
fail-closed callbacks, renderer compilation, and representative native
presentation snapshots. The showcase covers default copy, custom action
labels, long copy, repeated use, and programmatic closure.

Physical-device accessibility and presentation evidence, plus documentation
screenshots from the sibling showcase, remain component-release checks and do
not block an honest off-device development verdict.
