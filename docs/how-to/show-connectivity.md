---
title: Show connectivity and refresh on reconnect
description: Show Offline and sync status in the nav bar subtitle, display your app's write queue, and refresh stale data when the device comes back online or the app returns.
type: how-to
audience: consumer
sources:
  - src/Connectivity/ConnectivityManager.php
  - src/Connectivity/ConnectivityFake.php
  - src/Connectivity/PendingChanges.php
  - src/Facades/Connectivity.php
  - src/Concerns/TracksFreshness.php
  - lang/en/chrome.php
  - tests/Feature/ConnectivityTest.php
---

# Show Connectivity and Refresh on Reconnect

Firstlight tells users when the device is offline with one line in the nav bar
subtitle, and refreshes stale screens when the device comes back online:

| When | Subtitle |
| --- | --- |
| Online, nothing waiting | none |
| Offline | `Offline` |
| Offline with writes your app queued | `Offline · 3 waiting to sync` |
| Back online, your queue is sending | `Syncing 3 changes…` |
| Some writes failed | `1 failed to sync` |

Firstlight never fetches and doesn't queue writes. It reads the OS, shows the
counts your queue reports, and calls your screen's `revalidate()` hook.

## Install the Network plugin

Firstlight reads `Network::status()`, so install NativePHP's Network plugin:

```bash
composer require nativephp/mobile-network
```

Without it Firstlight can't tell, treats the device as online, and shows
nothing. In debug builds it logs one warning saying so.

## Put the line in the nav bar

Mobile UI owns the bar. Set its subtitle from your layout:

```php
use FirstlightUI\Facades\Connectivity;
use Native\Mobile\Edge\Layouts\Builders\NavBar;
use Native\Mobile\Edge\NativeComponent;

public function navBar(NativeComponent $screen): ?NavBar
{
    return NavBar::make()
        ->title($screen->navTitle())
        ->subtitle(Connectivity::statusLine());
}
```

`statusLine()` returns null when there's nothing to say, which leaves no
subtitle. On iOS, NativePHP Mobile 4.7 doesn't draw the subtitle on a tab
root yet; [mobile-air#673](https://github.com/NativePHP/mobile-air/pull/673)
fixes it.

Anywhere else, `Connectivity::isOffline()` is true only when the OS says the
device is offline.

## Refresh when the user is back

With `TracksFreshness`, override `revalidate()`. Firstlight calls it on the
screen on display when the device comes back online, and when the app returns
from the background after longer than the screen's shortest `freshFor`
window:

```php
protected function revalidate(): void
{
    if ($this->freshness('servers')->isStale()) {
        $this->reload();
    }
}
```

The screen [Show data freshness](show-data-freshness.md) builds needs only
this method. Data loaded a few seconds ago is still fresh, so returning from
the camera, which counts as leaving the app on Android, costs nothing.

Firstlight hears the app return from NativePHP's `AppForegrounded` event, or
from App Guard's `Foregrounded` on NativePHP Mobile versions without it.
Without either, it still notices a reconnect when a screen opens or is shown
again after Back.

## Show your write queue

If your app queues writes while offline, implement `PendingChanges` on the
queue and register it in a service provider:

```php
use FirstlightUI\Connectivity\PendingChanges;

class Outbox implements PendingChanges
{
    public function waitingCount(): int { /* not yet sent, including sending */ }

    public function failedCount(): int { /* refused by the server */ }

    public function isSyncing(): bool { /* a drain is running */ }

    public function sync(): void
    {
        // Start one drain, such as an AsyncTask, and return.
    }
}
```

```php
// AppServiceProvider::boot()
Connectivity::pendingUsing(Outbox::class);
```

When the device comes back online, Firstlight calls `sync()` once, then
`revalidate()`. When your drain finishes, report it:

```php
Connectivity::syncFinished($synced, $failed);
```

That shows `Synced 3 changes.`, and `1 change failed to sync.` with a
`Review` action. Open your own pending screen from it:

```php
use FirstlightUI\Connectivity\ConnectivityManager;
use FirstlightUI\Events\FeedbackActionPressed;
use Native\Mobile\Attributes\On;

#[On(FeedbackActionPressed::class)]
public function feedbackAction(string $id, string $actionKey): void
{
    if ($actionKey === ConnectivityManager::REVIEW_ACTION) {
        $this->navigate('/pending');
    }
}
```

For rows changed while offline, use a Status Label with Firstlight's strings:

```blade
<firstlight:status-label :label="__('firstlight::chrome.sync_waiting_label')" tone="neutral" />
<firstlight:status-label :label="__('firstlight::chrome.sync_failed_label')" tone="danger" />
```

## Change the wording

Override `connectivity_offline`, `connectivity_offline_waiting`,
`connectivity_syncing`, `connectivity_failed`, `sync_succeeded`,
`sync_failed`, `sync_review`, `sync_waiting_label`, and `sync_failed_label`
in `lang/vendor/firstlight/{locale}/chrome.php`, as described in
[Localize chrome](localize.md). Counted strings use Laravel plural forms,
such as `Synced :count change.|Synced :count changes.`.

## Test it

`Connectivity::fake()` starts online and drives the transitions:

```php
use App\NativeComponents\ServersScreen;
use FirstlightUI\Facades\Connectivity;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Testing\Native;

it('loads the servers when the device comes back online', function () {
    $online = false;
    Http::fake(function () use (&$online) {
        return $online
            ? Http::response(['data' => [['id' => 1, 'name' => 'web-1']]])
            : Http::failedConnection();
    });

    $connectivity = Connectivity::fake()->goOffline();
    $screen = Native::test(ServersScreen::class)->assertSet('servers', []);

    $online = true;
    $connectivity->goOnline();

    $screen->assertSet('servers', [['id' => 1, 'name' => 'web-1']]);
});
```

`goOffline()`, `goOnline()`, `goUnknown()`, and `foreground($secondsAway)`
mirror what the device sends. The fake keeps your `pendingUsing()` queue.
