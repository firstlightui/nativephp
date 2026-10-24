<?php

namespace FirstlightUI\Validation;

use Illuminate\Support\MessageBag;

final class FieldErrorBag
{
    private static ?MessageBag $current = null;

    private static ?ValidationAlert $alert = null;

    public static function current(): ?MessageBag
    {
        return self::$current;
    }

    public static function alert(): ?ValidationAlert
    {
        return self::$alert;
    }

    public static function using(MessageBag $bag, callable $callback, ?ValidationAlert $alert = null): mixed
    {
        $previous = [self::$current, self::$alert];
        self::$current = $bag;
        self::$alert = $alert;

        try {
            return $callback();
        } finally {
            [self::$current, self::$alert] = $previous;
        }
    }

    public static function reset(): void
    {
        self::$current = null;
        self::$alert = null;
    }
}
