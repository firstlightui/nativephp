<?php

use FirstlightUI\Elements\Slider;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.slider', Slider::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectFirstlightSlider(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.slider', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

function sliderAttributes(array $overrides = []): array
{
    return [
        'value' => 5,
        'min' => 0,
        'max' => 10,
        'label' => 'Dose',
        ...$overrides,
    ];
}

it('publishes required renderer floats and a validated default step grid', function () {
    $tree = collectFirstlightSlider(sliderAttributes());

    expect($tree['type'])->toBe('firstlight.slider')
        ->and($tree['props'])->toMatchArray([
            'value' => 5.0,
            'min' => 0.0,
            'max' => 10.0,
            'step' => 1.0,
            'interval_count' => 10,
            'label' => 'Dose',
            'helper' => '',
            'error' => '',
            'disabled' => false,
            'sync_mode' => 'live',
            'debounce_ms' => 300,
        ]);
});

it('publishes fractional and negative grids without coercing the authored value', function () {
    $props = collectFirstlightSlider(sliderAttributes([
        'value' => 0.25,
        'min' => -1.5,
        'max' => 1.5,
        'step' => 0.25,
    ]))['props'];

    expect($props)->toMatchArray([
        'value' => 0.25,
        'min' => -1.5,
        'max' => 1.5,
        'step' => 0.25,
        'interval_count' => 12,
    ]);
});

it('requires explicitly authored value min and max props', function (array $attributes, string $missing) {
    expect(fn () => collectFirstlightSlider($attributes))
        ->toThrow(InvalidArgumentException::class, "requires `{$missing}`");
})->with([
    'value' => [['min' => 0, 'max' => 10, 'label' => 'Dose'], 'value'],
    'min' => [['value' => 5, 'max' => 10, 'label' => 'Dose'], 'min'],
    'max' => [['value' => 5, 'min' => 0, 'label' => 'Dose'], 'max'],
]);

it('rejects non numeric non finite or native Float overflowing range props', function (string $attribute, mixed $value) {
    collectFirstlightSlider(sliderAttributes([$attribute => $value]));
})->with([
    ['value', '5'], ['value', true], ['value', null], ['value', NAN], ['value', INF],
    ['min', '0'], ['min', false], ['min', null], ['min', -INF],
    ['max', '10'], ['max', true], ['max', null], ['max', PHP_FLOAT_MAX],
    ['step', '1'], ['step', false], ['step', null], ['step', NAN],
])->throws(InvalidArgumentException::class, 'finite int or float within the native Float range');

it('requires an ordered nondegenerate range', function (int|float $min, int|float $max) {
    collectFirstlightSlider(sliderAttributes(['min' => $min, 'max' => $max]));
})->with([
    'equal' => [5, 5],
    'reversed' => [10, 0],
])->throws(InvalidArgumentException::class, '`min` must be less than `max`');

it('requires a finite positive step', function (int|float $step) {
    collectFirstlightSlider(sliderAttributes(['step' => $step]));
})->with([0, -1, -0.25])->throws(InvalidArgumentException::class, '`step` must be greater than zero');

it('requires the range and accepted value to lie on the step grid', function (array $overrides, string $message) {
    expect(fn () => collectFirstlightSlider(sliderAttributes($overrides)))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'range' => [['value' => 0.6, 'min' => 0, 'max' => 1, 'step' => 0.3], 'Slider range must lie on the step grid'],
    'value' => [['value' => 0.31, 'min' => 0, 'max' => 1, 'step' => 0.1], 'Slider `value` must lie on the step grid'],
]);

it('accepts binary floating noise within the documented grid epsilon', function () {
    expect(collectFirstlightSlider(sliderAttributes([
        'value' => 0.1 + 0.2,
        'min' => 0.0,
        'max' => 1.0,
        'step' => 0.1,
    ]))['props']['interval_count'])->toBe(10);
});

it('requires the accepted value to remain inside inclusive bounds', function (int|float $value, string $message) {
    expect(fn () => collectFirstlightSlider(sliderAttributes(['value' => $value])))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'below' => [-1, 'must not be below `min`'],
    'above' => [11, 'must not be above `max`'],
]);

it('rejects grids larger than the native Material interval count', function () {
    collectFirstlightSlider(sliderAttributes([
        'value' => 0,
        'min' => 0,
        'max' => 2_147_483_648,
        'step' => 1,
    ]));
})->throws(InvalidArgumentException::class, 'exceeds the native interval limit');

it('publishes field and accessibility metadata without a visible value formatter', function () {
    $props = collectFirstlightSlider(sliderAttributes([
        'helper' => 'Choose a whole milligram dose.',
        'error' => 'Dose is outside policy.',
        'disabled' => true,
        'a11y-label' => 'Medication dose',
        'a11y-hint' => 'Swipe vertically to adjust',
        'a11y-value' => '5 milligrams',
    ]))['props'];

    expect($props)->toMatchArray([
        'label' => 'Dose',
        'helper' => 'Choose a whole milligram dose.',
        'error' => 'Dose is outside policy.',
        'disabled' => true,
        'a11y_label' => 'Medication dose',
        'a11y_hint' => 'Swipe vertically to adjust',
        'a11y_value' => '5 milligrams',
    ])->not->toHaveKeys(['value_label', 'formatter', 'show_value']);
});

