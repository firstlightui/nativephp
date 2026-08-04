<?php

namespace FirstlightUI\Support;

final class CallbackExpression
{
    public static function appendInteger(string $expression, int $value): string
    {
        return self::appendValue($expression, $value);
    }

    /** @param string|int|array<array-key, string|int>|null $value */
    public static function appendValue(string $expression, string|int|array|null $value): string
    {
        $literal = json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
        $literal = str_replace("'", '\\u0027', $literal);

        if (! str_contains($expression, '(')) {
            return "{$expression}({$literal})";
        }

        $expression = rtrim($expression);
        $prefix = substr($expression, 0, -1);

        return str_ends_with($prefix, '(')
            ? "{$prefix}{$literal})"
            : "{$prefix}, {$literal})";
    }
}
