<?php

it('derives opaque selected segment colors from the semantic theme without an iOS 26 appearance override', function () {
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
        ->toContain('for: .disabled')
        ->not->toContain('#available(iOS 26.0, *)');
});
