<?php

it('indexes the service-backed Transient Feedback contract', function () {
    $root = dirname(__DIR__, 2);
    $publicPath = $root.'/docs/components/transient-feedback.md';
    $specPath = $root.'/spec/components/transient-feedback.md';

    expect(is_file($publicPath))->toBeTrue()
        ->and(is_file($specPath))->toBeTrue();

    $public = file_get_contents($publicPath);
    $spec = file_get_contents($specPath);
    $docsIndex = file_get_contents($root.'/docs/index.md');
    $specIndex = file_get_contents($root.'/spec/index.md');

    expect($public)->toContain('FirstlightUI\\Facades\\Feedback')
        ->toContain('FeedbackActionPressed')
        ->toContain('FeedbackDismissed')
        ->not->toContain('<firstlight:transient-feedback')
        ->and($spec)->toContain('firstlight.feedback-center')
        ->and($docsIndex)->toContain('(components/transient-feedback.md)')
        ->and($specIndex)->toContain('(components/transient-feedback.md)');
});

it('registers the Transient Feedback screenshot handoff and structural gate', function () {
    $root = dirname(__DIR__, 2);
    $manifest = json_decode(
        file_get_contents($root.'/spec/screenshots.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($manifest['components']['transient-feedback'] ?? null)->toBe([
        'route' => '/captures/transient-feedback',
        'test' => 'php artisan test tests/Feature/TransientFeedbackCaptureTest.php',
        'outputs' => [
            'ios-light' => 'docs/screenshots/transient-feedback/ios-light.png',
            'ios-dark' => 'docs/screenshots/transient-feedback/ios-dark.png',
            'android-light' => 'docs/screenshots/transient-feedback/android-light.png',
            'android-dark' => 'docs/screenshots/transient-feedback/android-dark.png',
        ],
    ])->and(is_executable($root.'/bin/check-transient-feedback'))->toBeTrue();
});
