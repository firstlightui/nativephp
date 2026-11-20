<?php

declare(strict_types=1);

namespace FirstlightUI\Documentation;

use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Throwable;

final class DocumentationScreenshotCapture
{
    /** @var array<string, string> */
    private array $commands = [];

    public function __construct(
        private readonly CaptureCommandRunner $runner,
        private readonly string $packageRoot,
    ) {}

    public function capture(CaptureRequest $request): CaptureReport
    {
        $this->commands = [];
        $this->validateRequest($request);

        $packageRoot = $this->existingDirectory($request->packageRoot, 'package');
        $showcaseRoot = $this->existingDirectory($request->showcaseRoot, 'showcase');
        if ($packageRoot !== $this->existingDirectory($this->packageRoot, 'configured package')) {
            throw new InvalidArgumentException('Capture request package root does not match the configured package root.');
        }

        [$slug, $manifest] = $this->componentManifest($request->component, $packageRoot);
        $outputs = $this->outputPaths($manifest, $packageRoot);

        $packageRevision = trim($this->execute('package-revision', ['git', 'rev-parse', 'HEAD'], $packageRoot)['stdout']);
        $showcaseRevision = trim($this->execute('showcase-revision', ['git', 'rev-parse', 'HEAD'], $showcaseRoot)['stdout']);
        $packageDirty = trim($this->execute('package-status', ['git', 'status', '--porcelain'], $packageRoot)['stdout']) !== '';
        $showcaseDirty = trim($this->execute('showcase-status', ['git', 'status', '--porcelain'], $showcaseRoot)['stdout']) !== '';

        if ($request->release && ($packageDirty || $showcaseDirty)) {
            throw new RuntimeException('Release capture requires clean package and showcase repositories.');
        }

        $landscape = ($manifest['orientation'] ?? 'portrait') === 'landscape';
        $platforms = $manifest['platforms'] ?? ['ios', 'android'];
        $android = in_array('android', $platforms, true);

        $this->assertInstalledPackageRevision($showcaseRoot, $packageRevision);
        $this->assertIosSimulator($request->iosUdid, $manifest['ios-device'] ?? null);
        if ($android) {
            $this->assertAndroidEmulator($request->androidSerial);
        }
        $this->assertNativeHostProjects($showcaseRoot, $platforms);
        $androidAppId = $this->nativeAppId($showcaseRoot);
        $this->runFocusedShowcaseTest($manifest, $showcaseRoot);

        $temporaryRoot = sys_get_temp_dir().'/firstlight-docs-capture-'.bin2hex(random_bytes(8));
        if (! mkdir($temporaryRoot, 0700, true)) {
            throw new RuntimeException('Unable to create a temporary screenshot directory.');
        }

        $originalIosAppearance = null;
        $originalIosReduceMotion = null;
        $originalAndroidAppearance = null;
        $originalAndroidAnimatorScale = false;
        $originalAndroidRotation = null;
        $iosRotated = false;
        $environmentPath = $showcaseRoot.'/.env';
        $originalEnvironment = is_file($environmentPath) ? file_get_contents($environmentPath) : false;
        $failure = null;
        $restorationFailure = null;
        $published = [];

        try {
            if (! is_string($originalEnvironment)) {
                throw new RuntimeException('Showcase .env is required for native screenshot capture.');
            }
            $this->primeCaptureEnvironment($environmentPath, $originalEnvironment, $manifest['route']);

            $originalIosReduceMotion = $this->iosReduceMotion($request->iosUdid);
            $this->setIosReduceMotion($request->iosUdid, true, 'enable-ios-reduce-motion');

            $this->terminateIosApplication($request->iosUdid, $androidAppId);
            $this->execute(
                'launch-ios',
                ['php', 'artisan', 'native:run', 'ios', $request->iosUdid, '--start-url='.$manifest['route'], '--no-tty'],
                $showcaseRoot,
            );
            if ($android) {
                $this->execute(
                    'terminate-android-before-launch',
                    ['adb', '-s', $request->androidSerial, 'shell', 'am', 'force-stop', $androidAppId],
                );
                $this->execute(
                    'launch-android',
                    ['php', 'artisan', 'native:run', 'android', $request->androidSerial, '--start-url='.$manifest['route'], '--no-tty'],
                    $showcaseRoot,
                );
                $this->assertAndroidApplicationForeground($request->androidSerial, $androidAppId, 'after launch');
                $this->assertAndroidCaptureReady($request->androidSerial, $androidAppId, $request->component, $manifest);
            }

            $originalIosAppearance = $this->iosAppearance($request->iosUdid);
            if ($android) {
                $originalAndroidAppearance = $this->androidAppearance($request->androidSerial);
                $originalAndroidAnimatorScale = $this->androidAnimatorScale($request->androidSerial);
                $this->setAndroidAnimatorScale($request->androidSerial, '0', 'freeze-android-animator-scale');
            }

            if ($landscape) {
                $iosRotated = true;
                $this->rotateIos($request->iosUdid, landscape: true, label: 'rotate-ios-landscape');
                if ($android) {
                    $originalAndroidRotation = $this->androidRotation($request->androidSerial);
                    $this->setAndroidRotation($request->androidSerial, ['0', '1'], 'rotate-android-landscape');
                }
                $this->execute('rotation-settle', ['sleep', '2']);
            }

            foreach (['light', 'dark'] as $appearance) {
                $this->execute(
                    "ios-{$appearance}-appearance",
                    ['xcrun', 'simctl', 'ui', $request->iosUdid, 'appearance', $appearance],
                );
                $this->openIosPress($request->iosUdid, $androidAppId, $manifest, "ios-{$appearance}");
                $this->captureStable(
                    "ios-{$appearance}",
                    ['xcrun', 'simctl', 'io', $request->iosUdid, 'screenshot'],
                    $temporaryRoot.'/ios-'.$appearance.'.png',
                    commandWritesFile: true,
                );
                if ($landscape) {
                    // Simulator writes landscape screenshots portrait-sized with an EXIF rotation.
                    $path = $temporaryRoot.'/ios-'.$appearance.'.png';
                    $this->execute("ios-{$appearance}-orient", ['magick', $path, '-auto-orient', '-strip', $path]);
                }
            }

            foreach ($android ? ['light' => 'no', 'dark' => 'yes'] : [] as $appearance => $mode) {
                $this->execute(
                    "android-{$appearance}-appearance",
                    ['adb', '-s', $request->androidSerial, 'shell', 'cmd', 'uimode', 'night', $mode],
                );
                $this->assertAndroidApplicationForeground($request->androidSerial, $androidAppId, "before {$appearance} capture");
                if ($landscape) {
                    // The emulator can drop user_rotation when the app relaunches or changes mode.
                    $this->setAndroidRotation($request->androidSerial, ['0', '1'], "android-{$appearance}-landscape");
                    $this->execute("android-{$appearance}-rotation-settle", ['sleep', '2']);
                }
                $this->openAndroidPress($request->androidSerial, $manifest, "android-{$appearance}");
                $this->captureStable(
                    "android-{$appearance}",
                    ['adb', '-s', $request->androidSerial, 'exec-out', 'screencap', '-p'],
                    $temporaryRoot.'/android-'.$appearance.'.png',
                );
            }

            foreach ($platforms as $platform) {
                if (hash_file('sha256', $temporaryRoot."/{$platform}-light.png") === hash_file('sha256', $temporaryRoot."/{$platform}-dark.png")) {
                    throw new RuntimeException(ucfirst($platform).' light and dark captures are byte-identical.');
                }
            }

            $published = $this->publishAtomically($outputs, $temporaryRoot);
        } catch (Throwable $exception) {
            $failure = $exception;
        } finally {
            // Each step runs even when an earlier one fails, so one failure can't leave the
            // simulator, emulator or showcase .env changed. The first failure is reported.
            $restorations = [
                fn () => $iosRotated && $this->rotateIos($request->iosUdid, landscape: false, label: 'restore-ios-orientation'),
                fn () => $originalAndroidRotation !== null && $this->setAndroidRotation($request->androidSerial, $originalAndroidRotation, 'restore-android-orientation'),
                fn () => $originalIosAppearance !== null && $this->execute(
                    'restore-ios-appearance',
                    ['xcrun', 'simctl', 'ui', $request->iosUdid, 'appearance', $originalIosAppearance],
                ),
                fn () => $originalIosReduceMotion !== null && $this->setIosReduceMotion(
                    $request->iosUdid,
                    $originalIosReduceMotion,
                    'restore-ios-reduce-motion',
                ),
                fn () => $originalAndroidAppearance !== null && $this->execute(
                    'restore-android-appearance',
                    ['adb', '-s', $request->androidSerial, 'shell', 'cmd', 'uimode', 'night', $originalAndroidAppearance],
                ),
                fn () => $originalAndroidAnimatorScale !== false && $this->setAndroidAnimatorScale(
                    $request->androidSerial,
                    $originalAndroidAnimatorScale,
                    'restore-android-animator-scale',
                ),
                function () use ($originalEnvironment, $environmentPath): void {
                    if (is_string($originalEnvironment) && file_put_contents($environmentPath, $originalEnvironment) === false) {
                        throw new RuntimeException('Unable to restore showcase .env after screenshot capture.');
                    }
                },
            ];
            foreach ($restorations as $restore) {
                try {
                    $restore();
                } catch (Throwable $exception) {
                    $restorationFailure ??= $exception;
                }
            }

            if (! $request->keepFailed || $failure === null) {
                $this->removeDirectory($temporaryRoot);
            }
        }

        if ($failure !== null) {
            throw $failure;
        }
        if ($restorationFailure !== null) {
            throw $restorationFailure;
        }

        return new CaptureReport(
            packageRevision: $packageRevision,
            showcaseRevision: $showcaseRevision,
            packageDirty: $packageDirty,
            showcaseDirty: $showcaseDirty,
            iosUdid: $request->iosUdid,
            androidSerial: $request->androidSerial,
            commands: $this->commands,
            outputs: $published,
        );
    }

