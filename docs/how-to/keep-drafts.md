---
title: Keep drafts across navigation
description: Keep a screen's unsaved changes when the user goes back, and offer them when the screen reopens.
type: how-to
audience: consumer
sources:
  - src/Concerns/KeepsDrafts.php
  - src/Drafts/Draft.php
  - src/Drafts/DraftStore.php
  - src/Facades/Drafts.php
  - src/Facades/Feedback.php
  - lang/en/chrome.php
  - tests/Feature/KeepsDraftsTest.php
---

# Keep Drafts Across Navigation

On iOS, Back and the edge swipe close a screen before PHP hears about it, so
an edit screen can't ask "Discard changes?". Firstlight keeps the unsaved
changes instead. When the screen opens again, they come back with a
`Restored your unsaved changes.` message and a `Discard` action. Android works
the same way.

Drafts are kept in memory while the app runs. They are never written to disk,
and they are lost when the app is closed.

## Declare what to keep

Use `KeepsDrafts` and return a `Draft` from `draft()`. Firstlight calls it
whenever the screen closes. Return null when nothing is unsaved. This screen
edits a post:

```php
<?php

namespace App\NativeComponents;

use App\Models\Post;
use FirstlightUI\Concerns\KeepsDrafts;
use FirstlightUI\Drafts\Draft;
use FirstlightUI\NativeComponent;
use Illuminate\View\View;

class EditPostScreen extends NativeComponent
{
    use KeepsDrafts;

    public Post $post;

    public string $title = '';

    public string $body = '';

    /** @var array<string, string> */
    protected array $rules = [
        'title' => 'required|min:3',
        'body' => 'required',
    ];

    public function mount(): void
    {
        $this->title = $this->post->title;
        $this->body = $this->post->body;

        $this->restoreDraft("post:{$this->post->id}", basedOn: $this->post->updated_at?->toISOString());
    }

    protected function draft(): ?Draft
    {
        if ($this->title === $this->post->title && $this->body === $this->post->body) {
            return null;
        }

        return new Draft(
            "post:{$this->post->id}",
            ['title' => $this->title, 'body' => $this->body],
            basedOn: $this->post->updated_at?->toISOString(),
        );
    }

    public function save(): void
    {
        $saved = $this->submit(
            action: fn () => $this->post->update([
                'title' => $this->title,
                'excerpt' => str($this->body)->limit(60)->toString(),
                'body' => $this->body,
            ]),
            successMessage: 'Post saved',
        );

        if ($saved) {
            $this->back();
        }
    }

    public function navTitle(): string
    {
        return 'Edit post';
    }

    public function render(): View
    {
        return view('native.edit-post');
    }
}
```

Register it with `Route::native('/posts/{post}/edit', EditPostScreen::class)`.
Its view is an ordinary form: a Text Field bound to `title`, a Text Area bound
to `body`, and a Save button that calls `save`.

- The key names the record, such as `post:5`. Drafts are already kept apart
  per screen class.
- The values are public property names and their unsaved values. Firstlight
  assigns them back to those properties. Keep them to strings, numbers, Booleans
  and arrays.
- `basedOn` is the version you edited from, such as `updated_at` or a content
  hash. It can be left out when the record has no version.
- After a save, the properties match the record, so `draft()` returns null and
  nothing is kept.

Call `restoreDraft()` once the data has loaded. If you load asynchronously,
call it in the callback that fills the fields, not in `mount()`.

## When the record changed

When the record's version changed since the draft was kept, Firstlight doesn't
overwrite the new data. It shows a warning that stays until the user acts:
`This changed since you last edited it. Your unsaved changes weren't restored.`
with a `Restore anyway` action.

- `Restore anyway` puts the draft into the fields.
- Swiping the warning away forgets the draft.
- Leaving without either keeps the draft, so it's offered again next time.
  New edits made in the meantime are kept instead.

## Keep each user's drafts apart

On a shared device, scope drafts to the signed-in account in a service
provider, and forget them all when someone signs out:

```php
use FirstlightUI\Facades\Drafts;

Drafts::scopeUsing(fn () => auth()->id());

// In your logout action
Drafts::flush();
```

While the resolver returns null, such as while signed out, nothing is kept or
restored.

## Change the wording

The four strings are Firstlight chrome. Override `draft_restored`,
`draft_changed`, `restore_anyway`, and `discard` in
`lang/vendor/firstlight/{locale}/chrome.php`, as described in
[Localize chrome](localize.md).

## Test it

Leave the screen the way the router does, then open it again:

```php
use App\NativeComponents\EditPostScreen;
use FirstlightUI\Facades\Drafts;
use Native\Mobile\Testing\Native;

it('keeps an unsaved edit when the user goes back', function () {
    $screen = Native::visit('/posts/1/edit')
        ->set('body', 'An unsaved edit.')
        ->pressBack()
        ->assertWentBack();
    $screen->instance()->unmount();

    expect(Drafts::has(EditPostScreen::class, 'post:1'))->toBeTrue();

    Native::visit('/posts/1/edit')->assertSet('body', 'An unsaved edit.');
});
```

`Drafts::has()` reports whether a draft is waiting for a closed screen. The
[Showcase](https://github.com/firstlightui/showcase) runs this screen as the Edit
post recipe.
