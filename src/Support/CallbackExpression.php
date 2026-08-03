<?php

namespace Clinically\Firstlight\Support;

final class CallbackExpression
{
    public static function appendInteger(string $expression, int $value): string
    {
        if (! str_contains($expression, '(')) {
            return "{$expression}({$value})";
        }

        $expression = rtrim($expression);
        $prefix = substr($expression, 0, -1);

        return str_ends_with($prefix, '(')
            ? "{$prefix}{$value})"
            : "{$prefix}, {$value})";
    }
}
