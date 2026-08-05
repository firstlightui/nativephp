<?php

use FirstlightUI\Documentation\DocumentationPage;
use Symfony\Component\Process\Process;

require_once dirname(__DIR__, 2).'/bin/support/DocumentationPage.php';

/** @return array{package: string, showcase: string} */
function transientFeedbackGateFixture(): array
{
    $source = dirname(__DIR__, 2);
    $base = sys_get_temp_dir().'/firstlight-transient-feedback-gate-'.bin2hex(random_bytes(6));
    $package = $base.'/package';
    $showcase = $base.'/showcase';

    mkdir($package.'/bin', 0777, true);
    mkdir($package.'/docs/screenshots/transient-feedback', 0777, true);
    mkdir($package.'/spec/reviews', 0777, true);
    mkdir($showcase.'/routes', 0777, true);
    mkdir($showcase.'/tests/Feature', 0777, true);

    copy($source.'/bin/check-transient-feedback', $package.'/bin/check-transient-feedback');
    chmod($package.'/bin/check-transient-feedback', 0755);
    symlink($source.'/bin/support', $package.'/bin/support');

    foreach (['src', 'resources', 'tests', 'vendor'] as $directory) {
        symlink($source.'/'.$directory, $package.'/'.$directory);
    }

    foreach (['components', 'concepts', 'getting-started', 'reference'] as $directory) {
        symlink($source.'/docs/'.$directory, $package.'/docs/'.$directory);
    }
    symlink($source.'/docs/index.md', $package.'/docs/index.md');
    symlink($source.'/spec/components', $package.'/spec/components');
    symlink($source.'/spec/index.md', $package.'/spec/index.md');
    symlink($source.'/spec/documentation.json', $package.'/spec/documentation.json');
    copy($source.'/spec/screenshots.json', $package.'/spec/screenshots.json');

    foreach (['Constitution.md', 'Package.swift', 'composer.json', 'nativephp.json', 'llms.txt', 'llms-full.txt'] as $file) {
        symlink($source.'/'.$file, $package.'/'.$file);
    }

    return compact('package', 'showcase');
}

/** @param array{package: string, showcase: string} $fixture */
function removeTransientFeedbackGateFixture(array $fixture): void
{
    $root = dirname($fixture['package']);
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($items as $item) {
        if ($item->isLink() || $item->isFile()) {
            unlink($item->getPathname());
        } else {
            rmdir($item->getPathname());
        }
    }

    rmdir($root);
}

function transientFeedbackGatePng(string $variant): string
{
    return "\x89PNG\r\n\x1a\n".pack('N', 13).'IHDR'.pack('NN', 390, 844).$variant;
}

/** @param array{package: string, showcase: string} $fixture */
function completeTransientFeedbackReleaseEvidence(array $fixture): void
{
    foreach (['ios-light', 'ios-dark', 'android-light', 'android-dark'] as $variant) {
        file_put_contents(
            $fixture['package'].'/docs/screenshots/transient-feedback/'.$variant.'.png',
            transientFeedbackGatePng($variant),
        );
    }

    file_put_contents(
        $fixture['package'].'/spec/reviews/transient-feedback-alpha.md',
        <<<'MARKDOWN'
---
title: Transient Feedback alpha review evidence
description: Complete release evidence for Transient Feedback.
status: current
audience: maintainer
sources:
  - Constitution.md
---

# Transient Feedback Alpha Review Evidence

## Release identity

| Field | Evidence |
| --- | --- |
| Component | Transient Feedback |
| Capture mode | release |
| Package revision | `1111111111111111111111111111111111111111` |
| Showcase revision | `2222222222222222222222222222222222222222` |
| Visual approval | APPROVED: Maintainer reviewed the complete matrix. |

## Screenshot evidence

| Variant | Path | Result |
| --- | --- | --- |
| ios-light | `docs/screenshots/transient-feedback/ios-light.png` | PASS |
| ios-dark | `docs/screenshots/transient-feedback/ios-dark.png` | PASS |
| android-light | `docs/screenshots/transient-feedback/android-light.png` | PASS |
| android-dark | `docs/screenshots/transient-feedback/android-dark.png` | PASS |

## Required release evidence

| Evidence | Result | Detail |
| --- | --- | --- |
| Focused showcase test | PASS | Focused fixture passed against the installed package revision. |
| Release screenshot capture | PASS | Guarded release capture published the complete matrix. |
| iOS platform runtime | PASS | Native queue and lifecycle were exercised on iOS. |
| Android platform runtime | PASS | Native queue and lifecycle were exercised on Android. |
| Navigation and background lifecycle | PASS | Navigation and background timing were exercised. |
| VoiceOver | PASS | VoiceOver announcement, focus, and actions were reviewed. |
| TalkBack | PASS | TalkBack live region, focus, and actions were reviewed. |
| iOS physical device | PASS | Interaction and presentation were reviewed on an iOS device. |
| Android physical device | PASS | Interaction and presentation were reviewed on an Android device. |
MARKDOWN,
    );
}

