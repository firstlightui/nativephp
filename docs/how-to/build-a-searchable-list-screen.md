---
title: Build a searchable list screen
description: Recipe for a complete screen with native search, a paginated List, pull-to-refresh, row navigation, and confirmed swipe deletion.
type: how-to
audience: consumer
sources:
  - src/NativeComponent.php
  - src/Concerns/PaginatesLists.php
  - src/Concerns/DestroysListItems.php
  - src/Concerns/AuthorizesActions.php
  - src/Elements/SearchField.php
  - src/Elements/ListContainer.php
  - src/Elements/ListItem.php
  - src/Elements/ConfirmationDialog.php
  - src/Elements/StatusLabel.php
---

# Build a Searchable List Screen

This recipe builds the index screen most apps need: a search field above a
list of records, with pull-to-refresh, infinite scrolling, a tap that opens
the record, and a swipe action that deletes it after native confirmation.

Use it when a screen shows many records of one model and the user needs to
find, open, or remove one. Use [List](../components/list.md) without search for
short, fixed collections, and a settings layout when rows change preferences
instead of opening records.

Every piece is an existing Firstlight tag or `NativeComponent` helper. There is
no list-screen tag to learn.

## 1. Register the route

```php
use App\NativeComponents\Layouts\AppLayout;
use App\NativeComponents\PostsScreen;
use Illuminate\Support\Facades\Route;

Route::native('/posts', PostsScreen::class)
    ->name('posts')
    ->layout(AppLayout::class);
```

`AppLayout` gives the screen a native title bar and back navigation. It is
defined in [Build a list and detail flow](build-a-list-detail-flow.md).

## 2. Write the screen

Extend `FirstlightUI\NativeComponent`. It already includes
[PaginatesLists](paginate-lists.md), [DestroysListItems](destroy-list-items.md),
and [AuthorizesActions](authorize-actions.md).

```php
<?php

namespace App\NativeComponents;

use App\Models\Post;
use FirstlightUI\NativeComponent;
use FirstlightUI\Pagination\ListPage;
use Illuminate\View\View;

class PostsScreen extends NativeComponent
{
    public string $query = '';

    public function mount(): void
    {
        $this->reload();
    }

    public function updatedQuery(): void
    {
        $this->reload();
    }

    public function reload(): void
    {
        $this->refreshList(fn (ListPage $page) => $this->posts($page));
    }

    public function loadMore(): void
    {
        $this->loadMoreList(fn (ListPage $page) => $this->posts($page));
    }

    public function openPost(int $id): void
    {
        $this->navigate('/posts/'.$id);
    }

    public function requestDeletion(int $id): void
    {
        $this->requestDestructiveListAction($id, 'delete');
    }

    public function cancelDeletion(): void
    {
        $this->cancelDestructiveListAction();
    }

    public function deletePost(): void
    {
        $this->confirmDestructiveListAction(
            destroy: fn (Post $post) => $post->delete(),
            ability: 'delete',
            successMessage: 'Post deleted',
        );
    }

    public function navTitle(): string
    {
        return 'Posts';
    }

    public function render(): View
    {
        return view('native.posts');
    }

    private function posts(ListPage $page)
    {
        return Post::query()
            ->when($this->query !== '', fn ($query) => $query->where('title', 'like', '%'.$this->query.'%'))
            ->latest()
            ->paginate(perPage: 20, page: $page->page);
    }
}
```

`updatedQuery()` runs whenever PHP accepts a new query, so the list restarts
from page one for each search. Keys passed to `openPost()` and
`requestDeletion()` are model ids, never loop positions.

## 3. Write the view

Save this as `resources/views/native/posts.blade.php`:

```blade
<native:column class="w-full flex-1">
    <firstlight:search-field
        placeholder="Search posts"
        a11y-label="Search posts"
        native:model.debounce.300ms="query"
        class="mx-4 my-2"
    />

    @if (count($listItems) === 0)
        <firstlight:status-label
            label="{{ $query === '' ? 'No posts yet' : 'No matching posts' }}"
            tone="neutral"
            class="mx-4"
        />
    @endif

    <firstlight:list separator @refresh="reload" @endReached="loadMore" class="flex-1">
        @foreach ($listItems as $post)
            <firstlight:list-item
                :headline="$post->title"
                :supporting="$post->excerpt"
                trailing-icon="chevron-right"
                trailing-icon-ios="chevron.right"
                trailing-icon-android="chevron_right"
                @press="openPost({{ $post->id }})"
                :trailing-actions="[
                    ['method' => 'requestDeletion('.$post->id.')', 'label' => 'Delete', 'role' => 'destructive'],
                ]"
            />
        @endforeach
    </firstlight:list>

    <firstlight:confirmation-dialog
        :visible="$confirmingListDestruction"
        title="Delete post?"
        message="This action cannot be undone."
        confirm-label="Delete"
        cancel-label="Keep post"
        tone="destructive"
        @press="deletePost"
        @dismiss="cancelDeletion"
    />
</native:column>
```

## What each platform shows

| Behaviour | iOS | Android |
| --- | --- | --- |
| Search | `UISearchTextField` with the system clear button | Material 3 search field with the search IME action |
| Rows | SwiftUI `List` rows with dividers | `LazyColumn` Material list items with dividers |
| Refresh | System pull-to-refresh | Material pull-to-refresh |
| Delete | Trailing swipe action, then a native confirmation dialog | Trailing swipe action, then a native confirmation dialog |
| Feedback | "Post deleted" through Transient Feedback | "Post deleted" through Transient Feedback |

The same Blade works on both platforms. Swipe actions are also exposed as
VoiceOver and TalkBack actions, so screen-reader users can delete without a
swipe gesture.

## Rules to keep

- Keep `@endReached` bound even on the last page; extra events are ignored.
- Do not omit the Delete action for rows the Policy denies. Disable the row
  with `:disabled` instead, as described in [Authorize actions](authorize-actions.md).
- Full swipes never delete. The swipe only opens the confirmation dialog, and
  PHP authorizes again before deleting.
- Use `native:model.debounce.300ms` for search so each keystroke does not
  query the database.

## Adapt the recipe

- **No deletion:** remove `trailing-actions`, the dialog, and the three
  deletion methods.
- **Cursor pagination:** pass `$page->cursor` to `cursorPaginate()`, as shown
  in [Paginate lists](paginate-lists.md#cursor-pagination).
- **Search on submit instead of while typing:** bind `native:model.lazy="query"`.
