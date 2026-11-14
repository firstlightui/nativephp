<?php

use FirstlightUI\Elements\IconButton;
use FirstlightUI\Elements\Toolbar;
use FirstlightUI\Elements\ToolbarItem;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
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

it('publishes icon actions, the primary action, badges and menus', function () {
    $registry = new CallbackRegistry;
    $tree = collectToolbar([
        toolbarItem('Checklist', ['icon' => 'checklist', 'haptic' => 'keypress']),
        toolbarItem('Inbox', ['badge' => 120]),
        [
            'icon' => 'paperclip',
            'icon-android' => 'attach_file',
            'a11y-label' => 'Attach',
            'menu' => [
                ['method' => 'attachPhoto', 'label' => 'Photo', 'icon' => 'photo'],
                ['method' => 'attachFile', 'label' => 'File', 'disabled' => true],
                ['method' => 'clearAttachments', 'label' => 'Remove all', 'role' => 'destructive'],
            ],
        ],
        toolbarItem('New note', ['icon' => 'square.and.pencil', 'primary' => true, 'disabled' => true]),
    ], [], $registry);

    [$checklist, $inbox, $attach, $compose] = $tree['children'];
    $menu = json_decode($attach['props']['menu_json'], true, flags: JSON_THROW_ON_ERROR);

    expect($tree['type'])->toBe('firstlight.toolbar')
        ->and($tree['props'] ?? [])->toBe([])
        ->and(array_column($tree['children'], 'type'))->each->toBe('firstlight.toolbar-item')
        ->and($checklist['props'])->toMatchArray(['icon' => 'checklist', 'a11y_label' => 'Checklist', 'haptic' => 'keypress', 'primary' => false])
        ->and($registry->resolve($checklist['on_press'])['method'])->toBe('runChecklist')
        ->and($inbox['props'])->toMatchArray(['badge' => '99+'])
        ->and($attach['props'])->not->toHaveKey('badge')
        ->and($attach['on_press'] ?? 0)->toBe(0)
        ->and(array_column($menu, 'label'))->toBe(['Photo', 'File', 'Remove all'])
        ->and($registry->resolve($menu[0]['cb'])['method'])->toBe('attachPhoto')
        ->and($menu[1])->toMatchArray(['cb' => 0, 'disabled' => true])
        ->and($menu[2]['role'])->toBe('destructive')
        ->and($compose['props'])->toMatchArray(['icon' => 'square.and.pencil', 'primary' => true, 'disabled' => true]);
});

it('takes no space in the screen layout', function () {
    $tree = collectToolbar([toolbarItem('Share')]);

    expect($tree['layout'])->toMatchArray(['position_type' => 1, 'width' => 0.0, 'height' => 0.0]);
});

it('limits the toolbar to five actions and one primary action', function (array $items, string $message) {
    expect(fn () => collectToolbar($items))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'six actions' => [array_map(fn (int $i) => toolbarItem("Action {$i}"), range(1, 6)), 'at most 5 items'],
    'two primary' => [[toolbarItem('A', ['primary' => true]), toolbarItem('B', ['primary' => true])], 'at most one `primary`'],
    'empty' => [[], 'at least one Toolbar Item'],
]);

it('allows five actions plus a primary one', function () {
    $items = array_map(fn (int $i) => toolbarItem("Action {$i}"), range(1, 5));
    $items[] = toolbarItem('Compose', ['primary' => true]);

    expect(collectToolbar($items)['children'])->toHaveCount(6);
});

it('accepts only Toolbar Item children', function () {
    collectToolbar([['type' => 'firstlight.icon-button', 'icon' => 'star', 'a11y-label' => 'Star', '_press' => 'star']]);
})->throws(InvalidArgumentException::class, 'accepts only Toolbar Item children');

it('takes no attributes of its own', function (string $attribute) {
    collectToolbar([toolbarItem('Share')], [$attribute => 'x']);
})->with(['a11y-label', 'label', 'haptic'])->throws(InvalidArgumentException::class, 'Firstlight Toolbar does not support');

it('precompiles the paired toolbar and its items through the NativePHP Blade seam', function () {
    NativeTagPrecompiler::setActive(true);

    $compiled = (new FirstlightTagPrecompiler)(<<<'BLADE'
<firstlight:toolbar>
    <firstlight:toolbar-item icon="checklist" a11y-label="Checklist" @press="addChecklist" />
    <firstlight:toolbar-item primary icon="square.and.pencil" a11y-label="New note" @press="compose" />
</firstlight:toolbar>
BLADE);

    expect($compiled)
        ->toContain('<x-native-firstlight-toolbar>')
        ->toContain('<x-native-firstlight-toolbar-item')
        ->toContain('_press="compose"')
        ->toContain('</x-native-firstlight-toolbar>')
        ->not->toContain('<firstlight:toolbar');
});
