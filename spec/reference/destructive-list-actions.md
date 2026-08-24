---
title: Firstlight destructive list actions
description: Current PHP helper for authorizing Confirmation Dialog destruction of List rows by stable keys and republishing `$listItems`.
status: current
audience: maintainer
sources:
  - Constitution.md
  - src/Concerns/DestroysListItems.php
  - src/Concerns/AuthorizesActions.php
  - src/Concerns/PaginatesLists.php
  - src/NativeComponent.php
  - src/Facades/Feedback.php
  - src/Elements/ListItem.php
  - src/Elements/ConfirmationDialog.php
  - tests/Feature/DestroysListItemsTest.php
  - docs/how-to/destroy-list-items.md
---

# Firstlight Destructive List Actions

Destructive list actions are a PHP extension over existing List Item rows,
Confirmation Dialog, Gate authorization, Transient Feedback, and the
`$listItems` collection owned by list pagination (or an authored equivalent).
They add no Element Tree type, swipe API, trailing action vocabulary, Empty
primitive, or renderer index.

## Public PHP surface

`FirstlightUI\Concerns\DestroysListItems` exposes:

```php
public bool $confirmingListDestruction = false;

public mixed $pendingListDestructionKey = null;

public function requestDestructiveListAction(mixed $key, string $ability = 'delete'): bool;

public function cancelDestructiveListAction(): void;

public function confirmDestructiveListAction(
    callable $destroy,
    string $ability = 'delete',
    ?string $successMessage = null,
): bool;
```

`FirstlightUI\NativeComponent` includes this trait together with
`AuthorizesActions` and `PaginatesLists`. A screen that extends NativePHP's
component directly must `use` `AuthorizesActions` and `DestroysListItems`, and
must expose a public `$listItems` array (or override resolution).

## Execution order

### Request

`requestDestructiveListAction()`:

1. resolves the row for `$key` through `resolveDestructiveListItem()`;
2. returns `false` and clears pending state when the row is missing;
3. calls `$this->authorize($ability, $item)`;
4. on denial, clears pending state and returns `false` (danger Feedback comes
   from `authorize()`);
5. stores `$pendingListDestructionKey = $key`, sets
   `$confirmingListDestruction = true`, and returns `true`.

`$key` must be a stable domain identity (model key, `id`, or equivalent). It
must not be a renderer index or display position.

### Cancel

`cancelDestructiveListAction()` clears `$confirmingListDestruction` and
`$pendingListDestructionKey` without invoking `$destroy` or Feedback.

### Confirm

`confirmDestructiveListAction()`:

1. captures and clears the pending key and confirmation flag so the dialog
   closes on the next publication;
2. returns `false` when no key was pending or the row can no longer be resolved;
3. calls `$this->authorize($ability, $item)` again;
4. on denial, returns `false` without destroying or mutating `$listItems`;
5. invokes `$destroy($item)`;
6. removes matching rows from `$listItems` by stable key;
7. sends `Feedback::success($successMessage)->send()` when the message is
   non-null and non-blank after trimming; and
8. returns `true`.

Unexpected exceptions from `$destroy` propagate after confirmation state is
cleared. `$listItems` is left unchanged when destroy fails.

## Key resolution

Default `destructiveListItemKey()` accepts, in order:

- an object with `getKey()`;
- an object with a public `id` property; or
- an array with an `id` key.

Anything else fails with `InvalidArgumentException` unless the screen overrides
`destructiveListItemKey()`. Key comparison uses loose equality so string route
arguments match integer model keys. Override `resolveDestructiveListItem()` to
reload from the database instead of walking `$listItems`.

## Product boundary

List Item remains a single `@press` row. Confirmation Dialog remains the
destructive confirmation surface with `tone="destructive"`. This extension does
not enable swipe-delete, trailing action buttons, menus, or Multi-select.
Presentation policy for denied destructive controls (keep visible, disable)
stays in [Action authorization](action-authorization.md).

Consumer guidance lives in
[Destroy Firstlight list items](../../docs/how-to/destroy-list-items.md).