/** @param array{package: string, showcase: string} $fixture */
function completeTransientFeedbackShowcaseContract(array $fixture): void
{
    file_put_contents(
        $fixture['showcase'].'/routes/native.php',
        <<<'PHP'
<?php

use App\NativeComponents\Captures\TransientFeedbackCapture;
use Illuminate\Support\Facades\Route;

Route::native('/captures/transient-feedback', TransientFeedbackCapture::class);
PHP,
    );
    file_put_contents(
        $fixture['showcase'].'/tests/Feature/TransientFeedbackCaptureTest.php',
        <<<'PHP'
<?php

use Native\Mobile\Facades\Native;

it('publishes stable transient feedback capture content', function () {
    Native::visit('/captures/transient-feedback');
});
PHP,
    );
}

/** @param list<string> $arguments */
function runTransientFeedbackGate(array $fixture, array $arguments = []): Process
{
    $process = new Process([
        $fixture['package'].'/bin/check-transient-feedback',
        ...$arguments,
    ], $fixture['package']);
    $process->setTimeout(15);
    $process->run();

    return $process;
}

it('indexes the service-backed Transient Feedback contract', function () {
    $root = dirname(__DIR__, 2);
    $publicPath = $root.'/docs/components/transient-feedback.md';
    $specPath = $root.'/spec/components/transient-feedback.md';

    expect(is_file($publicPath))->toBeTrue()
        ->and(is_file($specPath))->toBeTrue();

    $public = DocumentationPage::fromFile($root, 'docs/components/transient-feedback.md');
    $spec = DocumentationPage::fromFile($root, 'spec/components/transient-feedback.md');
    $docsIndex = file_get_contents($root.'/docs/index.md');
    $specIndex = file_get_contents($root.'/spec/index.md');

    expect($public->title)->toBe('Transient Feedback')
        ->and($public->type)->toBe('reference')
        ->and($public->audience)->toBe('consumer')
        ->and($public->status)->toBeNull()
        ->and($public->sources)->toContain(
            'src/FirstlightServiceProvider.php',
            'resources/views/native/feedback-center.blade.php',
            'resources/ios/FirstlightUIInit.swift',
            'resources/android/FirstlightUIInit.kt',
        )
        ->and($spec->title)->toBe('Transient Feedback maintained contract')
        ->and($spec->type)->toBeNull()
        ->and($spec->audience)->toBe('maintainer')
        ->and($spec->status)->toBe('current')
        ->and($spec->sources)->toContain(
            'src/Elements/FeedbackItem.php',
            'resources/ios/FeedbackCenterState.swift',
            'resources/android/FeedbackCenterState.kt',
        )
        ->and($public->body)->toContain('FirstlightUI\\Facades\\Feedback')
        ->toContain('FeedbackActionPressed')
        ->toContain('FeedbackDismissed')
        ->not->toContain('<firstlight:transient-feedback')
        ->toContain('incomplete native action metadata')
        ->toContain('otherwise eligible item remains visible without an action')
        ->and($spec->body)->toContain('firstlight.feedback-center')
        ->toContain('normalizes to no action')
        ->and($docsIndex)->toContain('- [Transient Feedback](components/transient-feedback.md)')
        ->and($specIndex)->toContain('- [Transient Feedback maintained contract](components/transient-feedback.md)');
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

it('executes the Transient Feedback development gate as part of the docs contract', function () {
    $root = dirname(__DIR__, 2);
    $process = new Process([$root.'/bin/check-transient-feedback', '--development'], $root);
    $process->setTimeout(15);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput())
        ->and($process->getOutput())->toContain('Transient Feedback structural checks passed.')
        ->toContain('PHP callback lifecycle checks passed.')
        ->toContain('Native behavioral suites are structurally required but run separately.');
});

it('release gate requires the sibling showcase root', function () {
    $fixture = transientFeedbackGateFixture();
    completeTransientFeedbackReleaseEvidence($fixture);

    try {
        $missing = $fixture['showcase'].'-missing';
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$missing]);

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain('Missing showcase root: '.$missing);
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
});