it('requires strict metadata strings and disabled booleans', function (string $attribute, mixed $value, string $message) {
    expect(fn () => collectFirstlightSlider(sliderAttributes([$attribute => $value])))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    ['label', 5, '`label` must be a string'],
    ['helper', true, '`helper` must be a string'],
    ['error', [], '`error` must be a string'],
    ['a11y-label', 5, '`a11y-label` must be a string'],
    ['a11y-hint', false, '`a11y-hint` must be a string'],
    ['a11y-value', 5, '`a11y-value` must be a string'],
    ['disabled', 1, '`disabled` must be a boolean'],
    ['disabled', 'false', '`disabled` must be a boolean'],
]);

it('registers one standard float change callback', function () {
    $registry = new CallbackRegistry;
    $tree = collectFirstlightSlider(sliderAttributes(['_change' => 'doseChanged']), $registry);

    expect($tree['props']['on_change'])->toBeInt()
        ->and($registry->resolve($tree['props']['on_change']))->toBe([
            'method' => 'doseChanged',
            'args' => [],
        ]);
});

it('publishes live blur and debounce synchronization policies', function (array $attributes, array $expected) {
    expect(collectFirstlightSlider(sliderAttributes($attributes))['props'])->toMatchArray($expected);
})->with([
    'live default' => [[], ['sync_mode' => 'live', 'debounce_ms' => 300]],
    'live explicit' => [['sync-mode' => 'live'], ['sync_mode' => 'live']],
    'blur' => [['sync-mode' => 'blur'], ['sync_mode' => 'blur']],
    'debounce' => [['sync-mode' => 'debounce', 'debounce-ms' => 500], ['sync_mode' => 'debounce', 'debounce_ms' => 500]],
]);

it('rejects unsupported sync policies and invalid debounce configuration', function (array $attributes, string $message) {
    expect(fn () => collectFirstlightSlider(sliderAttributes($attributes)))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'lazy' => [['sync-mode' => 'lazy'], 'sync-mode must be one of [live, blur, debounce]'],
    'unknown' => [['sync-mode' => 'manual'], 'sync-mode must be one of [live, blur, debounce]'],
    'short debounce' => [['sync-mode' => 'debounce', 'debounce-ms' => 20], 'at least 50 milliseconds'],
    'debounce without mode' => [['debounce-ms' => 500], 'debounce-ms requires sync-mode debounce'],
]);

it('warns in development when visible and explicit labels are blank', function () {
    set_error_handler(function (int $severity, string $message): bool {
        expect($severity)->toBe(E_USER_WARNING)
            ->and($message)->toContain('requires a visible `label` or `a11y-label`');

        return true;
    });

    try {
        collectFirstlightSlider(sliderAttributes(['label' => '']));
    } finally {
        restore_error_handler();
    }
});

it('rejects alternate events visible value formatting and visual escape props', function (string $attribute, mixed $value) {
    collectFirstlightSlider(sliderAttributes([$attribute => $value]));
})->with([
    ['_input', 'changing'], ['_press', 'pressed'], ['_submit', 'submitted'], ['_click', 'clicked'],
    ['orientation', 'vertical'], ['range', true], ['marks', [0, 5, 10]], ['ticks', true],
    ['show-value', true], ['value-label', '5 mg'], ['formatter', 'dose'],
    ['min-label', 'Low'], ['max-label', 'High'], ['required', true],
    ['color', '#ff0000'], ['track-color', '#ff0000'], ['thumb-color', '#ff0000'],
    ['style', 'compact'], ['variant', 'soft'], ['size', 'lg'],
])->throws(InvalidArgumentException::class, 'does not support');

it('retains external layout classes and drops component styling', function () {
    $tree = collectFirstlightSlider(sliderAttributes([
        'width' => 'fill',
        'padding' => 12,
        'background' => '#ff0000',
    ]));

    expect($tree['layout']['width'])->toBe('fill')
        ->and($tree['layout'])->not->toHaveKey('padding')
        ->and($tree['style'] ?? [])->toBe([]);
});

it('precompiles the exact self closing public tag and native model policy', function () {
    NativeTagPrecompiler::setActive(true);

    $compiled = (new FirstlightTagPrecompiler)(
        '<firstlight:slider native:model.blur="dose" :min="0" :max="10" label="Dose" />'
    );

    expect($compiled)->toContain('<x-native-firstlight-slider')
        ->toContain(':value="$dose"')
        ->toContain('_change="__syncProperty(\'dose\')"')
        ->toContain('sync-mode="blur"')
        ->not->toContain('<firstlight:slider');
});

it('declares collision-safe paired renderer manifest identifiers', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['components'])->toContain([
        'type' => 'firstlight.slider',
        'element' => 'FirstlightUI\\Elements\\Slider',
        'blade' => 'FirstlightUI\\Components\\Slider',
        'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.FirstlightSliderRenderer',
        'ios_renderer' => 'SliderRenderer',
        'self_closing' => true,
    ]);
});
