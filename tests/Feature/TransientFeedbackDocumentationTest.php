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
    mkdir($showcase.'/app/NativeComponents/Captures', 0777, true);
    mkdir($showcase.'/resources/views/native/captures', 0777, true);

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

function transientFeedbackPngChunk(string $type, string $data, bool $corruptCrc = false): string
{
    $crc = pack('N', crc32($type.$data));
    if ($corruptCrc) {
        $crc[0] = chr(ord($crc[0]) ^ 0xff);
    }

    return pack('N', strlen($data)).$type.$data.$crc;
}

function transientFeedbackValidTestPng(
    string $variant,
    bool $badIdatCrc = false,
    bool $invalidZlib = false,
    bool $includeIend = true,
    int $interlace = 0,
    int $width = 320,
    int $height = 568,
    int $bitDepth = 8,
    int $colorType = 6,
    ?string $rawOverride = null,
): string
{
    $seed = array_sum(array_map('ord', str_split($variant))) % 255;
    $pixel = static fn (int $x, int $y): string => chr(($seed + $x + $y) % 255).chr(($x * 3 + $y) % 255).chr(($x + $y * 5) % 255).chr(255);
    $raw = '';
    if ($rawOverride !== null) {
        $raw = $rawOverride;
    } elseif ($interlace === 0) {
        for ($y = 0; $y < $height; $y++) {
            $raw .= chr(0);
            for ($x = 0; $x < $width; $x++) {
                $raw .= $pixel($x, $y);
            }
        }
    } else {
        foreach ([
            [0, 0, 8, 8],
            [4, 0, 8, 8],
            [0, 4, 4, 8],
            [2, 0, 4, 4],
            [0, 2, 2, 4],
            [1, 0, 2, 2],
            [0, 1, 1, 2],
        ] as [$startX, $startY, $stepX, $stepY]) {
            for ($y = $startY; $y < $height; $y += $stepY) {
                $raw .= chr(0);
                for ($x = $startX; $x < $width; $x += $stepX) {
                    $raw .= $pixel($x, $y);
                }
            }
        }
    }
    $idat = $invalidZlib ? 'not-zlib-data' : gzcompress($raw, 9);
    $png = "\x89PNG\r\n\x1a\n"
        .transientFeedbackPngChunk('IHDR', pack('NNCCCCC', $width, $height, $bitDepth, $colorType, 0, 0, $interlace))
        .transientFeedbackPngChunk('IDAT', $idat, $badIdatCrc);

    if ($includeIend) {
        $png .= transientFeedbackPngChunk('IEND', '');
    }

    return $png;
}

/** @param array{package: string, showcase: string} $fixture */
function writeTransientFeedbackReview(array $fixture, string $packageRevision, string $showcaseRevision): void
{
    $review = <<<MARKDOWN
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
| Review date | 2026-08-05 |
| Reviewer | Firstlight release reviewer |
| Reviewed package revision | `{$packageRevision}` |
| Showcase revision | `{$showcaseRevision}` |
| iOS release target | iPhone 17 Pro, iOS 26.5, simulator release target |
| Android release target | Pixel 9 Pro, Android API 36, emulator release target |

## Screenshot evidence

| Variant | Path | Result | Detail |
| --- | --- | --- | --- |
| ios-light | `docs/screenshots/transient-feedback/ios-light.png` | PASS | Release capture reviewed on the recorded iOS target. |
| ios-dark | `docs/screenshots/transient-feedback/ios-dark.png` | PASS | Dark release capture reviewed on the recorded iOS target. |
| android-light | `docs/screenshots/transient-feedback/android-light.png` | PASS | Release capture reviewed on the recorded Android target. |
| android-dark | `docs/screenshots/transient-feedback/android-dark.png` | PASS | Dark release capture reviewed on the recorded Android target. |

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
| Visual approval | APPROVED | Firstlight release reviewer approved all four images on 2026-08-05. |
MARKDOWN;

    file_put_contents($fixture['package'].'/spec/reviews/transient-feedback-alpha.md', $review);
}

/** @param array{package: string, showcase: string} $fixture */
function writeTransientFeedbackShowcaseContract(array $fixture, int $testExit = 0, ?string $artisanBody = null): void
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
        $fixture['showcase'].'/app/NativeComponents/Captures/TransientFeedbackCapture.php',
        <<<'PHP'
<?php

namespace App\NativeComponents\Captures;

use FirstlightUI\Facades\Feedback;
use Native\Mobile\Edge\NativeComponent;

final class TransientFeedbackCapture extends NativeComponent
{
    public function mount(): void
    {
        Feedback::success('Appointment saved')
            ->id('transient-feedback-capture')
            ->action('Undo', 'undo-save')
            ->hold()
            ->send();
    }

    public function navTitle(): string
    {
        return 'Firstlight Transient Feedback';
    }

    public function render(): mixed
    {
        return view('native.captures.transient-feedback');
    }
}
PHP,
    );
    file_put_contents(
        $fixture['showcase'].'/resources/views/native/captures/transient-feedback.blade.php',
        "<native:scroll-view><native:text>Stable capture fixture</native:text></native:scroll-view>\n",
    );
    file_put_contents(
        $fixture['showcase'].'/tests/Feature/TransientFeedbackCaptureTest.php',
        <<<'PHP'
<?php

use FirstlightUI\Feedback\FeedbackStore;
use FirstlightUI\Feedback\FeedbackTone;
use Native\Mobile\Testing\Native as NativeScreen;

it('publishes stable transient feedback capture content', function () {
    $screen = NativeScreen::visit(
        '/captures/transient-feedback',
    );
    $screen->assertSee('Stable capture fixture')->assertAccessible();
    $items = app(FeedbackStore::class)->all();

    expect($screen->tree()['props']['title'] ?? null)->toBe('Firstlight Transient Feedback')
        ->and($screen->tree()['props']['back'] ?? null)->toBeFalse()
        ->and($items)->toHaveCount(1)
        ->and($items[0]->message)->toBe('Appointment saved')
        ->and($items[0]->id)->toBe('transient-feedback-capture')
        ->and($items[0]->tone)->toBe(FeedbackTone::Success)
        ->and($items[0]->actionLabel)->toBe('Undo')
        ->and($items[0]->actionKey)->toBe('undo-save')
        ->and($items[0]->hold)->toBeTrue();
});
PHP,
    );
    file_put_contents(
        $fixture['showcase'].'/artisan',
        $artisanBody ?? "#!/usr/bin/env php\n<?php fwrite(STDERR, 'fixture focused test failure'); exit({$testExit});\n",
    );
    chmod($fixture['showcase'].'/artisan', 0755);
}

