<?php

use FirstlightUI\Documentation\CaptureCommandRunner;
use FirstlightUI\Documentation\CaptureRequest;
use FirstlightUI\Documentation\DocumentationScreenshotCapture;

foreach ([
    'CaptureRequest.php',
    'CaptureReport.php',
    'CaptureCommandRunner.php',
    'DocumentationScreenshotCapture.php',
] as $supportFile) {
    $path = dirname(__DIR__, 2).'/bin/support/'.$supportFile;
    if (is_file($path)) {
        require_once $path;
    }
}

if (class_exists(CaptureCommandRunner::class)) {
    class FakeCaptureCommandRunner extends CaptureCommandRunner
    {
        /** @var list<array{command: list<string>, cwd: string|null}> */
        public array $calls = [];

        public function __construct(private readonly Closure $handler) {}

        public function run(array $command, ?string $cwd = null): array
        {
            $this->calls[] = ['command' => $command, 'cwd' => $cwd];

            return ($this->handler)($command, $cwd);
        }
    }
}

/** @return array{package: string, showcase: string} */
function screenshotCaptureFixture(): array
{
    $base = sys_get_temp_dir().'/firstlight-capture-'.bin2hex(random_bytes(6));
    $package = $base.'/package';
    $showcase = $base.'/showcase';

    mkdir($package.'/spec', 0777, true);
    mkdir($package.'/docs/screenshots/segmented', 0777, true);
    mkdir($showcase.'/vendor/composer', 0777, true);
    mkdir($showcase.'/nativephp/android', 0777, true);
    mkdir($showcase.'/nativephp/ios', 0777, true);

    file_put_contents($package.'/spec/screenshots.json', json_encode([
        'components' => [
            'segmented' => [
                'route' => '/captures/segmented',
                'test' => 'php artisan test tests/Feature/SegmentedCaptureTest.php',
                'outputs' => [
                    'ios-light' => 'docs/screenshots/segmented/ios-light.png',
                    'ios-dark' => 'docs/screenshots/segmented/ios-dark.png',
                    'android-light' => 'docs/screenshots/segmented/android-light.png',
                    'android-dark' => 'docs/screenshots/segmented/android-dark.png',
                ],
            ],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

    file_put_contents($showcase.'/vendor/composer/installed.php', <<<'PHP'
        <?php
        return ['versions' => ['firstlightui/nativephp' => ['reference' => 'package-sha']]];
        PHP);
    file_put_contents($showcase.'/.env', "NATIVEPHP_APP_ID=dev.firstlightui.showcase\n");

    return compact('package', 'showcase');
}

function fakeCapturePng(string $variant): string
{
    return "\x89PNG\r\n\x1a\n".pack('N', 13).'IHDR'.pack('NN', 390, 844).str_pad($variant, 24, '!');
}

/** @param array{package: string, showcase: string} $fixture */
function removeScreenshotCaptureFixture(array $fixture): void
{
    $root = dirname($fixture['package']);
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($root);
}

/** @return array{0: FakeCaptureCommandRunner, 1: Closure(): array{ios: string, android: string}} */
function successfulCaptureRunner(array $fixture, bool $identicalAppearances = false, bool $failAndroidCapture = false): array
{
    $iosAppearance = 'light';
    $androidAppearance = 'no';

    $runner = new FakeCaptureCommandRunner(function (array $command, ?string $cwd) use (
        $fixture,
        $identicalAppearances,
        $failAndroidCapture,
        &$iosAppearance,
        &$androidAppearance,
    ): array {
        $joined = implode(' ', $command);
        $ok = fn (string $stdout = ''): array => ['exitCode' => 0, 'stdout' => $stdout, 'stderr' => ''];

        if ($command === ['git', 'rev-parse', 'HEAD']) {
            return $ok($cwd === realpath($fixture['package']) ? "package-sha\n" : "showcase-sha\n");
        }
        if ($command === ['git', 'status', '--porcelain']) {
            return $ok();
        }
        if ($command === ['xcrun', 'simctl', 'list', 'devices', '-j']) {
            return $ok(json_encode(['devices' => ['runtime' => [['udid' => 'IOS-1', 'name' => 'iPhone 17', 'state' => 'Booted', 'isAvailable' => true]]]], JSON_THROW_ON_ERROR));
        }
        if ($command === ['adb', '-s', 'emulator-5554', 'get-state']) {
            return $ok("device\n");
        }
        if ($command === ['adb', '-s', 'emulator-5554', 'shell', 'getprop', 'ro.kernel.qemu']) {
            return $ok("1\n");
        }
        if ($command === ['adb', '-s', 'emulator-5554', 'shell', 'pm', 'path', 'dev.firstlightui.showcase']) {
            return $ok("package:/data/app/dev.firstlightui.showcase/base.apk\n");
        }
        if ($command === ['adb', '-s', 'emulator-5554', 'shell', 'dumpsys', 'window']) {
            return $ok("mCurrentFocus=Window{1 u0 dev.firstlightui.showcase/com.nativephp.mobile.ui.MainActivity}\n");
        }
        if ($command === ['adb', '-s', 'emulator-5554', 'shell', 'sleep', '1']) {
            return $ok();
        }
        if ($command === ['xcrun', 'simctl', 'ui', 'IOS-1', 'appearance']) {
            return $ok($iosAppearance."\n");
        }
        if ($command === ['adb', '-s', 'emulator-5554', 'shell', 'cmd', 'uimode', 'night']) {
            return $ok("Night mode: {$androidAppearance}\n");
        }
        if (array_slice($command, 0, 5) === ['xcrun', 'simctl', 'ui', 'IOS-1', 'appearance']) {
            $iosAppearance = $command[5];

            return $ok();
        }
        if (array_slice($command, 0, 7) === ['adb', '-s', 'emulator-5554', 'shell', 'cmd', 'uimode', 'night']) {
            $androidAppearance = $command[7];

            return $ok();
        }
        if (array_slice($command, 0, 5) === ['xcrun', 'simctl', 'io', 'IOS-1', 'screenshot'] && isset($command[5])) {
            file_put_contents($command[5], fakeCapturePng($identicalAppearances ? 'same' : 'ios-'.$iosAppearance));

            return $ok();
        }
        if ($command === ['adb', '-s', 'emulator-5554', 'exec-out', 'screencap', '-p']) {
            if ($failAndroidCapture) {
                return ['exitCode' => 1, 'stdout' => '', 'stderr' => 'capture failed'];
            }

            return $ok(fakeCapturePng($identicalAppearances ? 'same' : 'android-'.$androidAppearance));
        }
        if (str_contains($joined, 'SegmentedCaptureTest.php') || str_contains($joined, 'native:run')) {
            return $ok();
        }

        return ['exitCode' => 1, 'stdout' => '', 'stderr' => 'Unexpected command: '.$joined];
    });

    return [$runner, fn (): array => ['ios' => $iosAppearance, 'android' => $androidAppearance]];
}

function captureRequest(array $fixture, bool $release = false, string $ios = 'IOS-1', string $android = 'emulator-5554'): CaptureRequest
{
    return new CaptureRequest('Segmented', $fixture['package'], $fixture['showcase'], $ios, $android, $release, false);
}

it('rejects missing explicit targets before running commands', function () {
    $fixture = screenshotCaptureFixture();
    [$runner] = successfulCaptureRunner($fixture);

    try {
        $capture = new DocumentationScreenshotCapture($runner, $fixture['package']);

        expect(fn () => $capture->capture(captureRequest($fixture, ios: '', android: '')))
            ->toThrow(InvalidArgumentException::class, 'Explicit iOS Simulator and Android emulator identifiers are required')
            ->and($runner->calls)->toBe([]);
    } finally {
        removeScreenshotCaptureFixture($fixture);
    }
});

it('rejects a physical Android target', function () {
    $fixture = screenshotCaptureFixture();
    [$normalRunner] = successfulCaptureRunner($fixture);
    $runner = new FakeCaptureCommandRunner(function (array $command, ?string $cwd) use ($normalRunner): array {
        if ($command === ['adb', '-s', 'emulator-5554', 'shell', 'getprop', 'ro.kernel.qemu']) {
            return ['exitCode' => 0, 'stdout' => "0\n", 'stderr' => ''];
        }

        return $normalRunner->run($command, $cwd);
    });

    try {
        expect(fn () => (new DocumentationScreenshotCapture($runner, $fixture['package']))->capture(captureRequest($fixture)))
            ->toThrow(RuntimeException::class, 'not an Android emulator');
    } finally {
        removeScreenshotCaptureFixture($fixture);
    }
});

it('rejects dirty release repositories before device capture', function () {
    $fixture = screenshotCaptureFixture();
    [$normalRunner] = successfulCaptureRunner($fixture);
    $runner = new FakeCaptureCommandRunner(function (array $command, ?string $cwd) use ($fixture, $normalRunner): array {
        if ($command === ['git', 'status', '--porcelain'] && $cwd === realpath($fixture['showcase'])) {
            return ['exitCode' => 0, 'stdout' => " M routes/native.php\n", 'stderr' => ''];
        }

        return $normalRunner->run($command, $cwd);
    });

    try {
        expect(fn () => (new DocumentationScreenshotCapture($runner, $fixture['package']))->capture(captureRequest($fixture, release: true)))
            ->toThrow(RuntimeException::class, 'Release capture requires clean package and showcase repositories');
    } finally {
        removeScreenshotCaptureFixture($fixture);
    }
});

it('publishes no files after a capture failure and restores appearances', function () {
    $fixture = screenshotCaptureFixture();
    [$runner, $appearances] = successfulCaptureRunner($fixture, failAndroidCapture: true);

    try {
        expect(fn () => (new DocumentationScreenshotCapture($runner, $fixture['package']))->capture(captureRequest($fixture)))
            ->toThrow(RuntimeException::class, 'capture failed')
            ->and(glob($fixture['package'].'/docs/screenshots/segmented/*.png'))->toBe([])
            ->and($appearances())->toBe(['ios' => 'light', 'android' => 'no']);
    } finally {
        removeScreenshotCaptureFixture($fixture);
    }
});

it('publishes the complete differentiated matrix atomically', function () {
    $fixture = screenshotCaptureFixture();
    [$runner, $appearances] = successfulCaptureRunner($fixture);

    try {
        $report = (new DocumentationScreenshotCapture($runner, $fixture['package']))->capture(captureRequest($fixture));

        expect(array_keys($report->outputs))->toBe(['ios-light', 'ios-dark', 'android-light', 'android-dark'])
            ->and(array_map('is_file', $report->outputs))->each->toBeTrue()
            ->and(hash_file('sha256', $report->outputs['ios-light']))->not->toBe(hash_file('sha256', $report->outputs['ios-dark']))
            ->and(hash_file('sha256', $report->outputs['android-light']))->not->toBe(hash_file('sha256', $report->outputs['android-dark']))
            ->and($appearances())->toBe(['ios' => 'light', 'android' => 'no']);
    } finally {
        removeScreenshotCaptureFixture($fixture);
    }
});

it('rejects byte-identical light and dark captures', function () {
    $fixture = screenshotCaptureFixture();
    [$runner] = successfulCaptureRunner($fixture, identicalAppearances: true);

    try {
        expect(fn () => (new DocumentationScreenshotCapture($runner, $fixture['package']))->capture(captureRequest($fixture)))
            ->toThrow(RuntimeException::class, 'light and dark captures are byte-identical')
            ->and(glob($fixture['package'].'/docs/screenshots/segmented/*.png'))->toBe([]);
    } finally {
        removeScreenshotCaptureFixture($fixture);
    }
});

it('rejects Android captures when the showcase is not foregrounded', function () {
    $fixture = screenshotCaptureFixture();
    [$normalRunner] = successfulCaptureRunner($fixture);
    $runner = new FakeCaptureCommandRunner(function (array $command, ?string $cwd) use ($normalRunner): array {
        if ($command === ['adb', '-s', 'emulator-5554', 'shell', 'dumpsys', 'window']) {
            return [
                'exitCode' => 0,
                'stdout' => "mCurrentFocus=Window{1 u0 com.google.android.apps.nexuslauncher/.NexusLauncherActivity}\n",
                'stderr' => '',
            ];
        }

        return $normalRunner->run($command, $cwd);
    });

    try {
        expect(fn () => (new DocumentationScreenshotCapture($runner, $fixture['package']))->capture(captureRequest($fixture)))
            ->toThrow(RuntimeException::class, 'showcase is not foregrounded')
            ->and(glob($fixture['package'].'/docs/screenshots/segmented/*.png'))->toBe([]);
    } finally {
        removeScreenshotCaptureFixture($fixture);
    }
});

it('waits for the Android showcase to become foregrounded after launch', function () {
    $fixture = screenshotCaptureFixture();
    [$normalRunner] = successfulCaptureRunner($fixture);
    $foregroundChecks = 0;
    $runner = new FakeCaptureCommandRunner(function (array $command, ?string $cwd) use ($normalRunner, &$foregroundChecks): array {
        if ($command === ['adb', '-s', 'emulator-5554', 'shell', 'dumpsys', 'window'] && ++$foregroundChecks === 1) {
            return [
                'exitCode' => 0,
                'stdout' => "mCurrentFocus=Window{1 u0 com.google.android.apps.nexuslauncher/.NexusLauncherActivity}\n",
                'stderr' => '',
            ];
        }

        return $normalRunner->run($command, $cwd);
    });

    try {
        $report = (new DocumentationScreenshotCapture($runner, $fixture['package']))->capture(captureRequest($fixture));

        expect($foregroundChecks)->toBeGreaterThan(1)
            ->and(array_map('is_file', $report->outputs))->each->toBeTrue()
            ->and(collect($runner->calls)->contains(
                fn (array $call): bool => $call['command'] === ['adb', '-s', 'emulator-5554', 'shell', 'sleep', '1'],
            ))->toBeTrue();
    } finally {
        removeScreenshotCaptureFixture($fixture);
    }
});
