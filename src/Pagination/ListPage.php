<?php

namespace FirstlightUI\Pagination;

final readonly class ListPage
{
    public function __construct(
        public int $page,
        public ?string $cursor = null,
    ) {}
}
