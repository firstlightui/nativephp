<?php

// Source-wiring guard, not a replacement for native accessibility-tree tests.
it('labels every iOS text field variant through their shared editable input', function () {
    $source = file_get_contents(__DIR__.'/../../resources/ios/TextFieldControl.swift');

    expect($source)->toMatch('/input\s+\.disabled\(configuration.disabled\)\s+\.accessibilityLabel\(Text\(configuration.accessibilityLabel\)\)\s+\.accessibilityHint\(Text\(configuration.accessibilityHint\)\)/');
    expect($source)->toContain('SecureField(', 'TextField(', 'FirstlightMaskedTextInput(');
});

it('keeps text area and search accessibility names attached to their iOS editors', function () {
    $area = file_get_contents(__DIR__.'/../../resources/ios/TextAreaControl.swift');
    $search = file_get_contents(__DIR__.'/../../resources/ios/SearchFieldControl.swift');

    expect($area)->toContain('.modifier(TextAreaAccessibilityLabelModifier(label: configuration.accessibilityLabel))');
    expect($search)->toContain('field.accessibilityLabel = configuration.accessibilityLabel');
});

it('keeps Android text input names on native editable control semantics', function (string $component) {
    $source = file_get_contents(__DIR__.'/../../resources/android/'.$component.'Control.kt');

    expect($source)->toContain('configuration.accessibilityLabel', 'contentDescription = description');
})->with(['TextField', 'TextArea', 'SearchField']);
