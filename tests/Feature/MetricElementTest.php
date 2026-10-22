<?php

use FirstlightUI\Elements\Metric;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\NativeTagPrecompiler;

it('publishes a complete static metric with a single derived accessible name', function () {
    $metric = new Metric;
    $metric->applyAttributes(['label' => 'Events', 'value' => '5', 'detail' => 'last 10h ago', 'tone' => 'danger']);
    $tree = $metric->toArray(new CallbackRegistry);

    expect($tree['type'])->toBe('firstlight.metric')
        ->and($tree['props'])->toMatchArray([
            'label' => 'Events', 'value' => '5', 'detail' => 'last 10h ago',
            'tone' => 'danger', 'a11y_label' => 'Events: 5, last 10h ago',
        ])
        ->and($tree['props'])->not->toHaveKeys(['on_press', 'on_change', 'disabled']);
});

it('keeps zero and formatted values intact and allows an accessible override', function () {
    $metric = Metric::make('Users', '0');
    $metric->applyAttributes(['a11y-label' => 'No affected users', 'a11y-hint' => 'Since the last release']);
    $props = $metric->toArray(new CallbackRegistry)['props'];
    expect($props)->toMatchArray([
        'label' => 'Users', 'value' => '0', 'tone' => 'neutral',
        'a11y_label' => 'No affected users', 'a11y_hint' => 'Since the last release',
    ]);
    expect(Metric::make('Response', '1,234.50 ms')->toArray(new CallbackRegistry)['props']['a11y_label'])
        ->toBe('Response: 1,234.50 ms');
});

it('rejects missing malformed and interactive metric attributes', function (array $attrs) {
    $metric = new Metric;
    $metric->applyAttributes($attrs);
    $metric->toArray(new CallbackRegistry);
})->with([
    [[]],
    [['label' => 'Events', 'value' => ' ']],
    [['label' => ' ', 'value' => '5']],
    [['label' => 'Events', 'value' => 5]],
    [['label' => 'Events', 'value' => '5', 'detail' => []]],
    [['label' => 'Events', 'value' => '5', 'tone' => 'bad']],
    [['label' => 'Events', 'value' => '5', '_press' => 'open']],
    [['label' => 'Events', 'value' => '5', '_change' => 'update']],
    [['label' => 'Events', 'value' => '5', 'disabled' => true]],
    [['label' => 'Events', 'value' => '5', 'selected' => true]],
    [['label' => 'Events', 'value' => '5', 'a11y-label' => ' ']],
])->throws(InvalidArgumentException::class);

it('registers and compiles the public metric tag', function () {
    $manifest = json_decode(file_get_contents(__DIR__.'/../../nativephp.json'), true);
    $entry = array_values(array_filter($manifest['components'], fn ($entry) => $entry['type'] === 'firstlight.metric'));
    expect($entry)->toHaveCount(1);
    NativeTagPrecompiler::setActive(true);
    try {
        expect((new FirstlightTagPrecompiler)('<firstlight:metric label="Events" value="5" />'))
            ->toContain('<x-native-firstlight-metric');
    } finally {
        NativeTagPrecompiler::setActive(false);
    }
});
