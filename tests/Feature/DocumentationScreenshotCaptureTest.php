<?php

use FirstlightUI\Documentation\CaptureCommandRunner;
use FirstlightUI\Documentation\CaptureRequest;
use FirstlightUI\Documentation\BatchCaptureRequest;
use FirstlightUI\Documentation\DocumentationScreenshotCapture;

foreach ([
    'CaptureRequest.php',
    'CaptureReport.php',
    'BatchCaptureRequest.php',
    'BatchCaptureReport.php',
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
    mkdir($package.'/docs/screenshots/status-label', 0777, true);
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
            'status-label' => [
                'route' => '/captures/status-label',
                'test' => 'php artisan test tests/Feature/StatusLabelCaptureTest.php',
                'outputs' => [
                    'ios-light' => 'docs/screenshots/status-label/ios-light.png',
                    'ios-dark' => 'docs/screenshots/status-label/ios-dark.png',
                    'android-light' => 'docs/screenshots/status-label/android-light.png',
                    'android-dark' => 'docs/screenshots/status-label/android-dark.png',
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
    $iosReduceMotion = '0';
    $androidAppearance = 'no';
    $androidAnimatorScale = '1.0';

    $runner = new FakeCaptureCommandRunner(function (array $command, ?string $cwd) use (
        $fixture,
        $identicalAppearances,
        $failAndroidCapture,
        &$iosAppearance,
        &$iosReduceMotion,
        &$androidAppearance,
        &$androidAnimatorScale,
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
        if ($command === ['xcrun', 'simctl', 'terminate', 'IOS-1', 'dev.firstlightui.showcase']) {
            return $ok();
        }
        if ($command === ['adb', '-s', 'emulator-5554', 'shell', 'am', 'force-stop', 'dev.firstlightui.showcase']) {
            return $ok();
        }
        if ($command === ['adb', '-s', 'emulator-5554', 'shell', 'dumpsys', 'window']) {
            return $ok("mCurrentFocus=Window{1 u0 dev.firstlightui.showcase/com.nativephp.mobile.ui.MainActivity}\n");
        }
        if ($command === ['adb', '-s', 'emulator-5554', 'exec-out', 'uiautomator', 'dump', '/dev/tty']) {
            return $ok('<hierarchy><node text="Firstlight Segmented" package="dev.firstlightui.showcase" /></hierarchy>');
        }
        if ($command === ['adb', '-s', 'emulator-5554', 'shell', 'sleep', '1']) {
            return $ok();
        }
        if ($command === ['xcrun', 'simctl', 'ui', 'IOS-1', 'appearance']) {
            return $ok($iosAppearance."\n");
        }
        if ($command === ['xcrun', 'simctl', 'spawn', 'IOS-1', 'defaults', 'read', 'com.apple.Accessibility', 'ReduceMotionEnabled']) {
            return $ok($iosReduceMotion."\n");
        }
        if (array_slice($command, 0, 9) === ['xcrun', 'simctl', 'spawn', 'IOS-1', 'defaults', 'write', 'com.apple.Accessibility', 'ReduceMotionEnabled', '-bool']) {
            $iosReduceMotion = $command[9] === 'true' ? '1' : '0';

            return $ok();
        }
        if ($command === ['adb', '-s', 'emulator-5554', 'shell', 'cmd', 'uimode', 'night']) {
            return $ok("Night mode: {$androidAppearance}\n");
        }
        if ($command === ['adb', '-s', 'emulator-5554', 'shell', 'settings', 'get', 'global', 'animator_duration_scale']) {
            return $ok($androidAnimatorScale."\n");
        }
        if (array_slice($command, 0, 8) === ['adb', '-s', 'emulator-5554', 'shell', 'settings', 'put', 'global', 'animator_duration_scale']) {
            $androidAnimatorScale = $command[8];

            return $ok();
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

it('forces a fresh development bundle for the requested start URL and restores the showcase environment', function () {
    $fixture = screenshotCaptureFixture();
    $originalEnvironment = file_get_contents($fixture['showcase'].'/.env');
    [$normalRunner] = successfulCaptureRunner($fixture);
    $launchEnvironments = [];
    $runner = new FakeCaptureCommandRunner(function (array $command, ?string $cwd) use (
        $fixture,
        $normalRunner,
        &$launchEnvironments,
    ): array {
        if (array_slice($command, 0, 3) === ['php', 'artisan', 'native:run']) {
            $launchEnvironments[] = file_get_contents($fixture['showcase'].'/.env');
        }

        return $normalRunner->run($command, $cwd);
    });

    try {
        (new DocumentationScreenshotCapture($runner, $fixture['package']))->capture(captureRequest($fixture));

        expect($launchEnvironments)->toHaveCount(2);

        foreach ($launchEnvironments as $environment) {
            expect($environment)
                ->toContain("NATIVEPHP_START_URL=/captures/segmented\n")
                ->toContain("NATIVEPHP_APP_VERSION=DEBUG\n");
        }

        expect(file_get_contents($fixture['showcase'].'/.env'))->toBe($originalEnvironment);
    } finally {
        removeScreenshotCaptureFixture($fixture);
    }
});

it('terminates each installed app before launching the requested capture route', function () {
    $fixture = screenshotCaptureFixture();
    [$runner] = successfulCaptureRunner($fixture);

    try {
        (new DocumentationScreenshotCapture($runner, $fixture['package']))->capture(captureRequest($fixture));

        $commands = array_column($runner->calls, 'command');
        $iosStop = array_search(['xcrun', 'simctl', 'terminate', 'IOS-1', 'dev.firstlightui.showcase'], $commands, true);
        $iosLaunch = array_search(['php', 'artisan', 'native:run', 'ios', 'IOS-1', '--start-url=/captures/segmented', '--no-tty'], $commands, true);
        $androidStop = array_search(['adb', '-s', 'emulator-5554', 'shell', 'am', 'force-stop', 'dev.firstlightui.showcase'], $commands, true);
        $androidLaunch = array_search(['php', 'artisan', 'native:run', 'android', 'emulator-5554', '--start-url=/captures/segmented', '--no-tty'], $commands, true);

        expect($iosStop)->toBeInt()->toBeLessThan($iosLaunch)
            ->and($androidStop)->toBeInt()->toBeLessThan($androidLaunch);
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

it('waits for the Android capture title before taking screenshots', function () {
    $fixture = screenshotCaptureFixture();
    [$normalRunner] = successfulCaptureRunner($fixture);
    $readinessChecks = 0;
    $runner = new FakeCaptureCommandRunner(function (array $command, ?string $cwd) use ($normalRunner, &$readinessChecks): array {
        if ($command === ['adb', '-s', 'emulator-5554', 'exec-out', 'uiautomator', 'dump', '/dev/tty']) {
            $readinessChecks++;

            return [
                'exitCode' => 0,
                'stdout' => $readinessChecks === 1
                    ? '<hierarchy><node text="Loading..." package="dev.firstlightui.showcase" /></hierarchy>'
                    : '<hierarchy><node text="Firstlight Segmented" package="dev.firstlightui.showcase" /></hierarchy>',
                'stderr' => '',
            ];
        }

        return $normalRunner->run($command, $cwd);
    });

    try {
        $report = (new DocumentationScreenshotCapture($runner, $fixture['package']))->capture(captureRequest($fixture));
        $commands = array_column($runner->calls, 'command');
        $lastReadinessCheck = array_search(
            ['adb', '-s', 'emulator-5554', 'exec-out', 'uiautomator', 'dump', '/dev/tty'],
            array_reverse($commands, preserve_keys: true),
            true,
        );
        $firstScreenshot = array_search(
            ['adb', '-s', 'emulator-5554', 'exec-out', 'screencap', '-p'],
            $commands,
            true,
        );

        expect($readinessChecks)->toBeGreaterThan(1)
            ->and($lastReadinessCheck)->toBeInt()->toBeLessThan($firstScreenshot)
            ->and(array_map('is_file', $report->outputs))->each->toBeTrue();
    } finally {
        removeScreenshotCaptureFixture($fixture);
    }
});

it('captures multiple components by platform appearance after only one build per platform', function () {
    $fixture = screenshotCaptureFixture();
    $iosContainer = dirname($fixture['package']).'/ios-container';
    mkdir($iosContainer.'/Documents/app', 0777, true);
    file_put_contents($iosContainer.'/Documents/app/.env', "NATIVEPHP_START_URL=/captures/segmented\nNATIVEPHP_APP_VERSION=0.1.123\n");
    $iosAppearance = 'dark';
    $iosReduceMotion = '0';
    $androidAppearance = 'yes';
    $androidAnimatorScale = '1.0';
    $iosComponent = 'Segmented';
    $androidComponent = 'Segmented';
    $androidRuntimeEnvironment = "NATIVEPHP_START_URL=/captures/segmented\nNATIVEPHP_APP_VERSION=0.1.123\n";
    $androidPushedEnvironment = '';

    $runner = new FakeCaptureCommandRunner(function (array $command, ?string $cwd) use (
        $fixture,
        $iosContainer,
        &$iosAppearance,
        &$iosReduceMotion,
        &$androidAppearance,
        &$androidAnimatorScale,
        &$iosComponent,
        &$androidComponent,
        &$androidRuntimeEnvironment,
        &$androidPushedEnvironment,
    ): array {
        $ok = fn (string $stdout = ''): array => ['exitCode' => 0, 'stdout' => $stdout, 'stderr' => ''];
        $joined = implode(' ', $command);

        if ($command === ['git', 'rev-parse', 'HEAD']) {
            return $ok($cwd === realpath($fixture['package']) ? "package-sha\n" : "showcase-sha\n");
        }
        if ($command === ['git', 'status', '--porcelain']) {
            return $ok();
        }
        if ($command === ['xcrun', 'simctl', 'list', 'devices', '-j']) {
            return $ok(json_encode(['devices' => ['runtime' => [['udid' => 'IOS-1', 'isAvailable' => true]]]], JSON_THROW_ON_ERROR));
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
        if ($command === ['xcrun', 'simctl', 'terminate', 'IOS-1', 'dev.firstlightui.showcase']
            || $command === ['adb', '-s', 'emulator-5554', 'shell', 'am', 'force-stop', 'dev.firstlightui.showcase']) {
            return $ok();
        }
        if (array_slice($command, 0, 3) === ['php', 'artisan', 'native:run']) {
            return $ok();
        }
        if ($command === ['xcrun', 'simctl', 'ui', 'IOS-1', 'appearance']) {
            return $ok($iosAppearance."\n");
        }
        if ($command === ['xcrun', 'simctl', 'spawn', 'IOS-1', 'defaults', 'read', 'com.apple.Accessibility', 'ReduceMotionEnabled']) {
            return $ok($iosReduceMotion."\n");
        }
        if (array_slice($command, 0, 9) === ['xcrun', 'simctl', 'spawn', 'IOS-1', 'defaults', 'write', 'com.apple.Accessibility', 'ReduceMotionEnabled', '-bool']) {
            $iosReduceMotion = $command[9] === 'true' ? '1' : '0';

            return $ok();
        }
        if ($command === ['adb', '-s', 'emulator-5554', 'shell', 'cmd', 'uimode', 'night']) {
            return $ok("Night mode: {$androidAppearance}\n");
        }
        if ($command === ['adb', '-s', 'emulator-5554', 'shell', 'settings', 'get', 'global', 'animator_duration_scale']) {
            return $ok($androidAnimatorScale."\n");
        }
        if (array_slice($command, 0, 8) === ['adb', '-s', 'emulator-5554', 'shell', 'settings', 'put', 'global', 'animator_duration_scale']) {
            $androidAnimatorScale = $command[8];

            return $ok();
        }
        if (array_slice($command, 0, 5) === ['xcrun', 'simctl', 'ui', 'IOS-1', 'appearance']) {
            $iosAppearance = $command[5];

            return $ok();
        }
        if (array_slice($command, 0, 7) === ['adb', '-s', 'emulator-5554', 'shell', 'cmd', 'uimode', 'night']) {
            $androidAppearance = $command[7];

            return $ok();
        }
        if ($command === ['xcrun', 'simctl', 'get_app_container', 'IOS-1', 'dev.firstlightui.showcase', 'data']) {
            return $ok($iosContainer."\n");
        }
        if ($command === ['xcrun', 'simctl', 'launch', 'IOS-1', 'dev.firstlightui.showcase']) {
            $iosComponent = str_contains((string) file_get_contents($iosContainer.'/Documents/app/.env'), 'status-label') ? 'Status Label' : 'Segmented';
            return $ok();
        }
        if ($command === ['adb', '-s', 'emulator-5554', 'exec-out', 'run-as', 'dev.firstlightui.showcase', 'cat', '/data/data/dev.firstlightui.showcase/app_storage/laravel/.env']) {
            return $ok($androidRuntimeEnvironment);
        }
        if (array_slice($command, 0, 4) === ['adb', '-s', 'emulator-5554', 'push']) {
            $androidPushedEnvironment = (string) file_get_contents($command[4]);
            return $ok();
        }
        if (array_slice($command, 0, 7) === ['adb', '-s', 'emulator-5554', 'shell', 'run-as', 'dev.firstlightui.showcase', 'cp']) {
            $androidRuntimeEnvironment = $androidPushedEnvironment;
            return $ok();
        }
        if (array_slice($command, 0, 5) === ['adb', '-s', 'emulator-5554', 'shell', 'rm']) {
            return $ok();
        }
        if ($command === ['sleep', '3']) {
            return $ok();
        }
        if (array_slice($command, 0, 8) === ['adb', '-s', 'emulator-5554', 'shell', 'am', 'start', '-W', '-a']) {
            $androidComponent = str_contains($joined, 'status-label') ? 'Status Label' : 'Segmented';

            return $ok();
        }
        if (array_slice($command, 0, 6) === ['adb', '-s', 'emulator-5554', 'shell', 'monkey', '-p']) {
            $androidComponent = str_contains($androidRuntimeEnvironment, 'status-label') ? 'Status Label' : 'Segmented';
            return $ok();
        }
        if ($command === ['adb', '-s', 'emulator-5554', 'shell', 'dumpsys', 'window']) {
            return $ok("mCurrentFocus=Window{1 u0 dev.firstlightui.showcase/com.nativephp.mobile.ui.MainActivity}\n");
        }
        if ($command === ['adb', '-s', 'emulator-5554', 'exec-out', 'uiautomator', 'dump', '/dev/tty']) {
            return $ok('<hierarchy><node text="Firstlight '.$androidComponent.'" package="dev.firstlightui.showcase" /></hierarchy>');
        }
        if (array_slice($command, 0, 5) === ['xcrun', 'simctl', 'io', 'IOS-1', 'screenshot'] && isset($command[5])) {
            file_put_contents($command[5], fakeCapturePng('ios-'.$iosAppearance.'-'.$iosComponent));

            return $ok();
        }
        if ($command === ['adb', '-s', 'emulator-5554', 'exec-out', 'screencap', '-p']) {
            return $ok(fakeCapturePng('android-'.$androidAppearance.'-'.$androidComponent));
        }
        if (str_contains($joined, 'CaptureTest.php')) {
            return $ok();
        }

        return ['exitCode' => 1, 'stdout' => '', 'stderr' => 'Unexpected command: '.$joined];
    });

    try {
        $report = (new DocumentationScreenshotCapture($runner, $fixture['package']))->captureBatch(
            new BatchCaptureRequest(
                components: ['Segmented', 'StatusLabel'],
                packageRoot: $fixture['package'],
                showcaseRoot: $fixture['showcase'],
                iosUdid: 'IOS-1',
                androidSerial: 'emulator-5554',
                release: false,
                keepFailed: false,
            ),
        );

        $commands = array_column($runner->calls, 'command');
        $builds = array_values(array_filter($commands, fn (array $command): bool => array_slice($command, 0, 3) === ['php', 'artisan', 'native:run']));
        $captures = array_keys($report->outputs);

        expect($builds)->toHaveCount(2)
            ->and($captures)->toBe(['segmented', 'status-label'])
            ->and($report->outputs['segmented'])->toHaveKeys(['ios-light', 'ios-dark', 'android-light', 'android-dark'])
            ->and($report->outputs['status-label'])->toHaveKeys(['ios-light', 'ios-dark', 'android-light', 'android-dark'])
            ->and(array_map('is_file', array_merge(...array_values($report->outputs))))->each->toBeTrue()
            ->and($iosAppearance)->toBe('dark')
            ->and($iosReduceMotion)->toBe('0')
            ->and($androidAppearance)->toBe('yes')
            ->and($androidAnimatorScale)->toBe('1.0');

        $freezeAnimator = array_search(
            ['adb', '-s', 'emulator-5554', 'shell', 'settings', 'put', 'global', 'animator_duration_scale', '0'],
            $commands,
            true,
        );
        $firstAndroidScreenshot = array_search(
            ['adb', '-s', 'emulator-5554', 'exec-out', 'screencap', '-p'],
            $commands,
            true,
        );
        $restoreAnimator = array_search(
            ['adb', '-s', 'emulator-5554', 'shell', 'settings', 'put', 'global', 'animator_duration_scale', '1.0'],
            array_reverse($commands, preserve_keys: true),
            true,
        );

        expect($freezeAnimator)->toBeInt()->toBeLessThan($firstAndroidScreenshot)
            ->and($restoreAnimator)->toBeInt()->toBeGreaterThan($firstAndroidScreenshot);

        $enableReduceMotion = array_search(
            ['xcrun', 'simctl', 'spawn', 'IOS-1', 'defaults', 'write', 'com.apple.Accessibility', 'ReduceMotionEnabled', '-bool', 'true'],
            $commands,
            true,
        );
        $iosLaunch = array_search(
            ['php', 'artisan', 'native:run', 'ios', 'IOS-1', '--start-url=/captures/segmented', '--no-tty'],
            $commands,
            true,
        );
        $restoreReduceMotion = array_search(
            ['xcrun', 'simctl', 'spawn', 'IOS-1', 'defaults', 'write', 'com.apple.Accessibility', 'ReduceMotionEnabled', '-bool', 'false'],
            array_reverse($commands, preserve_keys: true),
            true,
        );

        expect($enableReduceMotion)->toBeInt()->toBeLessThan($iosLaunch)
            ->and($restoreReduceMotion)->toBeInt()->toBeGreaterThan($iosLaunch);

        expect(collect($commands)->contains(
            fn (array $command): bool => in_array('openurl', $command, true),
        ))->toBeFalse()
            ->and(collect($commands)->filter(
                fn (array $command): bool => $command === ['sleep', '3'],
            ))->toHaveCount(4)
            ->and(file_get_contents($iosContainer.'/Documents/app/.env'))->toContain("NATIVEPHP_START_URL=/\n")
            ->and($androidRuntimeEnvironment)->toContain("NATIVEPHP_START_URL=/\n");

        $captureLabels = array_keys(array_filter(
            $report->commands,
            fn (string $command): bool => str_contains($command, 'screenshot') || str_contains($command, 'screencap'),
        ));
        expect($captureLabels)->toBe([
            'ios-light-segmented-capture-1',
            'ios-light-segmented-capture-2',
            'ios-light-status-label-capture-1',
            'ios-light-status-label-capture-2',
            'ios-dark-segmented-capture-1',
            'ios-dark-segmented-capture-2',
            'ios-dark-status-label-capture-1',
            'ios-dark-status-label-capture-2',
            'android-light-segmented-capture-1',
            'android-light-segmented-capture-2',
            'android-light-status-label-capture-1',
            'android-light-status-label-capture-2',
            'android-dark-segmented-capture-1',
            'android-dark-segmented-capture-2',
            'android-dark-status-label-capture-1',
            'android-dark-status-label-capture-2',
        ]);
    } finally {
        removeScreenshotCaptureFixture($fixture);
    }
});

it('accepts only minute intentional animation between consecutive capture frames', function () {
    $runner = new FakeCaptureCommandRunner(function (array $command): array {
        if (array_slice($command, 0, 4) !== ['magick', 'compare', '-metric', 'AE']) {
            return ['exitCode' => 1, 'stdout' => '', 'stderr' => 'Unexpected command'];
        }

        return [
            'exitCode' => 1,
            'stdout' => '',
            'stderr' => str_contains($command[5], 'minor')
                ? '35.6147 (1.12629e-05)'
                : '316500 (0.1)',
        ];
    });
    $capture = new DocumentationScreenshotCapture($runner, __DIR__);
    $method = new ReflectionMethod($capture, 'screenshotsAreVisuallyStable');

    expect($method->invoke($capture, '/tmp/previous.png', '/tmp/minor.png', 'ios-light-button', 2))->toBeTrue()
        ->and($method->invoke($capture, '/tmp/previous.png', '/tmp/major.png', 'ios-light-button', 2))->toBeFalse();
});

it('accepts a visually recurring animation frame from recent capture history', function () {
    $temporaryRoot = sys_get_temp_dir().'/firstlight-recurring-capture-'.bin2hex(random_bytes(6));
    mkdir($temporaryRoot, 0700, true);
    $output = $temporaryRoot.'/ios-light.png';
    $frames = ['phase-a-1', 'phase-b', 'phase-a-2'];
    $captureAttempt = 0;

    $runner = new FakeCaptureCommandRunner(function (array $command) use (&$captureAttempt, $frames): array {
        if (array_slice($command, 0, 5) === ['xcrun', 'simctl', 'io', 'IOS-1', 'screenshot']) {
            if (! isset($frames[$captureAttempt])) {
                return ['exitCode' => 1, 'stdout' => '', 'stderr' => 'Capture should have stabilised on the recurring phase.'];
            }

            file_put_contents($command[5], fakeCapturePng($frames[$captureAttempt++]));

            return ['exitCode' => 0, 'stdout' => '', 'stderr' => ''];
        }

        if (array_slice($command, 0, 4) === ['magick', 'compare', '-metric', 'AE']) {
            $firstFrame = str_ends_with($command[4], '.1');
            $recurringFrame = str_ends_with($command[5], '.3');

            return [
                'exitCode' => 1,
                'stdout' => '',
                'stderr' => $firstFrame && $recurringFrame
                    ? '20 (5.0e-06)'
                    : '950 (0.03)',
            ];
        }

        return ['exitCode' => 1, 'stdout' => '', 'stderr' => 'Unexpected command: '.implode(' ', $command)];
    });
    $capture = new DocumentationScreenshotCapture($runner, __DIR__);
    $method = new ReflectionMethod($capture, 'captureStable');

    try {
        $method->invoke(
            $capture,
            'ios-light',
            ['xcrun', 'simctl', 'io', 'IOS-1', 'screenshot'],
            $output,
            true,
        );

        expect(file_get_contents($output))->toBe(fakeCapturePng('phase-a-2'));
    } finally {
        foreach (glob($temporaryRoot.'/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($temporaryRoot);
    }
});
