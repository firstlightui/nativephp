<?php

it('derives opaque selected segment colors from the semantic theme for the pre-Liquid Glass thumb', function () {
    $control = file_get_contents(dirname(__DIR__, 2).'/resources/ios/SegmentedControl.swift');
    $renderer = file_get_contents(dirname(__DIR__, 2).'/resources/ios/SegmentedRenderer.swift');

    expect($renderer)->toContain('FirstlightSegmentedTokens.from(')
        ->toContain('theme: theme')
        ->toContain('userInterfaceStyle: colorScheme == .dark ? .dark : .light')
        ->and($control)->toContain('UIColor(theme.surface)')
        ->toContain('foreground: UIColor(theme.onPrimary)')
        ->toContain('opaqueComposite')
        ->toContain('contrastSafeSelectedTextColor')
        ->toContain('for: .selected')
        ->toContain('for: .disabled');
});

it('leaves the selected segment to the system thumb on Liquid Glass', function (string $file) {
    $source = file_get_contents(dirname(__DIR__, 2).'/resources/ios/'.$file);

    expect($source)->toMatch('/selectedSegmentTintColor = firstlightUsesLiquidGlass \? nil : /');
})->with(['SegmentedControl.swift', 'TabsControl.swift']);
