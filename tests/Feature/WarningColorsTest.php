<?php

use FirstlightUI\Elements\Callout;
use FirstlightUI\Elements\FeedbackItem;
use FirstlightUI\Elements\StatusLabel;
use FirstlightUI\Feedback\FeedbackRecord;
use FirstlightUI\Feedback\FeedbackTone;
use FirstlightUI\Support\WarningColors;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\UI\Theme;

afterEach(fn () => Theme::reset());

it('publishes the same independent amber fallback pair on every warning surface', function () {
    Theme::reset();
    Theme::load(['light' => ['accent' => '#FF00FF', 'on-accent' => '#000000']]);
    $expected = [
        'warning_color' => '#B45309',
        'on_warning_color' => '#FFFFFF',
        'dark_warning_color' => '#FBBF24',
        'dark_on_warning_color' => '#1C1917',
    ];
    expect(WarningColors::props())->toBe($expected);
    foreach ([StatusLabel::make('Warning')->tone('warning'), Callout::make('Warning')->tone('warning')] as $element) {
        expect($element->toArray(new CallbackRegistry)['props'])->toMatchArray($expected);
    }
    $item = FeedbackItem::fromRecord(new FeedbackRecord('warning', 'Warning', FeedbackTone::Warning, true, null, null), 1);
    expect($item->toArray(new CallbackRegistry)['props'])->toMatchArray($expected);
});

it('reads normalized custom warning tokens from the open ended effective theme', function () {
    Theme::load([
        'light' => ['warning' => 'amber-700', 'on-warning' => '#FFFFFF', 'accent' => '#FF00FF'],
        'dark' => ['warning' => '#FDE68A', 'on-warning' => '#292524'],
    ]);
    $expected = [
        'warning_color' => Theme::get('light.warning'),
        'on_warning_color' => '#FFFFFF',
        'dark_warning_color' => '#FDE68A',
        'dark_on_warning_color' => '#292524',
    ];
    expect($expected['warning_color'])->toStartWith('#')->not->toBe('#FF00FF');
    expect(WarningColors::props())->toBe($expected);
    foreach ([StatusLabel::make('Warning')->tone('warning'), Callout::make('Warning')->tone('warning')] as $element) {
        expect($element->toArray(new CallbackRegistry)['props'])->toMatchArray($expected);
    }
    $item = FeedbackItem::fromRecord(new FeedbackRecord('custom', 'Warning', FeedbackTone::Warning, true, null, null), 1);
    expect($item->toArray(new CallbackRegistry)['props'])->toMatchArray($expected);
    Theme::merge(['light' => ['warning' => '#92400E']]);
    expect(WarningColors::props()['warning_color'])->toBe('#92400E');
});

it('respects NativePHP effective derived dark warning colours', function () {
    Theme::load(['light' => ['warning' => '#B45309', 'on-warning' => '#FFFFFF']]);

    expect(WarningColors::props()['dark_warning_color'])->toBe(Theme::get('dark.warning'))
        ->and(WarningColors::props()['dark_on_warning_color'])->toBe(Theme::get('dark.on-warning'));
});
