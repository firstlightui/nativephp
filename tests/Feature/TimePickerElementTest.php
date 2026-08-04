<?php

use FirstlightUI\Elements\TimePicker;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.time-picker', TimePicker::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectFirstlightTimePicker(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.time-picker', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

it('publishes canonical time and complete field metadata', function () {
    $tree = collectFirstlightTimePicker([
        'value' => '14:30',
        'label' => 'Appointment time',
        'placeholder' => 'Choose a time',
        'helper' => 'Clinic local time',
        'error' => 'Choose another time.',
        'required' => true,
        'disabled' => true,
        'a11y-label' => 'Clinic appointment time',
        'a11y-hint' => 'Opens a time picker',
    ]);

    expect($tree['type'])->toBe('firstlight.time-picker')
        ->and($tree['props'])->toMatchArray([
            'has_value' => true,
            'value' => '14:30',
            'label' => 'Appointment time',
            'placeholder' => 'Choose a time',
            'helper' => 'Clinic local time',
            'error' => 'Choose another time.',
            'required' => true,
            'disabled' => true,
            'a11y_label' => 'Clinic appointment time',
            'a11y_hint' => 'Opens a time picker',
        ]);
});

it('publishes null and omission as the explicit null pair', function (?string $value, bool $authored) {
    $attributes = ['label' => 'Appointment time'];
    if ($authored) {
        $attributes['value'] = $value;
    }

    expect(collectFirstlightTimePicker($attributes)['props'])->toMatchArray([
        'has_value' => false,
        'value' => '',
    ]);
})->with([
    'null' => [null, true],
    'omitted' => [null, false],
]);

it('accepts exact minute boundaries', function (string $value) {
    expect(collectFirstlightTimePicker([
        'value' => $value,
        'label' => 'Appointment time',
    ])['props']['value'])->toBe($value);
})->with(['00:00', '07:05', '12:30', '23:59']);

it('rejects noncanonical padded coerced or second-bearing values', function (mixed $value) {
    collectFirstlightTimePicker(['value' => $value, 'label' => 'Appointment time']);
})->with([
    'empty' => [''],
    'leading whitespace' => [' 07:05'],
    'trailing whitespace' => ['07:05 '],
    'unpadded hour' => ['7:05'],
    'unpadded minute' => ['07:5'],
    'seconds' => ['07:05:00'],
    '24 hour' => ['24:00'],
    'bad minute' => ['07:60'],
    'timestamp' => ['2026-08-04T07:05:00'],
    'integer' => [705],
    'boolean' => [false],
    'date object' => [new DateTimeImmutable('07:05')],
])->throws(InvalidArgumentException::class, 'canonical `HH:mm` string or null');

it('passes locale and timezone for display and seed without shifting the wire value', function (string $locale) {
    expect(collectFirstlightTimePicker([
        'value' => '14:30',
        'locale' => $locale,
        'timezone' => 'Australia/Sydney',
        'label' => 'Appointment time',
    ])['props'])->toMatchArray([
        'value' => '14:30',
        'locale' => $locale,
        'timezone' => 'Australia/Sydney',
    ]);
})->with(['en-AU', 'de-DE', 'zh-Hant-TW']);

it('rejects malformed locales', function (mixed $locale) {
    collectFirstlightTimePicker(['locale' => $locale, 'label' => 'Appointment time']);
})->with(['', ' ', 'en_AU', 'en--AU', 'en-@@', 42])
    ->throws(InvalidArgumentException::class, 'valid non-empty BCP-47');

it('rejects non IANA timezones', function (mixed $timezone) {
    collectFirstlightTimePicker(['timezone' => $timezone, 'label' => 'Appointment time']);
})->with(['', ' ', '+10:00', 'Mars/Olympus_Mons', 42])
    ->throws(InvalidArgumentException::class, 'valid IANA timezone');

it('registers a single standard change callback', function () {
    $registry = new CallbackRegistry;
    $tree = collectFirstlightTimePicker([
        'value' => null,
        'label' => 'Appointment time',
        '_change' => 'timeChosen',
    ], $registry);

    expect($registry->resolve($tree['props']['on_change']))->toBe([
        'method' => 'timeChosen',
        'args' => [],
    ]);
});

it('accepts only live synchronization', function (string $mode, bool $valid) {
    $action = fn () => collectFirstlightTimePicker([
        'label' => 'Appointment time',
        'sync-mode' => $mode,
    ]);

    $valid
        ? expect($action()['props'])->not->toHaveKeys(['sync_mode', 'debounce_ms'])
        : expect($action)->toThrow(InvalidArgumentException::class, 'supports only native:model or native:model.live');
})->with([
    'live' => ['live', true],
    'blur' => ['blur', false],
    'debounce' => ['debounce', false],
]);

it('requires real booleans for field state', function (string $attribute, mixed $value) {
    collectFirstlightTimePicker(['label' => 'Appointment time', $attribute => $value]);
})->with([
    ['required', 'true'],
    ['required', 1],
    ['disabled', 'false'],
    ['disabled', 0],
])->throws(InvalidArgumentException::class, 'must be a boolean');

it('requires strings for field and accessibility copy', function (string $attribute, mixed $value) {
    collectFirstlightTimePicker(['label' => 'Appointment time', $attribute => $value]);
})->with([
    ['label', 42],
    ['placeholder', false],
    ['helper', ['text']],
    ['error', 1.5],
    ['a11y-label', 42],
    ['a11y-hint', false],
])->throws(InvalidArgumentException::class, 'must be a string');

it('warns in development when visible and explicit labels are blank', function () {
    set_error_handler(function (int $severity, string $message): bool {
        expect($severity)->toBe(E_USER_WARNING)
            ->and($message)->toContain('requires a visible `label` or `a11y-label`');

        return true;
    });

    try {
        collectFirstlightTimePicker(['value' => null]);
    } finally {
        restore_error_handler();
    }
});

it('rejects excluded time ranges modes styles affordances and events', function (string $attribute, mixed $value) {
    collectFirstlightTimePicker(['label' => 'Appointment time', $attribute => $value]);
})->with([
    ['hour-format', '24'], ['min', '09:00'], ['max', '17:00'], ['range', true],
    ['step', 15], ['mode', 'time'], ['picker-style', 'inline'],
    ['display-style', 'wheel'], ['clearable', true], ['read-only', true],
    ['title', 'Choose time'], ['confirm-label', 'Done'], ['cancel-label', 'Cancel'],
    ['icon', 'clock'], ['loading', true], ['tone', 'info'], ['variant', 'compact'],
    ['size', 'lg'], ['_press', 'pressed'], ['_submit', 'submitted'], ['_clear', 'cleared'],
])->throws(InvalidArgumentException::class, 'does not support');

it('retains external layout and drops component styling and padding', function () {
    $tree = collectFirstlightTimePicker([
        'label' => 'Appointment time',
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
        '<firstlight:time-picker native:model="appointmentTime" label="Appointment time" />'
    );

    expect($compiled)->toContain('<x-native-firstlight-time-picker')
        ->toContain(':value="$appointmentTime"')
        ->toContain('_change="__syncProperty(\'appointmentTime\')"')
        ->toContain('sync-mode="live"')
        ->not->toContain('<firstlight:time-picker');
});

it('declares exact paired renderer manifest identifiers', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['components'])->toContain([
        'type' => 'firstlight.time-picker',
        'element' => 'FirstlightUI\\Elements\\TimePicker',
        'blade' => 'FirstlightUI\\Components\\TimePicker',
        'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.TimePickerRenderer',
        'ios_renderer' => 'TimePickerRenderer',
        'self_closing' => true,
    ]);
});