function bracketTransientFeedbackCaptureTest(string $test, string $namespace): string
{
    $body = preg_replace('/^<\?php\s*/', '', $test) ?? $test;
    $declaration = $namespace === '' ? 'namespace {' : "namespace {$namespace} {";

    return "<?php\n\n{$declaration}\n{$body}\n}\n";
}

function initializeTransientFeedbackGitRepository(string $path): string
{
    foreach ([
        ['git', 'init', '-q'],
        ['git', 'config', 'user.name', 'Firstlight Gate Test'],
        ['git', 'config', 'user.email', 'gate-test@example.invalid'],
        ['git', 'add', '.'],
        ['git', 'commit', '-q', '-m', 'test fixture'],
    ] as $command) {
        $process = new Process($command, $path);
        $process->run();
        expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());
    }

    $revision = new Process(['git', 'rev-parse', 'HEAD'], $path);
    $revision->run();
    expect($revision->isSuccessful())->toBeTrue($revision->getErrorOutput());

    return trim($revision->getOutput());
}

function commitTransientFeedbackFixture(string $path, string $message): string
{
    foreach ([['git', 'add', '.'], ['git', 'commit', '-q', '-m', $message]] as $command) {
        $process = new Process($command, $path);
        $process->run();
        expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());
    }

    $revision = new Process(['git', 'rev-parse', 'HEAD'], $path);
    $revision->run();

    return trim($revision->getOutput());
}

/** @return array{package: string, showcase: string, package_revision: string, showcase_revision: string} */
function initializeTransientFeedbackReleaseRepositories(array $fixture, int $testExit = 0, ?string $artisanBody = null): array
{
    writeTransientFeedbackShowcaseContract($fixture, $testExit, $artisanBody);
    $showcaseRevision = initializeTransientFeedbackGitRepository($fixture['showcase']);
    $packageRevision = initializeTransientFeedbackGitRepository($fixture['package']);

    return [
        ...$fixture,
        'package_revision' => $packageRevision,
        'showcase_revision' => $showcaseRevision,
    ];
}

/**
 * @param  list<string>  $arguments
 * @param  array<string, string>  $environment
 */
