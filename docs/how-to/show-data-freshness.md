---
title: Show data freshness
description: Tell users how old a screen's data is, that they're seeing cached data while offline, and that a refresh failed, with a Try again action.
type: how-to
audience: consumer
sources:
  - src/Concerns/TracksFreshness.php
  - src/Freshness/Freshness.php
  - src/Freshness/FreshnessNotice.php
  - resources/views/native/freshness.blade.php
  - lang/en/chrome.php
  - tests/Feature/TracksFreshnessTest.php
---

# Show Data Freshness

A screen that shows cached data should say how old it is. Firstlight tracks
the age of each dataset on a screen and shows one Callout above it:

| When | Callout |
| --- | --- |
| The data is older than its window | `Updated 12 minutes ago`, or `Updated at 2:05 PM` after an hour |
| The device is offline | `Showing data from 2:05 PM` |
| Offline with nothing cached | `You're offline. This will load when you're back online.` |
| A refresh failed | `Couldn't refresh. Showing data from 2:05 PM.` with `Try again` |
| The first load failed | `Couldn't load this.` with `Try again` |

Firstlight never fetches or caches anything. Your screen loads its data as it
does now, and tells Firstlight what it's showing and how the refresh went.

## Track a dataset

Use `TracksFreshness`. Call `freshness()` with a key and a window in seconds
the first time, then report each load. This screen lists servers from an API
and keeps the last response in the cache:

```php
<?php

namespace App\NativeComponents;

use Carbon\CarbonImmutable;
use FirstlightUI\Concerns\TracksFreshness;
use FirstlightUI\NativeComponent;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class ServersScreen extends NativeComponent
{
    use TracksFreshness;

    /** @var list<array{id: int, name: string}> */
    public array $servers = [];

    public function mount(): void
    {
        $cached = Cache::get('servers');
        $this->servers = $cached['items'] ?? [];

        $this->freshness('servers', freshFor: 60)
            ->showing($cached ? CarbonImmutable::parse($cached['fetched_at']) : null);

        if ($this->freshness('servers')->isStale()) {
            $this->reload();
        }
    }

    public function reload(): void
    {
        $this->freshness('servers')->refreshing();

        try {
            $items = Http::acceptJson()->get('https://api.example.com/servers')->throw()->json('data');
        } catch (ConnectionException|RequestException) {
            $this->freshness('servers')->failed(retry: 'reload');

            return;
        }

        $fetchedAt = CarbonImmutable::now();
        Cache::forever('servers', ['items' => $items, 'fetched_at' => $fetchedAt->toIso8601String()]);

        $this->servers = $items;
        $this->freshness('servers')->refreshed($fetchedAt);
    }

    public function render(): View
    {
        return view('native.servers');
    }
}
```

- `showing($fetchedAt)` reports what the screen shows: the time the data was
  fetched, or null when nothing is cached.
- `refreshing()` marks a refresh in progress. A failure notice stays up until
  the refresh lands.
- `refreshed($fetchedAt)` reports a successful refresh. It defaults to now.
- `failed()` reports a failed refresh or load. `retry` names the public method
  that `Try again` calls; leave it out for no action.
- `isStale()` is true when nothing is cached or the data is older than
  `freshFor`. Use it to decide whether to refresh.

A screen with several datasets tracks each under its own key, such as
`servers` and `alerts`, with its own window.

## Refresh when the user is back

Override `revalidate()` to refresh stale data when the device comes back
online or the app returns from the background:

```php
protected function revalidate(): void
{
    if ($this->freshness('servers')->isStale()) {
        $this->reload();
    }
}
```

[Show connectivity](show-connectivity.md) explains when Firstlight calls it.

## Show the notice

Include the partial where the Callout belongs, usually above the data:

```blade
<native:column class="gap-3 p-4">
    @include('firstlight::native.freshness', ['notice' => $this->freshnessNotice('servers')])

    @foreach ($servers as $server)
        <native:text :text="$server['name']" />
    @endforeach
</native:column>
```

It renders nothing while the data is fresh. While online with nothing cached
and no failure, it also renders nothing, so your own loading or empty state
shows.

The notice is worked out each time the screen renders. It doesn't count up on
its own: `Updated 12 minutes ago` changes the next time the screen republishes.

## Use your own failure message

Pass a reason when the API tells you why, such as a rate limit:

```php
$this->freshness('servers')->failed('Too many requests. Try again in a minute.');
```

With cached data the Callout reads
`Too many requests. Try again in a minute. Showing data from 2:05 PM.`. With
nothing cached it shows the reason alone.

## How Firstlight knows the device is offline

Firstlight asks NativePHP's `Network::status()`. Install
`nativephp/mobile-network` to get the offline notices:

```bash
composer require nativephp/mobile-network
```

Without the plugin, or when the status can't be read, Firstlight treats the
device as online and shows only age and failure notices.

## Change the wording

The strings are Firstlight chrome. Override `freshness_updated`,
`freshness_updated_at`, `freshness_showing`, `freshness_offline_empty`,
`freshness_refresh_failed`, `freshness_failed_showing`,
`freshness_load_failed`, and `try_again` in
`lang/vendor/firstlight/{locale}/chrome.php`, as described in
[Localize chrome](localize.md). Times use the app's locale and the phone's
timezone, so `Showing data from 2:05 PM` matches the phone's clock.

## Test it

Freeze time, fake the network, and check the Callout:

```php
use App\NativeComponents\ServersScreen;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Testing\Native;

it('shows cached servers with their time while offline', function () {
    CarbonImmutable::setTestNow('2026-11-13 14:20');
    Cache::forever('servers', ['items' => [['id' => 1, 'name' => 'web-1']], 'fetched_at' => '2026-11-13T14:05:00Z']);
    Http::fake(['*' => Http::failedConnection()]);
    Native::fakeBridge()->respondTo('Network.Status', ['connected' => false, 'type' => 'unknown']);

    Native::test(ServersScreen::class)->assertElement('firstlight.callout', fn (array $callout) => $callout['props']['tone'] === 'warning'
        && $callout['props']['message'] === 'Showing data from 2:05 PM');
});
```

Callout messages aren't visible text to `assertSee()`, so match the
`firstlight.callout` element and its `message` and `tone` props.

With `nativephp/mobile-network` installed, `Native::fakeBridge()->withOffline()`
does the same as the `respondTo()` call.
