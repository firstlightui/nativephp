<?php

use FirstlightUI\Components\Button as ButtonComponent;
use FirstlightUI\Elements\Button;
use FirstlightUI\FirstlightTagPrecompiler;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\ComponentSlot;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.button', Button::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectButton(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.button', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

it('publishes the portable button contract through the official primitive shape', function () {
    $registry = new CallbackRegistry;
    $tree = collectButton([
        'label' => 'Continue',
        'variant' => 'success',
        'size' => 'lg',
        'disabled' => true,
        'loading' => true,
        'icon' => 'arrow-right',
        'icon-trailing' => 'chevron-right',
        'a11y-label' => 'Continue to confirmation',
        'a11y-hint' => 'Opens the confirmation step',
        '_press' => 'continueToConfirmation',
        'width' => 'fill',
    ], $registry);

    expect($tree['type'])->toBe('firstlight.button')
        ->and($tree['props'])->toMatchArray([
            'label' => 'Continue',
            'variant' => 'success',
            'size' => 'lg',
            'disabled' => true,
            'loading' => true,
            'leading_icon' => 'arrow-right',
            'trailing_icon' => 'chevron-right',
            'a11y_label' => 'Continue to confirmation',
            'a11y_hint' => 'Opens the confirmation step',
        ])
        ->and($tree['layout']['width'])->toBe('fill')
        ->and($tree['style'] ?? [])->toBe([])
        ->and($registry->resolve($tree['props']['on_press']))->toBe([
            'method' => 'continueToConfirmation',
            'args' => [],
        ]);
});

it('publishes stable defaults for every ordinary text button', function () {
    expect(collectButton(['label' => 'Save'])['props'])->toBe([
        'variant' => 'primary',
        'size' => 'md',
        'disabled' => false,
        'loading' => false,
        'label' => 'Save',
    ]);
});

it('accepts every documented variant', function (string $variant) {
    expect(collectButton([
        'label' => ucfirst($variant),
        'variant' => $variant,
    ])['props']['variant'])->toBe($variant);
})->with(['primary', 'secondary', 'destructive', 'success', 'ghost']);

it('accepts every documented size', function (string $size) {
    expect(collectButton([
        'label' => strtoupper($size),
        'size' => $size,
    ])['props']['size'])->toBe($size);
})->with(['sm', 'md', 'lg']);

it('requires non-empty visible text so icon-only actions use Icon Button', function (array $attributes) {
    collectButton($attributes);
})->with([
    'missing label' => [[]],
    'empty label' => [['label' => '']],
    'whitespace label' => [['label' => " \n\t "]],
    'icon without label' => [['icon' => 'plus', 'a11y-label' => 'Add item']],
])->throws(InvalidArgumentException::class, 'non-empty `label`');

it('rejects unsupported variants instead of silently changing intent', function () {
    collectButton(['label' => 'Save', 'variant' => 'accent']);
})->throws(InvalidArgumentException::class, 'Unsupported Button variant [accent]');

it('rejects unsupported sizes instead of relying on renderer fallbacks', function () {
    collectButton(['label' => 'Save', 'size' => 'xl']);
})->throws(InvalidArgumentException::class, 'Unsupported Button size [xl]');

it('rejects Mobile UI escape hatches outside the Firstlight contract', function (string $attribute, mixed $value) {
    collectButton([
        'label' => 'Save',
        $attribute => $value,
    ]);
})->with([
    ['font', 'Inter-Bold'],
    ['line-height', 1.2],
    ['line-height-px', 24],
    ['menu', []],
    ['glass', 1],
    ['_longPress', 'showOptions'],
    ['_doubleTap', 'saveTwice'],
    ['_pressDown', 'pressed'],
    ['_pressUp', 'released'],
    ['_navigate', ['route' => '/settings']],
    ['value', 'save'],
    ['sync-mode', 'live'],
    ['_change', 'changed'],
    ['options', ['save']],
    ['helper', 'Saves changes'],
    ['error', 'Unable to save'],
    ['required', true],
])->throws(InvalidArgumentException::class, 'does not support');

it('requires real booleans for disabled and loading state', function (string $attribute, mixed $value) {
    collectButton([
        'label' => 'Save',
        $attribute => $value,
    ]);
})->with([
    ['disabled', 'false'],
    ['disabled', 1],
    ['loading', 'true'],
    ['loading', 0],
])->throws(InvalidArgumentException::class, 'must be a boolean');

it('captures paired-tag text through the inherited Mobile UI Blade adapter', function () {
    $component = new ButtonComponent;
    $render = $component->render();

    $render([
        'attributes' => new ComponentAttributeBag(['_press' => 'save']),
        'slot' => new ComponentSlot("  Save\nchanges  "),
    ]);

    $registry = new CallbackRegistry;
    $tree = NativeElementCollector::collect()->toArray($registry);

    expect($tree['type'])->toBe('firstlight.button')
        ->and($tree['props']['label'])->toBe('Save changes')
        ->and($registry->resolve($tree['props']['on_press']))->toBe([
            'method' => 'save',
            'args' => [],
        ]);
});

it('precompiles paired and self-closing public tags through the NativePHP Blade seam', function () {
    NativeTagPrecompiler::setActive(true);

    $paired = (new FirstlightTagPrecompiler)(
        '<firstlight:button variant="primary" @press="save">Save</firstlight:button>'
    );
    $selfClosing = (new FirstlightTagPrecompiler)(
        '<firstlight:button label="Save" @press="save" />'
    );
    $mixed = (new FirstlightTagPrecompiler)(
        '<firstlight:button label="Unavailable" disabled /></native:column>'
        .'<native:column><firstlight:button @press="save">Save</firstlight:button>'
    );

    expect($paired)
        ->toContain('<x-native-firstlight-button')
        ->toContain('_press="save"')
        ->toContain('>Save</x-native-firstlight-button>')
        ->and($selfClosing)
        ->toContain('<x-native-firstlight-button')
        ->toContain('label="Save"')
        ->toContain('_press="save"')
        ->and($mixed)
        ->toContain('<x-native-firstlight-button label="Unavailable" disabled />')
        ->toContain('</native:column><native:column>')
        ->toContain('<x-native-firstlight-button _press="save">Save</x-native-firstlight-button>')
        ->not->toContain('NativeElementCollector::close()')
        ->not->toContain('<firstlight:button');
});

it('declares an exact nativephp mobile ui adapter mapping', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['components'])->toContain([
        'type' => 'firstlight.button',
        'element' => 'FirstlightUI\\Elements\\Button',
        'blade' => 'FirstlightUI\\Components\\Button',
        'android_renderer' => 'com.nativephp.plugins.native_ui.ui.ButtonRenderer',
        'ios_renderer' => 'NativeUIButtonRenderer',
        'self_closing' => true,
        'adapter' => [
            'package' => 'nativephp/mobile-ui',
            'type' => 'button',
        ],
    ]);
});
