<?php

namespace FirstlightUI\Freshness;

/** What the freshness partial shows: one Callout, with "Try again" when the screen gave a retry. */
final readonly class FreshnessNotice
{
    public function __construct(
        public string $key,
        public string $message,
        public string $tone,
        public ?string $actionLabel = null,
    ) {}
}
