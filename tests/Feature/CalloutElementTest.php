<?php

use FirstlightUI\Elements\Callout;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.callout', Callout::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectCallout(array $attributes, ?CallbackRegistry $registry = null): array
{
    NativeElementCollector::leaf('firstlight.callout', $attributes);

    return NativeElementCollector::collect()->toArray($registry ?? new CallbackRegistry);
}

it('publishes a persistent semantic message and optional standard action', function () {
    $registry = new CallbackRegistry;
    $tree = collectCallout([
        'message' => 'Your changes have not been submitted.',
        'tone' => 'warning',
        'action-label' => 'Review changes',
        'a11y-label' => 'Submission warning: changes have not been submitted',
        'a11y-hint' => 'Review the form before continuing',
        '_press' => 'reviewChanges',
        'margin' => 8,
    ], $registry);

    expect($tree['type'])->toBe('firstlight.callout')
        ->and($tree['props'])->toBe([
            'a11y_label' => 'Submission warning: changes have not been submitted',
            'a11y_hint' => 'Review the form before continuing',
            'message' => 'Your changes have not been submitted.',
            'tone' => 'warning',
            'action_label' => 'Review changes',
        ])
        ->and($tree['layout']['margin'])->toBe(8.0)
        ->and($registry->resolve($tree['on_press']))->toBe([
            'method' => 'reviewChanges',
            'args' => [],
        ]);
});

it('defaults to informational tone and publishes no callback for a message-only callout', function () {
    $tree = collectCallout(['message' => 'Appointments sync every five minutes.']);

    expect($tree['props'])->toBe([
        'message' => 'Appointments sync every five minutes.',
        'tone' => 'info',
        'action_label' => '',
    ])->and($tree)->not->toHaveKeys(['on_press', 'on_change'])
        ->and($tree['props'])->not->toHaveKeys(['value', 'disabled', 'loading']);
});

it('accepts every documented semantic tone', function (string $tone) {
    expect(collectCallout([
        'message' => ucfirst($tone).' message',
        'tone' => $tone,
    ])['props']['tone'])->toBe($tone);
})->with(['neutral', 'info', 'success', 'warning', 'danger']);

it('supports the same strict contract through the fluent API', function () {
    $registry = new CallbackRegistry;
    $callout = Callout::make('A newer version is available.')
        ->tone('info')
        ->actionLabel('Update now')
        ->onPress('updateNow');

    $tree = $callout->toArray($registry);

    expect($tree['props'])->toMatchArray([
        'message' => 'A newer version is available.',
        'tone' => 'info',
        'action_label' => 'Update now',
    ])->and($registry->resolve($tree['on_press'])['method'])->toBe('updateNow');
});

it('requires non-empty message copy', function (array $attributes) {
    collectCallout($attributes);
})->with([
    'missing message' => [[]],
    'empty message' => [['message' => '']],
    'whitespace message' => [['message' => " \n\t "]],
])->throws(InvalidArgumentException::class, 'non-empty `message`');

it('requires the optional action label and press handler together', function (array $attributes) {
    collectCallout($attributes);
})->with([
    'label only' => [['message' => 'Message', 'action-label' => 'Retry']],
    'handler only' => [['message' => 'Message', '_press' => 'retry']],
    'blank label with handler' => [['message' => 'Message', 'action-label' => ' ', '_press' => 'retry']],
])->throws(InvalidArgumentException::class, '`action-label` and `@press` together');

it('requires strict message, action label, and tone types', function (array $attributes, string $message) {
    try {
        collectCallout(['message' => 'Message', ...$attributes]);
    } catch (InvalidArgumentException $exception) {
        expect($exception->getMessage())->toContain($message);

        return;
    }

    test()->fail('Expected Callout validation to fail.');
})->with([
    'numeric message' => [['message' => 42], '`message` must be a string'],
    'array action label' => [['action-label' => ['Retry']], '`action-label` must be a string'],
    'numeric tone' => [['tone' => 1], '`tone` must be one of'],
    'unknown tone' => [['tone' => 'critical'], 'Unsupported Callout tone [critical]'],
]);

it('rejects state, dismissal, visual escape, and non-standard event APIs', function (string $attribute, mixed $value) {
    collectCallout([
        'message' => 'Message',
        $attribute => $value,
    ]);
})->with([
    ['value', true],
    ['native:model', 'status'],
    ['disabled', true],
    ['loading', true],
    ['dismissible', true],
    ['title', 'Heading'],
    ['icon', 'warning'],
    ['helper', 'Supporting text'],
    ['error', 'Invalid'],
    ['sync-mode', 'live'],
    ['_change', 'changed'],
    ['_submit', 'submitted'],
    ['_longPress', 'held'],
    ['_navigate', ['route' => '/home']],
])->throws(InvalidArgumentException::class, 'does not support');

it('precompiles only the self-closing public Firstlight tag', function () {
    NativeTagPrecompiler::setActive(true);

    $compiled = (new FirstlightTagPrecompiler)(
        '<firstlight:callout message="Please review" action-label="Review" @press="review" />'
    );
    $paired = (new FirstlightTagPrecompiler)(
        '<firstlight:callout tone="warning">Please review</firstlight:callout>'
    );

    expect($compiled)
        ->toContain('<x-native-firstlight-callout')
        ->toContain('action-label="Review"')
        ->not->toContain('<firstlight:callout')
        ->and($paired)->toBe(
            '<firstlight:callout tone="warning">Please review</firstlight:callout>'
        );
});
