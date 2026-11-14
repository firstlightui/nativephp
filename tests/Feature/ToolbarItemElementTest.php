<?php

use FirstlightUI\Elements\IconButton;
use FirstlightUI\Elements\Toolbar;
use FirstlightUI\Elements\ToolbarItem;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.toolbar', Toolbar::class);
    ElementRegistry::register('firstlight.toolbar-item', ToolbarItem::class);
    ElementRegistry::register('firstlight.icon-button', IconButton::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

require_once __DIR__.'/../Support/ToolbarHarness.php';

it('shows no badge for zero or null, and text badges as written', function (mixed $badge, ?string $expected) {
    $tree = collectToolbar([toolbarItem('Inbox', ['badge' => $badge])]);

    $expected === null
        ? expect($tree['children'][0]['props'])->not->toHaveKey('badge')
        : expect($tree['children'][0]['props']['badge'])->toBe($expected);
})->with([
    [0, null],
    [null, null],
    [7, '7'],
    [99, '99'],
    [100, '99+'],
    ['New', 'New'],
]);

it('requires an icon, an accessible label, and a press or a menu', function (array $item, string $message) {
    expect(fn () => collectToolbar([$item]))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'no icon' => [['a11y-label' => 'Share', '_press' => 'share'], 'requires a non-empty `icon`'],
    'no label' => [['icon' => 'share', '_press' => 'share'], 'requires a non-empty `a11y-label`'],
    'no action' => [['icon' => 'share', 'a11y-label' => 'Share'], 'requires `@press` or a `menu`'],
    'both' => [['icon' => 'share', 'a11y-label' => 'Share', '_press' => 'share', 'menu' => [['method' => 'a', 'label' => 'A']]], 'not both'],
]);

it('rejects malformed menu items', function (array $menu) {
    collectToolbar([['icon' => 'share', 'a11y-label' => 'Share', 'menu' => $menu]]);
})->with([
    'no label' => [[['method' => 'a']]],
    'unknown key' => [[['method' => 'a', 'label' => 'A', 'url' => '/a']]],
    'bad role' => [[['method' => 'a', 'label' => 'A', 'role' => 'cancel']]],
    'platform icon without shared icon' => [[['method' => 'a', 'label' => 'A', 'ios' => 'star']]],
])->throws(InvalidArgumentException::class);

it('rejects attributes that belong to other controls', function (string $attribute) {
    collectToolbar([toolbarItem('Share', [$attribute => 'x'])]);
})->with(['label', 'variant', 'glass', 'url', 'a11y-hint', 'badge-a11y-label'])->throws(InvalidArgumentException::class, 'does not support');
