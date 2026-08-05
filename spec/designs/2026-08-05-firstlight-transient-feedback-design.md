---
title: Firstlight Transient Feedback design
description: App-level FIFO feedback, semantic actions and tones, native lifecycle, accessibility, and evidence boundaries.
status: review
audience: maintainer
sources:
  - Constitution.md
  - roadmap-v2.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - spec/components/activity-indicator.md
  - spec/components/confirmation-dialog.md
  - vendor/nativephp/mobile-ui/src/Concerns/HasFloatingOverlay.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIFloatingOverlayHost.swift
  - vendor/nativephp/mobile-ui/resources/android/NativeFloatingOverlayHost.kt
  - vendor/nativephp/mobile/src/Dialog.php
  - vendor/nativephp/mobile/resources/xcode/NativePHP/Components/Toast.swift
  - vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/bridge/functions/DialogFunctions.kt
---

# Firstlight Transient Feedback Design

## Objective

Firstlight Transient Feedback communicates brief application outcomes through
one app-level queue. It supports passive messages and one optional action,
survives native screen navigation, and reports action and dismissal outcomes as
application-level events.

This is an action/display system, not a field or durable notification centre.
It has no model binding, read state, history, multiple actions, arbitrary
duration, placement, colour, icon, animation, or layout API. The queue exists
only for the current application process and is not restored after relaunch.

## Approved product contract

- Feedback is application-level and survives navigation between native
  screens in the current process.
- Items use a FIFO queue with exactly one item visible at a time.
- An item supports zero or one action.
- Every user action and dismissal produces an application-level event.
- Tones are semantic: `default`, `success`, `warning`, and `danger`.
- Timing is automatic from content and accessibility context, or the item may
  opt into `hold` until explicit dismissal.
- Held items expose a visible, accessible dismiss control in addition to the
  optional semantic action.
- The visible item and pending queue pause while the app is backgrounded and
  resume when it returns.
- V1 has no swipe-to-dismiss gesture.

## Primitive audit and architecture decision

NativePHP Mobile 4 provides `Dialog::toast($message, $duration)`. The installed
implementation is an imperative bridge service: Android presents a Snackbar,
while iOS presents a custom UIKit label. It does not provide semantic tones,
actions, dismissal events, persistent hold, declarative reconciliation, or an
equal accessibility contract. Wrapping it would also move ordinary UI state
outside Firstlight's SuperNative element lifecycle.

Mobile UI 0.3.0 provides a content-agnostic app-level floating-overlay seam.
It proves that `ChromeContributorRegistry` and `NativeRootHostRegistry` are the
official extension points for content that persists above routed screens.
Using the existing overlay directly is insufficient because its callbacks are
bound to the active screen and it has no queue, timer, tombstone, or lifecycle
contract.

Firstlight will therefore implement a package-owned Feedback Center through
the official chrome contributor, nested NativeComponent, element tree,
callback registry, plugin init function, and native root-host seams. No JSON
bridge call, generated-host edit, or parallel event transport is introduced.

## Public PHP API

The primary public surface is the `FirstlightUI\Facades\Feedback` facade:

```php
use FirstlightUI\Facades\Feedback;

Feedback::success('Appointment saved')
    ->action('Undo', 'undo-save')
    ->send();

Feedback::warning('Connection lost')
    ->hold()
    ->send();
```

Factories are `message()`, `success()`, `warning()`, and `danger()`.
`message()` creates the `default` tone. Each factory requires a non-empty
message and returns a pending immutable builder.

The builder supports:

- `id(string $id)` for a deliberate stable ID; otherwise `send()` generates
  one;
- `action(string $label, string $key)` for one optional action;
- `hold()` to replace automatic timing with explicit dismissal; and
- `send()` to append or update the record and return its ID.

`Feedback::dismiss(string $id)` removes a pending or visible item
programmatically. It does not emit a user event.

Publishing a new ID appends it. Publishing an existing pending ID updates the
message, tone, action metadata, and current callback IDs in place without
moving it, restarting its timer, or announcing it again. A completed ID may be
used for a later item only after an intervening publication has confirmed its
absence; callers should normally use a new generated ID for a new outcome.

Blank messages, IDs, action labels, or action keys fail before publication.
Unsupported tones fail loudly. An action label and key are an inseparable pair.

## Events

The package dispatches synchronous Laravel events after native semantic
interaction:

- `FeedbackActionPressed` carries the feedback ID and action key.
- `FeedbackDismissed` carries the feedback ID and one of `timeout`, `manual`,
  or `action`.

Pressing the optional action produces `FeedbackActionPressed` and then
`FeedbackDismissed` with reason `action`. The package removes the record before
application listeners run, and cleanup remains guaranteed if a listener
throws. Listener exceptions are not swallowed.

