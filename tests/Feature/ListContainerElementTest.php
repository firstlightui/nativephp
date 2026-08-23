<?php

use FirstlightUI\Elements\ListContainer;
use FirstlightUI\Elements\ListItem;
use FirstlightUI\Elements\ListSection;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.list', ListContainer::class);
    ElementRegistry::register('firstlight.list-item', ListItem::class);
    ElementRegistry::register('firstlight.list-section', ListSection::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function makeListItem(string $headline, string $press = 'noop'): ListItem
{
    return ListItem::make($headline)->onPress($press);
}

function collectList(array $attributes, array $children, ?CallbackRegistry $registry = null): array
{
    $list = ListContainer::make(...$children);
    $list->applyAttributes($attributes);
    $registry ??= new CallbackRegistry;
    $nextId = 1;
    $emittedIds = [];
    $lastNodeHashes = [];

    return $list->toArray($registry, $nextId, '', 0, $emittedIds, $lastNodeHashes);
}

it('publishes the portable list contract through the official primitive shape', function () {
    $registry = new CallbackRegistry;
    $tree = collectList([
        'separator' => true,
        'plain' => true,
        'shows-indicators' => false,
        'on-refresh' => 'reloadItems',
        'on-end-reached' => 'loadMore',
    ], [
        makeListItem('Account', 'openAccount'),
        ListSection::make('Preferences', makeListItem('Notifications', 'openNotifications'))
            ->footer('Changes sync automatically'),
    ], $registry);

    expect($tree['type'])->toBe('firstlight.list')
        ->and($tree['props'])->toMatchArray([
            'separator' => true,
            'plain' => true,
            'shows_indicators' => false,
        ])
        ->and($registry->resolve($tree['props']['on_refresh']))->toBe([
            'method' => 'reloadItems',
            'args' => [],
        ])
        ->and($registry->resolve($tree['props']['on_end_reached']))->toBe([
            'method' => 'loadMore',
            'args' => [],
        ])
        ->and($tree['children'][0]['type'])->toBe('firstlight.list-item')
        ->and($tree['children'][1]['type'])->toBe('list_section')
        ->and($tree['children'][1]['props'])->toMatchArray([
            'header' => 'Preferences',
            'footer' => 'Changes sync automatically',
        ]);
});

it('publishes stable defaults for an ordinary vertical list', function () {
    $tree = collectList([], [makeListItem('Account')]);

    expect($tree['props'] ?? [])->toBe([]);
});

it('rejects horizontal layout outside the Firstlight contract', function () {
    collectList(['horizontal' => true], [makeListItem('Account')]);
})->throws(InvalidArgumentException::class, 'does not support `horizontal`');

it('rejects unsupported child types instead of silently coercing them', function () {
    $foreign = ListItem::make('Account');
    $foreignReflection = new ReflectionClass($foreign);
    $type = $foreignReflection->getProperty('type');
    $type->setAccessible(true);
    $type->setValue($foreign, 'button');

    collectList([], [$foreign]);
})->throws(InvalidArgumentException::class, 'List Item and List Section children');

it('requires real booleans for separator, plain, and shows-indicators', function (string $attribute, mixed $value) {
    collectList([$attribute => $value], [makeListItem('Account')]);
})->with([
    ['separator', 'true'],
    ['plain', 1],
    ['shows-indicators', 'false'],
])->throws(InvalidArgumentException::class, 'must be a boolean');

it('precompiles nested list sections before the parent list container', function () {
    NativeTagPrecompiler::setActive(true);

    $compiled = (new FirstlightTagPrecompiler)(<<<'BLADE'
<firstlight:list separator>
    <firstlight:list-item headline="Account" @press="openAccount" />
    <firstlight:list-section header="Preferences">
        <firstlight:list-item headline="Privacy" @press="openPrivacy" />
    </firstlight:list-section>
</firstlight:list>
BLADE);

    expect($compiled)
        ->toContain('<x-native-firstlight-list-section')
        ->toContain('<x-native-firstlight-list ')
        ->not->toContain('<firstlight:list-section')
        ->not->toContain('<firstlight:list');
});

it('precompiles paired list tags through the NativePHP Blade seam', function () {
    NativeTagPrecompiler::setActive(true);

    $compiled = (new FirstlightTagPrecompiler)(<<<'BLADE'
<firstlight:list separator @refresh="reloadItems">
    <firstlight:list-item headline="Account" @press="openAccount" />
</firstlight:list>
BLADE);

    expect($compiled)
        ->toContain('<x-native-firstlight-list')
        ->toContain('_refresh="reloadItems"')
        ->toContain('<x-native-firstlight-list-item')
        ->toContain('</x-native-firstlight-list>')
        ->not->toContain('<firstlight:list');
});

it('declares an exact nativephp mobile ui adapter mapping', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['components'])->toContain([
        'type' => 'firstlight.list',
        'element' => 'FirstlightUI\\Elements\\ListContainer',
        'blade' => 'FirstlightUI\\Components\\ListContainer',
        'android_renderer' => 'com.nativephp.plugins.native_ui.ui.ListRenderer',
        'ios_renderer' => 'NativeUIListRenderer',
        'self_closing' => false,
        'adapter' => [
            'package' => 'nativephp/mobile-ui',
            'type' => 'list',
        ],
    ]);
});
