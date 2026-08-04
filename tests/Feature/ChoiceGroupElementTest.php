<?php

use FirstlightUI\Elements\ChoiceGroup;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.choice-group', ChoiceGroup::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectChoiceGroup(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.choice-group', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

it('publishes single-choice rows with radio semantics and stable values', function () {
    $registry = new CallbackRegistry;
    $tree = collectChoiceGroup([
        'options' => ['routine' => 'Routine', 'urgent' => 'Urgent'],
        'value' => 'routine',
        'label' => 'Priority',
        'helper' => 'Choose one priority',
        'required' => true,
        '_change' => 'selectPriority',
        'a11y-label' => 'Document priority',
        'a11y-hint' => 'Sets the processing priority',
    ], $registry);

    expect($tree['type'])->toBe('firstlight.choice-group')
        ->and($tree['props']['multiple'])->toBeFalse()
        ->and($tree['props']['value_type'])->toBe('string')
        ->and($tree['props']['selected_values'])->toBe(['routine'])
        ->and($tree['props']['option_values'])->toBe(['routine', 'urgent'])
        ->and($tree['props']['option_labels'])->toBe(['Routine', 'Urgent'])
        ->and($tree['props']['option_enabled'])->toBe(['1', '1'])
        ->and($tree['props']['label'])->toBe('Priority')
        ->and($tree['props']['helper'])->toBe('Choose one priority')
        ->and($tree['props']['required'])->toBeTrue()
        ->and($tree['props']['a11y_label'])->toBe('Document priority')
        ->and($tree['props']['a11y_hint'])->toBe('Sets the processing priority')
        ->and($tree['props']['option_callbacks'])->toHaveCount(2)
        ->and($tree['props']['option_callbacks'][0])->toBe('0')
        ->and($registry->resolve((int) $tree['props']['option_callbacks'][1]))->toBe([
            'method' => 'selectPriority',
            'args' => ['urgent'],
        ]);
});

