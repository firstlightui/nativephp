<?php

use FirstlightUI\Elements\Progress;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.progress', Progress::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectProgress(array $attributes): array
{
    NativeElementCollector::leaf('firstlight.progress', $attributes);

    return NativeElementCollector::collect()->toArray(new CallbackRegistry);
}

it('publishes determinate progress through the official primitive shape', function () {
    $tree = collectProgress([
        'value' => 0.4,
        'a11y-label' => 'Uploading documents',
        'width' => 'fill',
    ]);

    expect($tree['type'])->toBe('firstlight.progress')
        ->and($tree['props'])->toBe([
            'a11y_label' => 'Uploading documents',
            'value' => 0.4,
            'indeterminate' => false,
        ])
        ->and($tree['layout']['width'])->toBe('fill')
        ->and($tree['style'] ?? [])->toBe([])
        ->and($tree['props'])->not->toHaveKeys(['on_change', 'on_press']);
});

it('defaults an omitted or null value to explicit indeterminate progress', function (array $attributes) {
    expect(collectProgress($attributes)['props'])->toBe([
        'a11y_label' => 'Preparing documents',
        'indeterminate' => true,
    ]);
})->with([
    'omitted value' => [['a11y-label' => 'Preparing documents']],
    'null value' => [['value' => null, 'a11y-label' => 'Preparing documents']],
    'explicit mode' => [['indeterminate' => true, 'a11y-label' => 'Preparing documents']],
]);

it('keeps zero distinct from indeterminate progress and normalises integers', function (int $value, float $expected) {
    expect(collectProgress([
        'value' => $value,
        'a11y-label' => 'Uploading documents',
    ])['props'])->toBe([
        'a11y_label' => 'Uploading documents',
        'value' => $expected,
        'indeterminate' => false,
    ]);
})->with([
    'not started' => [0, 0.0],
    'complete' => [1, 1.0],
]);

it('accepts every finite boundary and intermediate value', function (float $value) {
    expect(collectProgress([
        'value' => $value,
        'a11y-label' => 'Uploading documents',
    ])['props']['value'])->toBe($value);
})->with([0.0, 0.25, 0.999, 1.0]);

it('rejects invalid authored progress values instead of relying on native clamping', function (mixed $value) {
    collectProgress([
        'value' => $value,
        'a11y-label' => 'Uploading documents',
    ]);
})->with([
    'negative' => [-0.01],
    'above one' => [1.01],
    'numeric string' => ['0.4'],
    'boolean' => [true],
    'array' => [[0.4]],
    'NaN' => [NAN],
    'positive infinity' => [INF],
    'negative infinity' => [-INF],
])->throws(InvalidArgumentException::class, 'finite number from 0.0 through 1.0');

it('requires a non-empty explicit accessibility label', function (array $attributes) {
    collectProgress($attributes);
})->with([
    'missing label' => [[]],
    'empty label' => [['a11y-label' => '']],
    'whitespace label' => [['a11y-label' => " \n\t "]],
    'null label' => [['a11y-label' => null]],
    'non-string label' => [['a11y-label' => 42]],
])->throws(InvalidArgumentException::class, 'non-empty `a11y-label`');

it('requires a real boolean and rejects contradictory mode declarations', function (array $attributes, string $message) {
    try {
        collectProgress(['a11y-label' => 'Uploading documents', ...$attributes]);
    } catch (InvalidArgumentException $exception) {
        expect($exception->getMessage())->toContain($message);

        return;
    }

    test()->fail('Expected Progress mode validation to fail.');
})->with([
    'string boolean' => [['indeterminate' => 'true'], 'must be a boolean'],
    'integer boolean' => [['indeterminate' => 1], 'must be a boolean'],
    'value with indeterminate mode' => [['value' => 0.4, 'indeterminate' => true], 'cannot combine'],
    'determinate without value' => [['indeterminate' => false], 'requires a non-null `value`'],
    'null determinate value' => [['value' => null, 'indeterminate' => false], 'requires a non-null `value`'],
]);

it('rejects unsupported events state styling and ignored accessibility props', function (string $attribute, mixed $value) {
    collectProgress([
        'a11y-label' => 'Uploading documents',
        $attribute => $value,
    ]);
})->with([
    ['label', 'Uploading documents'],
    ['a11y-hint', 'You can continue working'],
    ['color', '#22C55E'],
    ['track-color', '#DCFCE7'],
    ['tone', 'success'],
    ['variant', 'circular'],
    ['size', 'lg'],
    ['disabled', true],
    ['loading', true],
    ['helper', 'Four files remain'],
    ['error', 'Upload failed'],
    ['required', true],
    ['sync-mode', 'live'],
    ['_change', 'changed'],
    ['_press', 'pressed'],
    ['icon', 'arrow-up'],
])->throws(InvalidArgumentException::class, 'does not support');

it('precompiles the self-closing public tag through the NativePHP Blade seam', function () {
    NativeTagPrecompiler::setActive(true);

    $compiled = (new FirstlightTagPrecompiler)(
        '<firstlight:progress :value="$progress" a11y-label="Uploading documents" />'
    );

    expect($compiled)
        ->toContain('<x-native-firstlight-progress')
        ->toContain(':value="$progress"')
        ->toContain('a11y-label="Uploading documents"')
        ->not->toContain('<firstlight:progress');
});

it('declares an exact nativephp mobile ui adapter mapping', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['components'])->toContain([
        'type' => 'firstlight.progress',
        'element' => 'FirstlightUI\\Elements\\Progress',
        'blade' => 'FirstlightUI\\Components\\Progress',
        'android_renderer' => 'com.nativephp.plugins.native_ui.ui.ProgressBarRenderer',
        'ios_renderer' => 'NativeUIProgressBarRenderer',
        'self_closing' => true,
        'adapter' => [
            'package' => 'nativephp/mobile-ui',
            'type' => 'progress_bar',
        ],
    ]);
});
