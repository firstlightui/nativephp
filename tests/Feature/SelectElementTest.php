<?php

use FirstlightUI\Elements\Select;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.select', Select::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

it('precompiles the public Firstlight tag through the NativePHP Blade seam', function () {
    NativeTagPrecompiler::setActive(true);

    $compiled = (new FirstlightTagPrecompiler)(
        '<firstlight:select :options="$options" native:model="priority" label="Priority" />'
    );

    expect($compiled)
        ->toContain('<x-native-firstlight-select')
        ->toContain(':options="$options"')
        ->toContain(':value="$priority"')
        ->toContain('_change="__syncProperty(\'priority\')"')
        ->toContain('sync-mode="live"')
        ->not->toContain('<firstlight:select');
});

it('declares exact paired renderer manifest identifiers', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['components'])->toContain([
        'type' => 'firstlight.select',
        'element' => 'FirstlightUI\\Elements\\Select',
        'blade' => 'FirstlightUI\\Components\\Select',
        'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.FirstlightSelectRenderer',
        'ios_renderer' => 'SelectRenderer',
        'self_closing' => true,
    ]);
});

function collectFirstlightSelect(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.select', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

it('publishes stable mapped options and complete field metadata', function () {
    $registry = new CallbackRegistry;
    $tree = collectFirstlightSelect([
        'options' => ['routine' => 'Routine', 'urgent' => 'Urgent'],
        'value' => 'routine',
        'label' => 'Priority',
        'placeholder' => 'Select a priority',
        'helper' => 'Choose one priority',
        'error' => 'Review this value',
        'required' => true,
        '_change' => 'selectPriority',
        'a11y-label' => 'Document priority',
        'a11y-hint' => 'Opens available priorities',
    ], $registry);

    expect($tree['type'])->toBe('firstlight.select')
        ->and($tree['props']['value_type'])->toBe('string')
        ->and($tree['props']['selected_values'])->toBe(['routine'])
        ->and($tree['props']['option_values'])->toBe(['routine', 'urgent'])
        ->and($tree['props']['option_labels'])->toBe(['Routine', 'Urgent'])
        ->and($tree['props']['option_enabled'])->toBe(['1', '1'])
        ->and($tree['props']['search_enabled'])->toBeFalse()
        ->and($tree['props']['label'])->toBe('Priority')
        ->and($tree['props']['placeholder'])->toBe('Select a priority')
        ->and($tree['props']['helper'])->toBe('Choose one priority')
        ->and($tree['props']['error'])->toBe('Review this value')
        ->and($tree['props']['required'])->toBeTrue()
        ->and($tree['props']['a11y_label'])->toBe('Document priority')
        ->and($tree['props']['a11y_hint'])->toBe('Opens available priorities')
        ->and($tree['props']['option_callbacks'][0])->toBe('0')
        ->and($registry->resolve((int) $tree['props']['option_callbacks'][1]))->toBe([
            'method' => 'selectPriority',
            'args' => ['urgent'],
        ]);
});

it('preserves integer values and existing callback arguments', function () {
    $registry = new CallbackRegistry;
    $tree = collectFirstlightSelect([
        'options' => [10 => 'Routine', 20 => 'Urgent'],
        'value' => 20,
        'label' => 'Priority',
        '_change' => "selectPriority('documents')",
    ], $registry);

    expect($tree['props']['value_type'])->toBe('integer')
        ->and($tree['props']['selected_values'])->toBe(['20'])
        ->and($registry->resolve((int) $tree['props']['option_callbacks'][0]))->toBe([
            'method' => 'selectPriority',
            'args' => ['documents', 10],
        ])
        ->and($tree['props']['option_callbacks'][1])->toBe('0');
});

it('uses an empty selected-value list for null and disables empty option sets', function () {
    $tree = collectFirstlightSelect([
        'options' => ['routine' => 'Routine'],
        'value' => null,
        'label' => 'Priority',
    ]);
    $empty = collectFirstlightSelect([
        'options' => [],
        'value' => null,
        'label' => 'Priority',
    ]);

    expect($tree['props']['selected_values'])->toBe([])
        ->and($tree['props']['disabled'])->toBeFalse()
        ->and($empty['props']['selected_values'])->toBe([])
        ->and($empty['props']['disabled'])->toBeTrue()
        ->and($empty['props']['option_callbacks'])->toBe([]);
});

it('publishes rich disabled options as inert choices', function () {
    $registry = new CallbackRegistry;
    $tree = collectFirstlightSelect([
        'options' => [
            ['value' => 'routine', 'label' => 'Routine'],
            ['value' => 'urgent', 'label' => 'Urgent', 'disabled' => true],
            ['value' => 'critical', 'label' => 'Critical'],
        ],
        'value' => 'routine',
        'label' => 'Priority',
        '_change' => 'selectPriority',
    ], $registry);

    expect($tree['props']['option_enabled'])->toBe(['1', '0', '1'])
        ->and($tree['props']['option_callbacks'])->toHaveCount(3)
        ->and($tree['props']['option_callbacks'][0])->toBe('0')
        ->and($tree['props']['option_callbacks'][1])->toBe('0')
        ->and($registry->resolve((int) $tree['props']['option_callbacks'][2])['args'])->toBe(['critical']);
});

it('automatically enables search at thirteen options and allows an explicit smaller search', function () {
    $twelve = collectFirstlightSelect([
        'options' => array_map(fn (int $value): string => "Option {$value}", range(1, 12)),
        'value' => null,
        'label' => 'Option',
    ]);
    $thirteen = collectFirstlightSelect([
        'options' => array_map(fn (int $value): string => "Option {$value}", range(1, 13)),
        'value' => null,
        'label' => 'Option',
    ]);
    $forced = collectFirstlightSelect([
        'options' => ['Mine', 'All'],
        'value' => null,
        'label' => 'Queue',
        'searchable' => true,
    ]);

    expect($twelve['props']['search_enabled'])->toBeFalse()
        ->and($thirteen['props']['search_enabled'])->toBeTrue()
        ->and($forced['props']['search_enabled'])->toBeTrue();
});

it('requires every non-null value to match one option exactly', function (array $attributes, string $message) {
    expect(fn () => collectFirstlightSelect([
        'options' => ['routine' => 'Routine', 'urgent' => 'Urgent'],
        'label' => 'Priority',
        ...$attributes,
    ]))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'unknown value' => [['value' => 'missing'], 'must match an option exactly'],
    'type mismatch' => [['options' => [10 => 'Routine'], 'value' => '10'], 'must match an option exactly'],
    'array value' => [['value' => ['routine']], 'string, integer, or null'],
    'boolean value' => [['value' => true], 'string, integer, or null'],
    'non-null value with empty options' => [['options' => [], 'value' => 'routine'], 'must match an option exactly'],
]);

it('accepts immediate sync and rejects deferred sync modes', function () {
    $tree = collectFirstlightSelect([
        'options' => ['Routine', 'Urgent'],
        'value' => 'Routine',
        'label' => 'Priority',
        'sync-mode' => 'live',
    ]);

    expect($tree['props'])->not->toHaveKey('sync_mode');

    foreach (['blur', 'debounce'] as $mode) {
        expect(fn () => collectFirstlightSelect([
            'options' => ['Routine', 'Urgent'],
            'value' => 'Routine',
            'label' => 'Priority',
            'sync-mode' => $mode,
        ]))->toThrow(InvalidArgumentException::class, 'Use plain `native:model`');
    }
});

it('rejects unrelated selection and presentation props', function (string $attribute, mixed $value) {
    collectFirstlightSelect([
        'options' => ['Routine', 'Urgent'],
        'value' => 'Routine',
        'label' => 'Priority',
        $attribute => $value,
    ]);
})->with([
    ['mode', 'menu'],
    ['style', 'compact'],
    ['multiple', true],
    ['clearable', true],
    ['variant', 'outlined'],
    ['tone', 'info'],
    ['_press', 'pressed'],
    ['_submit', 'submitted'],
])->throws(InvalidArgumentException::class, 'not supported');

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
        collectFirstlightSelect([
            'options' => ['Routine', 'Urgent'],
            'value' => null,
            'label' => ' ',
            'a11y-label' => '',
        ]);
    } finally {
        restore_error_handler();
    }

    expect($warnings)->toBe([
        'Firstlight Select requires a visible label or a11y-label.',
    ]);
});
