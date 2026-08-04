<?php

use FirstlightUI\Exceptions\InvalidOption;
use FirstlightUI\Support\OptionNormalizer;

it('normalizes simple string options', function () {
    $options = OptionNormalizer::normalize(['Mine', 'All']);

    expect($options->valueType)->toBe('string')
        ->and($options->wireValues())->toBe(['Mine', 'All'])
        ->and($options->labels())->toBe(['Mine', 'All'])
        ->and($options->enabledFlags())->toBe(['1', '1']);
});

it('normalizes associative values and labels', function () {
    $options = OptionNormalizer::normalize(['mine' => 'Mine', 'all' => 'All']);

    expect($options->wireValues())->toBe(['mine', 'all'])
        ->and($options->labels())->toBe(['Mine', 'All']);
});

it('preserves integer keys in associative options', function () {
    $options = OptionNormalizer::normalize([10 => 'Routine', 20 => 'Urgent']);

    expect($options->valueType)->toBe('integer')
        ->and($options->wireValues())->toBe(['10', '20']);
});

it('normalizes an empty option set as inert string options', function () {
    $options = OptionNormalizer::normalize([]);

    expect($options->valueType)->toBe('string')
        ->and($options->items)->toBe([]);
});

it('preserves integer values in rich options', function () {
    $options = OptionNormalizer::normalize([
        ['value' => 10, 'label' => 'Routine', 'disabled' => false],
        ['value' => 20, 'label' => 'Urgent', 'disabled' => true],
    ]);

    expect($options->valueType)->toBe('integer')
        ->and($options->wireValues())->toBe(['10', '20'])
        ->and($options->enabledFlags())->toBe(['1', '0']);
});

it('rejects mixed values, duplicate values, unknown fields, and non-string labels', function (array $input, string $message) {
    expect(fn () => OptionNormalizer::normalize($input))->toThrow(InvalidOption::class, $message);
})->with([
    'mixed types' => [[['value' => '1', 'label' => 'One'], ['value' => 2, 'label' => 'Two']], 'mix string and integer'],
    'duplicates' => [[['value' => 'same', 'label' => 'One'], ['value' => 'same', 'label' => 'Two']], 'duplicate value'],
    'unknown field' => [[['value' => 'one', 'label' => 'One', 'icon' => 'star']], 'unknown field'],
    'invalid label' => [[['value' => 'one', 'label' => 1]], 'label must be a string'],
]);
