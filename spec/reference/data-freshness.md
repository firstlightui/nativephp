---
title: Firstlight data freshness
description: Current PHP helper for tracking how old each dataset on a screen is and showing its age, offline, and failure state in a Callout.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-11-13-firstlight-offline-and-stale-data-design.md
  - src/Concerns/TracksFreshness.php
  - src/Freshness/Freshness.php
  - src/Freshness/FreshnessNotice.php
  - resources/views/native/freshness.blade.php
  - src/Elements/Callout.php
  - src/Support/Chrome.php
  - lang/en/chrome.php
  - vendor/nativephp/mobile/src/Network.php
  - vendor/nativephp/mobile/src/Device.php
  - tests/Feature/TracksFreshnessTest.php
  - docs/how-to/show-data-freshness.md
---

# Firstlight Data Freshness

Data freshness is a PHP extension over Callout. It adds no Element Tree type,
native code, or catalogue tag. Firstlight never fetches: the screen reports
what it shows and how its refreshes went, and Firstlight decides what to say
about it.

## Public PHP surface

`FirstlightUI\Concerns\TracksFreshness` is opt-in.
`FirstlightUI\NativeComponent` does not use it, so its method names cannot
clash with existing screen methods. It works on Firstlight's `NativeComponent`
and on NativePHP's component directly. It declares:

```php
protected function freshness(string $key, ?int $freshFor = null): Freshness;
protected function freshnessNotice(string $key): ?FreshnessNotice;
public function retryFreshness(string $key): void;
```

- `$key` names one dataset on the screen, such as `servers`. It may use only
  letters, digits, `_`, `.`, `:`, and `-`, because the partial puts it in a
  callback expression.
- `$freshFor` is the window in seconds, at least 1. It is required the first
  time a key is tracked; passing it again changes the window.
- Trackers live in a private property of the screen instance, so they last as
  long as the screen and are not public state.

`FirstlightUI\Freshness\Freshness` is the tracker for one dataset:

```php
public function showing(?DateTimeInterface $fetchedAt): self;   // null: nothing cached
public function refreshing(): self;
public function refreshed(?DateTimeInterface $fetchedAt = null): self; // default now
public function failed(?string $reason = null, ?string $retry = null): self;
public function freshFor(int $seconds): self;
public function isStale(): bool;
public function isRefreshing(): bool;
public function hasFailed(): bool;
public function fetchedAt(): ?CarbonImmutable;
public function notice(bool $offline): ?FreshnessNotice;
```

- `showing()` and `refreshed()` clear a failure and the refreshing flag.
- `refreshing()` leaves a failure in place, so its notice stays up until the
  refresh lands.
- `failed()` rejects a blank `$reason` and a `$retry` that is not a method
  name. `$retry` names a public screen method that `Try again` calls.
- `isStale()` is true when nothing is cached, or when `fetchedAt + freshFor`
  is now or earlier.

`FirstlightUI\Freshness\FreshnessNotice` is a final readonly value with
`key`, `message`, `tone`, and `actionLabel` (null without an action).

## Notice

`freshnessNotice()` reads `Native\Mobile\Network::status()` and passes
`offline` to `notice()`. The device counts as offline only when the status is
an object without `error` whose `connected` is `false`. Without the Network
plugin, when the read throws, or when it reports an error, connectivity is
unknown and counts as online. With `nativephp/mobile-network#5`, Android Wi-Fi
without validated internet reports `connected: false` and counts as offline.

Rules apply in this order; the first match wins:

| State | Message | Tone | Action |
| --- | --- | --- | --- |
| Offline, data cached | `Showing data from :time` | warning | none |
| Offline, nothing cached | `You're offline. This will load when you're back online.` | neutral | none |
| Failed, data cached | `:reason Showing data from :time.`, with `Couldn't refresh.` as the default reason | warning | `Try again` when a retry was given |
| Failed, nothing cached | the reason, or `Couldn't load this.` | danger | `Try again` when a retry was given |
| Stale under an hour | `Updated :when` | neutral | none |
| Stale an hour or more | `Updated at :time` | neutral | none |
| Fresh, or nothing cached while online | no notice | | |

The offline notice with nothing cached is neutral, not info: Callout's info
tone uses the theme's primary colour, which in a red-branded app reads as an
error.

Offline wins over a failure: while offline, the failure's cause is already
known and `Try again` cannot succeed. Nothing cached while online and not
failed shows nothing, so the screen's own loading or empty state is not
covered.

`:when` is Carbon `diffForHumans()` relative to now, with a one-minute minimum
unit and `just now` below it, such as `12 minutes ago`. `:time` is
`isoFormat('LT')` when the data is from today and `isoFormat('lll')` on an
earlier day. Both use the application locale that
[Package locale chrome](package-locale.md) defines. Clock times use the
phone's timezone from `Native\Mobile\Device::getTimezone()`, because Laravel's
`app.timezone` is not the phone's. Off the device, or when the phone reports a
name PHP doesn't know, they use the application timezone. Each read is a
`Device.GetInfo` bridge call, so the screen asks once, the first time a notice
needs a clock time, and keeps the answer while it lives.

The notice is computed when the screen renders. It does not tick on its own;
it changes when the screen republishes.

## Partial

`firstlight::native.freshness` takes a `notice` variable and renders one
`<firstlight:callout>` with the notice's message and tone, or nothing when the
notice is null. With an action label it adds `action-label` and
`@press="retryFreshness('{key}')"`.

`retryFreshness()` calls the retry method of the dataset's last failure. With
no failure or no retry it does nothing. A retry method the screen doesn't
declare throws `LogicException`.

## Strings

The eight strings are package chrome: `try_again`, `freshness_updated`,
`freshness_updated_at`, `freshness_showing`, `freshness_offline_empty`,
`freshness_refresh_failed`, `freshness_failed_showing`, and
`freshness_load_failed`, overridable through `firstlight::chrome`.

## Product boundary

No fetching, caching, polling, timers, or revalidation. Refreshing when the
device comes back online or the app returns to the foreground belongs to the
designed `Connectivity` helper and its `revalidate()` hook. Consumer guidance
lives in [Show data freshness](../../docs/how-to/show-data-freshness.md). The
wider offline design is in the
[offline and stale-data design](../designs/2026-11-13-firstlight-offline-and-stale-data-design.md).