function runTransientFeedbackGate(array $fixture, array $arguments = [], array $environment = []): Process
{
    $process = new Process([
        $fixture['package'].'/bin/check-transient-feedback',
        ...$arguments,
    ], $fixture['package'], $environment === [] ? null : $environment);
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

it('release gate requires real package and showcase Git worktrees', function (string $missing, string $message) {
    $fixture = transientFeedbackGateFixture();

    if ($missing === 'showcase') {
        initializeTransientFeedbackGitRepository($fixture['package']);
    }

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain($message);
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
})->with([
    'package' => ['package', 'Package root is not a Git worktree'],
    'showcase' => ['showcase', 'Showcase root is not a Git worktree'],
]);

it('release gate requires every exact Task 6 showcase artifact', function (string $missing, string $message) {
    $fixture = transientFeedbackGateFixture();
    writeTransientFeedbackShowcaseContract($fixture);

    $paths = [
        'route' => 'routes/native.php',
        'test' => 'tests/Feature/TransientFeedbackCaptureTest.php',
        'component' => 'app/NativeComponents/Captures/TransientFeedbackCapture.php',
        'view' => 'resources/views/native/captures/transient-feedback.blade.php',
    ];
    unlink($fixture['showcase'].'/'.$paths[$missing]);
    initializeTransientFeedbackGitRepository($fixture['showcase']);
    initializeTransientFeedbackGitRepository($fixture['package']);

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
    'component' => ['component', 'Missing showcase capture component: app/NativeComponents/Captures/TransientFeedbackCapture.php'],
    'view' => ['view', 'Missing showcase capture view: resources/views/native/captures/transient-feedback.blade.php'],
]);

it('release gate executes the exact manifest focused test and bounds its failure output', function () {
    $fixture = transientFeedbackGateFixture();
    $fixture = initializeTransientFeedbackReleaseRepositories($fixture, 7);

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain('Focused showcase test failed with exit code 7: fixture focused test failure');
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
});

it('release gate preflights the exact focused capture test before executing it', function (string $case, string $message) {
    $fixture = transientFeedbackGateFixture();
    writeTransientFeedbackShowcaseContract(
        $fixture,
        artisanBody: "#!/usr/bin/env php\n<?php file_put_contents(__DIR__.'/focused-test-executed', 'yes'); exit(0);\n",
    );
    $testPath = $fixture['showcase'].'/tests/Feature/TransientFeedbackCaptureTest.php';
    $test = (string) file_get_contents($testPath);

    if ($case === 'unrelated') {
        $test = "<?php\nit('passes without testing the capture', fn () => expect(true)->toBeTrue());\n";
    } elseif ($case === 'fake-native') {
        $test = str_replace('use Native\\Mobile\\Testing\\Native as NativeScreen;', '', $test);
        $test = str_replace('NativeScreen::visit(', 'FakeNativeScreen::visit(', $test);
        $test = str_replace(
            "it('publishes stable transient feedback capture content'",
            "final class FakeNativeScreen {}\n\nit('publishes stable transient feedback capture content'",
            $test,
        );
    } elseif ($case === 'visit-in-comment-and-string') {
        $test = str_replace(
            <<<'PHP'
    $screen = NativeScreen::visit(
        '/captures/transient-feedback',
    );
PHP,
            <<<'PHP'
    // NativeScreen::visit('/captures/transient-feedback');
    $inertVisit = "NativeScreen::visit('/captures/transient-feedback')";
    $screen = new class {};
PHP,
            $test,
        );
    } elseif ($case === 'namespaced-relative-native') {
        $test = str_replace("<?php\n", "<?php\n\nnamespace Showcase\\Tests;\n", $test);
        $test = str_replace('use Native\\Mobile\\Testing\\Native as NativeScreen;', '', $test);
        $test = str_replace('NativeScreen::visit(', 'Native\\Mobile\\Testing\\Native::visit(', $test);
    } elseif ($case === 'wrong-route') {
        $test = str_replace('/captures/transient-feedback', '/captures/callout', $test);
    } elseif ($case === 'no-visit') {
        $test = preg_replace(
            "/\\s*\\\$screen = NativeScreen::visit\\(\\s*'\\/captures\\/transient-feedback',?\\s*\\);/",
            "\n    \$screen = new class { public function tree(): array { return ['props' => ['title' => 'Firstlight Transient Feedback', 'back' => false]]; } };",
            $test,
        );
    } elseif ($case === 'no-feedback-assertion') {
        $test = preg_replace(
            '/\\s*\\$items = app\\(FeedbackStore::class\\)->all\\(\\);.*?->and\\(\\$items\\[0\\]->hold\\)->toBeTrue\\(\\);/s',
            "\n\n    expect(\$screen->tree()['props']['title'] ?? null)->toBe('Firstlight Transient Feedback')\n        ->and(\$screen->tree()['props']['back'] ?? null)->toBeFalse();",
            $test,
        );
    } elseif ($case === 'no-screen-identity') {
        $test = preg_replace(
            "/\\s*expect\\(\\\$screen->tree\\(\\)\\['props'\\]\\['title'\\] \\?\\? null\\)->toBe\\('Firstlight Transient Feedback'\\)\\s*->and\\(\\\$screen->tree\\(\\)\\['props'\\]\\['back'\\] \\?\\? null\\)->toBeFalse\\(\\)/",
            "\n    expect(true)->toBeTrue()",
            $test,
        );
    } elseif ($case === 'assert-see-on-unrelated-object') {
        $test = str_replace(
            <<<'PHP'
$screen->assertSee('Stable capture fixture')->assertAccessible();
PHP,
            <<<'PHP'
$otherScreen->assertSee('Stable capture fixture')->assertAccessible();
PHP,
            $test,
        );
    } elseif ($case === 'inert-expected-strings') {
        $test = preg_replace(
            '/\s*expect\(\$screen->tree\(\).*?->and\(\$items\[0\]->hold\)->toBeTrue\(\);/s',
            "\n    \$inertEvidence = ['Firstlight Transient Feedback', 'Appointment saved', 'transient-feedback-capture', 'Undo', 'undo-save', 'FeedbackTone::Success', 'toHaveCount(1)', 'toBeFalse()', 'toBeTrue()'];\n    expect(true)->toBeTrue();",
            $test,
        );
    } elseif ($case === 'wrong-expected-values') {
        $test = str_replace('Firstlight Transient Feedback', 'Wrong capture title', $test);
        $test = str_replace('Appointment saved', 'Wrong feedback message', $test);
    }
    file_put_contents($testPath, $test);
    initializeTransientFeedbackGitRepository($fixture['showcase']);
    initializeTransientFeedbackGitRepository($fixture['package']);

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain($message)
            ->and(is_file($fixture['showcase'].'/focused-test-executed'))->toBeFalse();
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
})->with([
    'unrelated passing test' => ['unrelated', 'Focused showcase capture test must call Native::visit'],
    'lookalike Native class with correct strings' => ['fake-native', 'Focused showcase capture test must call Native::visit'],
    'visit only in a comment and string' => ['visit-in-comment-and-string', 'Focused showcase capture test must call Native::visit'],
    'relative Native name inside another namespace' => ['namespaced-relative-native', 'Focused showcase capture test must call Native::visit'],
    'visit to wrong route' => ['wrong-route', 'Focused showcase capture test visits the wrong route'],
    'no Native visit' => ['no-visit', 'Focused showcase capture test must call Native::visit'],
    'no feedback capture assertion' => ['no-feedback-assertion', 'Focused showcase capture test must assert the deterministic feedback record'],
    'no capture screen identity assertion' => ['no-screen-identity', 'Focused showcase capture test must assert the capture screen identity'],
    'assertSee on an unrelated object' => ['assert-see-on-unrelated-object', 'Focused showcase capture test must assert the capture screen identity'],
    'expected strings are inert data' => ['inert-expected-strings', 'Focused showcase capture test must assert the capture screen identity'],
    'capture and feedback expected values are wrong' => ['wrong-expected-values', 'Focused showcase capture test must assert the capture screen identity'],
]);

it('release gate drains exactly 50 MiB of focused-test output into a bounded diagnostic', function () {
    $fixture = transientFeedbackGateFixture();
    $fixture = initializeTransientFeedbackReleaseRepositories(
        $fixture,
        artisanBody: "#!/usr/bin/env php\n<?php for (\$chunk = 0; \$chunk < 6400; \$chunk++) { fwrite(STDOUT, str_repeat('X', 8192)); } fwrite(STDERR, 'bounded-tail'); exit(23);\n",
    );

    try {
        $process = new Process([
            'php',
            '-d',
            'memory_limit=64M',
            $fixture['package'].'/bin/check-transient-feedback',
            '--showcase='.$fixture['showcase'],
        ], $fixture['package']);
        $process->setTimeout(15);
        $process->run();
        $output = $process->getErrorOutput();
        preg_match('/Focused showcase test failed with exit code 23: (.*)/', $output, $match);

        expect($process->getExitCode())->toBe(1)
            ->and($match)->not->toBeEmpty()
            ->and(strlen($match[1]))->toBeLessThanOrEqual(4000)
            ->and(strlen($output))->toBeLessThan(12_000);
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
});

it('release gate accepts a fully qualified Native testing call before executing the focused test', function () {
    $fixture = transientFeedbackGateFixture();
    writeTransientFeedbackShowcaseContract($fixture, 29);
    $testPath = $fixture['showcase'].'/tests/Feature/TransientFeedbackCaptureTest.php';
    $test = (string) file_get_contents($testPath);
    $test = str_replace("<?php\n", "<?php\n\nnamespace Showcase\\Tests;\n", $test);
    $test = str_replace('use Native\\Mobile\\Testing\\Native as NativeScreen;', '', $test);
    $test = str_replace('NativeScreen::visit(', '\\Native\\Mobile\\Testing\\Native::visit(', $test);
    file_put_contents($testPath, $test);
    initializeTransientFeedbackGitRepository($fixture['showcase']);
    initializeTransientFeedbackGitRepository($fixture['package']);

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain(
                'Focused showcase test failed with exit code 29: fixture focused test failure',
            );
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
});

it('release gate accepts an imported Native alias inside a namespace', function () {
    $fixture = transientFeedbackGateFixture();
    writeTransientFeedbackShowcaseContract($fixture, 28);
    $testPath = $fixture['showcase'].'/tests/Feature/TransientFeedbackCaptureTest.php';
    $test = (string) file_get_contents($testPath);
    $test = str_replace("<?php\n", "<?php\n\nnamespace Showcase\\Tests;\n", $test);
    file_put_contents($testPath, $test);
    initializeTransientFeedbackGitRepository($fixture['showcase']);
    initializeTransientFeedbackGitRepository($fixture['package']);

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain(
                'Focused showcase test failed with exit code 28: fixture focused test failure',
            );
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
});

it('release gate does not leak Native aliases or non-import uses between namespace scopes', function (string $case) {
    $fixture = transientFeedbackGateFixture();
    writeTransientFeedbackShowcaseContract(
        $fixture,
        artisanBody: "#!/usr/bin/env php\n<?php file_put_contents(__DIR__.'/focused-test-executed', 'yes'); exit(0);\n",
    );
    $testPath = $fixture['showcase'].'/tests/Feature/TransientFeedbackCaptureTest.php';
    $test = (string) file_get_contents($testPath);
    $test = preg_replace('/^<\?php\s*/', '', $test) ?? $test;
    $test = str_replace('use Native\\Mobile\\Testing\\Native as NativeScreen;', '', $test);
    if ($case === 'a-import-does-not-authorize-b') {
        $test = "<?php\n\nnamespace NamespaceA;\nuse Native\\Mobile\\Testing\\Native as NativeScreen;\n\nnamespace NamespaceB;\n{$test}";
    } elseif ($case === 'b-spoof-not-overridden') {
        $test .= "\nnamespace NamespaceA;\nuse Native\\Mobile\\Testing\\Native as NativeScreen;\n";
        $test = "<?php\n\nnamespace NamespaceB;\nuse Tests\\FakeNativeScreen as NativeScreen;\n{$test}";
    } elseif (in_array($case, ['bracketed-no-import', 'bracketed-spoof'], true)) {
        $bImport = $case === 'bracketed-spoof'
            ? "use Tests\\FakeNativeScreen as NativeScreen;\n"
            : '';
        $test = "<?php\n\nnamespace {\nuse Native\\Mobile\\Testing\\Native as NativeScreen;\n}\n\nnamespace NamespaceA {\nuse Native\\Mobile\\Testing\\Native as NativeScreen;\n}\n\nnamespace NamespaceB {\n{$bImport}{$test}\n}\n";
    } else {
        $nonImportUse = $case === 'closure-use'
            ? "\n    \$capturedAlias = null;\n    \$closure = static function () use (\$capturedAlias): void {};\n"
            : "\n    final class UsesNativeScreenAsTrait {\n        use NativeScreen;\n    }\n";
        $test = str_replace(
            "\nit('publishes stable transient feedback capture content'",
            "{$nonImportUse}\nit('publishes stable transient feedback capture content'",
            $test,
        );
        $test = "<?php\n\nnamespace NamespaceB;\n{$test}";
    }
    file_put_contents($testPath, $test);
    initializeTransientFeedbackGitRepository($fixture['showcase']);
    initializeTransientFeedbackGitRepository($fixture['package']);

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain('Focused showcase capture test must call Native::visit')
            ->and(is_file($fixture['showcase'].'/focused-test-executed'))->toBeFalse();
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
})->with([
    'namespace A import cannot authorize namespace B' => 'a-import-does-not-authorize-b',
    'later namespace A import cannot override namespace B spoof alias' => 'b-spoof-not-overridden',
    'bracketed global and A imports cannot authorize B' => 'bracketed-no-import',
    'bracketed B spoof alias is not overridden by global or A' => 'bracketed-spoof',
    'closure capture use is not a namespace import' => 'closure-use',
    'trait use is not a namespace import' => 'trait-use',
]);

it('release gate resolves aliases independently in bracketed and repeated namespace scopes', function (string $case, int $exitCode) {
    $fixture = transientFeedbackGateFixture();
    writeTransientFeedbackShowcaseContract($fixture, $exitCode);
    $testPath = $fixture['showcase'].'/tests/Feature/TransientFeedbackCaptureTest.php';
    $test = (string) file_get_contents($testPath);

    if ($case === 'bracketed-global') {
        $test = bracketTransientFeedbackCaptureTest($test, '');
    } elseif ($case === 'bracketed-named-alias') {
        $test = bracketTransientFeedbackCaptureTest($test, 'NamespaceB');
    } elseif ($case === 'bracketed-named-fqcn') {
        $test = str_replace('use Native\\Mobile\\Testing\\Native as NativeScreen;', '', $test);
        $test = str_replace('NativeScreen::visit(', '\\Native\\Mobile\\Testing\\Native::visit(', $test);
        $test = bracketTransientFeedbackCaptureTest($test, 'NamespaceB');
    } else {
        $body = preg_replace('/^<\?php\s*/', '', $test) ?? $test;
        $body = str_replace('use Native\\Mobile\\Testing\\Native as NativeScreen;', '', $body);
        $test = $case === 'bracketed-reused-alias'
            ? "<?php\n\nnamespace {\nuse Tests\\GlobalFakeNativeScreen as NativeScreen;\n}\n\nnamespace NamespaceA {\nuse Tests\\FakeNativeScreen as NativeScreen;\n}\n\nnamespace NamespaceB {\nuse Native\\Mobile\\Testing\\Native as NativeScreen;\n{$body}\n}\n"
            : "<?php\n\nnamespace NamespaceA;\nuse Tests\\FakeNativeScreen as NativeScreen;\n\nnamespace NamespaceB;\nuse Native\\Mobile\\Testing\\Native as NativeScreen;\n{$body}";
    }
    file_put_contents($testPath, $test);
    initializeTransientFeedbackGitRepository($fixture['showcase']);
    initializeTransientFeedbackGitRepository($fixture['package']);

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain(
                "Focused showcase test failed with exit code {$exitCode}: fixture focused test failure",
            );
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
})->with([
    'bracketed global namespace alias' => ['bracketed-global', 31],
    'bracketed named namespace alias' => ['bracketed-named-alias', 32],
    'bracketed named namespace FQCN' => ['bracketed-named-fqcn', 33],
    'same alias maps differently in namespaces A and B' => ['unbracketed-reused-alias', 34],
    'same alias maps differently in bracketed global A and B scopes' => ['bracketed-reused-alias', 35],
]);

it('release gate accepts screen assertions chained directly to an assigned Native visit result', function () {
    $fixture = transientFeedbackGateFixture();
    writeTransientFeedbackShowcaseContract($fixture, 30);
    $testPath = $fixture['showcase'].'/tests/Feature/TransientFeedbackCaptureTest.php';
    $test = (string) file_get_contents($testPath);
    $test = str_replace(
        <<<'PHP'
    $screen = NativeScreen::visit(
        '/captures/transient-feedback',
    );
    $screen->assertSee('Stable capture fixture')->assertAccessible();
PHP,
        <<<'PHP'
    $screen = NativeScreen::visit(
        '/captures/transient-feedback',
    )->assertSee('Stable capture fixture')->assertAccessible();
PHP,
        $test,
    );
    file_put_contents($testPath, $test);
    initializeTransientFeedbackGitRepository($fixture['showcase']);
    initializeTransientFeedbackGitRepository($fixture['package']);

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain(
                'Focused showcase test failed with exit code 30: fixture focused test failure',
            );
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
});

it('release gate terminates a hung focused showcase test within its bounded timeout', function () {
    $fixture = transientFeedbackGateFixture();
    $fixture = initializeTransientFeedbackReleaseRepositories(
        $fixture,
        artisanBody: "#!/usr/bin/env php\n<?php sleep(7);\n",
    );
    $startedAt = microtime(true);

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain('Focused showcase test timed out')
            ->and(microtime(true) - $startedAt)->toBeLessThan(8.0);
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
});

it('release gate cannot be starved past its timeout by continuously refilled output', function () {
    $fixture = transientFeedbackGateFixture();
    $fixture = initializeTransientFeedbackReleaseRepositories(
        $fixture,
        artisanBody: <<<'PHP'
#!/usr/bin/env php
<?php
$stdoutWriter = proc_open(['/usr/bin/yes', 'stdout-fairness-marker'], [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR], $stdoutPipes);
$stderrWriter = proc_open(['/usr/bin/yes', 'stderr-fairness-marker'], [0 => ['pipe', 'r'], 1 => STDERR, 2 => STDERR], $stderrPipes);
sleep(9);
PHP,
    );
    $startedAt = microtime(true);

    try {
        $process = new Process([
            $fixture['package'].'/bin/check-transient-feedback',
            '--showcase='.$fixture['showcase'],
        ], $fixture['package']);
        $process->setTimeout(12);
        $process->run();
        $diagnostic = $process->getErrorOutput();
        preg_match('/Focused showcase test timed out after 5 seconds: (.*)/', $diagnostic, $match);

        expect($process->getExitCode())->toBe(1)
            ->and($diagnostic)->toContain('Focused showcase test timed out')
            ->and($match)->not->toBeEmpty()
            ->and($match[1])->toContain('stdout-fairness-marker', 'stderr-fairness-marker')
            ->and(strlen($match[1]))->toBeLessThanOrEqual(4000)
            ->and(strlen($diagnostic))->toBeLessThan(12_000)
            ->and(microtime(true) - $startedAt)->toBeLessThan(6.5);
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
});

it('release gate terminates a reparented grandchild in the isolated focused-test process group', function () {
    if (! function_exists('posix_kill')) {
        $this->markTestSkipped('Process-tree assertion requires posix_kill.');
    }

    $fixture = transientFeedbackGateFixture();
    $rootPidPath = $fixture['showcase'].'/focused-root.pid';
    $childPidPath = $fixture['showcase'].'/focused-child.pid';
    $grandchildPidPath = $fixture['showcase'].'/focused-grandchild.pid';
    $heartbeatPath = $fixture['showcase'].'/focused-grandchild-heartbeat';
    $childCode = <<<'PHP'
file_put_contents($argv[1], (string) getmypid());
$grandchild = pcntl_fork();
if ($grandchild === 0) {
    $deadline = microtime(true) + 12;
    file_put_contents($argv[2], (string) getmypid());
    while (microtime(true) < $deadline) {
        file_put_contents($argv[3], '.', FILE_APPEND);
        usleep(20_000);
    }
    exit(0);
}
exit(0);
PHP;
    $artisanBody = "#!/usr/bin/env php\n<?php\n"
        ."file_put_contents(".var_export($rootPidPath, true).", (string) getmypid());\n"
        .'$child = proc_open(['.var_export(PHP_BINARY, true).", '-r', ".var_export($childCode, true).', '.var_export($childPidPath, true).', '.var_export($grandchildPidPath, true).', '.var_export($heartbeatPath, true)."], [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], \$pipes);\n"
        ."sleep(9);\n";
    $fixture = initializeTransientFeedbackReleaseRepositories($fixture, artisanBody: $artisanBody);
    $startedAt = microtime(true);
    $pids = [];

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);
        $diagnostic = $process->getErrorOutput();
        foreach ([$rootPidPath, $childPidPath, $grandchildPidPath] as $pidPath) {
            expect(is_file($pidPath))->toBeTrue("Missing PID fixture: {$pidPath}");
            $pids[] = (int) file_get_contents($pidPath);
        }
        usleep(150_000);
        clearstatcache(true, $heartbeatPath);
        $heartbeatBytes = is_file($heartbeatPath) ? filesize($heartbeatPath) : 0;
        usleep(250_000);
        clearstatcache(true, $heartbeatPath);
        $rootAlive = @posix_kill($pids[0], 0);
        $childAlive = @posix_kill($pids[1], 0);
        $grandchildAlive = @posix_kill($pids[2], 0);
        $groupAlive = @posix_kill(-$pids[0], 0);
        $groupError = posix_get_last_error();

        expect($process->getExitCode())->toBe(1)
            ->and($diagnostic)->toContain('Focused showcase test timed out')
            ->and(strlen($diagnostic))->toBeLessThan(12_000)
            ->and(microtime(true) - $startedAt)->toBeLessThan(7.0)
            ->and($rootAlive)->toBeFalse()
            ->and($childAlive)->toBeFalse()
            ->and($grandchildAlive)->toBeFalse()
            ->and($groupAlive)->toBeFalse()
            ->and($groupError)->toBe(3)
            ->and(is_file($heartbeatPath) ? filesize($heartbeatPath) : 0)->toBe($heartbeatBytes);
    } finally {
        if (($pids[0] ?? 0) > 1 && @posix_kill(-$pids[0], 0)) {
            @posix_kill(-$pids[0], 9);
        }
        foreach ($pids as $pid) {
            if ($pid > 1 && @posix_kill($pid, 0)) {
                @posix_kill($pid, 9);
            }
        }
        removeTransientFeedbackGateFixture($fixture);
    }
});

it('release gate fails actionably when the isolated focused-test wrapper is unsupported', function () {
    $fixture = transientFeedbackGateFixture();
    $fixture = initializeTransientFeedbackReleaseRepositories($fixture);

    try {
        $process = runTransientFeedbackGate(
            $fixture,
            ['--showcase='.$fixture['showcase']],
            ['FIRSTLIGHT_TRANSIENT_FEEDBACK_TEST_WRAPPER_MODE' => 'unsupported'],
        );

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain(
                'Unable to execute focused showcase test safely: isolated POSIX session wrapper is unavailable',
            );
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
});

it('release gate rejects dirty package and showcase release trees', function (string $dirty, string $message) {
    $fixture = transientFeedbackGateFixture();
    $fixture = initializeTransientFeedbackReleaseRepositories($fixture);
    file_put_contents($fixture[$dirty].'/uncommitted.txt', 'dirty');

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain($message);
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
})->with([
    'package' => ['package', 'Package release tree is dirty'],
    'showcase' => ['showcase', 'Showcase release tree is dirty'],
]);

it('release gate ties review revisions to a real package ancestor and exact showcase head', function (string $case, string $message) {
    $fixture = transientFeedbackGateFixture();
    $fixture = initializeTransientFeedbackReleaseRepositories($fixture);
    $packageRevision = $fixture['package_revision'];
    $showcaseRevision = $fixture['showcase_revision'];

    if ($case === 'nonexistent') {
        $packageRevision = str_repeat('1234567890', 4);
    } elseif ($case === 'non-ancestor') {
        $tree = new Process(['git', 'rev-parse', 'HEAD^{tree}'], $fixture['package']);
        $tree->run();
        $commit = new Process(['git', 'commit-tree', trim($tree->getOutput()), '-m', 'unrelated fixture'], $fixture['package']);
        $commit->run();
        expect($commit->isSuccessful())->toBeTrue($commit->getErrorOutput());
        $packageRevision = trim($commit->getOutput());
    } elseif ($case === 'showcase-mismatch') {
        $showcaseRevision = $fixture['package_revision'];
    }

    writeTransientFeedbackReview($fixture, $packageRevision, $showcaseRevision);
    commitTransientFeedbackFixture($fixture['package'], 'publish review evidence');

    if ($case === 'source-after-review') {
        file_put_contents($fixture['package'].'/bin/check-transient-feedback', "\n", FILE_APPEND);
        commitTransientFeedbackFixture($fixture['package'], 'change gate after review');
    }

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain($message);
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
})->with([
    'nonexistent package revision' => ['nonexistent', 'Reviewed package revision is not a package commit'],
    'non-ancestor package revision' => ['non-ancestor', 'Reviewed package revision is not an ancestor of package HEAD'],
    'source change after reviewed revision' => ['source-after-review', 'Non-evidence path changed after reviewed package revision: bin/check-transient-feedback'],
    'mismatched showcase head' => ['showcase-mismatch', 'Showcase revision does not match clean showcase HEAD'],
]);

it('release gate rejects structurally corrupt PNG evidence', function (string $png, string $reason) {
    $fixture = transientFeedbackGateFixture();
    $fixture = initializeTransientFeedbackReleaseRepositories($fixture);
    file_put_contents($fixture['package'].'/docs/screenshots/transient-feedback/ios-light.png', $png);

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain(
                'Invalid screenshot PNG: docs/screenshots/transient-feedback/ios-light.png ('.$reason.')',
            );
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
})->with([
    'signature and IHDR only' => [
        "\x89PNG\r\n\x1a\n".transientFeedbackPngChunk('IHDR', pack('NNCCCCC', 320, 568, 8, 6, 0, 0, 0)),
        'missing non-empty IDAT',
    ],
    'bad IDAT CRC' => [transientFeedbackValidTestPng('bad-crc', badIdatCrc: true), 'CRC mismatch for IDAT'],
    'invalid IDAT zlib stream' => [transientFeedbackValidTestPng('bad-zlib', invalidZlib: true), 'IDAT zlib decode failed'],
    'missing IEND' => [transientFeedbackValidTestPng('missing-iend', includeIend: false), 'missing IEND at EOF'],
    'trailing bytes' => [transientFeedbackValidTestPng('trailing').'garbage', 'trailing bytes after IEND'],
]);

it('PNG validator accepts structurally valid non-interlaced and Adam7 evidence without making a fake release pass', function (int $interlace) {
    $fixture = transientFeedbackGateFixture();
    writeTransientFeedbackShowcaseContract($fixture);
    file_put_contents(
        $fixture['package'].'/docs/screenshots/transient-feedback/ios-light.png',
        transientFeedbackValidTestPng('valid-'.$interlace, interlace: $interlace),
    );
    initializeTransientFeedbackGitRepository($fixture['showcase']);
    initializeTransientFeedbackGitRepository($fixture['package']);

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())
            ->not->toContain('Invalid screenshot PNG: docs/screenshots/transient-feedback/ios-light.png')
            ->toContain('Missing screenshot evidence: docs/screenshots/transient-feedback/ios-dark.png');
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
})->with([
    'non-interlaced' => 0,
    'Adam7 interlaced' => 1,
]);

it('PNG validator rejects decoded-data bombs and extreme formats before allocation', function (string $png, string $reason) {
    $fixture = transientFeedbackGateFixture();
    $fixture = initializeTransientFeedbackReleaseRepositories($fixture);
    file_put_contents($fixture['package'].'/docs/screenshots/transient-feedback/ios-light.png', $png);

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain(
                'Invalid screenshot PNG: docs/screenshots/transient-feedback/ios-light.png ('.$reason.')',
            );
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
})->with([
    'zip bomb beyond exact scanlines' => [
        transientFeedbackValidTestPng(
            'zip-bomb',
            rawOverride: str_repeat("\0", ((320 * 4 + 1) * 568) + 1_000_000),
        ),
        'decoded PNG data exceeds expected scanline bytes',
    ],
    '20MP RGBA16 exceeds decoded ceiling' => [
        transientFeedbackValidTestPng(
            'extreme',
            width: 4000,
            height: 5000,
            bitDepth: 16,
            rawOverride: "\0",
        ),
        'expected decoded PNG data exceeds 64 MiB ceiling',
    ],
]);

it('PNG validator rejects screenshot files beyond its explicit file ceiling', function () {
    $fixture = transientFeedbackGateFixture();
    $fixture = initializeTransientFeedbackReleaseRepositories($fixture);
    $path = $fixture['package'].'/docs/screenshots/transient-feedback/ios-light.png';
    $file = fopen($path, 'wb');
    expect($file)->not->toBeFalse();
    ftruncate($file, (32 * 1024 * 1024) + 1);
    fclose($file);

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain(
                'Invalid screenshot PNG: docs/screenshots/transient-feedback/ios-light.png (file exceeds 32 MiB ceiling)',
            );
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
});

it('release gate rejects incomplete duplicated placeholder and unresolved review evidence', function () {
    $fixture = transientFeedbackGateFixture();
    $fixture = initializeTransientFeedbackReleaseRepositories($fixture);
    writeTransientFeedbackReview($fixture, $fixture['package_revision'], $fixture['showcase_revision']);
    $reviewPath = $fixture['package'].'/spec/reviews/transient-feedback-alpha.md';
    $review = file_get_contents($reviewPath);
    $review = str_replace('| Review date | 2026-08-05 |', '| Review date | someday |', $review);
    $review = str_replace('| Reviewer | Firstlight release reviewer |', '| Reviewer | x |', $review);
    $review = str_replace(
        '| iOS release target | iPhone 17 Pro, iOS 26.5, simulator release target |',
        '| iOS release target | x |',
        $review,
    );
    $review = str_replace(
        '| android-light | `docs/screenshots/transient-feedback/android-light.png` | PASS | Release capture reviewed on the recorded Android target. |',
        '| android-light | `docs/screenshots/wrong.png` | PASS | Release capture reviewed on the recorded Android target. |',
        $review,
    );
    $review = str_replace(
        '| VoiceOver | PASS | VoiceOver announcement, focus, and actions were reviewed. |',
        '| VoiceOver | PASS | x |',
        $review,
    );
    $review .= "\n| TalkBack | PASS | Duplicate evidence row with otherwise substantive detail. |\n";
    $review .= "\nOPEN PENDING DEFERRED NOT RUN SKIP SKIPPED UNKNOWN TODO BLOCKED FAIL FAILED\n";
    file_put_contents($reviewPath, $review);
    commitTransientFeedbackFixture($fixture['package'], 'publish malformed review evidence');

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);
        $output = $process->getErrorOutput();

        expect($process->getExitCode())->toBe(1)
            ->and($output)->toContain('Release review date must use YYYY-MM-DD')
            ->toContain('Release review field must be substantive: Reviewer')
            ->toContain('Release review field must be substantive: iOS release target')
            ->toContain('Release review is missing exact PASS screenshot row: android-light')
            ->toContain('Release review evidence detail must be substantive: VoiceOver')
            ->toContain('Duplicate release review evidence row: TalkBack');

        foreach (['OPEN', 'PENDING', 'DEFERRED', 'NOT RUN', 'SKIP', 'SKIPPED', 'UNKNOWN', 'TODO', 'BLOCKED', 'FAIL', 'FAILED'] as $status) {
            expect($output)->toContain('Release review contains prohibited unresolved status: '.$status);
        }
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
});

