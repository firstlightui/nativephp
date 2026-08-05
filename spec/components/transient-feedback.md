---
title: Transient Feedback maintained contract
description: Current public, wire, ownership, queue, lifecycle, accessibility, platform, failure, and evidence contract for app-level Transient Feedback.
status: current
audience: maintainer
sources:
  - Constitution.md
  - composer.json
  - nativephp.json
  - Package.swift
  - spec/screenshots.json
  - vendor/nativephp/mobile-ui/nativephp.json
  - src/Facades/Feedback.php
  - src/Feedback/PendingFeedback.php
  - src/Feedback/FeedbackManager.php
  - src/Feedback/FeedbackStore.php
  - src/Feedback/FeedbackRecord.php
  - src/Feedback/FeedbackTone.php
  - src/Feedback/FeedbackDismissReason.php
  - src/Events/FeedbackActionPressed.php
  - src/Events/FeedbackDismissed.php
  - src/Elements/FeedbackCenter.php
  - src/Elements/FeedbackItem.php
  - src/NativeComponents/FeedbackCenter.php
  - src/Support/CallbackExpression.php
  - src/FirstlightServiceProvider.php
  - resources/views/native/feedback-center.blade.php
  - resources/ios/FeedbackCenterState.swift
  - resources/ios/FeedbackCenterControl.swift
  - resources/ios/FeedbackCenterHost.swift
  - resources/ios/FirstlightUIInit.swift
  - resources/android/FeedbackCenterState.kt
  - resources/android/FeedbackCenterControl.kt
  - resources/android/FeedbackCenterHost.kt
  - resources/android/FirstlightUIInit.kt
  - tests/Feature/TransientFeedbackApiTest.php
  - tests/Feature/FeedbackCenterTest.php
  - tests/Feature/PluginManifestTest.php
  - tests/ios/FeedbackCenterTests.swift
  - tests/ios/FeedbackCenterSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/FeedbackCenterTest.kt
---

# Transient Feedback Maintained Contract

## Purpose and public boundary

Transient Feedback is a process-local app service for brief queued outcomes. The public authoring identity is `FirstlightUI\Facades\Feedback`; there is no public Blade tag, consumer-authored host, model, or direct renderer API.

The complete factory surface is `message(string)`, `success(string)`, `warning(string)`, and `danger(string)`, each returning immutable `PendingFeedback`. The builder surface is `id(string): self`, `action(string $label, string $key): self`, `hold(): self`, and `send(): string`. `Feedback::dismiss(string $id): bool` is the only programmatic removal API.

`FeedbackTone` has exactly `default`, `success`, `warning`, and `danger`. `FeedbackDismissReason` has exactly `timeout`, `manual`, and `action`.

## Official primitive audit

Installed `nativephp/mobile-ui` 0.3 has no toast, snackbar, feedback, or app-level message element in its manifest. A screen-authored composition also cannot own a queue across navigation or contribute package chrome. Transient Feedback therefore uses paired Firstlight root hosts while retaining official EDGE publication, stable callbacks, SwiftUI, Jetpack Compose, and Material 3.

The feature must not use `Dialog::toast`, `nativephp_call`, a WebView, a manifest `bridge_functions` entry, or a screen-local overlay. Semantic outcomes return only through official EDGE press callbacks. Native timing, focus, animation, and queue presentation stay on the platform thread.

## PHP ownership and records

`FirstlightServiceProvider::register()` binds one process-local singleton `FeedbackStore` and `FeedbackManager`. `FeedbackStore` is an insertion-ordered map keyed by record ID. A new ID appends; replacing an existing ID changes its record without moving it. `all()` returns FIFO values and `remove()` returns the removed record or `null`.

`FeedbackRecord` owns `id`, `message`, `tone`, `hold`, `actionLabel`, and `actionKey`. IDs, messages, labels, and keys reject blank values but preserve authored non-blank whitespace. Action label and key must both be null or both present. `PendingFeedback::send()` uses the authored ID or a generated UUID.

The store intentionally has no persistence adapter. Navigation preserves the singleton, but process termination empties the queue.

## Package chrome and wire contract

The service provider registers the internal component tag `firstlight-feedback-center` and contributes `resources/views/native/feedback-center.blade.php` through `ChromeContributorRegistry` on every screen. Consumers do not author or install this host.

The child component publishes one layout-free sentinel `firstlight.feedback-center` with zero or more layout-free `firstlight.feedback-item` children. Native frame decoding observes normalized `firstlight_feedback_center` at the root and consumes it through a registered root host named `firstlight.feedback-center`; child type matching remains `firstlight.feedback-item` after the consumed center is decoded.

Each item publishes:

| Wire prop | Publication contract |
| --- | --- |
| `feedback_id` | Required stable string ID. |
| `message` | Required non-blank message. |
| `tone` | One of the four exact wire tones. |
| `hold` | Required Boolean lifetime mode. |
| `action_label` | Present only with a complete optional action. |
| `on_action` | Package-owned callback present only with `action_label`; routes `action(id, key)`. |
| `on_timeout` | Present only for automatic records; routes `dismiss(id, timeout)`. |
| `on_manual` | Present for every record; routes `dismiss(id, manual)`. |

Publishing `on_manual` for automatic records is an internal ownership requirement. Android Material snackbar accessibility exposes a native dismiss semantic for automatic, actionable, and held states; the callback lets that semantic reach the package child registry. It does not create a public prop or a visible dismiss control. Automatic feedback has no visible close button on either platform. Held feedback alone receives Firstlight's explicit visible dismiss button. iOS accepts manual queue completion only for held records.