it('publishes integer proposals without changing existing callback arguments', function () {
    $registry = new CallbackRegistry;
    $tree = collectChoiceGroup([
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

it('publishes multiple checkbox proposals while preserving order and unknown values', function () {
    $owner = "O'Connor, 東京";
    $registry = new CallbackRegistry;
    $tree = collectChoiceGroup([
        'options' => [
            ['value' => 'mine', 'label' => 'Mine'],
            ['value' => $owner, 'label' => 'Owner'],
            ['value' => 'all', 'label' => 'All'],
        ],
        'value' => ['missing', 'mine', $owner],
        'multiple' => true,
        'label' => 'Queues',
        '_change' => 'selectQueues',
    ], $registry);

    expect($tree['props']['multiple'])->toBeTrue()
        ->and($tree['props']['selected_values'])->toBe(['missing', 'mine', $owner])
        ->and($registry->resolve((int) $tree['props']['option_callbacks'][0])['args'])->toBe([
            ['missing', $owner],
        ])
        ->and($registry->resolve((int) $tree['props']['option_callbacks'][1])['args'])->toBe([
            ['missing', 'mine'],
        ])
        ->and($registry->resolve((int) $tree['props']['option_callbacks'][2])['args'])->toBe([
            ['missing', 'mine', $owner, 'all'],
        ]);
});

it('treats null and an empty list as no multiple selection', function (mixed $value) {
    $registry = new CallbackRegistry;
    $tree = collectChoiceGroup([
        'options' => ['mine' => 'Mine'],
        'value' => $value,
        'multiple' => true,
        'label' => 'Queues',
        '_change' => 'selectQueues',
    ], $registry);

    expect($tree['props']['selected_values'])->toBe([])
        ->and($registry->resolve((int) $tree['props']['option_callbacks'][0])['args'])->toBe([
            ['mine'],
        ]);
})->with([
    'null' => [null],
    'empty list' => [[]],
]);

it('publishes disabled choices and empty groups as inert controls', function () {
    $registry = new CallbackRegistry;
    $tree = collectChoiceGroup([
        'options' => [
            ['value' => 'mine', 'label' => 'Mine', 'disabled' => true],
            ['value' => 'all', 'label' => 'All'],
        ],
        'value' => null,
        'label' => 'Queue',
        '_change' => 'selectQueue',
    ], $registry);

    $empty = collectChoiceGroup([
        'options' => [],
        'value' => null,
        'label' => 'Queue',
        '_change' => 'selectQueue',
    ]);

    expect($tree['props']['option_enabled'])->toBe(['0', '1'])
        ->and($tree['props']['option_callbacks'][0])->toBe('0')
        ->and($registry->resolve((int) $tree['props']['option_callbacks'][1])['args'])->toBe(['all'])
        ->and($empty['props']['disabled'])->toBeTrue()
        ->and($empty['props']['option_values'])->toBe([])
        ->and($empty['props']['option_callbacks'])->toBe([]);
});

it('publishes error and disabled field metadata as primitive props', function () {
    $tree = collectChoiceGroup([
        'options' => ['Routine', 'Urgent'],
        'value' => 'Routine',
        'label' => 'Priority',
        'helper' => 'Choose one',
        'error' => 'Priority is required',
        'required' => true,
        'disabled' => true,
    ]);

    expect($tree['props']['error'])->toBe('Priority is required')
        ->and($tree['props']['required'])->toBeTrue()
        ->and($tree['props']['disabled'])->toBeTrue()
        ->and(json_decode(json_encode($tree, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR))->toBe($tree);
});

it('rejects values that do not match the choice mode', function (array $attributes, string $message) {
    expect(fn () => collectChoiceGroup([
        'options' => ['routine' => 'Routine', 'urgent' => 'Urgent'],
        'label' => 'Priority',
        ...$attributes,
    ]))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'array in single mode' => [['value' => ['routine']], 'single-choice value'],
    'scalar in multiple mode' => [['value' => 'routine', 'multiple' => true], 'multiple-choice value'],
    'map in multiple mode' => [['value' => ['routine' => true], 'multiple' => true], 'must be a list'],
    'duplicate selected values' => [['value' => ['routine', 'routine'], 'multiple' => true], 'duplicate selected value'],
    'mixed selected values' => [['value' => ['routine', 20], 'multiple' => true], 'cannot mix string and integer'],
    'selected type mismatch' => [['value' => [10], 'multiple' => true], 'must match option value type'],
    'unsupported value' => [['value' => true], 'string, integer, array, or null'],
]);

it('accepts immediate sync mode and rejects deferred modes', function () {
    $tree = collectChoiceGroup([
        'options' => ['Routine', 'Urgent'],
        'value' => 'Routine',
        'label' => 'Priority',
        'sync-mode' => 'live',
    ]);

    expect($tree['props'])->not->toHaveKey('sync_mode');

    foreach (['blur', 'debounce'] as $mode) {
        expect(fn () => collectChoiceGroup([
            'options' => ['Routine', 'Urgent'],
            'value' => 'Routine',
            'label' => 'Priority',
            'sync-mode' => $mode,
        ]))->toThrow(InvalidArgumentException::class, 'Use plain `native:model`');
    }
});

it('rejects incompatible presentation and event props', function (string $attribute, mixed $value) {
    collectChoiceGroup([
        'options' => ['Routine', 'Urgent'],
        'value' => 'Routine',
        'label' => 'Priority',
        $attribute => $value,
    ]);
})->with([
    ['selected', true],
    ['loading', true],
    ['tone', 'info'],
    ['variant', 'filled'],
    ['icon', 'star'],
    ['orientation', 'horizontal'],
    ['_press', 'pressed'],
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
        collectChoiceGroup([
            'options' => ['Routine', 'Urgent'],
            'value' => null,
            'label' => ' ',
            'a11y-label' => '',
        ]);
    } finally {
        restore_error_handler();
    }

    expect($warnings)->toBe([
        'Firstlight Choice Group requires a visible label or a11y-label.',
    ]);
});

it('precompiles the public Firstlight tag through the NativePHP Blade seam', function () {
    NativeTagPrecompiler::setActive(true);

    $compiled = (new FirstlightTagPrecompiler)(
        '<firstlight:choice-group :options="$options" native:model="priority" label="Priority" />'
    );

    expect($compiled)
        ->toContain('<x-native-firstlight-choice-group')
        ->toContain(':options="$options"')
        ->toContain('label="Priority"');
});
