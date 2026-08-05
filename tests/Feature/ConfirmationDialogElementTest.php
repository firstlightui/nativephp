<?php

use FirstlightUI\Elements\ConfirmationDialog;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.confirmation-dialog', ConfirmationDialog::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectConfirmationDialog(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.confirmation-dialog', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

it('publishes the complete confirmation contract and both standard action callbacks', function () {
    $registry = new CallbackRegistry;
    $tree = collectConfirmationDialog([
        'visible' => true,
        'title' => 'Delete appointment?',
        'message' => 'This action cannot be undone.',
        'confirm-label' => 'Delete',
        'cancel-label' => 'Keep appointment',
        'tone' => 'destructive',
        '_press' => 'deleteAppointment',
        '_dismiss' => 'cancelDelete',
        'margin' => 8,
    ], $registry);

    expect($tree['type'])->toBe('firstlight.confirmation-dialog')
        ->and($tree['props'])->toMatchArray([
            'visible' => true,
            'title' => 'Delete appointment?',
            'message' => 'This action cannot be undone.',
            'confirm_label' => 'Delete',
            'cancel_label' => 'Keep appointment',
            'tone' => 'destructive',
        ])
        ->and($tree['layout']['margin'])->toBe(8.0)
        ->and($tree['style'] ?? [])->toBe([])
        ->and($registry->resolve($tree['on_press']))->toBe([
            'method' => 'deleteAppointment',
            'args' => [],
        ])
        ->and($registry->resolve($tree['props']['on_dismiss']))->toBe([
            'method' => 'cancelDelete',
            'args' => [],
        ]);
});

it('publishes stable closed and copy defaults', function () {
    $tree = collectConfirmationDialog([
        'title' => 'Continue?',
        'message' => 'Review your changes before continuing.',
        '_press' => 'continue',
        '_dismiss' => 'cancel',
    ]);

    expect($tree['props'])->toMatchArray([
        'visible' => false,
        'title' => 'Continue?',
        'message' => 'Review your changes before continuing.',
        'confirm_label' => 'Confirm',
        'cancel_label' => 'Cancel',
        'tone' => 'default',
    ]);
});

it('accepts camel case label aliases', function () {
    $tree = collectConfirmationDialog([
        'title' => 'Continue?',
        'message' => 'Review your changes before continuing.',
        'confirmLabel' => 'Proceed',
        'cancelLabel' => 'Go back',
        '_press' => 'continue',
        '_dismiss' => 'cancel',
    ]);

    expect($tree['props']['confirm_label'])->toBe('Proceed')
        ->and($tree['props']['cancel_label'])->toBe('Go back');
});

it('supports the same strict contract through the fluent API', function () {
    $registry = new CallbackRegistry;
    $dialog = ConfirmationDialog::make('Delete appointment?', 'This action cannot be undone.')
        ->visible()
        ->confirmLabel('Delete')
        ->cancelLabel('Keep appointment')
        ->tone('destructive')
        ->onPress('deleteAppointment')
        ->onDismiss('cancelDelete');

    expect($dialog->toArray($registry)['props'])->toMatchArray([
        'visible' => true,
        'confirm_label' => 'Delete',
        'cancel_label' => 'Keep appointment',
        'tone' => 'destructive',
    ]);
});

it('requires non-empty dialog copy and both action callbacks', function (array $attributes, string $message) {
    try {
        collectConfirmationDialog($attributes);
    } catch (InvalidArgumentException $exception) {
        expect($exception->getMessage())->toContain($message);

        return;
    }

    test()->fail('Expected Confirmation Dialog validation to fail.');
})->with([
    'missing title' => [[
        'message' => 'Message', '_press' => 'confirm', '_dismiss' => 'cancel',
    ], 'non-empty `title`'],
    'blank message' => [[
        'title' => 'Title', 'message' => ' ', '_press' => 'confirm', '_dismiss' => 'cancel',
    ], 'non-empty `message`'],
    'blank confirmation label' => [[
        'title' => 'Title', 'message' => 'Message', 'confirm-label' => '', '_press' => 'confirm', '_dismiss' => 'cancel',
    ], 'non-empty `confirm-label`'],
    'blank cancel label' => [[
        'title' => 'Title', 'message' => 'Message', 'cancel-label' => '', '_press' => 'confirm', '_dismiss' => 'cancel',
    ], 'non-empty `cancel-label`'],
    'missing confirmation callback' => [[
        'title' => 'Title', 'message' => 'Message', '_dismiss' => 'cancel',
    ], 'requires `@press`'],
    'missing dismiss callback' => [[
        'title' => 'Title', 'message' => 'Message', '_press' => 'confirm',
    ], 'requires `@dismiss`'],
]);

it('requires strict prop types and stable tones', function (array $attributes, string $message) {
    collectConfirmationDialog([
        'title' => 'Title',
        'message' => 'Message',
        '_press' => 'confirm',
        '_dismiss' => 'cancel',
        ...$attributes,
    ]);
})->with([
    'string visible' => [['visible' => 'false'], '`visible` must be a boolean'],
    'numeric title' => [['title' => 42], '`title` must be a string'],
    'array message' => [['message' => ['Message']], '`message` must be a string'],
    'numeric confirm label' => [['confirm-label' => 42], '`confirm-label` must be a string'],
    'boolean cancel label' => [['cancel-label' => true], '`cancel-label` must be a string'],
    'invalid tone' => [['tone' => 'warning'], 'Unsupported Confirmation Dialog tone [warning]'],
    'numeric tone' => [['tone' => 1], '`tone` must be one of'],
])->throws(InvalidArgumentException::class);

it('rejects APIs outside the focused presentation contract', function (string $attribute, mixed $value) {
    collectConfirmationDialog([
        'title' => 'Title',
        'message' => 'Message',
        '_press' => 'confirm',
        '_dismiss' => 'cancel',
        $attribute => $value,
    ]);
})->with([
    ['value', true],
    ['disabled', true],
    ['loading', true],
    ['dismissible', false],
    ['a11y-label', 'Dialog'],
    ['variant', 'alert'],
    ['icon', 'warning'],
    ['sync-mode', 'live'],
    ['_change', 'changed'],
    ['_submit', 'submitted'],
    ['_longPress', 'held'],
    ['_navigate', ['route' => '/home']],
])->throws(InvalidArgumentException::class, 'does not support');

it('precompiles only the self-closing public tag through the NativePHP Blade seam', function () {
    NativeTagPrecompiler::setActive(true);

    $compiled = (new FirstlightTagPrecompiler)(
        '<firstlight:confirmation-dialog :visible="$confirming" title="Delete?" message="This cannot be undone." @press="delete" @dismiss="cancel" />'
    );
    $paired = (new FirstlightTagPrecompiler)(
        '<firstlight:confirmation-dialog title="Delete?">Message</firstlight:confirmation-dialog>'
    );

    expect($compiled)
        ->toContain('<x-native-firstlight-confirmation-dialog')
        ->toContain(':visible="$confirming"')
        ->not->toContain('<firstlight:confirmation-dialog')
        ->and($paired)->toBe(
            '<firstlight:confirmation-dialog title="Delete?">Message</firstlight:confirmation-dialog>'
        );
});
