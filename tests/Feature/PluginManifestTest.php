<?php

it('declares the Firstlight package identity', function () {
    $composer = json_decode(file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['name'])->toBe('firstlightui/firstlight-ui')
        ->and($composer['type'])->toBe('nativephp-ui-plugin')
        ->and($composer['require']['php'])->toBe('^8.4')
        ->and($composer['require']['nativephp/mobile'])->toBe('^4.0')
        ->and($composer['require']['nativephp/mobile-ui'])->toBe('^0.3')
        ->and($composer['require-dev']['pestphp/pest'])->toBe('^5.0')
        ->and($composer['autoload']['psr-4'])->toBe(['FirstlightUI\\' => 'src/'])
        ->and($manifest['name'])->toBe('firstlightui/firstlight-ui')
        ->and($manifest['version'])->toBe('0.1.0-alpha.1')
        ->and($manifest['namespace'])->toBe('Firstlight')
        ->and($manifest['platforms'])->toBe(['android', 'ios'])
        ->and($manifest['android']['min_version'])->toBe(29)
        ->and($manifest['ios']['min_version'])->toBe('18.0');
});

it('contains only the production Segmented component', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['components'])->toBe([
        [
            'type' => 'firstlight.segmented',
            'element' => 'FirstlightUI\\Elements\\Segmented',
            'blade' => 'FirstlightUI\\Components\\Segmented',
            'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.SegmentedRenderer',
            'ios_renderer' => 'SegmentedRenderer',
            'self_closing' => true,
        ],
    ]);
});
