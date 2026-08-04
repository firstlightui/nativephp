<?php

use FirstlightUI\Elements\DatePicker;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.date-picker', DatePicker::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectFirstlightDatePicker(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.date-picker', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

it('publishes an accepted canonical date and field metadata', function () {
    $tree = collectFirstlightDatePicker([
        'value' => '2026-07-25',
        'label' => 'Appointment date',
        'placeholder' => 'Choose a date',
        'helper' => 'Local clinic date',
        'error' => 'Choose an available date.',
        'required' => true,
        'disabled' => true,
        'a11y-label' => 'Clinic appointment date',
        'a11y-hint' => 'Opens a calendar',
    ]);

    expect($tree['type'])->toBe('firstlight.date-picker')
        ->and($tree['props'])->toMatchArray([
            'has_value' => true,
            'value' => '2026-07-25',
            'label' => 'Appointment date',
            'placeholder' => 'Choose a date',
            'helper' => 'Local clinic date',
            'error' => 'Choose an available date.',
            'required' => true,
            'disabled' => true,
            'a11y_label' => 'Clinic appointment date',
            'a11y_hint' => 'Opens a calendar',
        ]);
});

it('publishes null as an explicit wire pair', function () {
    $tree = collectFirstlightDatePicker([
        'value' => null,
        'label' => 'Appointment date',
        'placeholder' => 'Choose a date',
    ]);

    expect($tree['props'])->toMatchArray([
        'has_value' => false,
        'value' => '',
        'label' => 'Appointment date',
        'placeholder' => 'Choose a date',
    ]);
});

it('defaults an omitted value to the explicit null wire pair', function () {
    expect(collectFirstlightDatePicker([
        'label' => 'Appointment date',
    ])['props'])->toMatchArray([
        'has_value' => false,
        'value' => '',
    ]);
});

it('accepts exact proleptic Gregorian boundaries and leap days', function (string $value) {
    expect(collectFirstlightDatePicker([
        'value' => $value,
        'label' => 'Appointment date',
    ])['props']['value'])->toBe($value);
})->with(['0001-01-01', '2000-02-29', '2024-02-29', '9999-12-31']);

it('rejects noncanonical impossible padded or coerced date values', function (mixed $value) {
    collectFirstlightDatePicker([
        'value' => $value,
        'label' => 'Appointment date',
    ]);
})->with([
    'empty string' => [''],
    'leading whitespace' => [' 2026-07-25'],
    'trailing whitespace' => ['2026-07-25 '],
    'unpadded month' => ['2026-7-25'],
    'unpadded day' => ['2026-07-5'],
    'timestamp' => ['2026-07-25T14:30:00Z'],
    'year zero' => ['0000-01-01'],
    'non leap day' => ['2025-02-29'],
    'invalid month' => ['2026-13-01'],
    'invalid day' => ['2026-04-31'],
    'DateTimeInterface' => [new DateTimeImmutable('2026-07-25')],
    'integer' => [20260725],
    'boolean' => [false],
    'array' => [['2026-07-25']],
])->throws(InvalidArgumentException::class, 'canonical `YYYY-MM-DD` string or null');

it('publishes inclusive min and max bounds', function () {
    $tree = collectFirstlightDatePicker([
        'value' => '2026-07-25',
        'min' => '2026-07-25',
        'max' => '2026-07-25',
        'label' => 'Appointment date',
    ]);

    expect($tree['props'])->toMatchArray([
        'has_value' => true,
        'value' => '2026-07-25',
        'min' => '2026-07-25',
        'max' => '2026-07-25',
    ]);
});

it('omits null bounds while retaining an explicit null value', function () {
    $props = collectFirstlightDatePicker([
        'value' => null,
        'min' => null,
        'max' => null,
        'label' => 'Appointment date',
    ])['props'];

    expect($props)->toMatchArray(['has_value' => false, 'value' => ''])
        ->not->toHaveKeys(['min', 'max']);
});

it('rejects malformed reversed and violated bounds', function (array $attributes, string $message) {
    expect(fn () => collectFirstlightDatePicker([
        'value' => '2026-07-25',
        'label' => 'Appointment date',
        ...$attributes,
    ]))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'malformed min' => [['min' => '2026-7-01'], '`min`'],
    'malformed max' => [['max' => 'tomorrow'], '`max`'],
    'reversed' => [['min' => '2026-08-01', 'max' => '2026-07-01'], 'must not be after'],
    'below min' => [['min' => '2026-07-26'], 'before `min`'],
    'above max' => [['max' => '2026-07-24'], 'after `max`'],
]);

it('passes validated display locale and IANA timezone without shifting the wire date', function (string $locale) {
    $props = collectFirstlightDatePicker([
        'value' => '2026-07-25',
        'locale' => $locale,
        'timezone' => 'Australia/Sydney',
        'label' => 'Appointment date',
    ])['props'];

    expect($props)->toMatchArray([
        'has_value' => true,
        'value' => '2026-07-25',
        'locale' => $locale,
        'timezone' => 'Australia/Sydney',
    ]);
})->with(['en-AU', 'de-DE', 'zh-Hant-TW']);

