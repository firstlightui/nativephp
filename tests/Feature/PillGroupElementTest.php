<?php

use FirstlightUI\Elements\PillGroup;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.pill-group', PillGroup::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectPillGroup(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.pill-group', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

it('publishes a single-selection group with whole-value callbacks', function () {
    $registry = new CallbackRegistry;
    $tree = collectPillGroup([
        'options' => ['mine' => 'Mine', 'all' => 'All'],
        'value' => 'mine',
        'label' => 'Queue',
        'helper' => 'Choose a queue',
        'required' => true,
        '_change' => 'selectQueue',
        'a11y-label' => 'Document queue',
        'a11y-hint' => 'Filters the document list',
    ], $registry);

    expect($tree['type'])->toBe('firstlight.pill-group')
        ->and($tree['props']['multiple'])->toBeFalse()
        ->and($tree['props']['value_type'])->toBe('string')
        ->and($tree['props']['selected_values'])->toBe(['mine'])
        ->and($tree['props']['option_values'])->toBe(['mine', 'all'])
        ->and($tree['props']['option_labels'])->toBe(['Mine', 'All'])
        ->and($tree['props']['option_enabled'])->toBe(['1', '1'])
        ->and($tree['props']['label'])->toBe('Queue')
        ->and($tree['props']['helper'])->toBe('Choose a queue')
        ->and($tree['props']['required'])->toBeTrue()
        ->and($tree['props']['a11y_label'])->toBe('Document queue')
        ->and($tree['props']['a11y_hint'])->toBe('Filters the document list')
        ->and($tree['props']['option_callbacks'])->toHaveCount(2)
        ->and($registry->resolve((int) $tree['props']['option_callbacks'][0]))->toBe([
            'method' => 'selectQueue',
            'args' => [null],
        ])
        ->and($registry->resolve((int) $tree['props']['option_callbacks'][1]))->toBe([
            'method' => 'selectQueue',
            'args' => ['all'],
        ]);
});

it('publishes integer proposals without changing existing callback arguments', function () {
    $registry = new CallbackRegistry;
    $tree = collectPillGroup([
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
        ->and($registry->resolve((int) $tree['props']['option_callbacks'][1]))->toBe([
            'method' => 'selectPriority',
            'args' => ['documents', null],
        ]);
});

it('publishes multiple-selection proposals while preserving value order and unknown values', function () {
    $owner = "O'Connor, 東京";
    $registry = new CallbackRegistry;
    $tree = collectPillGroup([
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
    $tree = collectPillGroup([
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
    $tree = collectPillGroup([
        'options' => [
            ['value' => 'mine', 'label' => 'Mine', 'disabled' => true],
            ['value' => 'all', 'label' => 'All'],
        ],
        'value' => null,
        'label' => 'Queue',
        '_change' => 'selectQueue',
    ], $registry);

    $empty = collectPillGroup([
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
    $tree = collectPillGroup([
        'options' => ['Mine', 'All'],
        'value' => 'Mine',
        'label' => 'Queue',
        'helper' => 'Choose one',
        'error' => 'Queue is required',
        'required' => true,
        'disabled' => true,
    ]);

    expect($tree['props']['error'])->toBe('Queue is required')
        ->and($tree['props']['required'])->toBeTrue()
        ->and($tree['props']['disabled'])->toBeTrue()
        ->and(json_decode(json_encode($tree, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR))->toBe($tree);
});

it('rejects values that do not match the selection mode', function (array $attributes, string $message) {
    expect(fn () => collectPillGroup([
        'options' => ['mine' => 'Mine', 'all' => 'All'],
        'label' => 'Queue',
        ...$attributes,
    ]))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'array in single mode' => [['value' => ['mine']], 'single-selection value'],
    'scalar in multiple mode' => [['value' => 'mine', 'multiple' => true], 'multiple-selection value'],
    'map in multiple mode' => [['value' => ['mine' => true], 'multiple' => true], 'must be a list'],
    'duplicate selected values' => [['value' => ['mine', 'mine'], 'multiple' => true], 'duplicate selected value'],
    'mixed selected values' => [['value' => ['mine', 20], 'multiple' => true], 'cannot mix string and integer'],
    'selected type mismatch' => [['value' => [10], 'multiple' => true], 'must match option value type'],
    'unsupported value' => [['value' => true], 'string, integer, array, or null'],
]);

it('accepts live sync mode and rejects deferred modes', function () {
    $tree = collectPillGroup([
        'options' => ['Mine', 'All'],
        'value' => 'Mine',
        'label' => 'Queue',
        'sync-mode' => 'live',
    ]);

    expect($tree['props'])->not->toHaveKey('sync_mode');

    foreach (['blur', 'debounce'] as $mode) {
        expect(fn () => collectPillGroup([
            'options' => ['Mine', 'All'],
            'value' => 'Mine',
            'label' => 'Queue',
            'sync-mode' => $mode,
        ]))->toThrow(InvalidArgumentException::class, 'Use plain `native:model`');
    }
});

it('rejects incompatible presentation and event props', function (string $attribute, mixed $value) {
    collectPillGroup([
        'options' => ['Mine', 'All'],
        'value' => 'Mine',
        'label' => 'Queue',
        $attribute => $value,
    ]);
})->with([
    ['selected', true],
    ['loading', true],
    ['tone', 'info'],
    ['variant', 'filled'],
    ['icon', 'star'],
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
        collectPillGroup([
            'options' => ['Mine', 'All'],
            'value' => null,
            'label' => ' ',
            'a11y-label' => '',
        ]);
    } finally {
        restore_error_handler();
    }

    expect($warnings)->toBe([
        'Firstlight Pill Group requires a visible label or a11y-label.',
    ]);
});

it('precompiles the public Firstlight tag through the NativePHP Blade seam', function () {
    NativeTagPrecompiler::setActive(true);

    $compiled = (new FirstlightTagPrecompiler)(
        '<firstlight:pill-group :options="$options" native:model="queues" label="Queues" multiple />'
    );

    expect($compiled)
        ->toContain('<x-native-firstlight-pill-group')
        ->toContain(':options="$options"')
        ->toContain('label="Queues"')
        ->toContain('multiple');
});
