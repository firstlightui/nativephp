<?php

use FirstlightUI\Elements\ActivityIndicator;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.activity-indicator', ActivityIndicator::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectActivityIndicator(array $attributes): array
{
    NativeElementCollector::leaf('firstlight.activity-indicator', $attributes);

    return NativeElementCollector::collect()->toArray(new CallbackRegistry);
}

it('publishes the default medium indicator with its required accessibility label', function () {
    $tree = collectActivityIndicator([
        'a11y-label' => 'Loading patient details',
        'width' => 'fill',
    ]);

    expect($tree['type'])->toBe('firstlight.activity-indicator')
        ->and($tree['props'])->toBe([
            'a11y_label' => 'Loading patient details',
            'size' => 'md',
        ])
        ->and($tree['layout']['width'])->toBe('fill')
        ->and($tree['style'] ?? [])->toBe([])
        ->and($tree['props'])->not->toHaveKeys(['on_change', 'on_submit', 'on_press']);
});

it('accepts every stable size', function (string $size) {
    expect(collectActivityIndicator([
        'size' => $size,
        'a11y-label' => 'Loading patient details',
    ])['props']['size'])->toBe($size);
})->with(['sm', 'md', 'lg']);

it('accepts the camel case accessibility alias and preserves authored text', function () {
    expect(collectActivityIndicator([
        'a11yLabel' => '  Loading patient details  ',
    ])['props']['a11y_label'])->toBe('  Loading patient details  ');
});

it('supports the same strict contract through the fluent authoring API', function () {
    $indicator = ActivityIndicator::make()
        ->size('lg')
        ->a11yLabel('Loading patient details');

    expect($indicator->getResolvedProps(new CallbackRegistry))->toBe([
        'a11y_label' => 'Loading patient details',
        'size' => 'lg',
    ]);
});

it('requires a non-empty explicit accessibility label', function (array $attributes) {
    collectActivityIndicator($attributes);
})->with([
    'missing label' => [[]],
    'empty label' => [['a11y-label' => '']],
    'whitespace label' => [['a11y-label' => " \n\t "]],
    'null label' => [['a11y-label' => null]],
    'integer label' => [['a11y-label' => 42]],
    'boolean label' => [['a11y-label' => true]],
    'array label' => [['a11y-label' => ['Loading']]],
])->throws(InvalidArgumentException::class, 'non-empty `a11y-label`');

it('rejects invalid authored sizes instead of coercing or expanding the contract', function (mixed $size) {
    collectActivityIndicator([
        'size' => $size,
        'a11y-label' => 'Loading patient details',
    ]);
})->with([
    'small alias' => ['small'],
    'large alias' => ['large'],
    'extra small' => ['xs'],
    'extra large' => ['xl'],
    'integer' => [1],
    'null' => [null],
    'boolean' => [true],
    'array' => [['md']],
])->throws(InvalidArgumentException::class, 'one of: sm, md, lg');

it('rejects unsupported content state events styling and accessibility props', function (string $attribute, mixed $value) {
    collectActivityIndicator([
        'a11y-label' => 'Loading patient details',
        $attribute => $value,
    ]);
})->with([
    ['label', 'Loading patient details'],
    ['a11y-hint', 'Please wait'],
    ['a11yHint', 'Please wait'],
    ['value', 0.4],
    ['loading', true],
    ['active', true],
    ['visible', true],
    ['disabled', true],
    ['color', '#22C55E'],
    ['tone', 'success'],
    ['variant', 'circular'],
    ['sync-mode', 'live'],
    ['syncMode', 'live'],
    ['_change', 'changed'],
    ['_submit', 'submitted'],
    ['_press', 'pressed'],
])->throws(InvalidArgumentException::class, 'does not support');

it('precompiles only the self-closing public tag through the NativePHP Blade seam', function () {
    NativeTagPrecompiler::setActive(true);

    $compiled = (new FirstlightTagPrecompiler)(
        '<firstlight:activity-indicator size="lg" a11y-label="Loading patient details" />'
    );
    $paired = (new FirstlightTagPrecompiler)(
        '<firstlight:activity-indicator a11y-label="Loading">Please wait</firstlight:activity-indicator>'
    );

    expect($compiled)
        ->toContain('<x-native-firstlight-activity-indicator')
        ->toContain('size="lg"')
        ->toContain('a11y-label="Loading patient details"')
        ->not->toContain('<firstlight:activity-indicator')
        ->and($paired)->toBe(
            '<firstlight:activity-indicator a11y-label="Loading">Please wait</firstlight:activity-indicator>'
        );
});
