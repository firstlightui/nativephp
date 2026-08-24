<?php

use FirstlightUI\Elements\AlertDialog;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.alert-dialog', AlertDialog::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectAlertDialog(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.alert-dialog', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

it('publishes the complete acknowledgement contract and dismiss callback', function () {
    $registry = new CallbackRegistry;
    $tree = collectAlertDialog([
        'visible' => true,
        'title' => 'Changes saved',
        'message' => 'Your profile was updated.',
        'action-label' => 'OK',
        '_dismiss' => 'acknowledgeSaved',
        'margin' => 8,
    ], $registry);

    expect($tree['type'])->toBe('firstlight.alert-dialog')
        ->and($tree['props'])->toMatchArray([
            'visible' => true,
            'title' => 'Changes saved',
            'message' => 'Your profile was updated.',
            'action_label' => 'OK',
        ])
        ->and($tree['layout']['margin'])->toBe(8.0)
        ->and($tree['style'] ?? [])->toBe([])
        ->and($tree)->not->toHaveKey('on_press')
        ->and($registry->resolve($tree['props']['on_dismiss']))->toBe([
            'method' => 'acknowledgeSaved',
            'args' => [],
        ]);
});

it('publishes stable closed and copy defaults', function () {
    $tree = collectAlertDialog([
        'title' => 'Saved',
        'message' => 'Your changes were published.',
        '_dismiss' => 'acknowledge',
    ]);

    expect($tree['props'])->toMatchArray([
        'visible' => false,
        'title' => 'Saved',
        'message' => 'Your changes were published.',
        'action_label' => 'OK',
    ]);
});

it('accepts the camel case action label alias', function () {
    $tree = collectAlertDialog([
        'title' => 'Saved',
        'message' => 'Your changes were published.',
        'actionLabel' => 'Got it',
        '_dismiss' => 'acknowledge',
    ]);

    expect($tree['props']['action_label'])->toBe('Got it');
});

it('supports the same strict contract through the fluent API', function () {
    $registry = new CallbackRegistry;
    $dialog = AlertDialog::make('Changes saved', 'Your profile was updated.')
        ->visible()
        ->actionLabel('OK')
        ->onDismiss('acknowledgeSaved');

    expect($dialog->toArray($registry)['props'])->toMatchArray([
        'visible' => true,
        'title' => 'Changes saved',
        'message' => 'Your profile was updated.',
        'action_label' => 'OK',
    ]);
});

it('requires non-empty dialog copy and a dismiss callback', function (array $attributes, string $message) {
    try {
        collectAlertDialog($attributes);
    } catch (InvalidArgumentException $exception) {
        expect($exception->getMessage())->toContain($message);

        return;
    }

    test()->fail('Expected Alert Dialog validation to fail.');
})->with([
    'missing title' => [[
        'message' => 'Message', '_dismiss' => 'acknowledge',
    ], 'non-empty `title`'],
    'blank message' => [[
        'title' => 'Title', 'message' => ' ', '_dismiss' => 'acknowledge',
    ], 'non-empty `message`'],
    'blank action label' => [[
        'title' => 'Title', 'message' => 'Message', 'action-label' => '', '_dismiss' => 'acknowledge',
    ], 'non-empty `action-label`'],
    'missing dismiss callback' => [[
        'title' => 'Title', 'message' => 'Message',
    ], 'requires `@dismiss`'],
]);

it('requires strict prop types', function (array $attributes, string $message) {
    collectAlertDialog([
        'title' => 'Title',
        'message' => 'Message',
        '_dismiss' => 'acknowledge',
        ...$attributes,
    ]);
})->with([
    'string visible' => [['visible' => 'false'], '`visible` must be a boolean'],
    'numeric title' => [['title' => 42], '`title` must be a string'],
    'array message' => [['message' => ['Message']], '`message` must be a string'],
    'numeric action label' => [['action-label' => 42], '`action-label` must be a string'],
])->throws(InvalidArgumentException::class);

it('rejects APIs outside the focused acknowledgement contract', function (string $attribute, mixed $value) {
    collectAlertDialog([
        'title' => 'Title',
        'message' => 'Message',
        '_dismiss' => 'acknowledge',
        $attribute => $value,
    ]);
})->with([
    ['value', true],
    ['disabled', true],
    ['loading', true],
    ['dismissible', false],
    ['a11y-label', 'Dialog'],
    ['tone', 'destructive'],
    ['confirm-label', 'OK'],
    ['cancel-label', 'Cancel'],
    ['icon', 'warning'],
    ['_press', 'acknowledge'],
    ['_change', 'changed'],
    ['_submit', 'submitted'],
    ['_longPress', 'held'],
    ['_navigate', ['route' => '/home']],
])->throws(InvalidArgumentException::class, 'does not support');

it('precompiles only the self-closing public tag through the NativePHP Blade seam', function () {
    NativeTagPrecompiler::setActive(true);

    $compiled = (new FirstlightTagPrecompiler)(
        '<firstlight:alert-dialog :visible="$showingSaved" title="Saved" message="Your changes were published." @dismiss="acknowledge" />'
    );
    $paired = (new FirstlightTagPrecompiler)(
        '<firstlight:alert-dialog title="Saved">Message</firstlight:alert-dialog>'
    );

    expect($compiled)
        ->toContain('<x-native-firstlight-alert-dialog')
        ->toContain(':visible="$showingSaved"')
        ->not->toContain('<firstlight:alert-dialog')
        ->and($paired)->toBe(
            '<firstlight:alert-dialog title="Saved">Message</firstlight:alert-dialog>'
        );
});
