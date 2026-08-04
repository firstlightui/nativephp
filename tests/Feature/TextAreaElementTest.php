<?php

use FirstlightUI\Elements\TextArea;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.text-area', TextArea::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectTextArea(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.text-area', $attributes);
    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

it('publishes strict defaults and complete multiline field metadata', function () {
    $tree = collectTextArea([
        'value' => "History\nObservation",
        'label' => 'Clinical notes',
        'placeholder' => 'Add notes',
        'helper' => 'Relevant details only',
        'error' => 'Notes are too short',
        'required' => true,
        'disabled' => true,
        'read-only' => true,
        'min-lines' => 4,
        'max-lines' => 10,
        'autocapitalize' => 'sentences',
        'autocorrect' => false,
        'a11y-label' => 'Appointment notes',
        'a11y-hint' => 'Enter multiple lines',
        'margin' => 8,
    ]);

    expect($tree['type'])->toBe('firstlight.text-area')
        ->and($tree['props'])->toMatchArray([
            'value' => "History\nObservation",
            'label' => 'Clinical notes',
            'placeholder' => 'Add notes',
            'helper' => 'Relevant details only',
            'error' => 'Notes are too short',
            'required' => true,
            'disabled' => true,
            'read_only' => true,
            'min_lines' => 4,
            'max_lines' => 10,
            'autocapitalize' => 'sentences',
            'autocorrect_policy' => 'disabled',
            'sync_mode' => 'live',
            'debounce_ms' => 300,
            'a11y_label' => 'Appointment notes',
            'a11y_hint' => 'Enter multiple lines',
        ])
        ->and($tree['layout']['margin'])->toBe(8.0)
        ->and($tree['style'] ?? [])->toBe([])
        ->and($tree)->not->toHaveKeys(['on_press', 'on_submit']);
});

it('publishes stable empty defaults', function () {
    expect(collectTextArea(['label' => 'Notes'])['props'])->toBe([
        'value' => '',
        'label' => 'Notes',
        'placeholder' => '',
        'helper' => '',
        'error' => '',
        'required' => false,
        'disabled' => false,
        'read_only' => false,
        'min_lines' => 3,
        'max_lines' => 8,
        'autocapitalize' => '',
        'autocorrect_policy' => 'default',
        'sync_mode' => 'live',
        'debounce_ms' => 300,
    ]);
});

it('registers change callbacks and accepted sync modes', function () {
    $registry = new CallbackRegistry;
    $tree = collectTextArea([
        'label' => 'Notes',
        'sync-mode' => 'debounce',
        'debounce-ms' => 500,
        '_change' => 'notesChanged',
    ], $registry);

    expect($registry->resolve($tree['props']['on_change']))->toBe(['method' => 'notesChanged', 'args' => []])
        ->and($tree['props']['sync_mode'])->toBe('debounce')
        ->and($tree['props']['debounce_ms'])->toBe(500)
        ->and(collectTextArea(['label' => 'Notes', 'syncMode' => 'lazy'])['props']['sync_mode'])->toBe('blur');
});

it('accepts camel aliases for line and field attributes', function () {
    $tree = collectTextArea([
        'label' => 'Notes', 'minLines' => '4', 'maxLines' => '12', 'readOnly' => true,
    ]);
    expect($tree['props'])->toMatchArray(['min_lines' => 4, 'max_lines' => 12, 'read_only' => true]);
});

it('rejects non-string values and metadata', function (string $attribute, mixed $value) {
    collectTextArea(['label' => 'Notes', $attribute => $value]);
})->with([
    ['value', null], ['value', 42], ['label', []], ['placeholder', false],
    ['helper', 3], ['error', new stdClass], ['a11y-label', 42], ['a11y-hint', []],
])->throws(InvalidArgumentException::class, 'must be a string');

it('requires strict booleans', function (string $attribute, mixed $value) {
    collectTextArea(['label' => 'Notes', $attribute => $value]);
})->with([
    ['required', 'true'], ['disabled', 1], ['read-only', 'false'], ['autocorrect', 0],
])->throws(InvalidArgumentException::class, 'must be a boolean');

it('validates line bounds capitalization and sync policy', function (array $attributes, string $message) {
    try {
        collectTextArea(['label' => 'Notes', ...$attributes]);
    } catch (InvalidArgumentException $exception) {
        expect($exception->getMessage())->toContain($message);
        return;
    }
    test()->fail('Expected Text Area validation to fail.');
})->with([
    'zero min' => [['min-lines' => 0], 'positive integer'],
    'negative max' => [['max-lines' => -1], 'positive integer'],
    'float min' => [['min-lines' => 2.5], 'positive integer'],
    'inverted range' => [['min-lines' => 9, 'max-lines' => 8], 'less than or equal'],
    'capitalization' => [['autocapitalize' => 'title'], 'must be one of'],
    'sync mode' => [['sync-mode' => 'focus'], 'must be one of'],
    'short debounce' => [['debounce-ms' => 49], 'at least 50 milliseconds'],
]);

it('rejects single-line action icon secure and styling APIs', function (string $attribute, mixed $value) {
    collectTextArea(['label' => 'Notes', $attribute => $value]);
})->with([
    ['secure', true], ['keyboard', 'email'], ['content-type', 'name'],
    ['submit-label', 'done'], ['clearable', true], ['revealable', true],
    ['leading-icon', 'note'], ['trailing-icon', 'close'], ['prefix', 'Note:'],
    ['suffix', 'end'], ['loading', true], ['single-line', true], ['multiline', true],
    ['_submit', 'save'], ['_press', 'focus'], ['_longPress', 'select'],
    ['color', '#ff0000'], ['background', '#ffffff'], ['font', 'Custom'], ['variant', 'filled'],
])->throws(InvalidArgumentException::class, 'does not support');

it('warns when visible and accessible labels are blank during development', function () {
    $warnings = [];
    set_error_handler(function (int $severity, string $message) use (&$warnings): bool {
        if ($severity === E_USER_WARNING) {
            $warnings[] = $message;
            return true;
        }
        return false;
    });

    try {
        collectTextArea(['label' => ' ', 'a11y-label' => '']);
    } finally {
        restore_error_handler();
    }

    expect($warnings)->toBe(['Firstlight Text Area requires a visible label or a11y-label.']);
});

it('precompiles the self-closing public model tag', function () {
    NativeTagPrecompiler::setActive(true);
    $compiled = (new FirstlightTagPrecompiler)(
        '<firstlight:text-area label="Notes" native:model.debounce.500ms="notes" min-lines="4" />'
    );

    expect($compiled)
        ->toContain('<x-native-firstlight-text-area')
        ->toContain('sync-mode="debounce"')
        ->toContain('debounce-ms="500"')
        ->not->toContain('<firstlight:text-area');
});

it('declares paired package renderer identifiers', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);
    expect($manifest['components'])->toContain([
        'type' => 'firstlight.text-area',
        'element' => 'FirstlightUI\\Elements\\TextArea',
        'blade' => 'FirstlightUI\\Components\\TextArea',
        'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.TextAreaRenderer',
        'ios_renderer' => 'TextAreaRenderer',
        'self_closing' => true,
    ]);
});
