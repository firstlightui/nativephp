---
title: Destroy Firstlight list items
description: Authorize Confirmation Dialog deletion of List rows by stable keys, then Feedback and list republish.
type: how-to
audience: consumer
sources:
  - src/Concerns/DestroysListItems.php
  - src/Concerns/AuthorizesActions.php
  - src/Concerns/PaginatesLists.php
  - src/NativeComponent.php
  - src/Facades/Feedback.php
  - src/Elements/ListItem.php
  - src/Elements/ConfirmationDialog.php
  - tests/Feature/DestroysListItemsTest.php
---

# Destroy Firstlight List Items

Firstlight deletes list rows in PHP. Pass a stable domain key into the helper,
confirm with Confirmation Dialog, authorize again, destroy the model, and let
`$listItems` republish without that row.

There is no swipe-delete API, trailing action slot, or renderer index. See
[Authorize actions](authorize-actions.md) for hide-versus-disable rules and
[Paginate lists](paginate-lists.md) for `$listItems`.

## Declare the screen

Extend `FirstlightUI\NativeComponent` (or use `AuthorizesActions`,
`DestroysListItems`, and a `$listItems` array):

```php
<?php

namespace App\Native;

use App\Models\Post;
use FirstlightUI\NativeComponent;

class PostsScreen extends NativeComponent
{
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
}
```

`requestDestructiveListAction()` resolves the row by key from `$listItems`,
calls `authorize()`, and sets `$confirmingListDestruction` when allowed.
`confirmDestructiveListAction()` closes the dialog, authorizes again, runs the
destroy callable, removes the row from `$listItems`, and optionally sends
success Feedback.

Keys must be stable identities such as model ids. Do not pass loop indexes.

## Keep denied rows visible

```blade
<firstlight:list separator>
    @foreach ($this->listItems as $post)
        <firstlight:list-item
            :headline="$post->title"
            supporting="Delete permanently"
            :disabled="$this->denies('delete', $post)"
            @press="requestDeletion({{ $post->id }})"
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
```

Never omit a destructive List Item when the Policy denies it. Disable the row
and keep supporting copy that explains the unavailable action when the
surrounding interface does not already make that clear.

## Custom keys or database resolve

Override `destructiveListItemKey()` when rows do not expose `getKey()`, `id`,
or an `id` array key. Override `resolveDestructiveListItem()` to reload the
model from the database instead of trusting the in-memory `$listItems` copy:

```php
protected function resolveDestructiveListItem(mixed $key): mixed
{
    return Post::query()->find($key);
}
```

When you resolve from the database, `$listItems` is still updated after a
successful destroy so the published List matches the mutation.
