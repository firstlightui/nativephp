---
title: Firstlight offline and stale-data design
description: Approved placement, states, and PHP API for offline, reconnecting, stale data, pending changes, and drafts kept across navigation.
status: approved
sources:
  - Constitution.md
  - spec/reference/catalogue-boundary.md
  - spec/reference/kept-drafts.md
  - spec/reference/data-freshness.md
  - spec/reference/form-submit.md
  - spec/reference/package-locale.md
  - spec/components/callout.md
  - spec/components/status-label.md
  - spec/components/transient-feedback.md
  - src/NativeComponent.php
  - src/Concerns/KeepsDrafts.php
  - src/Drafts/Draft.php
  - src/Drafts/DraftStore.php
  - src/Facades/Drafts.php
  - src/Concerns/TracksFreshness.php
  - src/Freshness/Freshness.php
  - vendor/nativephp/mobile/src/Edge/NativeComponent.php
  - vendor/nativephp/mobile/src/Edge/NativeRouter.php
  - https://github.com/NativePHP/mobile-network/pull/4
  - https://github.com/NativePHP/mobile-network/pull/5
  - https://github.com/NativePHP/mobile-air/pull/672
  - https://github.com/NativePHP/mobile-air/pull/673
---

# Firstlight Offline and Stale-Data Design

Date: 2026-11-13

Status: approved by the maintainer for 1.2. The five open decisions use the
defaults recorded below until the maintainer changes them. `KeepsDrafts` is
implemented and specified in [Kept drafts](../reference/kept-drafts.md). The
other parts are designed, not implemented, and must not be described as
shipped.

## Problem

Two Firstlight apps each built their own offline handling:

- clinically-au/mobile has `ConnectivityState` (NativePHP's Network plugin
  sends no change event), a pending-actions queue, an API cache, and an
  offline line in the nav bar subtitle.
- consigliere has `StaleBanner`, a `Freshness` enum, and `KeepsEditorDrafts`,
  because iOS closes a screen on Back or an edge swipe before PHP hears about
  it, so the screen cannot ask "Discard changes?".

Their states differ, their strings differ, and consigliere's offline notice
never appears: it reads `Network::status()->connected ?? true` without
installing `nativephp/mobile-network`, so `status()` returns null and the
device always counts as online.

## Placement

The maintainer's rule: improvements go upstream, opinionated changes stay in
Firstlight. There is no new plugin and no new catalogue tag.