it('release gate rejects placeholder reviewed revisions', function () {
    $fixture = transientFeedbackGateFixture();
    $fixture = initializeTransientFeedbackReleaseRepositories($fixture);
    writeTransientFeedbackReview($fixture, str_repeat('0', 40), $fixture['showcase_revision']);
    commitTransientFeedbackFixture($fixture['package'], 'publish placeholder review evidence');

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain('Reviewed package revision must not be a placeholder hash');
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
});

it('release gate rejects placeholder words embedded in review fields', function (string $search, string $replacement, string $message) {
    $fixture = transientFeedbackGateFixture();
    $fixture = initializeTransientFeedbackReleaseRepositories($fixture);
    writeTransientFeedbackReview($fixture, $fixture['package_revision'], $fixture['showcase_revision']);
    $reviewPath = $fixture['package'].'/spec/reviews/transient-feedback-alpha.md';
    $review = (string) file_get_contents($reviewPath);
    file_put_contents($reviewPath, str_replace($search, $replacement, $review));
    commitTransientFeedbackFixture($fixture['package'], 'publish placeholder review evidence');

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->getExitCode())->toBe(1)
            ->and($process->getErrorOutput())->toContain($message);
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
})->with([
    'reviewer phrase' => [
        '| Reviewer | Firstlight release reviewer |',
        '| Reviewer | Placeholder release reviewer |',
        'Release review field contains placeholder token: Reviewer (PLACEHOLDER)',
    ],
    'date phrase case insensitive' => [
        '| Review date | 2026-08-05 |',
        '| Review date | tBd after device review |',
        'Release review field contains placeholder token: Review date (TBD)',
    ],
    'iOS target sample word' => [
        '| iOS release target | iPhone 17 Pro, iOS 26.5, simulator release target |',
        '| iOS release target | Sample iPhone 17 Pro, iOS 26.5 target |',
        'Release review field contains placeholder token: iOS release target (SAMPLE)',
    ],
    'Android target dummy word' => [
        '| Android release target | Pixel 9 Pro, Android API 36, emulator release target |',
        '| Android release target | Dummy Pixel 9 Pro, Android API 36 target |',
        'Release review field contains placeholder token: Android release target (DUMMY)',
    ],
    'screenshot detail fake word' => [
        '| ios-light | `docs/screenshots/transient-feedback/ios-light.png` | PASS | Release capture reviewed on the recorded iOS target. |',
        '| ios-light | `docs/screenshots/transient-feedback/ios-light.png` | PASS | Fake release capture reviewed on the recorded iOS target. |',
        'Release review screenshot detail contains placeholder token: ios-light (FAKE)',
    ],
    'evidence detail phrase' => [
        '| TalkBack | PASS | TalkBack live region, focus, and actions were reviewed. |',
        '| TalkBack | PASS | TBC after device review with the release build. |',
        'Release review evidence detail contains placeholder token: TalkBack (TBC)',
    ],
    'word boundary avoids substring assumptions' => [
        '| VoiceOver | PASS | VoiceOver announcement, focus, and actions were reviewed. |',
        '| VoiceOver | PASS | FIXME after VoiceOver review with the release build. |',
        'Release review evidence detail contains placeholder token: VoiceOver (FIXME)',
    ],
]);