    public function captureBatch(BatchCaptureRequest $request): BatchCaptureReport
    {
        $this->commands = [];
        $this->validateBatchRequest($request);

        $packageRoot = $this->existingDirectory($request->packageRoot, 'package');
        $showcaseRoot = $this->existingDirectory($request->showcaseRoot, 'showcase');
        if ($packageRoot !== $this->existingDirectory($this->packageRoot, 'configured package')) {
            throw new InvalidArgumentException('Capture request package root does not match the configured package root.');
        }

        $components = [];
        foreach ($request->components as $component) {
            [$slug, $manifest] = $this->componentManifest($component, $packageRoot);
            if (isset($manifest['orientation']) || isset($manifest['ios-device']) || isset($manifest['platforms'])) {
                throw new RuntimeException("{$component} sets its own orientation or device; capture it on its own with bin/capture-doc-screenshots.");
            }
            $components[$slug] = [
                'name' => $component,
                'manifest' => $manifest,
                'outputs' => $this->outputPaths($manifest, $packageRoot),
            ];
        }

        $packageRevision = trim($this->execute('package-revision', ['git', 'rev-parse', 'HEAD'], $packageRoot)['stdout']);
        $showcaseRevision = trim($this->execute('showcase-revision', ['git', 'rev-parse', 'HEAD'], $showcaseRoot)['stdout']);
        $packageDirty = trim($this->execute('package-status', ['git', 'status', '--porcelain'], $packageRoot)['stdout']) !== '';
        $showcaseDirty = trim($this->execute('showcase-status', ['git', 'status', '--porcelain'], $showcaseRoot)['stdout']) !== '';

        if ($request->release && ($packageDirty || $showcaseDirty)) {
            throw new RuntimeException('Release capture requires clean package and showcase repositories.');
        }

        $this->assertInstalledPackageRevision($showcaseRoot, $packageRevision);
        $this->assertIosSimulator($request->iosUdid);
        $this->assertAndroidEmulator($request->androidSerial);
        $this->assertNativeHostProjects($showcaseRoot, ['ios', 'android']);
        $androidAppId = $this->nativeAppId($showcaseRoot);

        foreach ($components as $component) {
            $this->runFocusedShowcaseTest($component['manifest'], $showcaseRoot);
        }

        $temporaryRoot = sys_get_temp_dir().'/firstlight-docs-batch-'.bin2hex(random_bytes(8));
        if (! mkdir($temporaryRoot, 0700, true)) {
            throw new RuntimeException('Unable to create a temporary screenshot directory.');
        }

        foreach (array_keys($components) as $slug) {
            if (! mkdir($temporaryRoot.'/'.$slug, 0700, true)) {
                throw new RuntimeException("Unable to create a temporary screenshot directory for {$slug}.");
            }
        }

        $first = reset($components);
        $firstRoute = $first['manifest']['route'];
        $captureVersion = '0.1.'.(time() % 100000000);
        $environmentPath = $showcaseRoot.'/.env';
        $originalEnvironment = is_file($environmentPath) ? file_get_contents($environmentPath) : false;
        $originalIosAppearance = null;
        $originalIosReduceMotion = null;
        $originalAndroidAppearance = null;
        $originalAndroidAnimatorScale = false;
        $failure = null;
        $restorationFailure = null;
        $published = [];
        $iosRuntimeEnvironmentPath = null;
        $iosRuntimeEnvironment = null;
        $androidRuntimeEnvironmentPath = "/data/data/{$androidAppId}/app_storage/laravel/.env";
        $androidRuntimeEnvironment = null;

        try {
            if (! is_string($originalEnvironment)) {
                throw new RuntimeException('Showcase .env is required for native screenshot capture.');
            }

            $this->primeCaptureEnvironment($environmentPath, $originalEnvironment, $firstRoute, appVersion: $captureVersion);

            $originalIosReduceMotion = $this->iosReduceMotion($request->iosUdid);
            $this->setIosReduceMotion($request->iosUdid, true, 'enable-ios-reduce-motion');

            $this->terminateIosApplication($request->iosUdid, $androidAppId);
            $this->execute(
                'launch-ios',
                ['php', 'artisan', 'native:run', 'ios', $request->iosUdid, '--start-url='.$firstRoute, '--no-tty'],
                $showcaseRoot,
            );
            $iosAppContainer = trim($this->execute(
                'ios-app-container',
                ['xcrun', 'simctl', 'get_app_container', $request->iosUdid, $androidAppId, 'data'],
            )['stdout']);
            if ($iosAppContainer === '' || ! is_dir($iosAppContainer.'/Documents/app')) {
                throw new RuntimeException('Unable to locate the installed iOS showcase application container.');
            }
            $iosRuntimeEnvironmentPath = $iosAppContainer.'/Documents/app/.env';
            $iosRuntimeEnvironment = is_file($iosRuntimeEnvironmentPath)
                ? file_get_contents($iosRuntimeEnvironmentPath)
                : false;
            if (! is_string($iosRuntimeEnvironment)) {
                throw new RuntimeException('Unable to read the installed iOS showcase environment.');
            }
            $this->execute(
                'terminate-android-before-launch',
                ['adb', '-s', $request->androidSerial, 'shell', 'am', 'force-stop', $androidAppId],
            );
            $this->execute(
                'launch-android',
                ['php', 'artisan', 'native:run', 'android', $request->androidSerial, '--start-url='.$firstRoute, '--no-tty'],
                $showcaseRoot,
            );
            $this->assertAndroidApplicationForeground($request->androidSerial, $androidAppId, 'after launch');
            $this->assertAndroidCaptureReady($request->androidSerial, $androidAppId, $first['name'], $first['manifest']);
            $androidRuntimeEnvironment = $this->execute(
                'android-runtime-environment',
                ['adb', '-s', $request->androidSerial, 'exec-out', 'run-as', $androidAppId, 'cat', $androidRuntimeEnvironmentPath],
            )['stdout'];

            $originalIosAppearance = $this->iosAppearance($request->iosUdid);
            $originalAndroidAppearance = $this->androidAppearance($request->androidSerial);
            $originalAndroidAnimatorScale = $this->androidAnimatorScale($request->androidSerial);
            $this->setAndroidAnimatorScale($request->androidSerial, '0', 'freeze-android-animator-scale');

            foreach (['light', 'dark'] as $appearance) {
                $this->execute(
                    "ios-{$appearance}-appearance",
                    ['xcrun', 'simctl', 'ui', $request->iosUdid, 'appearance', $appearance],
                );

                foreach ($components as $slug => $component) {
                    $this->writeIosRuntimeEnvironment(
                        $iosRuntimeEnvironmentPath,
                        $iosRuntimeEnvironment,
                        $component['manifest']['route'],
                    );
                    $this->terminateIosApplication($request->iosUdid, $androidAppId);
                    $this->execute(
                        "ios-{$appearance}-{$slug}-launch",
                        ['xcrun', 'simctl', 'launch', $request->iosUdid, $androidAppId],
                    );
                    $this->execute("ios-{$appearance}-{$slug}-route-wait", ['sleep', '3']);
                    $this->openIosPress($request->iosUdid, $androidAppId, $component['manifest'], "ios-{$appearance}-{$slug}");
                    $this->captureStable(
                        "ios-{$appearance}-{$slug}",
                        ['xcrun', 'simctl', 'io', $request->iosUdid, 'screenshot'],
                        $temporaryRoot."/{$slug}/ios-{$appearance}.png",
                        commandWritesFile: true,
                    );
                }
            }

            foreach (['light' => 'no', 'dark' => 'yes'] as $appearance => $mode) {
                $this->execute(
                    "android-{$appearance}-appearance",
                    ['adb', '-s', $request->androidSerial, 'shell', 'cmd', 'uimode', 'night', $mode],
                );

                foreach ($components as $slug => $component) {
                    $this->writeAndroidRuntimeEnvironment(
                        $request->androidSerial,
                        $androidAppId,
                        $androidRuntimeEnvironmentPath,
                        $androidRuntimeEnvironment,
                        $component['manifest']['route'],
                        $temporaryRoot,
                    );
                    $this->execute(
                        "android-{$appearance}-{$slug}-stop",
                        ['adb', '-s', $request->androidSerial, 'shell', 'am', 'force-stop', $androidAppId],
                    );
                    // Emulators without hardware keys reject monkey's default
                    // system-key event share (exit 251), so request none.
                    $this->execute(
                        "android-{$appearance}-{$slug}-launch",
                        ['adb', '-s', $request->androidSerial, 'shell', 'monkey', '-p', $androidAppId, '--pct-syskeys', '0', '-c', 'android.intent.category.LAUNCHER', '1'],
                    );
                    $this->assertAndroidApplicationForeground($request->androidSerial, $androidAppId, "before {$appearance} {$slug} capture");
                    $this->assertAndroidCaptureReady($request->androidSerial, $androidAppId, $component['name'], $component['manifest']);
                    $this->openAndroidPress($request->androidSerial, $component['manifest'], "android-{$appearance}-{$slug}");
                    $this->captureStable(
                        "android-{$appearance}-{$slug}",
                        ['adb', '-s', $request->androidSerial, 'exec-out', 'screencap', '-p'],
                        $temporaryRoot."/{$slug}/android-{$appearance}.png",
                    );
                }
            }

            foreach ($components as $slug => $component) {
                foreach (['ios', 'android'] as $platform) {
                    if (hash_file('sha256', $temporaryRoot."/{$slug}/{$platform}-light.png") === hash_file('sha256', $temporaryRoot."/{$slug}/{$platform}-dark.png")) {
                        throw new RuntimeException(ucfirst($platform)." light and dark captures are byte-identical for {$component['name']}.");
                    }
                }

                $published[$slug] = $this->publishAtomically($component['outputs'], $temporaryRoot.'/'.$slug);
            }
        } catch (Throwable $exception) {
            $failure = $exception;
        } finally {
            try {
                if (is_string($iosRuntimeEnvironmentPath) && is_string($iosRuntimeEnvironment)) {
                    $this->writeIosRuntimeEnvironment($iosRuntimeEnvironmentPath, $iosRuntimeEnvironment, '/');
                }
                if (is_string($androidRuntimeEnvironment)) {
                    $this->writeAndroidRuntimeEnvironment(
                        $request->androidSerial,
                        $androidAppId,
                        $androidRuntimeEnvironmentPath,
                        $androidRuntimeEnvironment,
                        '/',
                        $temporaryRoot,
                    );
                }
                if ($originalIosAppearance !== null) {
                    $this->execute(
                        'restore-ios-appearance',
                        ['xcrun', 'simctl', 'ui', $request->iosUdid, 'appearance', $originalIosAppearance],
                    );
                }
                if ($originalIosReduceMotion !== null) {
                    $this->setIosReduceMotion(
                        $request->iosUdid,
                        $originalIosReduceMotion,
                        'restore-ios-reduce-motion',
                    );
                }
                if ($originalAndroidAppearance !== null) {
                    $this->execute(
                        'restore-android-appearance',
                        ['adb', '-s', $request->androidSerial, 'shell', 'cmd', 'uimode', 'night', $originalAndroidAppearance],
                    );
                }
                if ($originalAndroidAnimatorScale !== false) {
                    $this->setAndroidAnimatorScale(
                        $request->androidSerial,
                        $originalAndroidAnimatorScale,
                        'restore-android-animator-scale',
                    );
                }
                if (is_string($originalEnvironment) && file_put_contents($environmentPath, $originalEnvironment) === false) {
                    throw new RuntimeException('Unable to restore showcase .env after screenshot capture.');
                }
            } catch (Throwable $exception) {
                $restorationFailure = $exception;
            }

            if (! $request->keepFailed || $failure === null) {
                $this->removeDirectory($temporaryRoot);
            }
        }

        if ($failure !== null) {
            throw $failure;
        }
        if ($restorationFailure !== null) {
            throw $restorationFailure;
        }

        return new BatchCaptureReport(
            packageRevision: $packageRevision,
            showcaseRevision: $showcaseRevision,
            packageDirty: $packageDirty,
            showcaseDirty: $showcaseDirty,
            iosUdid: $request->iosUdid,
            androidSerial: $request->androidSerial,
            commands: $this->commands,
            outputs: $published,
        );
    }

