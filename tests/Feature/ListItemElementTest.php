<?php

use FirstlightUI\Elements\ListItem;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;
use Native\Mobile\Icon\AndroidSymbol;
use Native\Mobile\Icon\IosSymbol;
use Native\Mobile\Platform;

enum ListItemTestIosIcon: string implements IosSymbol
{
    case Account = 'person.crop.circle';
    case Forward = 'chevron.right';
}

enum ListItemTestAndroidIcon: string implements AndroidSymbol
{
    case Account = 'account_circle';
    case Forward = 'chevron_right';

    public function variant(): string
    {
        return 'outlined';
    }
}

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    Platform::set(null);
    ElementRegistry::register('firstlight.list-item', ListItem::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
    Platform::set(null);
});

function collectListItem(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.list-item', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

it('publishes the complete application row contract', function () {
    $registry = new CallbackRegistry;
    $tree = collectListItem([
        'headline' => 'Account',
        'supporting' => 'Manage your profile and security',
        'leading-monogram' => 'WJ',
        'trailing-text' => 'Open',
        'disabled' => true,
        'a11y-label' => 'Account settings',
        'a11y-hint' => 'Opens account settings',
        '_press' => 'openAccount',
        'margin' => 8,
    ], $registry);

    expect($tree['type'])->toBe('firstlight.list-item')
        ->and($tree['props'])->toBe([
            'a11y_label' => 'Account settings',
            'a11y_hint' => 'Opens account settings',
            'disabled' => true,
            'headline' => 'Account',
            'supporting' => 'Manage your profile and security',
            'leading_type' => 'monogram',
            'leading_value' => 'WJ',
            'trailing_type' => 'text',
            'trailing_value' => 'Open',
        ])
        ->and($tree['layout']['margin'])->toBe(8.0)
        ->and($tree['style'] ?? [])->toBe([])
        ->and($registry->resolve($tree['on_press']))->toBe([
            'method' => 'openAccount',
            'args' => [],
        ]);
});

it('publishes stable defaults for a headline-only row', function () {
    expect(collectListItem([
        'headline' => 'Account',
        '_press' => 'openAccount',
    ])['props'])->toBe([
        'disabled' => false,
        'headline' => 'Account',
    ]);
});

it('accepts each leading identity and exactly one trailing affordance', function (array $attributes, array $expected) {
    expect(collectListItem([
        'headline' => 'Account',
        '_press' => 'openAccount',
        ...$attributes,
    ])['props'])->toMatchArray($expected);
})->with([
    'avatar' => [
        ['leading-avatar' => 'https://example.test/avatar.png'],
        ['leading_type' => 'avatar', 'leading_value' => 'https://example.test/avatar.png'],
    ],
    'monogram' => [
        ['leading-monogram' => 'Å'],
        ['leading_type' => 'monogram', 'leading_value' => 'Å'],
    ],
    'leading icon' => [
        ['leading-icon' => 'person'],
        ['leading_type' => 'icon', 'leading_value' => 'person'],
    ],
    'trailing icon' => [
        ['trailing-icon' => 'chevron-right'],
        ['trailing_type' => 'icon', 'trailing_value' => 'chevron-right'],
    ],
    'trailing text' => [
        ['trailing-text' => 'Open'],
        ['trailing_type' => 'text', 'trailing_value' => 'Open'],
    ],
]);

it('resolves icon fallbacks overrides and Android variants through IconResolver', function () {
    $unknown = ListItem::make('Account')
        ->leadingIcon('person', ListItemTestIosIcon::Account, ListItemTestAndroidIcon::Account)
        ->trailingIcon('chevron-right', ListItemTestIosIcon::Forward, ListItemTestAndroidIcon::Forward)
        ->onPress('openAccount')
        ->toArray(new CallbackRegistry);

    Platform::set(Platform::IOS);
    $ios = ListItem::make('Account')
        ->leadingIcon('person', ListItemTestIosIcon::Account, ListItemTestAndroidIcon::Account)
        ->trailingIcon('chevron-right', ListItemTestIosIcon::Forward, ListItemTestAndroidIcon::Forward)
        ->onPress('openAccount')
        ->toArray(new CallbackRegistry);

    Platform::set(Platform::ANDROID);
    $android = ListItem::make('Account')
        ->leadingIcon('person', ListItemTestIosIcon::Account, ListItemTestAndroidIcon::Account)
        ->trailingIcon('chevron-right', ListItemTestIosIcon::Forward, ListItemTestAndroidIcon::Forward)
        ->onPress('openAccount')
        ->toArray(new CallbackRegistry);

    expect($unknown['props'])->toMatchArray([
        'leading_value' => 'person',
        'trailing_value' => 'chevron-right',
    ])->and($unknown['props'])->not->toHaveKeys([
        'leading_icon_variant',
        'trailing_icon_variant',
    ])->and($ios['props'])->toMatchArray([
        'leading_value' => 'person.crop.circle',
        'trailing_value' => 'chevron.right',
    ])->and($android['props'])->toMatchArray([
        'leading_value' => 'account_circle',
        'leading_icon_variant' => 'outlined',
        'trailing_value' => 'chevron_right',
        'trailing_icon_variant' => 'outlined',
    ]);
});

it('accepts kebab and camel case aliases for content and icon overrides', function () {
    Platform::set(Platform::IOS);

    $kebab = collectListItem([
        'headline' => 'Account',
        'leading-icon' => 'person',
        'leading-icon-ios' => 'person.crop.circle',
        'trailing-icon' => 'chevron-right',
        'trailing-icon-ios' => 'chevron.right',
        '_press' => 'openAccount',
    ]);
    $camel = collectListItem([
        'headline' => 'Account',
        'leadingIcon' => 'person',
        'leadingIconIos' => 'person.circle',
        'trailingIcon' => 'chevron-right',
        'trailingIconIos' => 'arrow.forward',
        '_press' => 'openAccount',
    ]);

    expect($kebab['props'])->toMatchArray([
        'leading_value' => 'person.crop.circle',
        'trailing_value' => 'chevron.right',
    ])->and($camel['props'])->toMatchArray([
        'leading_value' => 'person.circle',
        'trailing_value' => 'arrow.forward',
    ]);
});

it('requires a non-empty headline and press callback', function (array $attributes, string $message) {
    collectListItem($attributes);
})->with([
    'missing headline' => [['_press' => 'openAccount'], 'non-empty `headline`'],
    'blank headline' => [['headline' => " \n", '_press' => 'openAccount'], 'non-empty `headline`'],
    'missing press' => [['headline' => 'Account'], 'requires `@press`'],
])->throws(InvalidArgumentException::class);

it('requires strict text booleans icon types and non-empty optional content', function (array $attributes, string $message) {
    collectListItem([
        'headline' => 'Account',
        '_press' => 'openAccount',
        ...$attributes,
    ]);
})->with([
    'numeric headline' => [['headline' => 42], '`headline` must be a string'],
    'numeric supporting' => [['supporting' => 42], '`supporting` must be a string'],
    'blank supporting' => [['supporting' => '  '], 'non-empty `supporting`'],
    'string disabled' => [['disabled' => 'false'], '`disabled` must be a boolean'],
    'numeric a11y label' => [['a11y-label' => 42], '`a11y-label` must be a string'],
    'blank a11y label' => [['a11y-label' => '  '], 'non-empty `a11y-label`'],
    'numeric a11y hint' => [['a11y-hint' => 42], '`a11y-hint` must be a string'],
    'blank a11y hint' => [['a11y-hint' => " \n"], 'non-empty `a11y-hint`'],
    'blank avatar' => [['leading-avatar' => ''], 'non-empty `leading-avatar`'],
    'long monogram' => [['leading-monogram' => 'ABC'], 'one or two characters'],
    'numeric monogram' => [['leading-monogram' => 42], '`leading-monogram` must be a string'],
    'blank trailing text' => [['trailing-text' => ''], 'non-empty `trailing-text`'],
    'numeric leading icon' => [['leading-icon' => 42], '`leading-icon` must be a string'],
    'invalid iOS leading icon' => [['leading-icon' => 'person', 'leading-icon-ios' => 42], '`leading-icon-ios` must be an IosSymbol or string'],
    'invalid Android trailing icon' => [['trailing-icon' => 'forward', 'trailing-icon-android' => []], '`trailing-icon-android` must be an AndroidSymbol or string'],
    'leading override without fallback' => [['leading-icon-ios' => 'person.circle'], '`leading-icon` shared fallback'],
    'trailing override without fallback' => [['trailing-icon-android' => 'chevron_right'], '`trailing-icon` shared fallback'],
])->throws(InvalidArgumentException::class);

it('rejects ambiguous leading and trailing content', function (array $attributes, string $message) {
    collectListItem([
        'headline' => 'Account',
        '_press' => 'openAccount',
        ...$attributes,
    ]);
})->with([
    'two leading identities' => [[
        'leading-avatar' => 'avatar.png',
        'leading-monogram' => 'AJ',
    ], 'exactly one leading'],
    'icon and identity' => [[
        'leading-icon' => 'person',
        'leading-avatar' => 'avatar.png',
    ], 'exactly one leading'],
    'two trailing affordances' => [[
        'trailing-icon' => 'chevron-right',
        'trailing-text' => 'Open',
    ], 'exactly one trailing'],
])->throws(InvalidArgumentException::class);

it('rejects APIs outside the narrow application row contract', function (string $attribute, mixed $value) {
    collectListItem([
        'headline' => 'Account',
        '_press' => 'openAccount',
        $attribute => $value,
    ]);
})->with([
    ['overline', 'Settings'],
    ['leading-image', 'cover.png'],
    ['leading-checkbox', true],
    ['leading-radio', true],
    ['trailing-checkbox', true],
    ['trailing-switch', true],
    ['trailing-icon-button', 'ellipsis'],
    ['trailing-a11y-label', 'More actions'],
    ['trailing-menu', []],
    ['leading-actions', []],
    ['trailing-actions', []],
    ['trailing-badges', []],
    ['on-swipe-delete', 'delete'],
    ['on-leading-change', 'changed'],
    ['on-trailing-change', 'changed'],
    ['_trailingPress', 'more'],
    ['_longPress', 'preview'],
    ['_doubleTap', 'openTwice'],
    ['_navigate', ['route' => '/account']],
    ['value', 'account'],
    ['selected', true],
    ['loading', true],
    ['sync-mode', 'live'],
    ['_change', 'changed'],
    ['helper', 'Choose an account'],
    ['error', 'Unavailable'],
    ['required', true],
    ['headline-color', '#FF0000'],
    ['container-color', '#FFFFFF'],
    ['tonal-elevation', 2],
    ['shadow-elevation', 2],
    ['font', 'Inter-Bold'],
])->throws(InvalidArgumentException::class, 'does not support');

it('precompiles only the self-closing public tag through the NativePHP Blade seam', function () {
    NativeTagPrecompiler::setActive(true);

    $compiled = (new FirstlightTagPrecompiler)(
        '<firstlight:list-item headline="Account" trailing-icon="chevron-right" @press="openAccount" />'
    );
    $paired = (new FirstlightTagPrecompiler)(
        '<firstlight:list-item headline="Account" @press="openAccount">Content</firstlight:list-item>'
    );

    expect($compiled)
        ->toContain('<x-native-firstlight-list-item')
        ->toContain('headline="Account"')
        ->toContain('trailing-icon="chevron-right"')
        ->toContain('_press="openAccount"')
        ->not->toContain('<firstlight:list-item')
        ->and($paired)->toBe(
            '<firstlight:list-item headline="Account" @press="openAccount">Content</firstlight:list-item>'
        );
});

it('declares paired renderer identifiers without an adapter mapping', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['components'])->toContain([
        'type' => 'firstlight.list-item',
        'element' => 'FirstlightUI\\Elements\\ListItem',
        'blade' => 'FirstlightUI\\Components\\ListItem',
        'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.FirstlightListItemRenderer',
        'ios_renderer' => 'ListItemRenderer',
        'self_closing' => true,
    ]);
});
