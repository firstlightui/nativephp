<?php

use FirstlightUI\Elements\CodeEditor;
use FirstlightUI\FirstlightTagPrecompiler;
use FirstlightUI\Validation\FieldErrorBag;
use Illuminate\Support\MessageBag;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    FieldErrorBag::reset();
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.code-editor', CodeEditor::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    FieldErrorBag::reset();
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectCodeEditor(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.code-editor', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

it('publishes stable defaults', function () {
    expect(collectCodeEditor(['label' => 'Payload'])['props'])->toBe([
        'value' => '',
        'label' => 'Payload',
        'helper' => '',
        'error' => '',
        'required' => false,
        'disabled' => false,
        'read_only' => false,
        'language' => 'plain',
        'line_numbers' => true,
        'wrap' => true,
        'indent' => 2,
        'follow' => false,
        'min_lines' => 6,
        'max_lines' => 20,
        'sync_mode' => 'live',
        'debounce_ms' => 300,
    ]);
});

it('publishes complete code field metadata verbatim', function () {
    $value = "APP_ENV=local\r\n\tDEBUG=true\n# 😀\n";
    $tree = collectCodeEditor([
        'value' => $value,
        'label' => 'Environment',
        'helper' => 'One KEY=value per line.',
        'error' => 'APP_KEY is missing.',
        'required' => true,
        'disabled' => true,
        'read-only' => true,
        'language' => 'env',
        'line-numbers' => false,
        'wrap' => false,
        'indent' => 4,
        'follow' => true,
        'min-lines' => 8,
        'max-lines' => 24,
        'a11y-label' => 'Environment file',
        'a11y-hint' => 'Edit environment variables',
        'margin' => 8,
    ]);

    expect($tree['type'])->toBe('firstlight.code-editor')
        ->and($tree['props'])->toMatchArray([
            'value' => $value,
            'label' => 'Environment',
            'helper' => 'One KEY=value per line.',
            'error' => 'APP_KEY is missing.',
            'required' => true,
            'disabled' => true,
            'read_only' => true,
            'language' => 'env',
            'line_numbers' => false,
            'wrap' => false,
            'indent' => 4,
            'follow' => true,
            'min_lines' => 8,
            'max_lines' => 24,
            'a11y_label' => 'Environment file',
            'a11y_hint' => 'Edit environment variables',
        ])
        ->and($tree['layout']['margin'])->toBe(8.0)
        ->and($tree['style'] ?? [])->toBe([])
        ->and($tree['props'])->not->toHaveKeys(['on_press', 'on_submit', 'placeholder']);
});

it('accepts every supported language', function (string $language) {
    expect(collectCodeEditor(['label' => 'Code', 'language' => $language])['props']['language'])->toBe($language);
})->with(['plain', 'json', 'yaml', 'env']);

it('accepts camel aliases and digit strings', function () {
    $tree = collectCodeEditor([
        'label' => 'Code', 'lineNumbers' => false, 'readOnly' => true, 'minLines' => '4', 'maxLines' => '9', 'indent' => '4',
    ]);

    expect($tree['props'])->toMatchArray([
        'line_numbers' => false, 'read_only' => true, 'min_lines' => 4, 'max_lines' => 9, 'indent' => 4,
    ]);
});

it('registers change callbacks and accepted sync modes', function () {
    $registry = new CallbackRegistry;
    $tree = collectCodeEditor([
        'label' => 'Payload',
        'sync-mode' => 'debounce',
        'debounce-ms' => 500,
        '_change' => 'payloadChanged',
    ], $registry);

    expect($registry->resolve($tree['props']['on_change']))->toBe(['method' => 'payloadChanged', 'args' => []])
        ->and($tree['props']['sync_mode'])->toBe('debounce')
        ->and($tree['props']['debounce_ms'])->toBe(500)
        ->and(collectCodeEditor(['label' => 'Payload', 'syncMode' => 'lazy'])['props']['sync_mode'])->toBe('blur');
});

it('fills the error slot from the validation bag unless an error is authored', function () {
    $bag = new MessageBag(['payload' => ['The payload must be valid JSON.']]);

    $bound = FieldErrorBag::using($bag, fn () => collectCodeEditor(['label' => 'Payload', 'native:model' => 'payload']));
    $authored = FieldErrorBag::using($bag, fn () => collectCodeEditor([
        'label' => 'Payload', 'native:model' => 'payload', 'error' => 'Authored message',
    ]));

    expect($bound['props']['error'])->toBe('The payload must be valid JSON.')
        ->and($authored['props']['error'])->toBe('Authored message');
});

it('rejects non-string text attributes', function (string $attribute, mixed $value) {
    collectCodeEditor(['label' => 'Code', $attribute => $value]);
})->with([
    ['value', null], ['value', 42], ['label', []], ['helper', 3], ['error', new stdClass],
    ['language', 2], ['a11y-label', 42], ['a11y-hint', []],
])->throws(InvalidArgumentException::class, 'must be a string');

it('requires strict booleans', function (string $attribute, mixed $value) {
    collectCodeEditor(['label' => 'Code', 'read-only' => true, $attribute => $value]);
})->with([
    ['required', 'true'], ['disabled', 1], ['read-only', 'false'], ['line-numbers', 0],
    ['wrap', 'yes'], ['follow', 1],
])->throws(InvalidArgumentException::class, 'must be a boolean');

it('validates language indent line bounds follow and sync policy', function (array $attributes, string $message) {
    try {
        collectCodeEditor(['label' => 'Code', ...$attributes]);
    } catch (InvalidArgumentException $exception) {
        expect($exception->getMessage())->toContain($message);

        return;
    }

    test()->fail('Expected Code Editor validation to fail.');
})->with([
    'unknown language' => [['language' => 'ruby'], 'language must be one of [plain, json, yaml, env]'],
    'uppercase language' => [['language' => 'JSON'], 'language must be one of'],
    'indent three' => [['indent' => 3], 'indent must be 2 or 4'],
    'indent tab' => [['indent' => 'tab'], 'indent must be 2 or 4'],
    'zero min' => [['min-lines' => 0], 'positive integer'],
    'float max' => [['max-lines' => 2.5], 'positive integer'],
    'inverted range' => [['min-lines' => 9, 'max-lines' => 8], 'less than or equal'],
    'follow without read-only' => [['follow' => true], 'follow requires read-only'],
    'sync mode' => [['sync-mode' => 'focus'], 'must be one of'],
    'short debounce' => [['debounce-ms' => 49], 'at least 50 milliseconds'],
]);

it('rejects excluded input action icon secure and styling APIs', function (string $attribute, mixed $value) {
    collectCodeEditor(['label' => 'Code', $attribute => $value]);
})->with([
    ['placeholder', 'KEY=value'], ['autocorrect', true], ['autocapitalize', 'none'],
    ['keyboard', 'ascii'], ['content-type', 'none'], ['contentType', 'none'],
    ['secure', true], ['sensitive', true], ['highlight', true], ['submit-label', 'done'],
    ['clearable', true], ['leading-icon', 'code'], ['trailing-icon', 'close'],
    ['prefix', '$'], ['suffix', ';'], ['loading', true], ['single-line', true],
    ['_submit', 'save'], ['_press', 'focus'], ['_longPress', 'select'],
    ['color', '#ff0000'], ['font', 'Menlo'], ['size', 14], ['variant', 'filled'], ['theme', 'dark'],
])->throws(InvalidArgumentException::class, 'Code Editor does not support');

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
        collectCodeEditor(['label' => ' ', 'a11y-label' => '']);
        collectCodeEditor(['a11y-label' => 'Payload']);
    } finally {
        restore_error_handler();
    }

    expect($warnings)->toBe(['Firstlight Code Editor requires a visible label or a11y-label.']);
});

it('precompiles the self-closing public model tag', function () {
    NativeTagPrecompiler::setActive(true);
    $compiled = (new FirstlightTagPrecompiler)(
        '<firstlight:code-editor label="Payload" language="json" native:model.debounce.500ms="payload" />'
    );

    expect($compiled)
        ->toContain('<x-native-firstlight-code-editor')
        ->toContain('language="json"')
        ->toContain('sync-mode="debounce"')
        ->toContain('debounce-ms="500"')
        ->not->toContain('<firstlight:code-editor');
});
