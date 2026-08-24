<?php

namespace FirstlightUI\Concerns;

use FirstlightUI\Pagination\ListPage;
use InvalidArgumentException;

trait PaginatesLists
{
    /** @var list<mixed> */
    public array $listItems = [];

    public bool $listHasMore = false;

    public int $listPage = 1;

    public ?string $listCursor = null;

    public bool $listPaginating = false;

    /**
     * Replace accumulated rows with the first page. Bind to List `@refresh`.
     *
     * @param  callable(ListPage): object  $fetch
     */
    public function refreshList(callable $fetch): bool
    {
        return $this->fetchListPage($fetch, replace: true);
    }

    /**
     * Append the next page. Bind to List `@end-reached`.
     *
     * @param  callable(ListPage): object  $fetch
     */
    public function loadMoreList(callable $fetch): bool
    {
        return $this->fetchListPage($fetch, replace: false);
    }

    /**
     * @param  callable(ListPage): object  $fetch
     */
    private function fetchListPage(callable $fetch, bool $replace): bool
    {
        if ($this->listPaginating) {
            return false;
        }

        if (! $replace && ! $this->listHasMore) {
            return false;
        }

        $this->listPaginating = true;

        try {
            $page = $replace
                ? new ListPage(1, null)
                : new ListPage($this->listPage + 1, $this->listCursor);

            $paginator = $fetch($page);
            $items = $this->listPaginatorItems($paginator);
            $hasMore = $this->listPaginatorHasMore($paginator);
            $listPage = $this->listPaginatorPage($paginator, $page->page);
            $listCursor = $this->listPaginatorCursor($paginator);

            if ($replace) {
                $this->listItems = $items;
            } else {
                $this->listItems = [...$this->listItems, ...$items];
            }

            $this->listHasMore = $hasMore;
            $this->listPage = $listPage;
            $this->listCursor = $listCursor;

            return true;
        } finally {
            $this->listPaginating = false;
        }
    }

    /** @return list<mixed> */
    private function listPaginatorItems(mixed $paginator): array
    {
        if (! is_object($paginator) || ! method_exists($paginator, 'items')) {
            throw new InvalidArgumentException(
                'Firstlight list pagination requires a Laravel paginator (or compatible object) with items() and hasMorePages(). LengthAwarePaginator, Paginator, and CursorPaginator are accepted.',
            );
        }

        $items = $paginator->items();

        if (is_object($items) && method_exists($items, 'all')) {
            $items = $items->all();
        }

        if (! is_array($items)) {
            throw new InvalidArgumentException(
                'Firstlight list pagination requires items() to return an array or a collection with all().',
            );
        }

        return array_values($items);
    }

    private function listPaginatorHasMore(object $paginator): bool
    {
        if (! method_exists($paginator, 'hasMorePages')) {
            throw new InvalidArgumentException(
                'Firstlight list pagination requires a Laravel paginator (or compatible object) with items() and hasMorePages(). LengthAwarePaginator, Paginator, and CursorPaginator are accepted.',
            );
        }

        $hasMore = $paginator->hasMorePages();

        if (! is_bool($hasMore)) {
            throw new InvalidArgumentException(
                'Firstlight list pagination requires hasMorePages() to return a boolean.',
            );
        }

        return $hasMore;
    }

    private function listPaginatorPage(object $paginator, int $fallback): int
    {
        if (! method_exists($paginator, 'currentPage')) {
            return $fallback;
        }

        $page = $paginator->currentPage();

        return is_int($page) && $page >= 1 ? $page : $fallback;
    }

    private function listPaginatorCursor(object $paginator): ?string
    {
        if (! method_exists($paginator, 'nextCursor')) {
            return null;
        }

        $cursor = $paginator->nextCursor();

        if ($cursor === null) {
            return null;
        }

        if (is_string($cursor)) {
            return $cursor;
        }

        if (is_object($cursor) && method_exists($cursor, 'encode')) {
            $encoded = $cursor->encode();

            if (is_string($encoded) && $encoded !== '') {
                return $encoded;
            }
        }

        throw new InvalidArgumentException(
            'Firstlight list pagination requires nextCursor() to return null, a string, or an object with encode().',
        );
    }
}
