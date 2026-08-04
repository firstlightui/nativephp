<?php

use FirstlightUI\Elements\StatusLabel;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.status-label', StatusLabel::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectStatusLabel(array $attributes): array
{
    NativeElementCollector::leaf('firstlight.status-label', $attributes);

    return NativeElementCollector::collect()->toArray(new CallbackRegistry);
}

it('publishes display-only semantic status metadata as primitive props', function () {
    $tree = collectStatusLabel([
        'label' => 'Awaiting review',
        'tone' => 'warning',
        'a11y-label' => 'Referral status: awaiting review',
        'a11y-hint' => 'Updated by the referrals team',
    ]);

    expect($tree['type'])->toBe('firstlight.status-label')
        ->and($tree['props'])->toBe([
            'a11y_label' => 'Referral status: awaiting review',
            'a11y_hint' => 'Updated by the referrals team',
            'label' => 'Awaiting review',
            'tone' => 'warning',
        ])
        ->and(json_decode(json_encode($tree, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR))->toBe($tree);
});

it('defaults to a neutral tone without publishing state or callbacks', function () {
    $tree = collectStatusLabel(['label' => 'Draft']);

    expect($tree['props'])->toBe([
        'label' => 'Draft',
        'tone' => 'neutral',
    ])->and($tree['props'])->not->toHaveKeys([
        'value',
        'disabled',
        'loading',
        'error',
        'on_change',
        'on_press',
    ]);
});

it('accepts every documented semantic tone', function (string $tone) {
    expect(collectStatusLabel([
        'label' => ucfirst($tone),
        'tone' => $tone,
    ])['props']['tone'])->toBe($tone);
})->with(['neutral', 'info', 'success', 'warning', 'danger']);

it('requires non-empty visible status text', function (array $attributes) {
    collectStatusLabel($attributes);
})->with([
    'missing label' => [[]],
    'empty label' => [['label' => '']],
    'whitespace label' => [['label' => " \n\t "]],
    'null label' => [['label' => null]],
])->throws(InvalidArgumentException::class, 'non-empty `label`');

it('rejects unsupported tones instead of substituting a colour', function () {
    collectStatusLabel([
        'label' => 'Paused',
        'tone' => 'paused',
    ]);
})->throws(InvalidArgumentException::class, 'Unsupported Status Label tone [paused]');

it('rejects interactive and field-state props', function (string $attribute, mixed $value) {
    collectStatusLabel([
        'label' => 'Draft',
        $attribute => $value,
    ]);
})->with([
    ['value', 'draft'],
    ['disabled', true],
    ['loading', true],
    ['error', 'Invalid'],
    ['required', true],
    ['helper', 'Supporting text'],
    ['sync-mode', 'live'],
    ['_change', 'changed'],
    ['_press', 'pressed'],
])->throws(InvalidArgumentException::class, 'display-only');

it('precompiles the public Firstlight tag through the NativePHP Blade seam', function () {
    NativeTagPrecompiler::setActive(true);

    $compiled = (new FirstlightTagPrecompiler)(
        '<firstlight:status-label label="Ready" tone="success" />'
    );

    expect($compiled)
        ->toContain('<x-native-firstlight-status-label')
        ->toContain('label="Ready"')
        ->toContain('tone="success"');
});
