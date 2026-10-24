<?php

namespace FirstlightUI\Support;

use InvalidArgumentException;

final class FiniteNumber
{
    /** Binary floating-point comparison tolerance used only for grid membership. */
    public const GRID_EPSILON = 1.0E-9;

    /** Material Slider exposes its interval count as a signed 32-bit integer. */
    public const MAX_NATIVE_INTERVALS = 2_147_483_647;

    public static function normalize(mixed $value, string $context): float
    {
        if (! is_int($value) && ! is_float($value)) {
            throw self::invalidNumber($context);
        }

        $number = (float) $value;
        if (! is_finite($number)) {
            throw self::invalidNumber($context);
        }

        $native = unpack('gvalue', pack('g', $number));
        $nativeValue = $native['value'];

        if (! is_finite($nativeValue) || ($number !== 0.0 && $nativeValue === 0.0)) {
            throw self::invalidNumber($context);
        }

        return $number;
    }

    /** Round-trip error budget, in Float epsilons, for values computed by a native renderer. */
    private const NATIVE_FLOAT_EPSILONS = 4 * 1.1920928955078125E-7;

    public static function gridIndex(float $value, float $origin, float $step, string $context, bool $fromNativeFloat = false): int
    {
        $quotient = ($value - $origin) / $step;
        if (! is_finite($quotient)) {
            throw new InvalidArgumentException("{$context} exceeds the native interval limit.");
        }

        $nearest = round($quotient);
        $tolerance = $fromNativeFloat ? self::nativeGridTolerance($value, $origin, $step) : self::GRID_EPSILON;
        if (abs($quotient - $nearest) > $tolerance) {
            throw new InvalidArgumentException("{$context} must lie on the step grid.");
        }

        if ($nearest < 0 || $nearest > self::MAX_NATIVE_INTERVALS) {
            throw new InvalidArgumentException("{$context} exceeds the native interval limit.");
        }

        return (int) $nearest;
    }

    /**
     * Grid-quotient tolerance for a value a native renderer computed in 32-bit Float
     * (for example 0.3f arrives as 0.30000001192...), which the strict epsilon rejects.
     */
    public static function nativeGridTolerance(float $value, float $origin, float $step): float
    {
        return self::GRID_EPSILON + self::NATIVE_FLOAT_EPSILONS * (abs($value) + abs($origin)) / $step;
    }

    private static function invalidNumber(string $context): InvalidArgumentException
    {
        return new InvalidArgumentException(
            "{$context} must be a finite int or float within the native Float range."
        );
    }
}