    private function validateRequest(CaptureRequest $request): void
    {
        if (trim($request->iosUdid) === '' || trim($request->androidSerial) === '') {
            throw new InvalidArgumentException('Explicit iOS Simulator and Android emulator identifiers are required.');
        }
        if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $request->component) !== 1) {
            throw new InvalidArgumentException('Component must be a StudlyCase name.');
        }
    }

    private function validateBatchRequest(BatchCaptureRequest $request): void
    {
        if (trim($request->iosUdid) === '' || trim($request->androidSerial) === '') {
            throw new InvalidArgumentException('Explicit iOS Simulator and Android emulator identifiers are required.');
        }
        if ($request->components === []) {
            throw new InvalidArgumentException('At least one component is required for batch capture.');
        }
        if (count(array_unique($request->components)) !== count($request->components)) {
            throw new InvalidArgumentException('Batch capture components must be unique.');
        }
        foreach ($request->components as $component) {
            if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $component) !== 1) {
                throw new InvalidArgumentException('Every component must be a StudlyCase name.');
            }
        }
    }

    private function existingDirectory(string $path, string $label): string
    {
        $resolved = realpath($path);
        if ($resolved === false || ! is_dir($resolved)) {
            throw new InvalidArgumentException("Missing {$label} root: {$path}");
        }

        return rtrim($resolved, '/');
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function componentManifest(string $component, string $packageRoot): array
    {
        $slug = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $component));
        $path = $packageRoot.'/spec/screenshots.json';

        try {
            $manifest = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Invalid screenshot manifest: '.$exception->getMessage(), previous: $exception);
        }

        $entry = $manifest['components'][$slug] ?? null;
        if (! is_array($entry) || ! is_string($entry['route'] ?? null) || ! is_string($entry['test'] ?? null) || ! is_array($entry['outputs'] ?? null)) {
            throw new RuntimeException("Incomplete screenshot manifest entry for {$slug}.");
        }
        if (array_key_exists('ready-text', $entry) && (! is_string($entry['ready-text']) || trim($entry['ready-text']) === '')) {
            throw new RuntimeException("Invalid screenshot readiness text for {$slug}.");
        }
        foreach (['long-press', 'tap'] as $gesture) {
            if (! array_key_exists($gesture, $entry)) {
                continue;
            }
            $press = $entry[$gesture];
            foreach (['text', 'until'] as $key) {
                if (! is_array($press) || ! is_string($press[$key] ?? null) || trim($press[$key]) === '') {
                    throw new RuntimeException("Screenshot {$gesture} for {$slug} needs non-empty `text` and `until` strings.");
                }
            }
        }
        if (array_key_exists('long-press', $entry) && array_key_exists('tap', $entry)) {
            throw new RuntimeException("Screenshot entry {$slug} can have a long-press or a tap, not both.");
        }
        if (array_key_exists('orientation', $entry) && ! in_array($entry['orientation'], ['portrait', 'landscape'], true)) {
            throw new RuntimeException("Screenshot orientation for {$slug} must be `portrait` or `landscape`.");
        }
        if (array_key_exists('platforms', $entry)) {
            $platforms = $entry['platforms'];
            // Every capture includes iOS; an entry can leave out Android, such as an iPad-only one.
            if (! in_array($platforms, [['ios'], ['ios', 'android'], ['android', 'ios']], true)) {
                throw new RuntimeException("Screenshot platforms for {$slug} must list `ios`, or `ios` and `android`.");
            }
        }
        if (array_key_exists('ios-device', $entry) && ! in_array($entry['ios-device'], ['iphone', 'ipad'], true)) {
            throw new RuntimeException("Screenshot ios-device for {$slug} must be `iphone` or `ipad`.");
        }

        return [$slug, $entry];
    }

    /** @param array<string, mixed> $manifest
     *  @return array<string, string>
     */
    private function outputPaths(array $manifest, string $packageRoot): array
    {
        $paths = [];
        $variants = [];
        foreach ($manifest['platforms'] ?? ['ios', 'android'] as $platform) {
            $variants[] = "{$platform}-light";
            $variants[] = "{$platform}-dark";
        }
        foreach ($variants as $variant) {
            $relative = $manifest['outputs'][$variant] ?? null;
            if (! is_string($relative) || preg_match('#^docs/screenshots/[a-z0-9-]+/[a-z0-9-]+\.png$#', $relative) !== 1) {
                throw new RuntimeException("Invalid screenshot output path for {$variant}.");
            }
            $paths[$variant] = $packageRoot.'/'.$relative;
        }

        return $paths;
    }

    private function assertInstalledPackageRevision(string $showcaseRoot, string $packageRevision): void
    {
        $path = $showcaseRoot.'/vendor/composer/installed.php';
        if (! is_file($path)) {
            throw new RuntimeException('Showcase dependencies are not installed.');
        }

        $installed = require $path;
        $reference = is_array($installed) ? ($installed['versions']['firstlightui/nativephp']['reference'] ?? null) : null;
        if (! is_string($reference) || $reference !== $packageRevision) {
            throw new RuntimeException("Showcase must install exact package revision {$packageRevision}; found ".($reference ?: 'none').'.');
        }
    }

    private function assertIosSimulator(string $udid, ?string $family = null): void
    {
        $result = $this->execute('ios-target', ['xcrun', 'simctl', 'list', 'devices', '-j']);
        try {
            $payload = json_decode($result['stdout'], true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to parse iOS Simulator inventory.', previous: $exception);
        }

        foreach ($payload['devices'] ?? [] as $devices) {
            foreach (is_array($devices) ? $devices : [] as $device) {
                if (($device['udid'] ?? null) === $udid && ($device['isAvailable'] ?? false) === true) {
                    $type = (string) ($device['deviceTypeIdentifier'] ?? '');
                    $expected = $family === null ? null : (['iphone' => '.iPhone-', 'ipad' => '.iPad-'][$family] ?? null);
                    if ($expected !== null && ! str_contains($type, $expected)) {
                        throw new RuntimeException("This capture needs an {$family} Simulator, but {$udid} is {$type}.");
                    }

                    return;
                }
            }
        }

        throw new RuntimeException("iOS target is not an available Simulator: {$udid}");
    }

    private function assertAndroidEmulator(string $serial): void
    {
        $state = trim($this->execute('android-target', ['adb', '-s', $serial, 'get-state'])['stdout']);
        if ($state !== 'device') {
            throw new RuntimeException("Android target is not booted: {$serial}");
        }

        $qemu = trim($this->execute('android-emulator-check', ['adb', '-s', $serial, 'shell', 'getprop', 'ro.kernel.qemu'])['stdout']);
        if ($qemu !== '1') {
            throw new RuntimeException("Android target is not an Android emulator: {$serial}");
        }
    }

    /** @param list<string> $platforms */
    private function assertNativeHostProjects(string $showcaseRoot, array $platforms): void
    {
        foreach ($platforms as $platform) {
            if (! is_dir($showcaseRoot.'/nativephp/'.$platform)) {
                throw new RuntimeException("Missing generated NativePHP {$platform} host. Run `php artisan native:install both` in the showcase.");
            }
        }
    }

    private function nativeAppId(string $showcaseRoot): string
    {
        $environmentPath = $showcaseRoot.'/.env';
        $environment = is_file($environmentPath) ? file_get_contents($environmentPath) : false;
        if (! is_string($environment) || preg_match('/^NATIVEPHP_APP_ID=(.+)$/m', $environment, $matches) !== 1) {
            throw new RuntimeException('Showcase .env must define NATIVEPHP_APP_ID for Android capture verification.');
        }

        $appId = trim($matches[1], " \t\n\r\0\x0B\"'");
        if (preg_match('/^[A-Za-z][A-Za-z0-9_.]+$/', $appId) !== 1) {
            throw new RuntimeException("Invalid showcase NATIVEPHP_APP_ID: {$appId}");
        }

        return $appId;
    }

    private function primeCaptureEnvironment(
        string $environmentPath,
        string $environment,
        string $route,
        ?string $appVersion = null,
    ): void
    {
        if (preg_match('#^/[A-Za-z0-9/_-]+$#', $route) !== 1) {
            throw new RuntimeException("Invalid screenshot route: {$route}");
        }

        $updated = $environment;
        $values = ['NATIVEPHP_START_URL' => $route, 'NATIVEPHP_APP_VERSION' => $appVersion ?? 'DEBUG'];

        foreach ($values as $key => $value) {
            $line = $key.'='.$value;
            $updated = preg_match('/^'.preg_quote($key, '/').'=.*$/m', $updated) === 1
                ? preg_replace('/^'.preg_quote($key, '/').'=.*$/m', $line, $updated)
                : rtrim($updated).PHP_EOL.$line.PHP_EOL;
        }

        if (! is_string($updated) || file_put_contents($environmentPath, $updated) === false) {
            throw new RuntimeException('Unable to prime showcase environment for screenshot capture.');
        }
    }

    private function environmentForRoute(string $environment, string $route): string
    {
        if (preg_match('#^/[A-Za-z0-9/_-]*$#', $route) !== 1) {
            throw new RuntimeException("Invalid installed screenshot route: {$route}");
        }

        $line = 'NATIVEPHP_START_URL='.$route;
        $updated = preg_match('/^NATIVEPHP_START_URL=.*$/m', $environment) === 1
            ? preg_replace('/^NATIVEPHP_START_URL=.*$/m', $line, $environment)
            : rtrim($environment).PHP_EOL.$line.PHP_EOL;

        if (! is_string($updated)) {
            throw new RuntimeException("Unable to prepare installed screenshot route: {$route}");
        }

        return $updated;
    }

    private function writeIosRuntimeEnvironment(string $path, string $environment, string $route): void
    {
        if (file_put_contents($path, $this->environmentForRoute($environment, $route)) === false) {
            throw new RuntimeException("Unable to update installed iOS screenshot route: {$route}");
        }
    }

    private function writeAndroidRuntimeEnvironment(
        string $serial,
        string $appId,
        string $devicePath,
        string $environment,
        string $route,
        string $temporaryRoot,
    ): void {
        $localPath = $temporaryRoot.'/android-runtime.env';
        if (file_put_contents($localPath, $this->environmentForRoute($environment, $route)) === false) {
            throw new RuntimeException("Unable to prepare installed Android screenshot route: {$route}");
        }

        $temporaryPath = '/data/local/tmp/firstlight_capture_runtime.env';
        $this->execute('android-runtime-environment-push', ['adb', '-s', $serial, 'push', $localPath, $temporaryPath]);
        $this->execute('android-runtime-environment-copy', ['adb', '-s', $serial, 'shell', 'run-as', $appId, 'cp', $temporaryPath, $devicePath]);
        $this->execute('android-runtime-environment-cleanup', ['adb', '-s', $serial, 'shell', 'rm', $temporaryPath]);
    }


    private function terminateIosApplication(string $udid, string $appId): void
    {
        $command = ['xcrun', 'simctl', 'terminate', $udid, $appId];
        $this->commands['terminate-ios-before-launch'] = implode(' ', array_map($this->quoteArgument(...), $command));
        $result = $this->runner->run($command);

        if ($result['exitCode'] === 0) {
            return;
        }

        $detail = trim($result['stderr']) ?: trim($result['stdout']);
        foreach (['found nothing to terminate', 'did not find requested application', 'not running'] as $allowed) {
            if (str_contains(strtolower($detail), $allowed)) {
                return;
            }
        }

        throw new RuntimeException($detail !== '' ? $detail : 'Unable to terminate iOS showcase before launch.');
    }

    private function assertAndroidApplicationForeground(string $serial, string $appId, string $stage): void
    {
        $commandStage = str_replace(' ', '-', $stage);
        $package = trim($this->execute(
            'android-package-'.$commandStage,
            ['adb', '-s', $serial, 'shell', 'pm', 'path', $appId],
        )['stdout']);
        if (! str_starts_with($package, 'package:')) {
            throw new RuntimeException("Android showcase package is not installed: {$appId}");
        }

        for ($attempt = 1; $attempt <= 15; $attempt++) {
            $windows = $this->execute(
                "android-foreground-{$commandStage}-{$attempt}",
                ['adb', '-s', $serial, 'shell', 'dumpsys', 'window'],
            )['stdout'];
            if (str_contains($windows, 'mCurrentFocus') && preg_match('/mCurrentFocus=.*'.preg_quote($appId, '/').'/', $windows)) {
                return;
            }

            if ($attempt < 15) {
                $this->execute(
                    "android-foreground-wait-{$commandStage}-{$attempt}",
                    ['adb', '-s', $serial, 'shell', 'sleep', '1'],
                );
            }
        }

        throw new RuntimeException("Android showcase is not foregrounded at {$stage}: {$appId}");
    }

    /** @param array<string, mixed> $manifest */
    private function assertAndroidCaptureReady(string $serial, string $appId, string $component, array $manifest): void
    {
        $title = $manifest['ready-text'] ?? 'Firstlight '.trim((string) preg_replace('/(?<!^)[A-Z]/', ' $0', $component));

        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $hierarchy = $this->execute(
                "android-capture-ready-{$attempt}",
                ['adb', '-s', $serial, 'exec-out', 'uiautomator', 'dump', '/dev/tty'],
            )['stdout'];

            if (str_contains($hierarchy, 'package="'.$appId.'"') && str_contains($hierarchy, 'text="'.$title.'"')) {
                return;
            }

            if ($attempt < 30) {
                $this->execute(
                    "android-capture-ready-wait-{$attempt}",
                    ['adb', '-s', $serial, 'shell', 'sleep', '1'],
                );
            }
        }

        throw new RuntimeException("Android capture route did not render: {$title}");
    }

    /**
     * Long-presses the element labelled `long-press.text`, such as a List Item
     * with a context menu, or taps `tap.text`, such as a picker field, and waits until `long-press.until`, such as one of
     * the menu's items, is on screen, so the capture shows the menu open. The
     * Simulator has no input command of its own, so iOS uses AXe
     * (`brew install cameroncooke/axe/axe`). Nothing happens when `until` is
     * already showing.
     *
     * @param  array<string, mixed>  $manifest
     */
    private function openIosPress(string $udid, string $appId, array $manifest, string $label): void
    {
        $long = isset($manifest['long-press']);
        $press = $manifest['long-press'] ?? $manifest['tap'] ?? null;
        if (! is_array($press)) {
            return;
        }
        if (! $long) {
            // AXe taps do not reach iOS 27 Simulators; the XCUITest driver taps the button labelled
            // `tap.text` and fails unless `tap.until` appears.
            $this->runIosDriver($udid, [
                'FIRSTLIGHT_APP' => $appId,
                'FIRSTLIGHT_TAP' => $press['text'],
                'FIRSTLIGHT_UNTIL' => $press['until'],
            ], "{$label}-tap");

            return;
        }
        if ($this->iosElementCenter($udid, $press['until'], "{$label}-long-press-check") !== null) {
            return;
        }

        $center = null;
        for ($attempt = 1; $attempt <= 10 && $center === null; $attempt++) {
            if ($attempt > 1) {
                $this->execute("{$label}-long-press-target-wait-{$attempt}", ['sleep', '1']);
            }
            $center = $this->iosElementCenter($udid, $press['text'], "{$label}-long-press-target-{$attempt}");
        }
        if ($center === null) {
            throw new RuntimeException("iOS long-press target is not on screen: {$press['text']}");
        }

        $this->execute("{$label}-long-press", $long
            ? ['axe', 'touch', '-x', (string) $center[0], '-y', (string) $center[1], '--down', '--up', '--delay', '1.5', '--udid', $udid]
            : ['axe', 'tap', '-x', (string) $center[0], '-y', (string) $center[1], '--udid', $udid]);

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            if ($this->iosElementCenter($udid, $press['until'], "{$label}-long-press-open-{$attempt}") !== null) {
                return;
            }
            $this->execute("{$label}-long-press-open-wait-{$attempt}", ['sleep', '1']);
        }

        throw new RuntimeException("iOS long press did not show: {$press['until']}");
    }

    /** @return array{0: int, 1: int}|null The centre, in points, of the first element labelled $text. */
    private function iosElementCenter(string $udid, string $text, string $label): ?array
    {
        try {
            $tree = json_decode($this->execute($label, ['axe', 'describe-ui', '--udid', $udid])['stdout'], true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to parse the iOS accessibility tree.', previous: $exception);
        }

        $pending = is_array($tree) ? (array_is_list($tree) ? $tree : [$tree]) : [];
        while ($pending !== []) {
            $element = array_shift($pending);
            if (! is_array($element)) {
                continue;
            }
            $name = (string) ($element['AXLabel'] ?? '');
            $frame = $element['frame'] ?? null;
            if (($name === $text || str_starts_with($name, $text.',')) && is_array($frame)) {
                return [
                    (int) round((float) $frame['x'] + (float) $frame['width'] / 2),
                    (int) round((float) $frame['y'] + (float) $frame['height'] / 2),
                ];
            }
            array_push($pending, ...(is_array($element['children'] ?? null) ? $element['children'] : []));
        }

        return null;
    }

    /**
     * Android counterpart of openIosPress(): a held `input swipe` on the
     * centre of the node whose text or content description is
     * `long-press.text`.
     *
     * @param  array<string, mixed>  $manifest
     */
    private function openAndroidPress(string $serial, array $manifest, string $label): void
    {
        $long = isset($manifest['long-press']);
        $press = $manifest['long-press'] ?? $manifest['tap'] ?? null;
        if (! is_array($press)) {
            return;
        }
        if ($this->androidNodeCenter($serial, $press['until'], "{$label}-long-press-check") !== null) {
            return;
        }

        $center = $this->androidNodeCenter($serial, $press['text'], "{$label}-long-press-target");
        if ($center === null) {
            throw new RuntimeException("Android long-press target is not on screen: {$press['text']}");
        }

        [$x, $y] = array_map('strval', $center);
        $this->execute("{$label}-long-press", $long
            ? ['adb', '-s', $serial, 'shell', 'input', 'swipe', $x, $y, $x, $y, '1500']
            : ['adb', '-s', $serial, 'shell', 'input', 'tap', $x, $y]);

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            if ($this->androidNodeCenter($serial, $press['until'], "{$label}-long-press-open-{$attempt}") !== null) {
                return;
            }
            $this->execute("{$label}-long-press-open-wait-{$attempt}", ['adb', '-s', $serial, 'shell', 'sleep', '1']);
        }

        throw new RuntimeException("Android long press did not show: {$press['until']}");
    }

    /** @return array{0: int, 1: int}|null The centre, in pixels, of the first node labelled $text. */
    private function androidNodeCenter(string $serial, string $text, string $label): ?array
    {
        $hierarchy = $this->execute($label, ['adb', '-s', $serial, 'exec-out', 'uiautomator', 'dump', '/dev/tty'])['stdout'];
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_XML1);

        preg_match_all('/<node\b[^>]*>/', $hierarchy, $nodes);
        foreach ($nodes[0] as $node) {
            $named = str_contains($node, 'text="'.$escaped.'"')
                || preg_match('/content-desc="'.preg_quote($escaped, '/').'(?:[,.][^"]*)?"/', $node) === 1;
            if ($named && preg_match('/bounds="\[(\d+),(\d+)\]\[(\d+),(\d+)\]"/', $node, $bounds) === 1) {
                return [intdiv((int) $bounds[1] + (int) $bounds[3], 2), intdiv((int) $bounds[2] + (int) $bounds[4], 2)];
            }
        }

        return null;
    }

    /** @param array<string, mixed> $manifest */
    private function runFocusedShowcaseTest(array $manifest, string $showcaseRoot): void
    {
        $tokens = preg_split('/\s+/', trim((string) $manifest['test'])) ?: [];
        if ($tokens === [] || implode(' ', $tokens) !== trim((string) $manifest['test'])) {
            throw new RuntimeException('Screenshot manifest test command must contain simple space-separated arguments.');
        }

        foreach ($tokens as $token) {
            if (preg_match('#^[A-Za-z0-9_./:-]+$#', $token) !== 1) {
                throw new RuntimeException('Screenshot manifest test command contains an unsupported argument.');
            }
        }

        $this->execute('showcase-test', $tokens, $showcaseRoot);
    }

    private function iosAppearance(string $udid): string
    {
        $appearance = strtolower(trim($this->execute('ios-original-appearance', ['xcrun', 'simctl', 'ui', $udid, 'appearance'])['stdout']));
        if (! in_array($appearance, ['light', 'dark'], true)) {
            throw new RuntimeException("Unsupported iOS Simulator appearance: {$appearance}");
        }

        return $appearance;
    }

    private function androidAppearance(string $serial): string
    {
        $output = strtolower(trim($this->execute('android-original-appearance', ['adb', '-s', $serial, 'shell', 'cmd', 'uimode', 'night'])['stdout']));
        if (preg_match('/(?:night mode:\s*)?(yes|no|auto)$/', $output, $matches) !== 1) {
            throw new RuntimeException("Unsupported Android emulator night mode: {$output}");
        }

        return $matches[1];
    }

    private function iosReduceMotion(string $udid): bool
    {
        $command = ['xcrun', 'simctl', 'spawn', $udid, 'defaults', 'read', 'com.apple.Accessibility', 'ReduceMotionEnabled'];
        $this->commands['ios-original-reduce-motion'] = implode(' ', array_map($this->quoteArgument(...), $command));
        $result = $this->runner->run($command);
        if ($result['exitCode'] !== 0) {
            // A Simulator where Reduce Motion was never changed has no key: it is off.
            if (str_contains($result['stderr'].$result['stdout'], 'does not exist') || str_contains($result['stderr'].$result['stdout'], 'Could not find')) {
                return false;
            }
            throw new RuntimeException('Unable to read iOS Simulator Reduce Motion: '.trim($result['stderr']));
        }
        $output = strtolower(trim($result['stdout']));

        return match ($output) {
            '1', 'true', 'yes' => true,
            '0', 'false', 'no' => false,
            default => throw new RuntimeException("Unsupported iOS Simulator Reduce Motion value: {$output}"),
        };
    }

    private function setIosReduceMotion(string $udid, bool $enabled, string $label): void
    {
        $this->execute(
            $label,
            [
                'xcrun', 'simctl', 'spawn', $udid,
                'defaults', 'write', 'com.apple.Accessibility', 'ReduceMotionEnabled',
                '-bool', $enabled ? 'true' : 'false',
            ],
        );
    }

    /**
     * Simulator has no rotate command, so a one-test XCUITest bundle in
     * bin/support/ios-orientation sets XCUIDevice.orientation, which lasts
     * after the test ends. It is generated with XcodeGen and built once per
     * Simulator, then run without building.
     */
    private function rotateIos(string $udid, bool $landscape, string $label): void
    {
        $this->runIosDriver($udid, ['FIRSTLIGHT_ORIENTATION' => $landscape ? 'landscape' : 'portrait'], $label);
    }

    /** @param array<string, string> $environment Passed to the driver as TEST_RUNNER_ variables. */
    private function runIosDriver(string $udid, array $environment, string $label): void
    {
        $source = $this->packageRoot.'/bin/support/ios-orientation';
        $build = sys_get_temp_dir().'/firstlight-orientation-driver-'.$udid;
        $products = glob($build.'/Build/Products/*.xctestrun') ?: [];
        if ($products === []) {
            if (! is_dir($build) && ! mkdir($build, 0700, true)) {
                throw new RuntimeException("Unable to create the iOS driver build directory: {$build}");
            }
            $this->execute("{$label}-generate", ['xcodegen', 'generate', '--spec', $source.'/project.yml', '--project', $build], $source);
            $this->execute("{$label}-build", [
                'xcodebuild', 'build-for-testing', '-project', $build.'/FirstlightOrientationDriver.xcodeproj',
                '-scheme', 'OrientationDriver', '-destination', "id={$udid}", '-derivedDataPath', $build,
            ]);
            $products = glob($build.'/Build/Products/*.xctestrun') ?: [];
            if ($products === []) {
                throw new RuntimeException('The iOS orientation driver did not build.');
            }
        }

        $variables = [];
        foreach ($environment as $key => $value) {
            $variables[] = "TEST_RUNNER_{$key}={$value}";
        }
        $this->execute($label, [
            'env', ...$variables,
            'xcodebuild', 'test-without-building', '-xctestrun', $products[0],
            '-destination', "id={$udid}", '-derivedDataPath', $build,
        ]);
    }

    /** @return list<string> The accelerometer_rotation and user_rotation settings. */
    private function androidRotation(string $serial): array
    {
        return array_map(
            fn (string $key): string => trim($this->execute(
                "android-original-{$key}",
                ['adb', '-s', $serial, 'shell', 'settings', 'get', 'system', $key],
            )['stdout']),
            ['accelerometer_rotation', 'user_rotation'],
        );
    }

    /** @param list<string> $values accelerometer_rotation, then user_rotation. */
    private function setAndroidRotation(string $serial, array $values, string $label): void
    {
        foreach (['accelerometer_rotation', 'user_rotation'] as $index => $key) {
            $value = $values[$index] ?? 'null';
            $this->execute("{$label}-{$key}", $value === 'null'
                ? ['adb', '-s', $serial, 'shell', 'settings', 'delete', 'system', $key]
                : ['adb', '-s', $serial, 'shell', 'settings', 'put', 'system', $key, $value]);
        }
    }

    private function androidAnimatorScale(string $serial): ?string
    {
        $output = strtolower(trim($this->execute(
            'android-original-animator-scale',
            ['adb', '-s', $serial, 'shell', 'settings', 'get', 'global', 'animator_duration_scale'],
        )['stdout']));

        if ($output === '' || $output === 'null') {
            return null;
        }
        if (preg_match('/^(?:0|[1-9][0-9]*)(?:\.[0-9]+)?$/', $output) !== 1) {
            throw new RuntimeException("Unsupported Android emulator animator duration scale: {$output}");
        }

        return $output;
    }

    private function setAndroidAnimatorScale(string $serial, ?string $scale, string $label): void
    {
        $command = $scale === null
            ? ['adb', '-s', $serial, 'shell', 'settings', 'delete', 'global', 'animator_duration_scale']
            : ['adb', '-s', $serial, 'shell', 'settings', 'put', 'global', 'animator_duration_scale', $scale];

        $this->execute($label, $command);
    }

    /** @param list<string> $command */
    private function captureStable(
        string $label,
        array $command,
        string $finalTemporaryPath,
        bool $commandWritesFile = false,
    ): void
    {
        $deadline = microtime(true) + 15;
        /** @var list<array{attempt: int, hash: string, path: string}> $recentFrames */
        $recentFrames = [];
        $attempt = 0;

        do {
            $attempt++;
            $attemptPath = $finalTemporaryPath.'.'.$attempt;
            $attemptCommand = $commandWritesFile ? [...$command, $attemptPath] : $command;
            $result = $this->execute("{$label}-capture-{$attempt}", $attemptCommand);
            if (! $commandWritesFile) {
                file_put_contents($attemptPath, $result['stdout']);
            }
            $this->assertPng($attemptPath);
            $hash = hash_file('sha256', $attemptPath);

            foreach (array_reverse($recentFrames) as $priorFrame) {
                if (
                    $hash === $priorFrame['hash']
                    || $this->screenshotsAreVisuallyStable(
                        $priorFrame['path'],
                        $attemptPath,
                        $label,
                        $attempt,
                        $priorFrame['attempt'],
                    )
                ) {
                    rename($attemptPath, $finalTemporaryPath);

                    return;
                }
            }

            $recentFrames[] = ['attempt' => $attempt, 'hash' => $hash, 'path' => $attemptPath];
            if (count($recentFrames) > 12) {
                array_shift($recentFrames);
            }
            usleep(100000);
        } while (microtime(true) < $deadline);

        throw new RuntimeException("Screenshot did not stabilise within 15 seconds: {$label}");
    }

    private function screenshotsAreVisuallyStable(
        string $previousPath,
        string $currentPath,
        string $label,
        int $attempt,
        ?int $priorAttempt = null,
    ): bool {
        $command = ['magick', 'compare', '-metric', 'AE', $previousPath, $currentPath, 'null:'];
        $comparison = $priorAttempt === null ? (string) $attempt : "{$priorAttempt}-{$attempt}";
        $this->commands["{$label}-visual-difference-{$comparison}"] = implode(' ', array_map($this->quoteArgument(...), $command));
        $result = $this->runner->run($command);

        if (! in_array($result['exitCode'], [0, 1], true)) {
            $detail = trim($result['stderr']) ?: trim($result['stdout']);
            throw new RuntimeException($detail !== '' ? $detail : 'Unable to compare screenshot frames.');
        }

        $metric = trim($result['stderr']) ?: trim($result['stdout']);
        if (preg_match('/\(([0-9.eE+-]+)\)\s*$/', $metric, $matches) !== 1) {
            throw new RuntimeException("Unable to parse screenshot visual difference: {$metric}");
        }

        return (float) $matches[1] <= 0.0001;
    }

    private function assertPng(string $path): void
    {
        $contents = (string) file_get_contents($path);
        if (! str_starts_with($contents, "\x89PNG\r\n\x1a\n") || strlen($contents) < 24) {
            throw new RuntimeException("Capture is not a valid PNG: {$path}");
        }

        $dimensions = unpack('Nwidth/Nheight', substr($contents, 16, 8));
        if (($dimensions['width'] ?? 0) < 1 || ($dimensions['height'] ?? 0) < 1) {
            throw new RuntimeException("Capture has invalid PNG dimensions: {$path}");
        }
    }

    /** @param array<string, string> $outputs
     *  @return array<string, string>
     */
    private function publishAtomically(array $outputs, string $temporaryRoot): array
    {
        $backups = [];
        $published = [];

        try {
            foreach ($outputs as $variant => $destination) {
                if (! is_dir(dirname($destination)) && ! mkdir(dirname($destination), 0755, true)) {
                    throw new RuntimeException("Unable to create screenshot output directory: ".dirname($destination));
                }

                if (is_file($destination)) {
                    $backup = $temporaryRoot.'/backup-'.$variant.'.png';
                    if (! rename($destination, $backup)) {
                        throw new RuntimeException("Unable to protect existing screenshot: {$destination}");
                    }
                    $backups[$variant] = $backup;
                }

                $source = $temporaryRoot.'/'.$variant.'.png';
                if (! rename($source, $destination)) {
                    throw new RuntimeException("Unable to publish screenshot: {$destination}");
                }
                $published[$variant] = $destination;
            }
        } catch (Throwable $exception) {
            foreach ($published as $destination) {
                if (is_file($destination)) {
                    unlink($destination);
                }
            }
            foreach ($backups as $variant => $backup) {
                if (is_file($backup)) {
                    rename($backup, $outputs[$variant]);
                }
            }

            throw $exception;
        }

        return $published;
    }

    /** @param list<string> $command
     *  @return array{exitCode: int, stdout: string, stderr: string}
     */
    private function execute(string $label, array $command, ?string $cwd = null): array
    {
        $this->commands[$label] = implode(' ', array_map($this->quoteArgument(...), $command));
        $result = $this->runner->run($command, $cwd);

        if ($result['exitCode'] !== 0) {
            $detail = trim($result['stderr']) ?: trim($result['stdout']);
            throw new RuntimeException($detail !== '' ? $detail : "Command failed: {$label}");
        }

        return $result;
    }

    private function quoteArgument(string $argument): string
    {
        return preg_match('#^[A-Za-z0-9_./:=+-]+$#', $argument) === 1 ? $argument : escapeshellarg($argument);
    }

    private function removeDirectory(string $root): void
    {
        if (! is_dir($root)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($root);
    }
}
