---
title: Paginate Firstlight lists
description: Bind Laravel paginators to List pull-to-refresh and end-reached without a new Firstlight tag.
type: how-to
audience: consumer
sources:
  - src/Concerns/PaginatesLists.php
  - src/Pagination/ListPage.php
  - src/NativeComponent.php
  - src/Elements/ListContainer.php
  - tests/Feature/PaginatesListsTest.php
---

# Paginate Firstlight Lists

Firstlight paginates lists in PHP. List already exposes `@refresh` and
`@end-reached`. The helper accumulates Laravel paginator pages into
`$this->listItems` so Blade can render ordinary List Item rows.

There is no `<firstlight:empty>` tag and no second scroll-event API.

## Declare the screen

Extend `FirstlightUI\NativeComponent` (or `use PaginatesLists`):

```php
<?php

namespace App\Native;

use App\Models\Conversation;
use FirstlightUI\NativeComponent;
use FirstlightUI\Pagination\ListPage;

class InboxScreen extends NativeComponent
{
    public function mount(): void
    {
        $this->reload();
    }

    public function reload(): void
    {
        $this->refreshList(
            fn (ListPage $page) => Conversation::query()->latest()->paginate(
                perPage: 20,
                page: $page->page,
            ),
        );
    }

    public function loadMore(): void
    {
        $this->loadMoreList(
            fn (ListPage $page) => Conversation::query()->latest()->paginate(
                perPage: 20,
                page: $page->page,
            ),
        );
    }
}
```

`refreshList()` replaces `$this->listItems` with the first page.
`loadMoreList()` appends the next page. Both return `false` when a fetch is
already running. `loadMoreList()` also returns `false` without querying when
`$this->listHasMore` is `false`.

Pass the paginator's `page` from `ListPage`. NativePHP has no HTTP `?page=`
query string for these events.

## Render the rows

```blade
@if (count($this->listItems) === 0)
    <firstlight:status-label
        label="No conversations yet"
        tone="neutral"
    />
    <firstlight:callout
        message="Pull to refresh after you add a conversation."
        tone="info"
    />
@endif

<firstlight:list separator @refresh="reload" @end-reached="loadMore">
    @foreach ($this->listItems as $conversation)
        <firstlight:list-item
            :headline="$conversation->title"
            @press="openConversation"
        />
    @endforeach
</firstlight:list>
```

Keep `@end-reached` bound even on the last page. Extra events are ignored.

Empty copy is ordinary Blade around List. Compose Status Label, Callout, and
Button when you need a retry action.

## Cursor pagination

`ListPage::$cursor` is `null` on refresh and the previous paginator's next
cursor afterward. Pass it through to `cursorPaginate()`:

```php
use Illuminate\Pagination\Cursor;

$this->refreshList(
    fn (ListPage $page) => Conversation::query()->latest()->cursorPaginate(
        perPage: 20,
        cursor: $page->cursor !== null ? Cursor::fromEncoded($page->cursor) : null,
    ),
);
```

Use the same callable with `loadMoreList()`.

Unexpected exceptions from the query are not converted to Feedback. Screens
that extend NativePHP's component directly can opt in with
`FirstlightUI\Concerns\PaginatesLists`.
