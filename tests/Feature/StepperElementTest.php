<?php

use FirstlightUI\Elements\Stepper;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.stepper', Stepper::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectFirstlightStepper(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.stepper', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

function stepperAttributes(array $overrides = []): array
{
    return [
        'value' => 5,
        'min' => 0,
        'max' => 10,
        'label' => 'Quantity',
        ...$overrides,
    ];
}

it('publishes exact integer semantics and bounded neighbouring values', function () {
    $tree = collectFirstlightStepper(stepperAttributes());

    expect($tree['type'])->toBe('firstlight.stepper')
        ->and($tree['props'])->toMatchArray([
            'value' => 5,
            'min' => 0,
            'max' => 10,
            'step' => 1,
            'display_value' => '5',
            'number_kind' => 'integer',
            'can_decrement' => true,
            'can_increment' => true,
            'decrement_value' => 4,
            'increment_value' => 6,
            'label' => 'Quantity',
            'helper' => '',
            'error' => '',
            'disabled' => false,
        ]);
});

it('preserves fractional semantics and exact precomputed callback payloads', function () {
    $registry = new CallbackRegistry;
    $props = collectFirstlightStepper(stepperAttributes([
        'value' => 0.5,
        'min' => 0.0,
        'max' => 1.0,
        'step' => 0.25,
        '_change' => "doseChanged('line-item')",
    ]), $registry)['props'];

    expect($props)->toMatchArray([
        'value' => 0.5,
        'min' => 0.0,
        'max' => 1.0,
        'step' => 0.25,
        'display_value' => '0.5',
        'number_kind' => 'float',
        'decrement_value' => 0.25,
        'increment_value' => 0.75,
    ])->and($registry->resolve($props['on_decrement']))->toBe([
        'method' => 'doseChanged',
        'args' => ['line-item', 0.25],
    ])->and($registry->resolve($props['on_increment']))->toBe([
        'method' => 'doseChanged',
        'args' => ['line-item', 0.75],
    ]);
});

it('keeps authored integral floats on the float wire contract', function () {
    $props = collectFirstlightStepper(stepperAttributes([
        'value' => 5.0,
        'min' => 0.0,
        'max' => 10.0,
        'step' => 1.0,
    ]))['props'];

    expect($props['value'])->toBeFloat()->toBe(5.0)
        ->and($props['min'])->toBeFloat()->toBe(0.0)
        ->and($props['max'])->toBeFloat()->toBe(10.0)
        ->and($props['step'])->toBeFloat()->toBe(1.0)
        ->and($props['decrement_value'])->toBeFloat()->toBe(4.0)
        ->and($props['increment_value'])->toBeFloat()->toBe(6.0)
        ->and($props['number_kind'])->toBe('float');
});

it('preserves exact integer proposals beyond binary float precision', function () {
    $registry = new CallbackRegistry;
    $props = collectFirstlightStepper(stepperAttributes([
        'value' => 9_007_199_254_740_994,
        'min' => 9_007_199_254_740_993,
        'max' => 9_007_199_254_740_995,
        '_change' => 'quantityChanged',
    ]), $registry)['props'];

    expect($props)->toMatchArray([
        'value' => 9_007_199_254_740_994,
        'min' => 9_007_199_254_740_993,
        'max' => 9_007_199_254_740_995,
        'decrement_value' => 9_007_199_254_740_993,
        'increment_value' => 9_007_199_254_740_995,
    ])->and($registry->resolve($props['on_decrement'])['args'])->toBe([
        9_007_199_254_740_993,
    ])->and($registry->resolve($props['on_increment'])['args'])->toBe([
        9_007_199_254_740_995,
    ]);
});

it('preserves exact signed integer grids without overflowing range subtraction', function () {
    $props = collectFirstlightStepper(stepperAttributes([
        'value' => -1,
        'min' => PHP_INT_MIN,
        'max' => -1,
        'step' => PHP_INT_MAX,
    ]))['props'];

    expect($props)->toMatchArray([
        'value' => -1,
        'min' => PHP_INT_MIN,
        'max' => -1,
        'step' => PHP_INT_MAX,
        'can_decrement' => true,
        'decrement_value' => PHP_INT_MIN,
        'can_increment' => false,
        'increment_value' => -1,
    ]);
});

it('uses float semantics when any authored number is a float', function () {
    $props = collectFirstlightStepper(stepperAttributes(['step' => 1.0]))['props'];

    expect($props['value'])->toBeFloat()
        ->and($props['min'])->toBeFloat()
        ->and($props['max'])->toBeFloat()
        ->and($props['number_kind'])->toBe('float');
});

