---
title: Confirmation Dialog component contract
description: Server-controlled presentation, native action roles, dismissal, accessibility, and paired-renderer contract for Firstlight Confirmation Dialog.
status: current
audience: maintainer
sources:
  - Constitution.md
  - nativephp.json
  - vendor/nativephp/mobile/src/Dialog.php
  - vendor/nativephp/mobile/src/PendingAlert.php
  - vendor/nativephp/mobile-ui/src/Elements/Modal.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIModalRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/ModalRenderer.kt
---

# Confirmation Dialog Component Contract

## Purpose and state class

`<firstlight:confirmation-dialog>` asks the user to confirm or cancel one
consequential action. It is an action/presentation component. PHP owns whether
the presentation is requested; the native platform owns dialog layout, action
order, focus containment, dismissal motion, and transient pressed state.

It is not a generic Modal, an alert with arbitrary buttons, a form container,
or a modelled choice. The dialog always has exactly one confirmation action
and one cancellation action.

## Public API

```blade
<firstlight:confirmation-dialog
    :visible="$confirmingDeletion"
    title="Delete appointment?"
    message="This action cannot be undone."
    confirm-label="Delete"
    cancel-label="Keep appointment"
    tone="destructive"
    @press="deleteAppointment"
    @dismiss="cancelDeletion"
/>
```

| Prop or event | Contract |
| --- | --- |
| `visible` | Server-published presentation request. Defaults to `false`. |
| `title` | Required non-empty dialog heading and accessible name. |
| `message` | Required non-empty explanation of the decision and consequence. |
| `confirm-label` | Required non-empty confirmation action label. Defaults to `Confirm`. |
| `cancel-label` | Required non-empty cancellation action label. Defaults to `Cancel`. |
| `tone` | Confirmation role: `default` or `destructive`. Defaults to `default`. |
| `@press` | Required standard action callback for explicit confirmation. |
| `@dismiss` | Required NativePHP dismissal callback for cancel, back, or outside dismissal. |
| `class` | External EDGE layout only; it does not style native dialog chrome. |

Blade documentation uses kebab-case for labels. Attribute adaptation also
accepts `confirmLabel` and `cancelLabel`. There is no value, `native:model`,
change event, loading state, disabled state, optional cancellation, icon,
arbitrary action list, or platform styling escape hatch.

## Events and reconciliation

Confirm dismisses the native presentation and emits `@press` exactly once.
The explicit cancel action and permitted system dismissal emit `@dismiss`
exactly once. Programmatic publication of `visible=false` emits nothing.
Repeated callbacks are suppressed after the first native dismissal.

After either user outcome, the handler sets its server property to `false`.
Publishing `false` closes an open dialog. A later `false` to `true` transition
opens it again. Copy-only publications update a still-open presentation but do
not reopen a dialog the user already dismissed. Missing native callback IDs
fail closed and never present an inert decision.

## Platform expression and accessibility

- iOS uses SwiftUI `confirmationDialog` with a visible title, message, native
  cancel action, and a confirm button whose role becomes `.destructive` only
  for destructive tone. SwiftUI owns action ordering and presentation style.
- Android uses Material 3 `AlertDialog` with title, supporting text, confirm
  and dismiss slots. The destructive confirmation uses the semantic
  destructive theme colour; Material owns surface, order, motion, and focus.

The authored title, message, and action labels provide the complete accessible
dialog structure. Native modal focus containment, VoiceOver and TalkBack
traversal, Dynamic Type or font scaling, dark appearance, contrast, reduced
motion, and right-to-left action ordering remain platform-owned. Native action
controls meet their platform interaction-target baselines.

## Official primitive decision

This is a paired-renderer component. NativePHP Mobile's `Dialog::alert()` is an
imperative service rather than an EDGE element and cannot participate in the
published tree or presentation reconciliation. Mobile UI's Modal is a generic
content container without fixed confirmation/cancellation roles. Adapting
either would leak a second public API or leave action semantics undefined.

Firstlight therefore uses the official SuperNative component seam with genuine
SwiftUI and Material 3 presentations. Both events reuse NativePHP's existing
press-event transport; the public contract invents no bridge event.

## Evidence plan

Pest proves strict copy, visibility, tones, callbacks, diagnostics, tag
compilation, layout-only classes, and manifest identifiers. XCTest and Kotlin
tests prove decoding, action routing, duplicate suppression, programmatic
closure, reconciliation, fail-closed callbacks, renderer compilation, and
representative native presentation snapshots. The showcase covers default,
destructive, long-copy, repeated use, cancel, and programmatic closure.

Physical-device accessibility and presentation evidence remains a
component-release check and does not block an honest off-device development
verdict.
