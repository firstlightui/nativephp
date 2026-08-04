<?php

use Symfony\Component\Process\Process;

/** @var list<string> */
$componentToolingTemporaryRoots = [];

function componentToolingRoot(): string
{
    global $componentToolingTemporaryRoots;

    $root = sys_get_temp_dir().'/firstlight-component-tooling-'.bin2hex(random_bytes(8));
    mkdir($root, 0755, true);
    $componentToolingTemporaryRoots[] = $root;

    return $root;
}

function copyComponentToolingPath(string $source, string $destination): void
{
    if (is_dir($source)) {
        mkdir($destination, 0755, true);

        foreach (scandir($source) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            copyComponentToolingPath($source.'/'.$entry, $destination.'/'.$entry);
        }

        return;
    }

    if (! is_dir(dirname($destination))) {
        mkdir(dirname($destination), 0755, true);
    }

    copy($source, $destination);
    chmod($destination, fileperms($source) & 0777);
}

function removeComponentToolingPath(string $path): void
{
    if (! file_exists($path)) {
        return;
    }

    if (is_dir($path)) {
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                removeComponentToolingPath($path.'/'.$entry);
            }
        }

        rmdir($path);

        return;
    }

    unlink($path);
}

function makeSegmentedValidationCopy(): string
{
    $sourceRoot = dirname(__DIR__, 2);
    $root = componentToolingRoot();

    foreach ([
        'Constitution.md',
        'nativephp.json',
        'bin/check-component',
        'src/Components/Segmented.php',
        'src/Elements/Segmented.php',
        'resources/ios/SegmentedControl.swift',
        'resources/ios/SegmentedRenderer.swift',
        'resources/android/SegmentedControl.kt',
        'resources/android/SegmentedRenderer.kt',
        'tests/Feature/SegmentedElementTest.php',
        'tests/ios/SegmentedControlSnapshotTests.swift',
        'tests/ios/SegmentedRendererContractTests.swift',
        'tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SegmentedControlScreenshotTest.kt',
        'tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/SegmentedRendererContractTest.kt',
    ] as $path) {
        copyComponentToolingPath($sourceRoot.'/'.$path, $root.'/'.$path);
    }

    return $root;
}

afterEach(function () use (&$componentToolingTemporaryRoots): void {
    foreach ($componentToolingTemporaryRoots as $root) {
        removeComponentToolingPath($root);
    }

    $componentToolingTemporaryRoots = [];
});

it('scaffolds every component layer without overwriting authored work', function () {
    $sourceRoot = dirname(__DIR__, 2);
    $root = componentToolingRoot();
    copyComponentToolingPath($sourceRoot.'/bin/scaffold-component', $root.'/bin/scaffold-component');

    $process = new Process([$root.'/bin/scaffold-component', 'ExampleControl']);
    $process->run();

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toContain('Scaffolded ExampleControl');

    $paths = [
        'src/Components/ExampleControl.php',
        'src/Elements/ExampleControl.php',
        'resources/ios/ExampleControl.swift',
        'resources/ios/ExampleControlRenderer.swift',
        'resources/android/ExampleControl.kt',
        'resources/android/ExampleControlRenderer.kt',
        'tests/Feature/ExampleControlElementTest.php',
        'tests/ios/ExampleControlSnapshotTests.swift',
        'tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/ExampleControlTest.kt',
        'docs/components/example-control.md',
    ];

    foreach ($paths as $path) {
        expect($root.'/'.$path)->toBeFile()
            ->and(file_get_contents($root.'/'.$path))->toContain('FIRSTLIGHT_NOT_IMPLEMENTED');
    }

    $again = new Process([$root.'/bin/scaffold-component', 'ExampleControl']);
    $again->run();

    expect($again->getExitCode())->toBe(1)
        ->and($again->getErrorOutput())->toContain('Refusing to overwrite');
});

it('accepts the implemented Segmented structure in development mode', function () {
    $process = new Process([dirname(__DIR__, 2).'/bin/check-component', 'Segmented', '--development']);
    $process->run();

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toContain('Constitution checks passed');
});

it('reports an exact missing renderer path', function () {
    $root = makeSegmentedValidationCopy();
    unlink($root.'/resources/android/SegmentedRenderer.kt');

    $process = new Process([$root.'/bin/check-component', 'Segmented', '--development']);
    $process->run();

    expect($process->getExitCode())->toBe(1)
        ->and($process->getErrorOutput())->toContain('resources/android/SegmentedRenderer.kt');
});

it('keeps the release gate closed until all documentation evidence exists', function () {
    $process = new Process([dirname(__DIR__, 2).'/bin/check-component', 'Segmented']);
    $process->run();

    expect($process->getExitCode())->toBe(1)
        ->and($process->getErrorOutput())
        ->toContain('docs/components/segmented.md')
        ->toContain('docs/screenshots/segmented/android-light.png')
        ->toContain('docs/screenshots/segmented/android-dark.png')
        ->toContain('docs/review/segmented-alpha.md');
});

it('ships four concise skills with the required workflow entrypoints', function () {
    $root = dirname(__DIR__, 2);
    $contracts = [
        'firstlight-create-component' => [
            'Constitution.md',
            'bin/scaffold-component',
            'firstlight-ios-component',
            'firstlight-android-component',
            'showcase',
            'firstlight-review-component',
            'Stop',
        ],
        'firstlight-ios-component' => [
            'genuine Apple',
            'SuperNative',
            'server-authoritative',
            'XCTest',
            'VoiceOver',
            'Dynamic Type',
            'Reduced Motion',
            'physical device',
            'Stop',
        ],
        'firstlight-android-component' => [
            'Material 3',
            'SuperNative',
            'server-authoritative',
            'Paparazzi',
            'TalkBack',
            'font scaling',
            'physical device',
            'publication fix',
            'Stop',
        ],
        'firstlight-review-component' => [
            'Constitution.md',
            'bin/check-component',
            'composer test',
            'xcodebuild',
            'testDebugUnitTest',
            'native:plugin:validate',
            'requirement-by-requirement',
            'Stop',
        ],
    ];

    foreach ($contracts as $name => $needles) {
        $path = $root."/.agents/skills/{$name}/SKILL.md";

        expect($path)->toBeFile();
        $contents = file_get_contents($path);

        expect($contents)
            ->toStartWith("---\nname: {$name}\ndescription: Use when")
            ->and(str_word_count(strip_tags($contents)))->toBeLessThan(500);

        foreach ($needles as $needle) {
            expect($contents)->toContain($needle);
        }
    }
});
