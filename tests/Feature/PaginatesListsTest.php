<?php

use FirstlightUI\Concerns\PaginatesLists;
use FirstlightUI\Pagination\ListPage;
use InvalidArgumentException;
use Native\Mobile\Edge\NativeComponent;
use RuntimeException;

final class ArrayPaginator
{
    /**
     * @param  list<mixed>  $items
     */
    public function __construct(
        private array $items,
        private bool $hasMore,
        private int $page = 1,
        private ?string $nextCursor = null,
    ) {}

    /** @return list<mixed> */
    public function items(): array
    {
        return $this->items;
    }

    public function hasMorePages(): bool
    {
        return $this->hasMore;
    }

    public function currentPage(): int
    {
        return $this->page;
    }

    public function nextCursor(): ?string
    {
        return $this->nextCursor;
    }
}

final class CollectionPaginator
{
    public function __construct(
        private object $items,
        private bool $hasMore,
    ) {}

    public function items(): object
    {
        return $this->items;
    }

    public function hasMorePages(): bool
    {
        return $this->hasMore;
    }
}

final class EncodedCursor
{
    public function encode(): string
    {
        return 'cursor-2';
    }
}

final class ObjectCursorPaginator
{
    /**
     * @param  list<mixed>  $items
     */
    public function __construct(
        private array $items,
        private bool $hasMore,
        private ?object $nextCursor,
    ) {}

    /** @return list<mixed> */
    public function items(): array
    {
        return $this->items;
    }

    public function hasMorePages(): bool
    {
        return $this->hasMore;
    }

    public function nextCursor(): ?object
    {
        return $this->nextCursor;
    }
}

final class PaginatingListScreen extends NativeComponent
{
    use PaginatesLists;
}

it('replaces items on refresh and appends on load more', function () {
    $screen = new PaginatingListScreen;
    $pages = [];

    expect($screen->refreshList(function (ListPage $page) use (&$pages): ArrayPaginator {
        $pages[] = $page;

        return new ArrayPaginator(['alpha', 'beta'], true, 1);
    }))->toBeTrue()
        ->and($pages)->toHaveCount(1)
        ->and($pages[0]->page)->toBe(1)
        ->and($pages[0]->cursor)->toBeNull()
        ->and($screen->listItems)->toBe(['alpha', 'beta'])
        ->and($screen->listHasMore)->toBeTrue()
        ->and($screen->listPage)->toBe(1)
        ->and($screen->listPaginating)->toBeFalse();

    expect($screen->loadMoreList(function (ListPage $page) use (&$pages): ArrayPaginator {
        $pages[] = $page;

        return new ArrayPaginator(['gamma'], false, 2);
    }))->toBeTrue()
        ->and($pages[1]->page)->toBe(2)
        ->and($screen->listItems)->toBe(['alpha', 'beta', 'gamma'])
        ->and($screen->listHasMore)->toBeFalse()
        ->and($screen->listPage)->toBe(2);
});

it('does not fetch when load more is requested without remaining pages', function () {
    $screen = new PaginatingListScreen;
    $fetched = 0;

    $screen->refreshList(function () use (&$fetched): ArrayPaginator {
        $fetched++;

        return new ArrayPaginator(['only'], false, 1);
    });

    expect($screen->loadMoreList(function () use (&$fetched): ArrayPaginator {
        $fetched++;

        return new ArrayPaginator(['extra'], false, 2);
    }))->toBeFalse()
        ->and($fetched)->toBe(1)
        ->and($screen->listItems)->toBe(['only']);
});

it('skips re-entry while a page fetch is running', function () {
    $screen = new PaginatingListScreen;
    $innerResult = null;

    $outerResult = $screen->refreshList(function () use ($screen, &$innerResult): ArrayPaginator {
        $innerResult = $screen->loadMoreList(fn (): ArrayPaginator => new ArrayPaginator(['nope'], false, 2));

        return new ArrayPaginator(['alpha'], true, 1);
    });

    expect($outerResult)->toBeTrue()
        ->and($innerResult)->toBeFalse()
        ->and($screen->listItems)->toBe(['alpha'])
        ->and($screen->listPaginating)->toBeFalse();
});

it('passes the stored cursor on the next page request', function () {
    $screen = new PaginatingListScreen;
    $cursors = [];

    $screen->refreshList(function (ListPage $page) use (&$cursors): ArrayPaginator {
        $cursors[] = $page->cursor;

        return new ArrayPaginator(['a'], true, 1, 'cursor-1');
    });

    $screen->loadMoreList(function (ListPage $page) use (&$cursors): ArrayPaginator {
        $cursors[] = $page->cursor;

        return new ArrayPaginator(['b'], false, 1, null);
    });

    expect($cursors)->toBe([null, 'cursor-1'])
        ->and($screen->listCursor)->toBeNull();
});

it('encodes object cursors from nextCursor()', function () {
    $screen = new PaginatingListScreen;

    $screen->refreshList(fn (): ObjectCursorPaginator => new ObjectCursorPaginator(
        ['a'],
        true,
        new EncodedCursor,
    ));

    expect($screen->listCursor)->toBe('cursor-2');
});

it('accepts collection-shaped items()', function () {
    $screen = new PaginatingListScreen;
    $collection = new class
    {
        /** @return list<string> */
        public function all(): array
        {
            return ['one', 'two'];
        }
    };

    $screen->refreshList(fn (): CollectionPaginator => new CollectionPaginator($collection, false));

    expect($screen->listItems)->toBe(['one', 'two']);
});

it('rejects a fetch result that is not a paginator', function () {
    $screen = new PaginatingListScreen;

    expect(fn () => $screen->refreshList(fn (): array => ['nope']))
        ->toThrow(InvalidArgumentException::class, 'items() and hasMorePages()')
        ->and($screen->listPaginating)->toBeFalse()
        ->and($screen->listItems)->toBe([]);
});

it('rethrows unexpected exceptions and releases the pagination guard', function () {
    $screen = new PaginatingListScreen;

    expect(fn () => $screen->refreshList(fn () => throw new RuntimeException('boom')))
        ->toThrow(RuntimeException::class, 'boom')
        ->and($screen->listPaginating)->toBeFalse()
        ->and($screen->listItems)->toBe([]);
});

it('exposes list pagination on Firstlight NativeComponent', function () {
    $screen = new class extends FirstlightUI\NativeComponent {};

    expect($screen->refreshList(fn (): ArrayPaginator => new ArrayPaginator(['row'], false)))
        ->toBeTrue()
        ->and($screen->listItems)->toBe(['row']);
});
