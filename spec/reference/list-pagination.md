---
title: Firstlight list pagination
description: Current PHP helper for binding Laravel paginators onto List refresh and end-reached without a new catalogue tag.
status: current
audience: maintainer
sources:
  - Constitution.md
  - src/Concerns/PaginatesLists.php
  - src/Pagination/ListPage.php
  - src/NativeComponent.php
  - spec/components/list.md
  - tests/Feature/PaginatesListsTest.php
  - docs/how-to/paginate-lists.md
---

# Firstlight List Pagination

List pagination is a PHP extension over existing List `@refresh` and
`@end-reached` callbacks. It adds no Element Tree type, Blade container, native
renderer, Empty primitive, or second scroll-event vocabulary.

## Public PHP surface

`FirstlightUI\Concerns\PaginatesLists` exposes:

```php
/** @var list<mixed> */
public array $listItems = [];

public bool $listHasMore = false;

public int $listPage = 1;

public ?string $listCursor = null;

public bool $listPaginating = false;

public function refreshList(callable $fetch): bool;

public function loadMoreList(callable $fetch): bool;
```

`FirstlightUI\NativeComponent` includes this trait. A screen that extends
NativePHP's component directly may `use FirstlightUI\Concerns\PaginatesLists`.

`$fetch` receives one `FirstlightUI\Pagination\ListPage` with `int $page` and
`?string $cursor`. It must return a Laravel `LengthAwarePaginator`,
`Paginator`, or `CursorPaginator`, or a compatible object that provides
`items()` and `hasMorePages()`.

## Execution order

When `$listPaginating` is already `true`, both methods return `false`
immediately and do not invoke `$fetch`.

`loadMoreList()` also returns `false` without fetching when `$listHasMore` is
`false`.

Otherwise the helper:

1. sets `$listPaginating` to `true`;
2. builds `ListPage` as page `1` and cursor `null` for refresh, or
   `$listPage + 1` and `$listCursor` for load more;
3. invokes `$fetch`;
4. replaces `$listItems` on refresh, or appends `items()` on load more;
5. stores `hasMorePages()`, `currentPage()` when present, and `nextCursor()`
   when present (`null`, a string, or an object with `encode()`);
6. returns `true`; and
7. restores `$listPaginating` to `false` in `finally`.

`items()` may return an array or a collection with `all()`. Values are stored
as a zero-indexed list of the paginator's items. The helper does not unique
rows or invent keys.

## Failure behaviour

A fetch result that is not an object with `items()` and `hasMorePages()`, or
whose `items()` / `hasMorePages()` / `nextCursor()` shapes are unusable, fails
with `InvalidArgumentException` before accumulated state changes.

Unexpected exceptions from `$fetch` propagate. The `finally` block still
releases `$listPaginating`. The helper does not convert those exceptions to
Feedback.

## State timing

The guard prevents re-entry only while the synchronous fetch runs on the same
PHP component. NativePHP compiles one tree after dispatch, so
`$listPaginating` cannot flash Activity Indicator during the default
one-request path. Bind List `@end-reached` unconditionally; extra events after
the last page are no-ops.

## Product boundary

Empty collections remain Blade composition of Status Label, Callout, and
Button around an empty List. This extension does not add an Empty tag, bind
HTTP `?page=` query strings, or wrap Mobile UI virtual lists. One trait
instance owns one accumulated `$listItems` list per screen.

Consumer guidance lives in
[Paginate Firstlight lists](../../docs/how-to/paginate-lists.md).
