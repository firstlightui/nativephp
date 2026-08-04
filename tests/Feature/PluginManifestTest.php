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
            'type' => 'firstlight.choice-group',
            'element' => 'FirstlightUI\\Elements\\ChoiceGroup',
            'blade' => 'FirstlightUI\\Components\\ChoiceGroup',
            'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.ChoiceGroupRenderer',
            'ios_renderer' => 'ChoiceGroupRenderer',
            'self_closing' => true,
        ],
        [
            'type' => 'firstlight.pill-group',
            'element' => 'FirstlightUI\\Elements\\PillGroup',
            'blade' => 'FirstlightUI\\Components\\PillGroup',
            'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.PillGroupRenderer',
            'ios_renderer' => 'PillGroupRenderer',
            'self_closing' => true,
        ],
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
            'type' => 'firstlight.badge',
            'element' => 'FirstlightUI\\Elements\\Badge',
            'blade' => 'FirstlightUI\\Components\\Badge',
            'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.FirstlightBadgeRenderer',
            'ios_renderer' => 'BadgeRenderer',
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
            'type' => 'firstlight.icon-button',
            'element' => 'FirstlightUI\\Elements\\IconButton',
            'blade' => 'FirstlightUI\\Components\\IconButton',
            'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.IconButtonRenderer',
            'ios_renderer' => 'IconButtonRenderer',
            'self_closing' => true,
        ],
        [
            'type' => 'firstlight.progress',
            'element' => 'FirstlightUI\\Elements\\Progress',
            'blade' => 'FirstlightUI\\Components\\Progress',
            'android_renderer' => 'com.nativephp.plugins.native_ui.ui.ProgressBarRenderer',
            'ios_renderer' => 'NativeUIProgressBarRenderer',
            'self_closing' => true,
            'adapter' => [
                'package' => 'nativephp/mobile-ui',
                'type' => 'progress_bar',
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
        [
            'type' => 'firstlight.search-field',
            'element' => 'FirstlightUI\\Elements\\SearchField',
            'blade' => 'FirstlightUI\\Components\\SearchField',
            'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.SearchFieldRenderer',
            'ios_renderer' => 'SearchFieldRenderer',
            'self_closing' => true,
        ],
        [
            'type' => 'firstlight.text-area',
            'element' => 'FirstlightUI\\Elements\\TextArea',
            'blade' => 'FirstlightUI\\Components\\TextArea',
            'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.TextAreaRenderer',
            'ios_renderer' => 'TextAreaRenderer',
            'self_closing' => true,
        ],
        [
            'type' => 'firstlight.date-picker',
            'element' => 'FirstlightUI\\Elements\\DatePicker',
            'blade' => 'FirstlightUI\\Components\\DatePicker',
            'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.FirstlightDatePickerRenderer',
            'ios_renderer' => 'DatePickerRenderer',
            'self_closing' => true,
        ],
        [
            'type' => 'firstlight.time-picker',
            'element' => 'FirstlightUI\\Elements\\TimePicker',
            'blade' => 'FirstlightUI\\Components\\TimePicker',
            'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.TimePickerRenderer',
            'ios_renderer' => 'TimePickerRenderer',
            'self_closing' => true,
        ],
        [
            'type' => 'firstlight.select',
            'element' => 'FirstlightUI\\Elements\\Select',
            'blade' => 'FirstlightUI\\Components\\Select',
            'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.FirstlightSelectRenderer',
            'ios_renderer' => 'SelectRenderer',
            'self_closing' => true,
        ],
        [
            'type' => 'firstlight.slider',
            'element' => 'FirstlightUI\\Elements\\Slider',
            'blade' => 'FirstlightUI\\Components\\Slider',
            'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.FirstlightSliderRenderer',
            'ios_renderer' => 'SliderRenderer',
            'self_closing' => true,
        ],
        [
            'type' => 'firstlight.switch',
            'element' => 'FirstlightUI\\Elements\\SwitchControl',
            'blade' => 'FirstlightUI\\Components\\SwitchControl',
            'android_renderer' => 'dev.firstlightui.plugins.firstlight_ui.ui.SwitchRenderer',
            'ios_renderer' => 'SwitchRenderer',
            'self_closing' => true,
        ],
    ]);
});
