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

        $this->assertInstalledPackageRevision($showcaseRoot, $packageRevision);
        $this->assertIosSimulator($request->iosUdid);
        $this->assertAndroidEmulator($request->androidSerial);
        $this->assertNativeHostProjects($showcaseRoot);
        $androidAppId = $this->nativeAppId($showcaseRoot);
        $this->runFocusedShowcaseTest($manifest, $showcaseRoot);

        $temporaryRoot = sys_get_temp_dir().'/firstlight-docs-capture-'.bin2hex(random_bytes(8));
        if (! mkdir($temporaryRoot, 0700, true)) {
            throw new RuntimeException('Unable to create a temporary screenshot directory.');
        }

        $originalIosAppearance = null;
        $originalAndroidAppearance = null;
        $environmentPath = $showcaseRoot.'/.env';
        $originalEnvironment = is_file($environmentPath) ? file_get_contents($environmentPath) : false;
        $failure = null;
        $restorationFailure = null;
        $published = [];

        try {
            if (! is_string($originalEnvironment)) {
                throw new RuntimeException('Showcase .env is required for native screenshot capture.');
            }
            $this->primeStartUrl($environmentPath, $originalEnvironment, $manifest['route']);

            $this->terminateIosApplication($request->iosUdid, $androidAppId);
            $this->execute(
                'launch-ios',
                ['php', 'artisan', 'native:run', 'ios', $request->iosUdid, '--start-url='.$manifest['route'], '--no-tty'],
                $showcaseRoot,
            );
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

            $originalIosAppearance = $this->iosAppearance($request->iosUdid);
            $originalAndroidAppearance = $this->androidAppearance($request->androidSerial);

            foreach (['light', 'dark'] as $appearance) {
                $this->execute(
                    "ios-{$appearance}-appearance",
                    ['xcrun', 'simctl', 'ui', $request->iosUdid, 'appearance', $appearance],
                );
                $this->captureStable(
                    "ios-{$appearance}",
                    ['xcrun', 'simctl', 'io', $request->iosUdid, 'screenshot'],
                    $temporaryRoot.'/ios-'.$appearance.'.png',
                    commandWritesFile: true,
                );
            }

            foreach (['light' => 'no', 'dark' => 'yes'] as $appearance => $mode) {
                $this->execute(
                    "android-{$appearance}-appearance",
                    ['adb', '-s', $request->androidSerial, 'shell', 'cmd', 'uimode', 'night', $mode],
                );
                $this->assertAndroidApplicationForeground($request->androidSerial, $androidAppId, "before {$appearance} capture");
                $this->captureStable(
                    "android-{$appearance}",
                    ['adb', '-s', $request->androidSerial, 'exec-out', 'screencap', '-p'],
                    $temporaryRoot.'/android-'.$appearance.'.png',
                );
            }

            foreach (['ios', 'android'] as $platform) {
                if (hash_file('sha256', $temporaryRoot."/{$platform}-light.png") === hash_file('sha256', $temporaryRoot."/{$platform}-dark.png")) {
                    throw new RuntimeException(ucfirst($platform).' light and dark captures are byte-identical.');
                }
            }

            $published = $this->publishAtomically($outputs, $temporaryRoot);
        } catch (Throwable $exception) {
            $failure = $exception;
        } finally {
            try {
                if ($originalIosAppearance !== null) {
                    $this->execute(
                        'restore-ios-appearance',
                        ['xcrun', 'simctl', 'ui', $request->iosUdid, 'appearance', $originalIosAppearance],
                    );
                }
                if ($originalAndroidAppearance !== null) {
                    $this->execute(
                        'restore-android-appearance',
                        ['adb', '-s', $request->androidSerial, 'shell', 'cmd', 'uimode', 'night', $originalAndroidAppearance],
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

    private function validateRequest(CaptureRequest $request): void
    {
        if (trim($request->iosUdid) === '' || trim($request->androidSerial) === '') {
            throw new InvalidArgumentException('Explicit iOS Simulator and Android emulator identifiers are required.');
        }
        if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $request->component) !== 1) {
            throw new InvalidArgumentException('Component must be a StudlyCase name.');
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

        return [$slug, $entry];
    }

    /** @param array<string, mixed> $manifest
     *  @return array<string, string>
     */
    private function outputPaths(array $manifest, string $packageRoot): array
    {
        $paths = [];
        foreach (['ios-light', 'ios-dark', 'android-light', 'android-dark'] as $variant) {
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

    private function assertIosSimulator(string $udid): void
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

    private function assertNativeHostProjects(string $showcaseRoot): void
    {
        foreach (['ios', 'android'] as $platform) {
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

    private function primeStartUrl(string $environmentPath, string $environment, string $route): void
    {
        if (preg_match('#^/[A-Za-z0-9/_-]+$#', $route) !== 1) {
            throw new RuntimeException("Invalid screenshot route: {$route}");
        }

        $line = 'NATIVEPHP_START_URL='.$route;
        $updated = preg_match('/^NATIVEPHP_START_URL=.*$/m', $environment) === 1
            ? preg_replace('/^NATIVEPHP_START_URL=.*$/m', $line, $environment)
            : rtrim($environment).PHP_EOL.$line.PHP_EOL;

        if (! is_string($updated) || file_put_contents($environmentPath, $updated) === false) {
            throw new RuntimeException('Unable to prime showcase start URL for screenshot capture.');
        }
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

    /** @param list<string> $command */
    private function captureStable(
        string $label,
        array $command,
        string $finalTemporaryPath,
        bool $commandWritesFile = false,
    ): void
    {
        $deadline = microtime(true) + 15;
        $previousHash = null;
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

            if ($previousHash !== null && $hash === $previousHash) {
                rename($attemptPath, $finalTemporaryPath);

                return;
            }

            $previousHash = $hash;
            usleep(100000);
        } while (microtime(true) < $deadline);

        throw new RuntimeException("Screenshot did not stabilise within 15 seconds: {$label}");
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
