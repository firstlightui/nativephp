<?php

use FirstlightUI\Elements\TextField;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Icon\AndroidSymbol;
use Native\Mobile\Icon\IosSymbol;
use Native\Mobile\Platform;

enum TextFieldTestIosIcon: string implements IosSymbol
{
    case Person = 'person.crop.circle';
}

enum TextFieldTestAndroidIcon: string implements AndroidSymbol
{
    case Person = 'person';

    public function variant(): string
    {
        return 'outlined';
    }
}

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    Platform::set(null);
    ElementRegistry::register('firstlight.text-field', TextField::class);
});

afterEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    Platform::set(null);
});

function collectTextField(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.text-field', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

it('publishes strict defaults and complete field metadata', function () {
    $tree = collectTextField([
        'label' => 'Email',
        'placeholder' => 'you@example.com',
        'helper' => 'Appointment updates',
        'error' => 'Enter a valid email',
        'required' => true,
        'disabled' => true,
        'read-only' => true,
        'keyboard' => 'email',
        'content-type' => 'email',
        'secure' => true,
        'autocapitalize' => 'none',
        'autocorrect' => false,
        'submit-label' => 'send',
        'a11y-label' => 'Contact email',
        'a11y-hint' => 'Used for appointment updates',
    ]);

    expect($tree['type'])->toBe('firstlight.text-field')
        ->and($tree['props'])->toMatchArray([
            'value' => '',
            'label' => 'Email',
            'placeholder' => 'you@example.com',
            'helper' => 'Appointment updates',
            'error' => 'Enter a valid email',
            'required' => true,
            'disabled' => true,
            'read_only' => true,
            'keyboard' => 'email',
            'content_type' => 'email',
            'secure' => true,
            'autocapitalize' => 'none',
            'autocorrect' => false,
            'submit_label' => 'send',
            'sync_mode' => 'live',
            'debounce_ms' => 300,
            'clearable' => false,
            'revealable' => false,
            'a11y_label' => 'Contact email',
            'a11y_hint' => 'Used for appointment updates',
        ]);
});

it('registers change submit and authored trailing press callbacks', function () {
    $registry = new CallbackRegistry;
    $tree = collectTextField([
        'value' => 'draft',
        'label' => 'Message',
        'trailing-icon' => 'arrow.right',
        'trailing-a11y-label' => 'Send draft',
        '_change' => 'draftChanged',
        '_submit' => 'submitDraft',
        '_press' => 'sendDraft',
    ], $registry);

    expect($registry->resolve($tree['props']['on_change']))->toBe(['method' => 'draftChanged', 'args' => []])
        ->and($registry->resolve($tree['props']['on_submit']))->toBe(['method' => 'submitDraft', 'args' => []])
        ->and($registry->resolve($tree['on_press']))->toBe(['method' => 'sendDraft', 'args' => []])
        ->and($tree['props']['trailing_a11y_label'])->toBe('Send draft');
});

it('uses shared icon fallbacks on an unknown platform', function () {
    $tree = TextField::make()
        ->label('Account')
        ->leadingIcon('person', TextFieldTestIosIcon::Person, TextFieldTestAndroidIcon::Person)
        ->trailingIcon('info')
        ->toArray(new CallbackRegistry);

    expect($tree['props']['leading_icon'])->toBe('person')
        ->and($tree['props']['trailing_icon'])->toBe('info')
        ->and($tree['props'])->not->toHaveKeys(['leading_icon_variant', 'trailing_icon_variant']);
});

it('uses Android overrides and preserves Material variants', function () {
    Platform::set(Platform::ANDROID);

    $tree = TextField::make()
        ->label('Account')
        ->leadingIcon('person', TextFieldTestIosIcon::Person, TextFieldTestAndroidIcon::Person)
        ->toArray(new CallbackRegistry);

    expect($tree['props']['leading_icon'])->toBe('person')
        ->and($tree['props']['leading_icon_variant'])->toBe('outlined');
});

it('accepts kebab and camel case icon overrides', function () {
    Platform::set(Platform::IOS);

    $kebab = collectTextField([
        'label' => 'Account',
        'leading-icon' => 'person',
        'leading-icon-ios' => 'person.crop.circle',
    ]);
    $camel = collectTextField([
        'label' => 'Account',
        'trailingIcon' => 'info',
        'trailingIconIos' => 'info.circle',
    ]);

    expect($kebab['props']['leading_icon'])->toBe('person.crop.circle')
        ->and($camel['props']['trailing_icon'])->toBe('info.circle');
});

it('accepts the supported sync policies and validates debounce', function () {
    expect(collectTextField(['label' => 'Name', 'sync-mode' => 'blur'])['props']['sync_mode'])->toBe('blur')
        ->and(collectTextField(['label' => 'Name', 'syncMode' => 'lazy'])['props']['sync_mode'])->toBe('blur')
        ->and(collectTextField([
            'label' => 'Name',
            'sync-mode' => 'debounce',
            'debounce-ms' => 500,
        ])['props'])->toMatchArray(['sync_mode' => 'debounce', 'debounce_ms' => 500]);

    collectTextField(['label' => 'Name', 'sync-mode' => 'debounce', 'debounce-ms' => 49]);
})->throws(InvalidArgumentException::class, 'at least 50 milliseconds');

it('rejects invalid values and enum options', function (array $attributes, string $message) {
    collectTextField(['label' => 'Field', ...$attributes]);
})->with([
    'null value' => [['value' => null], 'value must be a string'],
    'numeric value' => [['value' => 42], 'value must be a string'],
    'keyboard' => [['keyboard' => 'ascii'], 'keyboard must be one of'],
    'content type' => [['content-type' => 'address'], 'content-type must be one of'],
    'capitalization' => [['autocapitalize' => 'title'], 'autocapitalize must be one of'],
    'submit label' => [['submit-label' => 'return'], 'submit-label must be one of'],
    'sync mode' => [['sync-mode' => 'focus'], 'sync-mode must be one of'],
])->throws(InvalidArgumentException::class);

it('validates semantic trailing affordances and authored actions', function (array $attributes, string $message) {
    try {
        collectTextField(['label' => 'Field', ...$attributes]);
    } catch (InvalidArgumentException $exception) {
        expect($exception->getMessage())->toContain($message);
        throw $exception;
    }
})->with([
    'reveal requires secure' => [['revealable' => true], 'requires secure'],
    'semantic actions conflict' => [['clearable' => true, 'revealable' => true, 'secure' => true], 'mutually exclusive'],
    'clear conflicts with authored icon' => [['clearable' => true, 'trailing-icon' => 'xmark'], 'trailing slot'],
    'press requires icon' => [['_press' => 'pick'], 'trailing-icon'],
    'press requires label' => [['trailing-icon' => 'camera', '_press' => 'scan'], 'trailing-a11y-label'],
    'label without press' => [['trailing-icon' => 'camera', 'trailing-a11y-label' => 'Scan'], '@press'],
])->throws(InvalidArgumentException::class);

it('publishes semantic clear and reveal flags without consumer icon names', function () {
    $clear = collectTextField(['label' => 'Search term', 'value' => 'query', 'clearable' => true]);
    $reveal = collectTextField(['label' => 'Password', 'secure' => true, 'revealable' => true]);

    expect($clear['props']['clearable'])->toBeTrue()
        ->and($reveal['props']['revealable'])->toBeTrue()
        ->and($clear['props'])->not->toHaveKey('trailing_icon')
        ->and($reveal['props'])->not->toHaveKey('trailing_icon');
});
