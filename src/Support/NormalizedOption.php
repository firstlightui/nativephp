<?php

namespace FirstlightUI\Support;

final readonly class NormalizedOption
{
    public function __construct(
        public string|int $value,
        public string $label,
        public bool $disabled,
    ) {}

    public function wireValue(): string
    {
        return (string) $this->value;
    }
}
