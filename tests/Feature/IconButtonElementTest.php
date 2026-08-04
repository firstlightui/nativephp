<?php

use FirstlightUI\Elements\IconButton;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;
use Native\Mobile\Icon\AndroidSymbol;
use Native\Mobile\Icon\IosSymbol;
use Native\Mobile\Platform;

enum IconButtonTestIosIcon: string implements IosSymbol
{
    case Add = 'plus.circle';
}

enum IconButtonTestAndroidIcon: string implements AndroidSymbol
{
    case Add = 'add_circle';

    public function variant(): string
    {
        return 'outlined';
    }
}

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    Platform::set(null);
    ElementRegistry::register('firstlight.icon-button', IconButton::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
    Platform::set(null);
});

function collectIconButton(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.icon-button', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

it('publishes the complete compact action contract', function () {
    $registry = new CallbackRegistry;
    $tree = collectIconButton([
        'icon' => 'plus',
        'a11y-label' => 'Add item',
        'a11y-hint' => 'Adds a blank item',
        'variant' => 'success',
        'size' => 'lg',
        'disabled' => true,
        'loading' => true,
        '_press' => 'addItem',
        'margin' => 8,
    ], $registry);

    expect($tree['type'])->toBe('firstlight.icon-button')
        ->and($tree['props'])->toMatchArray([
            'icon' => 'plus',
            'a11y_label' => 'Add item',
            'a11y_hint' => 'Adds a blank item',
            'variant' => 'success',
            'size' => 'lg',
            'disabled' => true,
            'loading' => true,
        ])
        ->and($tree['layout']['margin'])->toBe(8.0)
        ->and($tree['style'] ?? [])->toBe([])
        ->and($registry->resolve($tree['on_press']))->toBe([
            'method' => 'addItem',
            'args' => [],
        ]);
});

it('publishes stable defaults', function () {
    $tree = collectIconButton([
        'icon' => 'plus',
        'a11y-label' => 'Add item',
        '_press' => 'addItem',
    ]);

    expect($tree['props'])->toBe([
        'a11y_label' => 'Add item',
        'variant' => 'primary',
        'size' => 'md',
        'disabled' => false,
        'loading' => false,
        'icon' => 'plus',
    ]);
});

it('accepts every documented variant and size', function (string $variant, string $size) {
    $tree = collectIconButton([
        'icon' => 'plus',
        'a11y-label' => 'Add item',
        'variant' => $variant,
        'size' => $size,
        '_press' => 'addItem',
    ]);

    expect($tree['props']['variant'])->toBe($variant)
        ->and($tree['props']['size'])->toBe($size);
})->with([
    ['primary', 'sm'],
    ['secondary', 'md'],
    ['destructive', 'lg'],
    ['success', 'sm'],
    ['ghost', 'md'],
]);

it('resolves shared and platform icon choices through IconResolver', function () {
    $unknown = IconButton::make()
        ->icon('plus', IconButtonTestIosIcon::Add, IconButtonTestAndroidIcon::Add)
        ->a11yLabel('Add item')
        ->onPress('addItem')
        ->toArray(new CallbackRegistry);

    Platform::set(Platform::IOS);
    $ios = IconButton::make()
        ->icon('plus', IconButtonTestIosIcon::Add, IconButtonTestAndroidIcon::Add)
        ->a11yLabel('Add item')
        ->onPress('addItem')
        ->toArray(new CallbackRegistry);

    Platform::set(Platform::ANDROID);
    $android = IconButton::make()
        ->icon('plus', IconButtonTestIosIcon::Add, IconButtonTestAndroidIcon::Add)
        ->a11yLabel('Add item')
        ->onPress('addItem')
        ->toArray(new CallbackRegistry);

    expect($unknown['props']['icon'])->toBe('plus')
        ->and($unknown['props'])->not->toHaveKey('icon_variant')
        ->and($ios['props']['icon'])->toBe('plus.circle')
        ->and($ios['props'])->not->toHaveKey('icon_variant')
        ->and($android['props']['icon'])->toBe('add_circle')
        ->and($android['props']['icon_variant'])->toBe('outlined');
});

it('accepts kebab and camel case icon overrides', function () {
    Platform::set(Platform::IOS);

    $kebab = collectIconButton([
        'icon' => 'plus',
        'icon-ios' => 'plus.circle',
        'a11y-label' => 'Add item',
        '_press' => 'addItem',
    ]);
    $camel = collectIconButton([
        'icon' => 'plus',
        'iconIos' => 'plus.square',
        'iconAndroid' => 'add_box',
        'a11y-label' => 'Add item',
        '_press' => 'addItem',
    ]);

    expect($kebab['props']['icon'])->toBe('plus.circle')
        ->and($camel['props']['icon'])->toBe('plus.square');
});

it('requires an icon accessible name and press callback', function (array $attributes, string $message) {
    try {
        collectIconButton($attributes);
    } catch (InvalidArgumentException $exception) {
        expect($exception->getMessage())->toContain($message);

        return;
    }

    test()->fail('Expected Icon Button validation to fail.');
})->with([
    'missing icon' => [['a11y-label' => 'Add item', '_press' => 'addItem'], 'non-empty `icon`'],
    'blank icon' => [['icon' => '  ', 'a11y-label' => 'Add item', '_press' => 'addItem'], 'non-empty `icon`'],
    'missing label' => [['icon' => 'plus', '_press' => 'addItem'], 'non-empty `a11y-label`'],
    'blank label' => [['icon' => 'plus', 'a11y-label' => " \n", '_press' => 'addItem'], 'non-empty `a11y-label`'],
    'missing press' => [['icon' => 'plus', 'a11y-label' => 'Add item'], 'requires `@press`'],
]);

it('requires strict booleans strings enums and platform icon types', function (array $attributes, string $message) {
    try {
        collectIconButton([
            'icon' => 'plus',
            'a11y-label' => 'Add item',
            '_press' => 'addItem',
            ...$attributes,
        ]);
    } catch (InvalidArgumentException $exception) {
        expect($exception->getMessage())->toContain($message);

        return;
    }

    test()->fail('Expected Icon Button validation to fail.');
})->with([
    'numeric icon' => [['icon' => 42], '`icon` must be a string'],
    'numeric label' => [['a11y-label' => 42], '`a11y-label` must be a string'],
    'numeric hint' => [['a11y-hint' => 42], '`a11y-hint` must be a string'],
    'string disabled' => [['disabled' => 'false'], '`disabled` must be a boolean'],
    'integer loading' => [['loading' => 1], '`loading` must be a boolean'],
    'invalid variant' => [['variant' => 'accent'], 'Unsupported Icon Button variant [accent]'],
    'invalid size' => [['size' => 'xl'], 'Unsupported Icon Button size [xl]'],
    'invalid ios icon' => [['icon-ios' => 42], '`icon-ios` must be an IosSymbol or string'],
    'invalid android icon' => [['icon-android' => []], '`icon-android` must be an AndroidSymbol or string'],
]);

it('rejects APIs outside the compact action contract', function (string $attribute, mixed $value) {
    collectIconButton([
        'icon' => 'plus',
        'a11y-label' => 'Add item',
        '_press' => 'addItem',
        $attribute => $value,
    ]);
})->with([
    ['label', 'Add'],
    ['icon-trailing', 'chevron-right'],
    ['menu', []],
    ['value', 'add'],
    ['sync-mode', 'live'],
    ['_change', 'changed'],
    ['_submit', 'submitted'],
    ['_longPress', 'repeat'],
    ['_doubleTap', 'repeat'],
    ['_pressDown', 'pressed'],
    ['_pressUp', 'released'],
    ['_navigate', ['route' => '/items/create']],
    ['color', '#FF0000'],
    ['background', '#FFFFFF'],
    ['font', 'Inter-Bold'],
])->throws(InvalidArgumentException::class, 'does not support');

it('precompiles the self-closing public tag through the NativePHP Blade seam', function () {
    NativeTagPrecompiler::setActive(true);

    $compiled = (new FirstlightTagPrecompiler)(
        '<firstlight:icon-button icon="plus" icon-ios="plus.circle" a11y-label="Add item" @press="addItem" />'
    );

    expect($compiled)
        ->toContain('<x-native-firstlight-icon-button')
        ->toContain('icon="plus"')
        ->toContain('icon-ios="plus.circle"')
        ->toContain('a11y-label="Add item"')
        ->toContain('_press="addItem"')
        ->not->toContain('<firstlight:icon-button');
});

it('declares paired package renderer identifiers', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['components'])->toContain([
        'type' => 'firstlight.icon-button',
        'element' => 'FirstlightUI\\Elements\\IconButton',
        'blade' => 'FirstlightUI\\Components\\IconButton',
        'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.IconButtonRenderer',
        'ios_renderer' => 'IconButtonRenderer',
        'self_closing' => true,
    ]);
});
