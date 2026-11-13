---
title: Firstlight kept drafts
description: Current PHP helper for keeping a screen's unsaved changes when it closes, restoring them on reopen, and offering drafts of a changed record.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-11-13-firstlight-offline-and-stale-data-design.md
  - src/Concerns/KeepsDrafts.php
  - src/Drafts/Draft.php
  - src/Drafts/DraftStore.php
  - src/Facades/Drafts.php
  - src/FirstlightServiceProvider.php
  - src/Support/Chrome.php
  - lang/en/chrome.php
  - src/Facades/Feedback.php
  - src/NativeComponents/FeedbackCenter.php
  - vendor/nativephp/mobile/src/Edge/NativeRouter.php
  - tests/Feature/KeepsDraftsTest.php
  - docs/how-to/keep-drafts.md
---

# Firstlight Kept Drafts

Kept drafts are a PHP extension over screen lifecycle and Transient Feedback.
They add no Element Tree type, native code, dialog, or catalogue tag.

iOS pops a screen on Back or an edge swipe before PHP hears about it, so a
screen cannot ask "Discard changes?". Instead the screen keeps its unsaved
values when it closes and offers them when it reopens. Android behaves the
same way so both platforms match.

## Public PHP surface

`FirstlightUI\Drafts\Draft` is a final readonly value:

```php
public function __construct(
    string $key,                    // the record within the screen, such as "note:5"
    array $values,                  // public property name => unsaved value
    string|int|null $basedOn = null // the version the values were edited from
);
```

`$key` rejects blank strings. `$values` must be keyed by non-empty property
names. `$basedOn` is stored as `?string`, so `7` and `'7'` are the same
version.

`FirstlightUI\Concerns\KeepsDrafts` is opt-in. `FirstlightUI\NativeComponent`
does not use it, because its `draft()` hook would clash with an existing
screen method of the same name. It works on Firstlight's `NativeComponent` and
on NativePHP's component directly. It declares:

```php
abstract protected function draft(): ?Draft;
protected function restoreDraft(string $key, string|int|null $basedOn = null): bool;
public function unmount(): void;
```

and two `#[On]` listeners, `answerDraftFeedback()` for `FeedbackActionPressed`
and `draftFeedbackDismissed()` for `FeedbackDismissed`.

`FirstlightUI\Facades\Drafts` resolves the `DraftStore` singleton registered by
`FirstlightServiceProvider`:

```php
Drafts::scopeUsing(Closure $resolver): void;      // fn (): string|int|null
Drafts::has(string $screen, string $key): bool;
Drafts::flush(): void;
```

## Keeping

`unmount()` runs on every way NativePHP removes a screen: back, system back
and edge swipe, replace, exit to web, and restart. It:

1. dismisses the screen's draft Feedback, if one is showing;
2. calls `draft()`; when that returns null, it uses a draft that was offered
   with `Restore anyway` and neither restored nor declined;
3. puts the result in the store, replacing any earlier draft for the same
   screen and key; and
4. calls `parent::unmount()`.

`draft()` returns null when nothing is unsaved, which includes after a
successful save. The helper does not compare values itself. A screen that
declares its own `unmount()` replaces the trait's, and then keeps no draft.

## Restoring

`restoreDraft()` takes the draft for this screen class and key out of the
store. With no draft it returns `false` and changes nothing. Otherwise:

- **Same version** (`basedOn` equal, including both null): it assigns each
  value to its public property, remembers the values they replaced, sends
  `Feedback::message('Restored your unsaved changes.')` with the action
  `Discard`, and returns `true`.
- **Different version:** it assigns nothing, sends a held
  `Feedback::warning("This changed since you last edited it. Your unsaved changes weren't restored.")`
  with the action `Restore anyway`, and returns `false`.

A value whose name is not a public, non-static property throws
`InvalidArgumentException` before any property is assigned.

Values are assigned directly, without `updatedFoo()` hooks. Screens call
`restoreDraft()` after their data has loaded, once per load that should
restore.

## Feedback actions

Each draft message gets a generated Feedback ID, which the screen remembers.
The listeners ignore any other ID, so the screen's own Feedback handlers keep
working. The `Restored` message after `Restore anyway` must not reuse the
warning's ID: the native queues treat an ID whose action was just pressed as
finished and would never show it.

| Outcome | Effect |
| --- | --- |
| `Discard` pressed | The replaced values are assigned back. |
| `Restore anyway` pressed | The offered values are assigned as on the same version, with the `Discard` Feedback. |
| The warning dismissed manually | The offered draft is forgotten. |
| The message times out | Nothing changes. |
| The screen is left | The Feedback is dismissed with `Feedback::dismiss()`; see Keeping. |

The four strings are package chrome: `draft_restored`, `draft_changed`,
`restore_anyway`, and `discard`, overridable through `firstlight::chrome`.

## Store and scope

`DraftStore` holds drafts in process memory and never writes them to disk; a
draft may hold secrets or patient data. A draft is lost when the process ends.

Drafts are keyed by screen class and key, so two screens using `note:5` do not
share a draft. With a scope resolver, they are also keyed by its value, and a
null value keeps and restores nothing. `flush()` forgets every draft in every
scope; call it on logout or account switch.

While a screen is open its draft is out of the store, so `has()` reports only
drafts of closed screens.

## Product boundary

No "Discard changes?" prompt, back interception, autosave, disk store, or merge
of a changed record. Consumer guidance lives in
[Keep drafts across navigation](../../docs/how-to/keep-drafts.md). The wider
offline design is in the
[offline and stale-data design](../designs/2026-11-13-firstlight-offline-and-stale-data-design.md).
