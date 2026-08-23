<?php

use FirstlightUI\Elements\Modal;
use FirstlightUI\Elements\StatusLabel;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.modal', Modal::class);
    ElementRegistry::register('firstlight.status-label', StatusLabel::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectModal(array $attributes, array $children = [], ?CallbackRegistry $registry = null): array
{
    $modal = Modal::make(...$children);
    $dismiss = $attributes['_dismiss'] ?? null;
    unset($attributes['_dismiss']);
    $modal->applyAttributes($attributes);
    if (is_string($dismiss)) {
        $modal->onDismiss($dismiss);
    }
    $registry ??= new CallbackRegistry;
    $nextId = 1;
    $emittedIds = [];
    $lastNodeHashes = [];

    return $modal->toArray($registry, $nextId, '', 0, $emittedIds, $lastNodeHashes);
}

it('publishes the portable modal contract through the official primitive shape', function () {
    $registry = new CallbackRegistry;
    $label = StatusLabel::make();
    $label->applyAttributes(['label' => 'Account details']);

    $tree = collectModal([
        'visible' => true,
        'dismissible' => false,
        'a11y-label' => 'Account details',
        '_dismiss' => 'closeModal',
    ], [$label], $registry);

    expect($tree['type'])->toBe('firstlight.modal')
        ->and($tree['props'])->toMatchArray([
            'visible' => true,
            'dismissible' => false,
            'a11y_label' => 'Account details',
        ])
        ->and($registry->resolve($tree['props']['on_dismiss']))->toBe([
            'method' => 'closeModal',
            'args' => [],
        ])
        ->and($tree['children'][0]['type'])->toBe('firstlight.status-label');
});

it('publishes a closed dismissible modal by default', function () {
    $tree = collectModal(['_dismiss' => 'closeModal']);

    expect($tree['props'] ?? [])->toMatchArray([])
        ->and($tree['props']['on_dismiss'] ?? null)->toBeInt();
});

it('requires dismiss for PHP reconciliation', function () {
    collectModal(['visible' => true]);
})->throws(InvalidArgumentException::class, 'requires `@dismiss`');

it('rejects modelled values and platform-only geometry', function (string $attribute, mixed $value) {
    collectModal([$attribute => $value, '_dismiss' => 'closeModal']);
})->with([
    ['native:model', 'open'],
    ['detents', 'medium,large'],
    ['_press', 'save'],
])->throws(InvalidArgumentException::class, 'does not support');

it('requires real booleans for visible and dismissible', function (string $attribute, mixed $value) {
    collectModal([$attribute => $value, '_dismiss' => 'closeModal']);
})->with([
    ['visible', 'true'],
    ['dismissible', 1],
])->throws(InvalidArgumentException::class, 'must be a boolean');

it('precompiles paired modal tags through the NativePHP Blade seam', function () {
    NativeTagPrecompiler::setActive(true);

    $compiled = (new FirstlightTagPrecompiler)(<<<'BLADE'
<firstlight:modal :visible="$open" a11y-label="Account details" @dismiss="closeModal">
    <firstlight:status-label label="Account details" />
</firstlight:modal>
BLADE);

    expect($compiled)
        ->toContain('<x-native-firstlight-modal')
        ->toContain('_dismiss="closeModal"')
        ->toContain('<x-native-firstlight-status-label')
        ->toContain('</x-native-firstlight-modal>')
        ->not->toContain('<firstlight:modal');
});

it('declares an exact nativephp mobile ui adapter mapping', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['components'])->toContain([
        'type' => 'firstlight.modal',
        'element' => 'FirstlightUI\\Elements\\Modal',
        'blade' => 'FirstlightUI\\Components\\Modal',
        'android_renderer' => 'com.nativephp.plugins.native_ui.ui.ModalRenderer',
        'ios_renderer' => 'NativeUIModalRenderer',
        'self_closing' => false,
        'adapter' => [
            'package' => 'nativephp/mobile-ui',
            'type' => 'modal',
        ],
    ]);
});
