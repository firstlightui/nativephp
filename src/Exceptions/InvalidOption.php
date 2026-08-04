<?php

namespace FirstlightUI\Exceptions;

use InvalidArgumentException;

final class InvalidOption extends InvalidArgumentException
{
    public static function at(int $index, string $message): self
    {
        return new self("Option at index {$index}: {$message}");
    }
}