The native controls reuse standard SuperNative press and dismiss callback wire
events. A package-owned nested component owns those callbacks and translates
them into application events, so a queued action never targets the screen that
originally created it.

## PHP and native ownership

The process-scoped PHP store owns the durable pending record set for the
current app run. A package chrome contributor mounts the Feedback Center on
every published screen and publishes the complete record list. A stable nested
component owns action and dismissal callbacks independently of consumer screen
method names.

The paired native root hosts own transient presentation state:

- current visible ID and FIFO position;
- automatic remaining time;
- background pause and foreground resume;
- entry and exit transition state;
- duplicate interaction suppression; and
- completion tombstones.

On navigation the newly published center carries the same semantic records
with fresh callback IDs. Matching native records update those callback IDs
without changing queue position, timing, or announcement state.

User completion advances the native queue immediately. A tombstone prevents a
stale PHP frame from re-enqueuing the completed ID. PHP handles the callback,
removes the record, dispatches the application event or events, and publishes
the confirmed absence. Programmatic removal advances without a user event.

Process termination discards both stores. Transient feedback is not persisted
to disk, session history, or notification storage.

## Platform expression

### iOS

iOS presents a SwiftUI material notice above bottom chrome and the keyboard.
It uses native typography, buttons, safe areas, focus, and system transitions.
Tone is expressed with restrained semantic symbol and accent treatment rather
than imitating Android Snackbar geometry. Held items include an explicit
dismiss button.

### Android

Android presents a Material 3 Snackbar through a root `SnackbarHost`. It uses
one optional action, a semantic tone treatment, Material typography and state
layers, system insets, and an explicit dismiss control for held items.

Both platforms preserve native geometry and motion while sharing message,
tone, action, dismissal, queue, timing, and event meaning. The public API does
not select position or platform widgets.

## Accessibility and timing

Only the newly visible item makes a polite announcement. Queued items remain
silent until displayed, and reconciliation does not repeat announcements or
steal accessibility focus. `danger` remains polite; an outcome requiring
immediate acknowledgement belongs in Confirmation Dialog.

Automatic duration is renderer policy derived from message length, action
presence, and platform accessibility timeout guidance. Callers cannot request
arbitrary seconds. Timers pause while the app is backgrounded or accessibility
focus remains inside the feedback. Held items never start a timer.

Message text wraps at Dynamic Type and Android font scaling sizes. The action
may reflow below the message rather than forcing truncation. Action and dismiss
targets meet the 44-point iOS and 48-dp Android baselines. Decorative tone
symbols remain silent.

Dark appearance, Increased Contrast or high contrast, layout direction, and
system typography come from platform and NativePHP theme values. Reduced
Motion replaces positional movement with opacity-only presentation.

## Failure and exclusion behaviour

- Action and dismiss controls are duplicate-suppressed; each lifecycle event
  fires once.
- Copy-only reconciliation does not reorder, restart, or reannounce an item.
- Missing or malformed callback data fails closed and never presents an inert
  action or held dismiss control.
- Application listener failures surface after queue cleanup.
- V1 excludes visual stacks, multiple actions, swipe dismissal, progress,
  input, arbitrary child content, custom durations, custom icons, styling,
  placement, persistence across relaunch, notification history, and push or
  local notifications.

## Evidence plan

Pest 5 tests begin red and prove facade and builder validation, generated and
explicit IDs, FIFO storage, in-place updates, programmatic removal, package
bindings, chrome contribution, nested callback ownership, callback refresh,
event payloads and reasons, and exactly-once cleanup.

XCTest and Kotlin tests use deterministic clocks and lifecycle hooks to prove
one-visible FIFO behaviour, automatic and held items, background pause/resume,
callback replacement across navigation, tombstones, duplicate suppression,
programmatic cancellation, announcements, accessibility timing, Reduced
Motion, and platform semantics. iOS snapshots and Android Paparazzi goldens
cover tones, actions, hold, long copy, scaling, RTL, light, and dark appearance.

The sibling showcase covers every tone, message-only feedback, one action,
hold and dismissal, a rapid three-item queue, duplicate-ID updates,
programmatic removal, and navigation survival. Full package and consumer tests,
plugin validation, exact generated-host source checksums, and both production
builds must pass.

Simulator, emulator, documentation screenshot, VoiceOver, TalkBack, and dated
physical-device evidence require explicit target authorization. Runtime review
must cover queue order, action and dismissal events, background/resume,
navigation, scaling, contrast, RTL, reduced motion, and rapid input.

If the official root-host, chrome, nested-component, and callback seams cannot
preserve app-level events across navigation without a generated-host patch or
new bridge transport, implementation stops and the contract returns to design.