it('release review placeholder matching uses words rather than arbitrary substrings', function () {
    $fixture = transientFeedbackGateFixture();
    $fixture = initializeTransientFeedbackReleaseRepositories($fixture);
    writeTransientFeedbackReview($fixture, $fixture['package_revision'], $fixture['showcase_revision']);
    $reviewPath = $fixture['package'].'/spec/reviews/transient-feedback-alpha.md';
    $review = (string) file_get_contents($reviewPath);
    $review = str_replace(
        'Release capture reviewed on the recorded iOS target.',
        'Release capture sampled directly from the recorded iOS target.',
        $review,
    );
    file_put_contents($reviewPath, $review);
    commitTransientFeedbackFixture($fixture['package'], 'publish sampled evidence wording');

    try {
        $process = runTransientFeedbackGate($fixture, ['--showcase='.$fixture['showcase']]);

        expect($process->getErrorOutput())->not->toContain('placeholder token: ios-light');
    } finally {
        removeTransientFeedbackGateFixture($fixture);
    }
});

it('keeps the real current release gate blocked until Task 8 evidence exists', function () {
    $root = dirname(__DIR__, 2);
    $process = new Process([$root.'/bin/check-transient-feedback'], $root);
    $process->setTimeout(15);
    $process->run();

    expect($process->getExitCode())->toBe(1);
});