| Piece | Where | State |
| --- | --- | --- |
| `NetworkStatusChanged` event | `nativephp/mobile-network` | [#4](https://github.com/NativePHP/mobile-network/pull/4), ready for review |
| Android `connected` requires a validated network | `nativephp/mobile-network` | [#5](https://github.com/NativePHP/mobile-network/pull/5), ready for review |
| `AppForegrounded(secondsAway)` and `AppBackgrounded` | `nativephp/mobile` core | [mobile-air#672](https://github.com/NativePHP/mobile-air/pull/672), ready for review |
| iOS tab roots draw the nav subtitle | `nativephp/mobile` core | [mobile-air#673](https://github.com/NativePHP/mobile-air/pull/673), ready for review |
| Drafts kept across navigation | Firstlight `KeepsDrafts` | implemented |
| Dataset age notices | Firstlight `TracksFreshness` over Callout | implemented |
| Connectivity, status line, `revalidate()` hook | Firstlight `Connectivity` | designed |
| Display of an app's write queue | Firstlight `PendingChanges` contract | designed |
| Write queue, API cache, conflict rules, data wipes | the apps | stays there |

Each Firstlight piece is a PHP SuperNative extension over existing
presentation: Callout, Transient Feedback, Status Label, and the Mobile UI nav
bar subtitle. Catalogue decision test question 2 is yes for all of them.

Firstlight never requires `nativephp/mobile-network`. It calls
`Network::status()` from core, and listens for upstream events only when their
classes exist.

## Where states appear

- **App-wide status goes in the nav bar subtitle.** It is native on both
  platforms. An overlay banner never insets the content, so it covers the last
  row of every list; mobile moved away from one for that reason. The app's
  layout places the subtitle; Firstlight supplies the text.
- **The age of each dataset goes in an inline Callout above that content.**
- **Transient Feedback is only for brief outcomes**, such as a finished sync or
  a restored draft.

Strings below are the English package chrome. Plurals need a `trans_choice`
path in `Chrome`, which it lacks today. Times use the application locale and
timezone, as Date Picker and Time Picker do.

### App-wide line (nav subtitle)

| State | Subtitle |
| --- | --- |
| Online, nothing pending | none |
| Unknown (no Network plugin, or the read failed) | none: fail open, and log once in debug builds |
| Offline | `Offline` |
| Offline with queued writes | `Offline · 3 waiting to sync` |
| Reconnecting: online and the queue is draining | `Syncing 3 changes…`, then cleared; success Feedback `Synced 3 changes.` if anything synced |
| Online, some writes failed | `1 failed to sync`; warning Feedback `1 change failed to sync.` with the action `Review` |

### Each dataset (inline Callout)

| State | Callout | Tone | Action |
| --- | --- | --- | --- |
| Loading, nothing cached | none (the app's placeholder) | | |
| Fresh | none | | |
| Stale and online | `Updated 12 minutes ago`, or `Updated at 14:05` after an hour | neutral | none |
| Offline with cached data | `Showing data from 14:05`, with the date on an earlier day, in the app's locale | warning | none |
| Offline, nothing cached | `You're offline. This will load when you're back online.` | info | none |
| Refresh failed with cached data | `Couldn't refresh. Showing data from 14:05.` or the app's reason | warning | `Try again` |
| Load failed, nothing cached | the app's message, or `Couldn't load this.` | danger | `Try again` |
| Reconnecting | the old notice stays until the refresh lands | | |

Rows changed while offline use Status Label: `Waiting to sync` (neutral) or
`Failed to sync` (danger), placed by the app.

### Drafts kept across navigation

| Moment | Behaviour |
| --- | --- |
| The user leaves an edited screen (iOS back or swipe, Android back, tab switch, replace) | Nothing is shown or asked. The draft is kept. Android matches iOS. |
| The screen reopens on the same base version | Fields are restored. Feedback `Restored your unsaved changes.` with the action `Discard`. |
| The screen reopens and the base version changed | Fields are not restored. Held warning Feedback `This changed since you last edited it. Your unsaved changes weren't restored.` with the action `Restore anyway`. |
| The user swipes that warning away | The old draft is forgotten. |
| The user leaves without answering it | The Feedback is dismissed. New edits are kept as usual; with none, the old draft is kept and offered again next time. |
| Save succeeds | The screen's `draft()` hook returns null, so nothing is kept. |
| Logout or account switch | `Drafts::flush()`; mobile-auth's logout cleanup calls it. |
| The process dies | The draft is lost with the memory store (decision 3). |

## When screens refresh

Firstlight never fetches. It calls a screen's `revalidate()` hook:

- when connectivity goes from offline to online, after asking
  `PendingChanges::sync()` to start one drain; and
- when the app returns to the foreground after longer than the shortest
  freshness window on the screen.

It republishes so ages and subtitles update. Until the upstream events ship,
Firstlight re-reads connectivity on mount and resume, and listens to App
Guard's `Foregrounded` when App Guard is installed.

On Android, opening the system camera or a Custom Tab counts as going to the
background (mobile-air#672 documents it), so returning from them may trigger a
foreground revalidation. Freshness windows make that cheap: a dataset loaded
seconds earlier is still fresh.

## PHP API

### Kept drafts (implemented)

```php
use FirstlightUI\Concerns\KeepsDrafts;
use FirstlightUI\Drafts\Draft;

final class EnvironmentEditor extends NativeComponent
{
    use KeepsDrafts;

    public string $text = '';

    // Called on leave. Null means nothing unsaved.
    protected function draft(): ?Draft
    {
        if ($this->hash === null || $this->text === $this->loadedText) {
            return null;
        }

        return new Draft("env:{$this->site}", ['text' => $this->text], basedOn: $this->hash);
    }

    // Called once the data has loaded.
    private function loaded(): void
    {
        $this->text = $this->loadedText;
        $this->restoreDraft("env:{$this->site}", basedOn: $this->hash);
    }
}

// AppServiceProvider
Drafts::scopeUsing(fn () => app(CurrentAccount::class)->get()?->id);
```

The full contract, including scoping, keys, and Feedback actions, is in
[Kept drafts](../reference/kept-drafts.md).

Differences from the plan sketch, decided while implementing:

- `KeepsDrafts` is opt-in, not part of `FirstlightUI\NativeComponent`. A
  default `draft()` on every screen would be a fatal signature clash for an
  existing screen with its own `draft()` method, such as a "save as draft"
  action.
- A draft holds public property values by name, and `restoreDraft()` assigns
  them. That lets `Restore anyway` and `Discard` work from a Feedback action
  without app code. The plan's `original()`/`current()` pair is replaced by
  the `draft()` hook returning null when nothing changed.
- The `what` label is dropped from the warning; apps rename by overriding the
  `firstlight::chrome` strings.
- No `discardDraft()`: while a screen is live its draft is already out of the
  store, so leaving with `draft()` returning null is the whole discard.

### Freshness (implemented)

```php
// Opt-in TracksFreshness, keyed per dataset
$this->freshness('servers', freshFor: 60)->showing($cached?->fetchedAt);
$this->freshness('servers')->refreshing();
$this->freshness('servers')->refreshed($fetchedAt);
$this->freshness('servers')->failed('Vito rate limit, refreshing in 30s', retry: 'reload');

protected function revalidate(): void
{
    if ($this->freshness('servers')->isStale()) {
        $this->reload();
    }
}
```

```blade
@include('firstlight::native.freshness', ['notice' => $this->freshnessNotice('servers')])
```

The partial renders one `<firstlight:callout>` or nothing. The trait is
opt-in, like `KeepsDrafts`. The `revalidate()` hook arrives with
`Connectivity`. [Data freshness](../reference/data-freshness.md) is the
current contract.

### Connectivity and pending changes (designed)

```php
use FirstlightUI\Facades\Connectivity;

Connectivity::state();       // ConnectivityState::Online | Offline | Unknown
Connectivity::isOffline();   // true only when the OS says so
Connectivity::statusLine();  // ?string, such as "Offline · 3 waiting to sync"

Connectivity::pendingUsing(PendingActionService::class); // implements PendingChanges

NavBar::make()->title($screen->navTitle())->subtitle(Connectivity::statusLine());
```

```php
interface PendingChanges
{
    public function waitingCount(): int;
    public function failedCount(): int;
    public function isSyncing(): bool;
    public function sync(): void; // start one drain, such as an AsyncTask
}
```

Tests drive transitions with `Connectivity::fake()->goOffline()` and
`goOnline()`, on top of `mobile-network`'s FakeBridge macros.

## Decisions and defaults

1. The inline notice does not repeat "Offline"; it says
   `Showing data from 14:05` and relies on the subtitle.
2. A draft whose base version changed is kept and offered with
   `Restore anyway`, not discarded. (Implemented.)
3. Drafts live in memory only in 1.2. An encrypted disk store would have to
   save on `AppBackgrounded`, because `unmount()` does not run when the OS
   kills the app, and it would put PHI on disk. Revisit after mobile-air#672.
4. Firstlight displays the app's queue and owns none. A shared
   `firstlightui/outbox` waits for a second app that queues writes.
5. If mobile-network#4 stays unanswered, follow up on it and keep the
   snapshot fallback. A temporary plugin with the same event shape is
   considered only if the fallback hurts in practice.

## What the apps delete

- consigliere: `KeepsEditorDrafts`, `EditorDrafts` and its singleton binding
  (editors switch to `KeepsDrafts`); later `StaleBanner`, its partial,
  `staleBanner()` and the `$loads` bookkeeping. It adds
  `nativephp/mobile-network`.
- clinically-au/mobile: about half of `ConnectivityState`,
  `Screen::refreshConnectivity()`, and, once mobile-air#673 ships,
  `nav-title-status.blade.php` and the `titleView` branch of `AppTabsLayout`.
  `PendingActionService` implements `PendingChanges`. The queue, the API cache,
  the Pending screen, and the sync jobs stay.
- mobile-auth: its logout cleanup calls `Drafts::flush()`.

## Evidence

Each piece follows [Testing](../workflows/testing.md): PHP contract tests
through `Native::test()`, consumer documentation naming only implemented APIs,
and no native code. Before release, the showcase gains an "Offline" screen and
the Article IX.4 device check records, on iOS and Android: airplane mode on
and off, backgrounding while offline, a swipe or system back with edits, and
reopening the edited screen.

## Out of scope

Local-first database sync, merging conflicting edits, the API cache, background
fetch while the app is closed, blocking iOS back to ask "Discard changes?", and
WebView apps.