it('publishes inert bounded proposals at inclusive endpoints', function () {
    $minimum = collectFirstlightStepper(stepperAttributes(['value' => 0]))['props'];
    $maximum = collectFirstlightStepper(stepperAttributes(['value' => 10]))['props'];

    expect($minimum)->toMatchArray([
        'can_decrement' => false,
        'decrement_value' => 0,
        'can_increment' => true,
        'increment_value' => 1,
    ])->and($maximum)->toMatchArray([
        'can_decrement' => true,
        'decrement_value' => 9,
        'can_increment' => false,
        'increment_value' => 10,
    ]);
});

it('appends exact values to plain and native model callbacks', function () {
    $plainRegistry = new CallbackRegistry;
    $plain = collectFirstlightStepper(stepperAttributes(['_change' => 'quantityChanged']), $plainRegistry)['props'];
    $modelRegistry = new CallbackRegistry;
    $model = collectFirstlightStepper(stepperAttributes([
        '_change' => "__syncProperty('quantity')",
    ]), $modelRegistry)['props'];

    expect($plainRegistry->resolve($plain['on_decrement']))->toBe([
        'method' => 'quantityChanged',
        'args' => [4],
    ])->and($plainRegistry->resolve($plain['on_increment']))->toBe([
        'method' => 'quantityChanged',
        'args' => [6],
    ])->and($modelRegistry->resolve($model['on_decrement']))->toBe([
        'method' => '__syncProperty',
        'args' => ['quantity', 4],
    ])->and($modelRegistry->resolve($model['on_increment']))->toBe([
        'method' => '__syncProperty',
        'args' => ['quantity', 6],
    ]);
});

it('requires explicitly authored value min and max props', function (array $attributes, string $missing) {
    expect(fn () => collectFirstlightStepper($attributes))
        ->toThrow(InvalidArgumentException::class, "requires `{$missing}`");
})->with([
    'value' => [['min' => 0, 'max' => 10, 'label' => 'Quantity'], 'value'],
    'min' => [['value' => 5, 'max' => 10, 'label' => 'Quantity'], 'min'],
    'max' => [['value' => 5, 'min' => 0, 'label' => 'Quantity'], 'max'],
]);

it('rejects non numeric non finite or native Float overflowing numbers', function (string $attribute, mixed $value) {
    collectFirstlightStepper(stepperAttributes([$attribute => $value]));
})->with([
    ['value', '5'], ['value', true], ['value', null], ['value', NAN], ['value', INF],
    ['min', '0'], ['min', false], ['min', null], ['min', -INF],
    ['max', '10'], ['max', true], ['max', null], ['max', PHP_FLOAT_MAX],
    ['step', '1'], ['step', false], ['step', null], ['step', NAN],
])->throws(InvalidArgumentException::class, 'finite int or float within the native Float range');

it('requires an ordered nondegenerate range', function (int|float $min, int|float $max) {
    collectFirstlightStepper(stepperAttributes(['min' => $min, 'max' => $max]));
})->with([
    'equal' => [5, 5],
    'reversed' => [10, 0],
])->throws(InvalidArgumentException::class, '`min` must be less than `max`');

it('requires a positive step', function (int|float $step) {
    collectFirstlightStepper(stepperAttributes(['step' => $step]));
})->with([0, -1, -0.25])->throws(InvalidArgumentException::class, '`step` must be greater than zero');

it('requires the range and value to lie on the minimum based step grid', function (array $overrides, string $message) {
    expect(fn () => collectFirstlightStepper(stepperAttributes($overrides)))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'range' => [['value' => 0.6, 'min' => 0, 'max' => 1, 'step' => 0.3], 'Stepper range must lie on the step grid'],
    'value' => [['value' => 0.31, 'min' => 0, 'max' => 1, 'step' => 0.1], 'Stepper `value` must lie on the step grid'],
]);

it('accepts binary floating noise within the shared grid epsilon', function () {
    $props = collectFirstlightStepper(stepperAttributes([
        'value' => 0.1 + 0.2,
        'min' => 0.0,
        'max' => 1.0,
        'step' => 0.1,
    ]))['props'];

    expect($props['decrement_value'])->toBe(0.2)
        ->and($props['increment_value'])->toBe(0.4);
});

it('requires the value inside inclusive bounds', function (int|float $value, string $message) {
    expect(fn () => collectFirstlightStepper(stepperAttributes(['value' => $value])))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'below' => [-1, 'must not be below `min`'],
    'above' => [11, 'must not be above `max`'],
]);

it('rejects grids larger than the shared native interval limit', function () {
    collectFirstlightStepper(stepperAttributes([
        'value' => 0,
        'min' => 0,
        'max' => 2_147_483_648,
        'step' => 1,
    ]));
})->throws(InvalidArgumentException::class, 'exceeds the native interval limit');

