<?php

use FirstlightUI\Elements\BottomSheet;
use FirstlightUI\Elements\StatusLabel;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.bottom-sheet', BottomSheet::class);
    ElementRegistry::register('firstlight.status-label', StatusLabel::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectBottomSheet(array $attributes, array $children = [], ?CallbackRegistry $registry = null): array
{
    $sheet = BottomSheet::make(...$children);
    $dismiss = $attributes['_dismiss'] ?? null;
    unset($attributes['_dismiss']);
    $sheet->applyAttributes($attributes);
    if (is_string($dismiss)) {
        $sheet->onDismiss($dismiss);
    }
    $registry ??= new CallbackRegistry;
    $nextId = 1;
    $emittedIds = [];
    $lastNodeHashes = [];

    return $sheet->toArray($registry, $nextId, '', 0, $emittedIds, $lastNodeHashes);
}

it('publishes the portable bottom sheet contract through the official primitive shape', function () {
    $registry = new CallbackRegistry;
    $label = StatusLabel::make();
    $label->applyAttributes(['label' => 'Filters']);

    $tree = collectBottomSheet([
        'visible' => true,
        'a11y-label' => 'Filters',
        '_dismiss' => 'closeSheet',
    ], [$label], $registry);

    expect($tree['type'])->toBe('firstlight.bottom-sheet')
        ->and($tree['props'])->toMatchArray([
            'visible' => true,
            'a11y_label' => 'Filters',
        ])
        ->and($tree['props'])->not->toHaveKey('detents')
        ->and($registry->resolve($tree['props']['on_dismiss']))->toBe([
            'method' => 'closeSheet',
            'args' => [],
        ])
        ->and($tree['children'][0]['type'])->toBe('firstlight.status-label');
});

it('publishes a closed sheet by default', function () {
    $tree = collectBottomSheet(['_dismiss' => 'closeSheet']);

    expect($tree['props'] ?? [])->toMatchArray([])
        ->and($tree['props']['on_dismiss'] ?? null)->toBeInt();
});

it('requires dismiss for PHP reconciliation', function () {
    collectBottomSheet(['visible' => true]);
})->throws(InvalidArgumentException::class, 'requires `@dismiss`');

it('rejects detents and modelled values from the shared API', function (string $attribute, mixed $value) {
    collectBottomSheet([$attribute => $value, '_dismiss' => 'closeSheet']);
})->with([
    ['detents', 'medium,large'],
    ['dismissible', false],
    ['native:model', 'open'],
])->throws(InvalidArgumentException::class, 'does not support');

it('requires a real boolean for visible', function () {
    collectBottomSheet(['visible' => 'true', '_dismiss' => 'closeSheet']);
})->throws(InvalidArgumentException::class, 'must be a boolean');

it('precompiles paired bottom sheet tags through the NativePHP Blade seam', function () {
    NativeTagPrecompiler::setActive(true);

    $compiled = (new FirstlightTagPrecompiler)(<<<'BLADE'
<firstlight:bottom-sheet :visible="$open" a11y-label="Filters" @dismiss="closeSheet">
    <firstlight:status-label label="Filters" />
</firstlight:bottom-sheet>
BLADE);

    expect($compiled)
        ->toContain('<x-native-firstlight-bottom-sheet')
        ->toContain('_dismiss="closeSheet"')
        ->toContain('<x-native-firstlight-status-label')
        ->toContain('</x-native-firstlight-bottom-sheet>')
        ->not->toContain('<firstlight:bottom-sheet');
});

it('declares an exact nativephp mobile ui adapter mapping', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['components'])->toContain([
        'type' => 'firstlight.bottom-sheet',
        'element' => 'FirstlightUI\\Elements\\BottomSheet',
        'blade' => 'FirstlightUI\\Components\\BottomSheet',
        'android_renderer' => 'com.nativephp.plugins.native_ui.ui.BottomSheetRenderer',
        'ios_renderer' => 'NativeUIBottomSheetRenderer',
        'self_closing' => false,
        'adapter' => [
            'package' => 'nativephp/mobile-ui',
            'type' => 'bottom_sheet',
        ],
    ]);
});
