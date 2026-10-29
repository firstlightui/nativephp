<?php

namespace FirstlightUI\Validation;

use Illuminate\Support\MessageBag;
use Native\Mobile\Edge\FieldErrors;

final class FieldErrorBag
{
    private static ?MessageBag $alertBag = null;

    private static ?ValidationAlert $alert = null;

    public static function current(): ?MessageBag
    {
        return FieldErrors::current();
    }

    public static function alert(): ?ValidationAlert
    {
        return self::$alertBag === self::current() ? self::$alert : null;
    }

    public static function using(MessageBag $bag, callable $callback, ?ValidationAlert $alert = null): mixed
    {
        $previous = [self::$alertBag, self::$alert];
        self::$alertBag = $bag;
        self::$alert = $alert;

        try {
            return FieldErrors::using($bag, $callback);
        } finally {
            [self::$alertBag, self::$alert] = $previous;
        }
    }

    public static function reset(): void
    {
        self::$alertBag = null;
        self::$alert = null;
    }
}
