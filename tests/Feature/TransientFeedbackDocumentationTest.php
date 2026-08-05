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
): string
{
    $width = 320;
    $height = 568;
    $seed = array_sum(array_map('ord', str_split($variant))) % 255;
    $row = chr(0).str_repeat(chr($seed).chr(40).chr(120).chr(255), $width);
    $raw = str_repeat($row, $height);
    $idat = $invalidZlib ? 'not-zlib-data' : gzcompress($raw, 9);
    $png = "\x89PNG\r\n\x1a\n"
        .transientFeedbackPngChunk('IHDR', pack('NNCCCCC', $width, $height, 8, 6, 0, 0, 0))
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
function writeTransientFeedbackShowcaseContract(array $fixture, int $testExit = 0): void
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

use Native\Mobile\Facades\Native;

it('publishes stable transient feedback capture content', function () {
    Native::visit('/captures/transient-feedback');
});
PHP,
    );
    file_put_contents(
        $fixture['showcase'].'/artisan',
        "#!/usr/bin/env php\n<?php fwrite(STDERR, 'fixture focused test failure'); exit({$testExit});\n",
    );
    chmod($fixture['showcase'].'/artisan', 0755);
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
function initializeTransientFeedbackReleaseRepositories(array $fixture, int $testExit = 0): array
{
    writeTransientFeedbackShowcaseContract($fixture, $testExit);
    $showcaseRevision = initializeTransientFeedbackGitRepository($fixture['showcase']);
    $packageRevision = initializeTransientFeedbackGitRepository($fixture['package']);

    return [
        ...$fixture,
        'package_revision' => $packageRevision,
        'showcase_revision' => $showcaseRevision,
    ];
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

it('keeps the real current release gate blocked until Task 8 evidence exists', function () {
    $root = dirname(__DIR__, 2);
    $process = new Process([$root.'/bin/check-transient-feedback'], $root);
    $process->setTimeout(15);
    $process->run();

    expect($process->getExitCode())->toBe(1);
});
