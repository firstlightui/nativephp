<?php

use FirstlightUI\Elements\ReorderList;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.reorder-list', ReorderList::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectReorderList(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.reorder-list', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

/** The value a row's move to a destination slot proposes, or null when it emits nothing. */
function proposedMove(array $tree, CallbackRegistry $registry, int $row, int $slot): mixed
{
    $id = (int) $tree['props']['move_callbacks'][$row * $tree['props']['move_slot_count'] + $slot];

    return $id === 0 ? null : $registry->resolve($id)['args'][0];
}

function tabItems(): array
{
    return [
        ['value' => 'home', 'label' => 'Home', 'icon' => 'house', 'locked' => true],
        ['value' => 'inbox', 'label' => 'Inbox', 'icon' => 'tray', 'icon-android' => 'inbox'],
        ['value' => 'reports', 'label' => 'Reports', 'supporting' => 'Weekly'],
        ['value' => 'files', 'label' => 'Files'],
    ];
}

it('publishes rows in the bound order with their sections and metadata', function () {
    $tree = collectReorderList([
        'items' => tabItems(),
        'sections' => ['bar' => 'Tab bar', 'more' => 'More'],
        'value' => ['bar' => ['home', 'reports', 'inbox'], 'more' => ['files']],
        'label' => 'Tabs',
        'helper' => 'Up to 5 tabs.',
        '_change' => 'arrange',
    ]);

    expect($tree['type'])->toBe('firstlight.reorder-list')
        ->and($tree['props'])->toMatchArray([
            'label' => 'Tabs',
            'helper' => 'Up to 5 tabs.',
            'section_keys' => ['bar', 'more'],
            'section_labels' => ['Tab bar', 'More'],
            'section_lengths' => ['3', '1'],
            'row_values' => ['home', 'reports', 'inbox', 'files'],
            'row_labels' => ['Home', 'Reports', 'Inbox', 'Files'],
            'row_supporting' => ['', 'Weekly', '', ''],
            'row_locked' => ['1', '0', '0', '0'],
            'move_slot_count' => 5,
        ])
        ->and($tree['props']['row_icons'][3])->toBe('')
        ->and($tree['props']['move_up_label'])->toBe('Move up')
        ->and($tree['props']['move_to_labels'])->toBe(['Move to Tab bar', 'Move to More']);
});

it('proposes the complete arrangement for each move across sections', function () {
    $registry = new CallbackRegistry;
    $tree = collectReorderList([
        'items' => tabItems(),
        'sections' => ['bar' => 'Tab bar', 'more' => 'More'],
        'value' => ['bar' => ['home', 'inbox', 'reports'], 'more' => ['files']],
        '_change' => 'arrange',
    ], $registry);

    // Inbox (row 1): bar slots 0-2 (bar without Inbox has 2 items), More slots 3-4.
    expect(proposedMove($tree, $registry, 1, 0))->toBe(['bar' => ['inbox', 'home', 'reports'], 'more' => ['files']])
        ->and(proposedMove($tree, $registry, 1, 1))->toBeNull()
        ->and(proposedMove($tree, $registry, 1, 2))->toBe(['bar' => ['home', 'reports', 'inbox'], 'more' => ['files']])
        ->and(proposedMove($tree, $registry, 1, 3))->toBe(['bar' => ['home', 'reports'], 'more' => ['inbox', 'files']])
        ->and(proposedMove($tree, $registry, 1, 4))->toBe(['bar' => ['home', 'reports'], 'more' => ['files', 'inbox']]);

    // Files (row 3): bar slots 0-3, More slot 4 is where it already is.
    expect(proposedMove($tree, $registry, 3, 3))->toBe(['bar' => ['home', 'inbox', 'reports', 'files'], 'more' => []])
        ->and(proposedMove($tree, $registry, 3, 4))->toBeNull();

    // Locked Home (row 0) never moves.
    expect(array_slice($tree['props']['move_callbacks'], 0, 5))->toBe(['0', '0', '0', '0', '0']);
});

it('binds a plain list when there are no sections', function () {
    $registry = new CallbackRegistry;
    $tree = collectReorderList([
        'items' => [['value' => 1, 'label' => 'One'], ['value' => 2, 'label' => 'Two'], ['value' => 3, 'label' => 'Three']],
        'value' => [2, 3, 1],
        'a11y-label' => 'Steps',
        '_change' => 'reorder',
    ], $registry);

    expect($tree['props']['section_keys'])->toBe([])
        ->and($tree['props']['section_lengths'])->toBe(['3'])
        ->and($tree['props']['row_values'])->toBe(['2', '3', '1'])
        ->and($tree['props']['move_slot_count'])->toBe(3)
        // Two (row 0) to the end; One (row 2) to the top.
        ->and(proposedMove($tree, $registry, 0, 2))->toBe([3, 1, 2])
        ->and(proposedMove($tree, $registry, 2, 0))->toBe([1, 2, 3]);
});

it('starts from the authored order in the first section when the value is null', function () {
    $tree = collectReorderList([
        'items' => tabItems(),
        'sections' => ['bar' => 'Tab bar', 'more' => 'More'],
        'value' => null,
        'label' => 'Tabs',
    ]);

    expect($tree['props']['row_values'])->toBe(['home', 'inbox', 'reports', 'files'])
        ->and($tree['props']['section_lengths'])->toBe(['4', '0'])
        ->and(array_unique($tree['props']['move_callbacks']))->toBe(['0']);
});

it('emits nothing while disabled', function () {
    $tree = collectReorderList([
        'items' => tabItems(), 'value' => null, 'label' => 'Tabs', 'disabled' => true, '_change' => 'arrange',
    ]);

    expect(array_unique($tree['props']['move_callbacks']))->toBe(['0']);
});

it('fails before publication for contracts it cannot honour', function (array $attributes, string $message) {
    expect(fn () => collectReorderList(['label' => 'Tabs', ...$attributes]))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'blank label' => [['items' => [['value' => 'a', 'label' => ' ']]], 'label'],
    'duplicate item' => [['items' => [['value' => 'a', 'label' => 'A'], ['value' => 'a', 'label' => 'B']]], 'duplicate'],
    'mixed types' => [['items' => [['value' => 'a', 'label' => 'A'], ['value' => 1, 'label' => 'B']]], 'mix'],
    'too many items' => [['items' => array_map(fn ($i) => ['value' => $i, 'label' => "Item {$i}"], range(1, 21))], 'at most 20'],
    'one section' => [['items' => [['value' => 'a', 'label' => 'A']], 'sections' => ['only' => 'Only']], 'at least two'],
    'missing value' => [['items' => [['value' => 'a', 'label' => 'A'], ['value' => 'b', 'label' => 'B']], 'value' => ['a']], 'every item'],
    'repeated value' => [['items' => [['value' => 'a', 'label' => 'A'], ['value' => 'b', 'label' => 'B']], 'value' => ['a', 'b', 'a']], 'every item'],
    'unknown value' => [['items' => [['value' => 'a', 'label' => 'A']], 'value' => ['z']], 'every item'],
    'list for sections' => [['items' => [['value' => 'a', 'label' => 'A']], 'sections' => ['x' => 'X', 'y' => 'Y'], 'value' => ['a']], 'map of section'],
    'unknown section' => [['items' => [['value' => 'a', 'label' => 'A']], 'sections' => ['x' => 'X', 'y' => 'Y'], 'value' => ['x' => ['a'], 'z' => []]], 'section'],
    'deferred sync' => [['items' => [['value' => 'a', 'label' => 'A']], 'sync-mode' => 'blur'], 'blur'],
]);
