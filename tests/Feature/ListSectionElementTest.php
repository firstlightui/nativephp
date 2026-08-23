<?php

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
    ElementRegistry::register('firstlight.list-item', ListItem::class);
    ElementRegistry::register('firstlight.list-section', ListSection::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function makeSectionListItem(string $headline, string $press = 'noop'): ListItem
{
    return ListItem::make($headline)->onPress($press);
}

function collectListSection(array $attributes, array $children, ?CallbackRegistry $registry = null): array
{
    $section = new ListSection;
    $section->setChildren($children);
    $section->applyAttributes($attributes);
    $registry ??= new CallbackRegistry;
    $nextId = 1;
    $emittedIds = [];
    $lastNodeHashes = [];

    return $section->toArray($registry, $nextId, '', 0, $emittedIds, $lastNodeHashes);
}

it('publishes grouped section metadata on the upstream wire type', function () {
    $tree = collectListSection([
        'header' => 'Account',
        'footer' => 'Signed in as Alex',
    ], [
        makeSectionListItem('Profile', 'openProfile'),
        makeSectionListItem('Security', 'openSecurity'),
    ]);

    expect($tree['type'])->toBe('list_section')
        ->and($tree['props'])->toBe([
            'header' => 'Account',
            'footer' => 'Signed in as Alex',
        ])
        ->and($tree['children'])->toHaveCount(2)
        ->and($tree['children'][0]['type'])->toBe('firstlight.list-item');
});

it('requires at least one list item child', function () {
    collectListSection(['header' => 'Empty'], []);
})->throws(InvalidArgumentException::class, 'at least one List Item child');

it('rejects empty header and footer strings when authored', function (string $attribute, string $value) {
    collectListSection([$attribute => $value], [
        makeSectionListItem('Profile'),
    ]);
})->with([
    ['header', ''],
    ['header', " \t"],
    ['footer', ''],
    ['footer', "\n"],
])->throws(InvalidArgumentException::class);

it('rejects unsupported list container props on sections', function () {
    collectListSection(['separator' => true], [
        makeSectionListItem('Profile'),
    ]);
})->throws(InvalidArgumentException::class, 'does not support `separator`');

it('rejects nested sections and foreign child types', function () {
    $nested = ListSection::make('Nested', makeSectionListItem('Profile'));

    collectListSection([], [$nested]);
})->throws(InvalidArgumentException::class, 'only List Item children');

it('precompiles paired list section tags through the NativePHP Blade seam', function () {
    NativeTagPrecompiler::setActive(true);

    $compiled = (new FirstlightTagPrecompiler)(<<<'BLADE'
<firstlight:list-section header="Account">
    <firstlight:list-item headline="Profile" @press="openProfile" />
</firstlight:list-section>
BLADE);

    expect($compiled)
        ->toContain('<x-native-firstlight-list-section')
        ->toContain('header="Account"')
        ->toContain('<x-native-firstlight-list-item')
        ->toContain('</x-native-firstlight-list-section>')
        ->not->toContain('<firstlight:list-section');
});

it('declares an exact nativephp mobile ui adapter mapping', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['components'])->toContain([
        'type' => 'firstlight.list-section',
        'element' => 'FirstlightUI\\Elements\\ListSection',
        'blade' => 'FirstlightUI\\Components\\ListSection',
        'android_renderer' => 'com.nativephp.plugins.native_ui.ui.EmptyRenderer',
        'ios_renderer' => 'NativeUIEmptyRenderer',
        'self_closing' => false,
        'adapter' => [
            'package' => 'nativephp/mobile-ui',
            'type' => 'list_section',
        ],
    ]);
});
