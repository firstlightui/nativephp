<?php

namespace FirstlightUI\Validation;

use Illuminate\Support\MessageBag;

final class FieldErrorBag
{
    private static ?MessageBag $current = null;

    public static function current(): ?MessageBag
    {
        return self::$current;
    }

    public static function using(MessageBag $bag, callable $callback): mixed
    {
        $previous = self::$current;
        self::$current = $bag;

        try {
            return $callback();
        } finally {
            self::$current = $previous;
        }
    }

    public static function reset(): void
    {
        self::$current = null;
    }
}