it('publishes field accessibility and disabled metadata', function () {
    $props = collectFirstlightStepper(stepperAttributes([
        'helper' => 'Adjust one item at a time.',
        'error' => 'Quantity is unavailable.',
        'disabled' => true,
        'a11y-label' => 'Medication quantity',
        'a11y-hint' => 'Use decrease or increase',
    ]))['props'];

    expect($props)->toMatchArray([
        'label' => 'Quantity',
        'helper' => 'Adjust one item at a time.',
        'error' => 'Quantity is unavailable.',
        'disabled' => true,
        'a11y_label' => 'Medication quantity',
        'a11y_hint' => 'Use decrease or increase',
    ])->not->toHaveKeys(['formatter', 'value_label', 'a11y_value']);
});

it('requires strict metadata strings and disabled booleans', function (string $attribute, mixed $value, string $message) {
    expect(fn () => collectFirstlightStepper(stepperAttributes([$attribute => $value])))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    ['label', 5, '`label` must be a string'],
    ['helper', true, '`helper` must be a string'],
    ['error', [], '`error` must be a string'],
    ['a11y-label', 5, '`a11y-label` must be a string'],
    ['a11y-hint', false, '`a11y-hint` must be a string'],
    ['disabled', 1, '`disabled` must be a boolean'],
    ['disabled', 'false', '`disabled` must be a boolean'],
]);

it('accepts immediate live synchronization and rejects deferred modes', function () {
    expect(collectFirstlightStepper(stepperAttributes(['sync-mode' => 'live']))['props'])
        ->not->toHaveKey('sync_mode');

    foreach (['blur', 'debounce', 'input'] as $mode) {
        expect(fn () => collectFirstlightStepper(stepperAttributes(['sync-mode' => $mode])))
            ->toThrow(InvalidArgumentException::class, 'only native:model or native:model.live');
    }
});

it('warns in development when visible and accessibility labels are blank', function () {
    $warnings = [];
    set_error_handler(function (int $severity, string $message) use (&$warnings): bool {
        if ($severity === E_USER_WARNING) {
            $warnings[] = $message;

            return true;
        }

        return false;
    });

    try {
        collectFirstlightStepper(stepperAttributes(['label' => ' ', 'a11y-label' => '']));
    } finally {
        restore_error_handler();
    }

    expect($warnings)->toBe([
        'Firstlight Stepper requires a visible `label` or `a11y-label`.',
    ]);
});

it('rejects alternate events formatting and visual escape props', function (string $attribute, mixed $value) {
    collectFirstlightStepper(stepperAttributes([$attribute => $value]));
})->with([
    ['_input', 'changing'], ['_press', 'pressed'], ['_submit', 'submitted'], ['_click', 'clicked'],
    ['increment-icon', 'plus-circle'], ['decrement-icon', 'minus-circle'], ['formatter', 'quantity'],
    ['value-label', 'Five'], ['show-min', true], ['show-max', true], ['min-label', 'None'],
    ['max-label', 'Many'], ['wraparound', true], ['orientation', 'vertical'],
    ['acceleration', true], ['long-press', true], ['color', '#ff0000'],
    ['background', '#ff0000'], ['style', 'compact'], ['variant', 'soft'], ['size', 'lg'],
    ['tone', 'info'], ['a11y-value', 'Five'],
])->throws(InvalidArgumentException::class, 'does not support');

it('retains external layout classes and drops component styling', function () {
    $tree = collectFirstlightStepper(stepperAttributes([
        'width' => 'fill',
        'padding' => 12,
        'class' => 'mt-4',
    ]));

    expect($tree['layout']['width'])->toBe('fill')
        ->and($tree['layout'])->not->toHaveKey('padding')
        ->and($tree['style'] ?? [])->toBe([]);
});

it('precompiles the exact self closing public tag and immediate native model policy', function () {
    NativeTagPrecompiler::setActive(true);

    $compiled = (new FirstlightTagPrecompiler)(
        '<firstlight:stepper native:model="quantity" :min="0" :max="10" label="Quantity" />'
    );

    expect($compiled)->toContain('<x-native-firstlight-stepper')
        ->toContain(':value="$quantity"')
        ->toContain('_change="__syncProperty(\'quantity\')"')
        ->toContain('sync-mode="live"')
        ->not->toContain('<firstlight:stepper');
});

it('declares paired renderer manifest identifiers', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['components'])->toContain([
        'type' => 'firstlight.stepper',
        'element' => 'FirstlightUI\\Elements\\Stepper',
        'blade' => 'FirstlightUI\\Components\\Stepper',
        'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.StepperRenderer',
        'ios_renderer' => 'StepperRenderer',
        'self_closing' => true,
    ]);
});
