<?php

use FirstlightUI\Elements\Badge;
use FirstlightUI\FirstlightTagPrecompiler;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

beforeEach(function () {
    NativeElementCollector::reset();
    ElementRegistry::reset();
    ElementRegistry::register('firstlight.badge', Badge::class);
});

afterEach(function () {
    NativeTagPrecompiler::setActive(false);
    NativeElementCollector::reset();
    ElementRegistry::reset();
});

function collectBadge(array $attributes): array
{
    NativeElementCollector::leaf('firstlight.badge', $attributes);

    return NativeElementCollector::collect()->toArray(new CallbackRegistry);
}

it('publishes a contextual count through the display-only contract', function () {
    $tree = collectBadge([
        'count' => 3, 'tone' => 'danger', 'a11y-label' => '3 unread messages',
        'a11y-hint' => 'Open the inbox to review them', 'margin' => 4,
    ]);

    expect($tree['type'])->toBe('firstlight.badge')
        ->and($tree['props'])->toBe([
            'a11y_label' => '3 unread messages',
            'a11y_hint' => 'Open the inbox to review them',
            'label' => '3', 'tone' => 'danger',
        ])
        ->and($tree['layout']['margin'])->toBe(4.0)
        ->and($tree['style'] ?? [])->toBe([])
        ->and($tree)->not->toHaveKeys(['on_press', 'on_change']);
});

it('publishes a short label with neutral defaults', function () {
    expect(collectBadge(['label' => 'New'])['props'])->toBe(['label' => 'New', 'tone' => 'neutral']);
});

it('formats count boundaries once in PHP', function (int $count, string $label) {
    expect(collectBadge(['count' => $count, 'a11y-label' => "{$count} notifications"])['props']['label'])->toBe($label);
})->with([[0, ''], [1, '1'], [99, '99'], [100, '99+'], [1000, '99+']]);

it('accepts every semantic tone', function (string $tone) {
    expect(collectBadge(['label' => 'New', 'tone' => $tone])['props']['tone'])->toBe($tone);
})->with(['neutral', 'info', 'success', 'warning', 'danger']);

it('requires exactly one valid display source', function (array $attrs) {
    collectBadge($attrs);
})->with([
    'missing source' => [[]],
    'both sources' => [['count' => 2, 'label' => 'New', 'a11y-label' => 'New items']],
    'negative count' => [['count' => -1, 'a11y-label' => 'Unread']],
    'string count' => [['count' => '3', 'a11y-label' => 'Unread']],
    'blank label' => [['label' => " \n"]],
    'numeric label' => [['label' => 3]],
    'count without context' => [['count' => 3]],
    'blank count context' => [['count' => 3, 'a11y-label' => ' ']],
])->throws(InvalidArgumentException::class);

it('requires strict metadata types and tones', function (array $attrs) {
    collectBadge(['label' => 'New', ...$attrs]);
})->with([
    [['tone' => 'accent']], [['tone' => 3]], [['a11y-label' => 3]], [['a11y-hint' => []]],
])->throws(InvalidArgumentException::class);

it('rejects interactive field and style APIs', function (string $attribute, mixed $value) {
    collectBadge(['label' => 'New', $attribute => $value]);
})->with([
    ['value', 'new'], ['disabled', true], ['loading', true], ['helper', 'Hint'],
    ['error', 'Invalid'], ['required', true], ['sync-mode', 'live'],
    ['_change', 'changed'], ['_press', 'pressed'], ['icon', 'star'],
    ['color', '#ff0000'], ['variant', 'filled'],
])->throws(InvalidArgumentException::class, 'display-only');

it('precompiles the self-closing public tag', function () {
    NativeTagPrecompiler::setActive(true);
    $compiled = (new FirstlightTagPrecompiler)('<firstlight:badge count="3" a11y-label="3 unread messages" />');

    expect($compiled)->toContain('<x-native-firstlight-badge')->not->toContain('<firstlight:badge');
});

it('declares paired package renderer identifiers', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);
    expect($manifest['components'])->toContain([
        'type' => 'firstlight.badge',
        'element' => 'FirstlightUI\\Elements\\Badge',
        'blade' => 'FirstlightUI\\Components\\Badge',
        'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.BadgeRenderer',
        'ios_renderer' => 'BadgeRenderer',
        'self_closing' => true,
    ]);
});
