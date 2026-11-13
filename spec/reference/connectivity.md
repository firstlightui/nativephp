---
title: Firstlight connectivity
description: Current PHP helper for the device's online state, the app-wide status line, the PendingChanges contract, and when screens revalidate.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-11-13-firstlight-offline-and-stale-data-design.md
  - src/Connectivity/ConnectivityManager.php
  - src/Connectivity/ConnectivityFake.php
  - src/Connectivity/ConnectivityState.php
  - src/Connectivity/PendingChanges.php
  - src/Facades/Connectivity.php
  - src/Concerns/TracksFreshness.php
  - src/FirstlightServiceProvider.php
  - src/Support/Chrome.php
  - lang/en/chrome.php
  - vendor/nativephp/mobile/src/Network.php
  - vendor/nativephp/mobile/src/Edge/NativeRouter.php
  - tests/Feature/ConnectivityTest.php
  - docs/how-to/show-connectivity.md
  - https://github.com/NativePHP/mobile-network/pull/4
  - https://github.com/NativePHP/mobile-air/pull/672
---

# Firstlight Connectivity

Connectivity is a PHP extension. It adds no Element Tree type, native code, or
catalogue tag. Firstlight never fetches and owns no write queue: it reads the
OS, shows the app's queue through `PendingChanges`, and calls a screen's
`revalidate()` hook.

## Public PHP surface

`FirstlightUI\Facades\Connectivity` resolves the
`FirstlightUI\Connectivity\ConnectivityManager` singleton:

```php
Connectivity::state(): ConnectivityState;    // Online | Offline | Unknown
Connectivity::isOffline(): bool;
Connectivity::statusLine(): ?string;
Connectivity::pendingUsing(PendingChanges|Closure|string|null $pending): void;
Connectivity::pending(): ?PendingChanges;
Connectivity::syncFinished(int $synced, int $failed = 0): void;
Connectivity::fake(): ConnectivityFake;
```

- `state()` is `Offline` only when `Native\Mobile\Network::status()` returns
  an object without `error` whose `connected` is `false`. It is `Unknown`
  without the Network plugin (`status()` returns null), when the read throws,
  or when it reports `error`. `Unknown` counts as online everywhere.
- The first time the state is `Unknown` with `app.debug` true, Firstlight logs
  one warning naming the cause, so a missing `nativephp/mobile-network` shows
  up in device logs. It stays silent while the app runs its unit tests.
- `pendingUsing()` takes an instance, a container class name, or a closure.
  It is resolved on each read; anything other than a `PendingChanges` or null
  throws `InvalidArgumentException`.
- `syncFinished()` sends success Feedback `Synced :count change(s).` when
  `$synced > 0` and warning Feedback `:count change(s) failed to sync.` with
  the action `Review` (key `ConnectivityManager::REVIEW_ACTION`,
  `firstlight-sync-review`) when `$failed > 0`. Negative counts throw.

`FirstlightUI\Connectivity\PendingChanges`:

```php
public function waitingCount(): int;  // not yet sent, including those being sent
public function failedCount(): int;   // refused by the server, need the user
public function isSyncing(): bool;
public function sync(): void;         // start one drain and return
```

## Status line

For the nav bar subtitle. The app's layout places it; Mobile UI owns the bar.
The first match wins:

| State | Line |
| --- | --- |
| Offline, `waitingCount() > 0` | `Offline · :count waiting to sync` |
| Offline | `Offline` |
| Online or unknown, `waitingCount() > 0` and `isSyncing()` | `Syncing :count change(s)…` |
| Online or unknown, `failedCount() > 0` | `:count failed to sync` |
| Otherwise | null |

## Reading the state

Before any change event arrives, every `state()` call reads
`Network::status()` through the bridge. Once a
`NativePHP\Network\Events\NetworkStatusChanged` (mobile-network#4) arrives, the
latest event answers reads without a bridge call. Events are not replayed
while the app is in the background, so a foreground event and every
`ScreenMounted` and `ScreenResumed` read the OS again and update the
remembered state, unless the read is `Unknown`.

## Revalidation

`TracksFreshness` declares:

```php
protected function revalidate(): void {} // override to refresh what is stale
```

Firstlight calls it on `NativeComponent::active()`, the screen on display,
when that screen uses `TracksFreshness`:

1. **Reconnect.** The known state goes from `Offline` to `Online`. Firstlight
   first calls `PendingChanges::sync()` once, then `revalidate()`. `Unknown`
   is not an observation: offline, unknown, then online is still a reconnect.
   Any `state()` read that returns `Offline`, such as a render, counts as
   seen, so a device that goes offline and back while the user stays on one
   screen reconnects at the next check that reads online.
   A reconnect noticed on `NetworkStatusChanged`, a foreground event, or
   `ScreenResumed` revalidates; one noticed on `ScreenMounted` only drains,
   because a newly mounted screen loads anyway.
2. **Foreground.** The app returns after `secondsAway` strictly longer than
   the shortest `freshFor` window the screen tracks. A screen that tracks
   nothing does not revalidate. A foreground return that is also a reconnect
   revalidates once.

The foreground events are core's `Native\Mobile\Events\App\AppForegrounded`
(mobile-air#672) and, while that class does not exist,
`FirstlightUI\AppGuard\Events\Foregrounded` from App Guard. Without either,
only reconnects and resumes are noticed. On Android, returning from the system
camera or a Custom Tab counts as a foreground return.

Firstlight registers Laravel listeners for these events by class name in
`FirstlightServiceProvider::boot()`; events from packages that are not
installed never fire. A Laravel listener for a native event makes the live
screen republish, so subtitles and freshness notices update.

`freshnessNotice()` reads offline state through `Connectivity::isOffline()`.

## Strings

Package chrome, overridable through `firstlight::chrome`:
`connectivity_offline`, `connectivity_offline_waiting`,
`connectivity_syncing`, `connectivity_failed`, `sync_succeeded`,
`sync_failed`, `sync_review`, and the Status Label strings
`sync_waiting_label` (`Waiting to sync`) and `sync_failed_label`
(`Failed to sync`) for rows the app places. Counted strings use Laravel
plural forms and take `:count`; `Chrome::choice()` resolves them and falls
back to English without a translator.

## Testing

`Connectivity::fake()` swaps in a `ConnectivityFake` that starts online, never
reads the bridge, and keeps the app's `pendingUsing()`:

```php
$fake = Connectivity::fake();
$fake->goOffline();       // offline
$fake->goOnline();        // from offline: sync() once, revalidate()
$fake->goUnknown();
$fake->foreground(120);   // as AppForegrounded with secondsAway 120
```

## Product boundary

No fetching, caching, polling, write queue, conflict rules, or banners.
Wider design: [offline and stale-data design](../designs/2026-11-13-firstlight-offline-and-stale-data-design.md).
Consumer guidance: [Show connectivity](../../docs/how-to/show-connectivity.md).
