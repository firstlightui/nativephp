<?php

it('declares the Firstlight package identity', function () {
    $composer = json_decode(file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['name'])->toBe('firstlightui/nativephp')
        ->and($composer['type'])->toBe('nativephp-ui-plugin')
        ->and($composer['require']['php'])->toBe('^8.4')
        ->and($composer['require']['nativephp/mobile'])->toBe('^4.0')
        ->and($composer['require']['nativephp/mobile-ui'])->toBe('^0.3')
        ->and($composer['require-dev']['pestphp/pest'])->toBe('^5.0')
        ->and($composer['autoload']['psr-4'])->toBe(['FirstlightUI\\' => 'src/'])
        ->and($manifest['name'])->toBe('firstlightui/nativephp')
        ->and($manifest['version'])->toBe('0.1.0-alpha.1')
        ->and($manifest['namespace'])->toBe('Firstlight')
        ->and($manifest['platforms'])->toBe(['android', 'ios'])
        ->and($manifest['android']['min_version'])->toBe(29)
        ->and($manifest['ios']['min_version'])->toBe('18.0');
});

it('registers the production component catalogue with canonical renderers', function () {
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
        [
            'type' => 'firstlight.status-label',
            'element' => 'FirstlightUI\\Elements\\StatusLabel',
            'blade' => 'FirstlightUI\\Components\\StatusLabel',
            'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.StatusLabelRenderer',
            'ios_renderer' => 'StatusLabelRenderer',
            'self_closing' => true,
        ],
        [
            'type' => 'firstlight.button',
            'element' => 'FirstlightUI\\Elements\\Button',
            'blade' => 'FirstlightUI\\Components\\Button',
            'android_renderer' => 'com.nativephp.plugins.native_ui.ui.ButtonRenderer',
            'ios_renderer' => 'NativeUIButtonRenderer',
            'self_closing' => true,
            'adapter' => [
                'package' => 'nativephp/mobile-ui',
                'type' => 'button',
            ],
        ],
        [
            'type' => 'firstlight.text-field',
            'element' => 'FirstlightUI\\Elements\\TextField',
            'blade' => 'FirstlightUI\\Components\\TextField',
            'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.TextFieldRenderer',
            'ios_renderer' => 'TextFieldRenderer',
            'self_closing' => true,
        ],
    ]);
});