it('release gate requires the exact showcase route and focused capture test', function (string $missing, string $message) {
    $fixture = transientFeedbackGateFixture();
    completeTransientFeedbackReleaseEvidence($fixture);
    completeTransientFeedbackShowcaseContract($fixture);

    if ($missing === 'route') {
        file_put_contents($fixture['showcase'].'/routes/native.php', "<?php\n");
    } else {
        unlink($fixture['showcase'].'/tests/Feature/TransientFeedbackCaptureTest.php');
    }

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain($message);
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
})->with([
    'route' => ['route', 'Missing exact showcase capture route: /captures/transient-feedback'],
    'test' => ['test', 'Missing focused showcase capture test: tests/Feature/TransientFeedbackCaptureTest.php'],
]);

it('release gate rejects arbitrary screenshot bytes and an empty alpha review', function () {
    $fixture = transientFeedbackGateFixture();
    completeTransientFeedbackShowcaseContract($fixture);
    foreach (['ios-light', 'ios-dark', 'android-light', 'android-dark'] as $variant) {
        file_put_contents(
            $fixture['package'].'/docs/screenshots/transient-feedback/'.$variant.'.png',
            'not-a-png-'.$variant,
        );
    }
    file_put_contents($fixture['package'].'/spec/reviews/transient-feedback-alpha.md', '');

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain('Invalid screenshot PNG: docs/screenshots/transient-feedback/ios-light.png')
            ->toContain('Release review must declare status: current')
            ->toContain('Release review is missing required evidence row: VoiceOver');
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
});

it('release gate rejects non-affirmative review evidence', function () {
    $fixture = transientFeedbackGateFixture();
    completeTransientFeedbackReleaseEvidence($fixture);
    completeTransientFeedbackShowcaseContract($fixture);
    $review = $fixture['package'].'/spec/reviews/transient-feedback-alpha.md';
    file_put_contents($review, str_replace(
        '| VoiceOver | PASS | VoiceOver announcement, focus, and actions were reviewed. |',
        '| VoiceOver | BLOCKED | Review is pending. |',
        file_get_contents($review),
    ));

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain('Release review contains prohibited unresolved status: BLOCKED')
            ->toContain('Release review evidence row must be PASS: VoiceOver');
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
});

it('release gate accepts a complete structural release fixture', function () {
    $fixture = transientFeedbackGateFixture();
    completeTransientFeedbackReleaseEvidence($fixture);
    completeTransientFeedbackShowcaseContract($fixture);

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->isSuccessful())->toBeTrue($process->getErrorOutput())
            ->and($process->getOutput())->toContain('Transient Feedback structural checks passed.');
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
});
