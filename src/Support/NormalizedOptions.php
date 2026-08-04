<?php

namespace FirstlightUI\Support;

final readonly class NormalizedOptions
{
    /** @param list<NormalizedOption> $items */
    public function __construct(public string $valueType, public array $items) {}

    public function wireValues(): array
    {
        return array_map(fn (NormalizedOption $option) => $option->wireValue(), $this->items);
    }

    public function labels(): array
    {
        return array_map(fn (NormalizedOption $option) => $option->label, $this->items);
    }

    public function enabledFlags(): array
    {
        return array_map(fn (NormalizedOption $option) => $option->disabled ? '0' : '1', $this->items);
    }
}
