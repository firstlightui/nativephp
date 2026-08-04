<?php

use FirstlightUI\Support\FiniteNumber;

it('normalizes finite integers and floats to renderer floats', function (int|float $value, float $expected) {
    expect(FiniteNumber::normalize($value, 'Slider `value`'))->toBe($expected);
})->with([
    'zero integer' => [0, 0.0],
    'positive integer' => [12, 12.0],
    'negative fraction' => [-1.25, -1.25],
]);

it('rejects non numeric non finite and native Float overflowing values', function (mixed $value) {
    FiniteNumber::normalize($value, 'Slider `value`');
})->with([
    'numeric string' => ['1.5'],
    'boolean' => [true],
    'null' => [null],
    'array' => [[1.5]],
    'nan' => [NAN],
    'positive infinity' => [INF],
    'negative infinity' => [-INF],
    'native Float overflow' => [PHP_FLOAT_MAX],
    'native Float underflow' => [1.0E-100],
])->throws(InvalidArgumentException::class, 'finite int or float within the native Float range');

it('resolves decimal grid positions with a binary comparison epsilon', function () {
    expect(FiniteNumber::gridIndex(0.3, 0.0, 0.1, 'Slider `value`'))->toBe(3)
        ->and(FiniteNumber::gridIndex(1.0, -1.0, 0.25, 'Slider range'))->toBe(8);
});

it('rejects values outside the declared step grid', function () {
    FiniteNumber::gridIndex(0.31, 0.0, 0.1, 'Slider `value`');
})->throws(InvalidArgumentException::class, 'must lie on the step grid');

it('rejects native grids that exceed the Material interval limit', function () {
    FiniteNumber::gridIndex(2_147_483_648.0, 0.0, 1.0, 'Slider range');
})->throws(InvalidArgumentException::class, 'exceeds the native interval limit');