it('rejects blank malformed or non BCP-47 locales', function (mixed $locale) {
    collectFirstlightDatePicker([
        'value' => null,
        'locale' => $locale,
        'label' => 'Appointment date',
    ]);
})->with([
    'blank' => [''],
    'whitespace' => [' '],
    'underscore' => ['en_AU'],
    'double separator' => ['en--AU'],
    'bad characters' => ['en-@@'],
    'numeric primary language' => ['419'],
    'non string' => [42],
])->throws(InvalidArgumentException::class, 'valid non-empty BCP-47');

it('rejects blank offset and unknown timezones', function (mixed $timezone) {
    collectFirstlightDatePicker([
        'value' => null,
        'timezone' => $timezone,
        'label' => 'Appointment date',
    ]);
})->with([
    'blank' => [''],
    'whitespace' => [' '],
    'offset' => ['+10:00'],
    'unknown' => ['Mars/Olympus_Mons'],
    'non string' => [42],
])->throws(InvalidArgumentException::class, 'valid IANA timezone');

it('registers a single standard change callback', function () {
    $registry = new CallbackRegistry;
    $tree = collectFirstlightDatePicker([
        'value' => null,
        'label' => 'Appointment date',
        '_change' => 'dateChosen',
    ], $registry);

    expect($tree['props']['on_change'])->toBeInt()
        ->and($registry->resolve($tree['props']['on_change']))->toBe([
            'method' => 'dateChosen',
            'args' => [],
        ]);
});

it('accepts live sync mode without renderer timing props', function () {
    $tree = collectFirstlightDatePicker([
        'value' => null,
        'label' => 'Appointment date',
        'sync-mode' => 'live',
    ]);

    expect($tree['props'])->not->toHaveKeys(['sync_mode', 'debounce_ms']);
});

it('rejects deferred sync modes', function (string $mode) {
    collectFirstlightDatePicker([
        'value' => null,
        'label' => 'Appointment date',
        'sync-mode' => $mode,
    ]);
})->with(['blur', 'debounce'])->throws(InvalidArgumentException::class, 'supports only native:model or native:model.live');

it('requires real booleans for field state', function (string $attribute, mixed $value) {
    collectFirstlightDatePicker([
        'value' => null,
        'label' => 'Appointment date',
        $attribute => $value,
    ]);
})->with([
    ['required', 'true'],
    ['required', 1],
    ['disabled', 'false'],
    ['disabled', 0],
])->throws(InvalidArgumentException::class, 'must be a boolean');

it('warns in development when visible and explicit labels are blank', function () {
    set_error_handler(function (int $severity, string $message): bool {
        expect($severity)->toBe(E_USER_WARNING)
            ->and($message)->toContain('requires a visible `label` or `a11y-label`');

        return true;
    });

    try {
        collectFirstlightDatePicker(['value' => null]);
    } finally {
        restore_error_handler();
    }
});

it('rejects excluded modes styles affordances and events', function (string $attribute, mixed $value) {
    collectFirstlightDatePicker([
        'value' => null,
        'label' => 'Appointment date',
        $attribute => $value,
    ]);
})->with([
    ['mode', 'datetime'],
    ['picker-style', 'inline'],
    ['display-style', 'wheel'],
    ['hour-format', '24'],
    ['clearable', true],
    ['title', 'Choose date'],
    ['confirm-label', 'Done'],
    ['cancel-label', 'Cancel'],
    ['icon', 'calendar'],
    ['icon-ios', 'calendar'],
    ['icon-android', 'calendar_month'],
    ['range', true],
    ['loading', true],
    ['tone', 'info'],
    ['variant', 'compact'],
    ['size', 'lg'],
    ['_press', 'pressed'],
    ['_submit', 'submitted'],
    ['_clear', 'cleared'],
])->throws(InvalidArgumentException::class, 'does not support');

it('retains external layout classes and drops component styling', function () {
    $tree = collectFirstlightDatePicker([
        'value' => null,
        'label' => 'Appointment date',
        'width' => 'fill',
        'padding' => 12,
        'color' => '#ff0000',
    ]);

    expect($tree['layout']['width'])->toBe('fill')
        ->and($tree['layout'])->not->toHaveKey('padding')
        ->and($tree['style'] ?? [])->toBe([]);
});

it('precompiles only the exact self closing public tag', function () {
    NativeTagPrecompiler::setActive(true);

    $compiled = (new FirstlightTagPrecompiler)(
        '<firstlight:date-picker native:model="appointmentDate" label="Appointment date" />'
    );

    expect($compiled)->toContain('<x-native-firstlight-date-picker')
        ->toContain(':value="$appointmentDate"')
        ->toContain('_change="__syncProperty(\'appointmentDate\')"')
        ->toContain('sync-mode="live"')
        ->not->toContain('<firstlight:date-picker');
});

it('declares exact paired renderer manifest identifiers', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['components'])->toContain([
        'type' => 'firstlight.date-picker',
        'element' => 'FirstlightUI\\Elements\\DatePicker',
        'blade' => 'FirstlightUI\\Components\\DatePicker',
        'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.FirstlightDatePickerRenderer',
        'ios_renderer' => 'DatePickerRenderer',
        'self_closing' => true,
    ]);
});