Callback IDs belong to the package `FeedbackCenter` child registry, never the consumer screen registry. Every publication, including a same-ID update after navigation, produces fresh callback IDs. The semantic ID, not a callback ID or native node ID, owns queue identity.

## Application outcomes

`FeedbackCenter::action($id, $key)` removes the record before validating the stored key. A missing record, duplicate callback, or mismatched key emits nothing. A valid action dispatches `FeedbackActionPressed($id, $key)` and then, in `finally`, `FeedbackDismissed($id, Action)`. An action-listener failure propagates after the dismissal event is dispatched.

`FeedbackCenter::dismiss($id, $reason)` accepts only `timeout` or `manual`. Malformed reasons and `action` fail closed without removal. A valid callback removes once and dispatches one `FeedbackDismissed`. `FeedbackManager::dismiss($id)` removes without either application event.

The events are final readonly payloads: `FeedbackActionPressed::$id/$actionKey` are strings; `FeedbackDismissed::$id` is a string and `$reason` is `FeedbackDismissReason`.

## Native queue and reconciliation

Each platform retains eligible published records in FIFO order and renders only the first. Same-ID publication refreshes content, node ID, and callbacks in place without restarting elapsed time. Programmatic absence removes a record silently and advances the queue.

Completing an item inserts a native tombstone and advances immediately. A stale frame cannot resurrect or re-complete that ID; the tombstone is released only after a later publication omits the ID. Stale action, timeout, manual-dismiss, and host-dismiss results are bound to the initiating semantic ID and cannot affect its successor.

Duplicate IDs in one frame, blank identity/message, unsupported tone, incomplete action pairs, and missing lifetime callbacks are ineligible. Automatic records require `on_timeout`. Held records require `on_manual`. Android additionally requires `on_manual` on all eligible records because its native snackbar dismiss semantic exists independently of Firstlight's visible held-only close control. A fully empty center resets queue timing and the absence epoch.

## Timing, focus, and lifecycle

The automatic base duration is four seconds plus one second per 40 characters beyond the first 40, capped at ten seconds for message reading time. An action adds two seconds. Held records have infinite duration.

iOS doubles the resulting duration when VoiceOver or Switch Control is active. Android API 29 calls `AccessibilityManager.getRecommendedTimeoutMillis` with the calculated base plus text and icon content flags, adding the controls flag for actionable or held feedback. This platform policy is deterministic and injectable in tests.

Only foreground, unfocused time accrues. iOS pauses below active `scenePhase` and while an action or dismiss button owns accessibility focus. Android pauses below lifecycle `RESUMED` and while a feedback control owns focus. Resume resets the native clock origin without charging background time. Policy or same-ID content changes preserve elapsed time; shortening below elapsed completes once through the refreshed callback, immediately or on resume as appropriate.

The PHP singleton survives screen navigation. Package chrome remounts the child component for the current screen, republishes the full store, and refreshes child-owned callbacks. Application backgrounding pauses the renderer queue rather than changing PHP records. Process death clears PHP memory and native presentation; no restore is promised.

## Accessibility and platform expression

Both hosts announce each newly visible semantic ID once and do not repeat the announcement for same-ID content updates. Tone is conveyed through a distinct native symbol as well as colour, and symbols are decorative to assistive technology. Text wraps without truncation. Labelled actions remain native controls.

iOS composes a bottom SwiftUI material surface using SF Symbols and `Button`, with at least 44-point actions, `ViewThatFits` reflow, safe-area padding, dark mode, Dynamic Type, VoiceOver/Switch Control policy, and opacity-only Reduced Motion transitions.

Android composes a bottom Material 3 `Snackbar`/`SnackbarHost` with at least 48-dp actions, semantic tone colour roles and decorative vector drawing, constrained-width or font-scale reflow, navigation-bar and IME padding, a polite TalkBack live region, and the API 29 recommended-timeout adapter.

Native visuals need not have pixel parity. Both hosts must preserve FIFO identity, one visible item, outcome ordering, pause/resume, stale-event isolation, and the same authored PHP example.

## Failure and exclusion boundary

PHP validation throws `InvalidArgumentException` before publication for blank authored data or incomplete direct records. Native decoders omit malformed records without crashing. Unsupported dismiss reasons, wrong action keys, duplicates, zero callbacks, incomplete actions, and stale results fail closed. No code path silently substitutes a different tone for an eligible malformed record.

Excluded public capabilities are Blade authoring, host installation, arbitrary duration or position, multiple actions, disabled/loading/model state, rich content, custom icons, swipe configuration, per-platform copy, untyped styling, durable persistence, and external queue mutation. Any such addition requires a new shared contract and paired-platform evidence.

## Compatibility and evidence boundary

The declared floors are PHP 8.4, NativePHP Mobile 4, NativePHP Mobile UI 0.3, iOS 18, Android API 29, and Swift tools 6.2. `nativephp.json` must retain exact init functions `registerFirstlightUI` and `dev.firstlightui.plugins.firstlight_ui.registerFirstlightUI`, with no `bridge_functions` key.

Current executable evidence covers the PHP facade/store/events, child-registry callbacks, navigation refresh, event ordering, queue/tombstones, platform timing, lifecycle suspension, accessibility semantics, init registration, snapshots, large text, RTL, and API 29 compilation. The registered showcase route is `/captures/transient-feedback` with a focused `TransientFeedbackCaptureTest.php` command and four standard image outputs.

The showcase fixture, runtime screenshot matrix, simulator/emulator execution, VoiceOver, TalkBack, and physical-device review are separate evidence. At this documentation stage the four PNGs and `spec/reviews/transient-feedback-alpha.md` are absent and visual approval is explicitly deferred. Development gates may report these gaps; release mode must fail until every image exists and the alpha review has no open checklist row.
