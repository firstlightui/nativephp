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

function makeButtonAdapterValidationCopy(): string
{
    $sourceRoot = dirname(__DIR__, 2);
    $root = componentToolingRoot();

    foreach ([
        'Constitution.md',
        'bin/check-component',
        'vendor/nativephp/mobile-ui/nativephp.json',
    ] as $path) {
        copyComponentToolingPath($sourceRoot.'/'.$path, $root.'/'.$path);
    }

    foreach ([
        'src/Components/Button.php',
        'src/Elements/Button.php',
        'tests/Feature/ButtonElementTest.php',
    ] as $path) {
        if (! is_dir(dirname($root.'/'.$path))) {
            mkdir(dirname($root.'/'.$path), 0755, true);
        }

        file_put_contents($root.'/'.$path, "<?php\n");
    }

    file_put_contents($root.'/nativephp.json', json_encode([
        'components' => [[
            'type' => 'firstlight.button',
            'element' => 'FirstlightUI\\Elements\\Button',
            'blade' => 'FirstlightUI\\Components\\Button',
            'android_renderer' => 'com.nativephp.plugins.native_ui.ui.ButtonRenderer',
            'ios_renderer' => 'NativeUIButtonRenderer',
            'self_closing' => true,
            'adapter' => [
                'package' => 'nativephp/mobile-ui',
                'type' => 'button',
            ],
        ]],
    ], JSON_THROW_ON_ERROR));

    installDocumentationGateProbe($root);

    return $root;
}

function installDocumentationGateProbe(string $root): string
{
    $log = $root.'/documentation-gate-arguments';
    $probe = <<<'PHP'
#!/usr/bin/env php
<?php

file_put_contents(dirname(__DIR__).'/documentation-gate-arguments', implode(' ', array_slice($argv, 1)));
PHP;

    file_put_contents($root.'/bin/check-docs', $probe);
    chmod($root.'/bin/check-docs', 0755);

    return $log;
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

it('accepts an official adapter without placeholder native sources', function () {
    $root = makeButtonAdapterValidationCopy();

    $process = new Process([$root.'/bin/check-component', 'Button', '--development']);
    $process->run();

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toContain('Constitution checks passed');
});

it('rejects adapter renderer mappings that drift from the official primitive', function () {
    $root = makeButtonAdapterValidationCopy();
    $manifest = json_decode(file_get_contents($root.'/nativephp.json'), true, flags: JSON_THROW_ON_ERROR);
    $manifest['components'][0]['ios_renderer'] = 'FirstlightButtonRenderer';
    file_put_contents($root.'/nativephp.json', json_encode($manifest, JSON_THROW_ON_ERROR));

    $process = new Process([$root.'/bin/check-component', 'Button', '--development']);
    $process->run();

    expect($process->getExitCode())->toBe(1)
        ->and($process->getErrorOutput())
        ->toContain('Manifest ios_renderer must match nativephp/mobile-ui button: NativeUIButtonRenderer');
});

it('keeps the release gate closed until all documentation evidence exists', function () {
    $root = makeSegmentedValidationCopy();

    $process = new Process([$root.'/bin/check-component', 'Segmented']);
    $process->run();

    expect($process->getExitCode())->toBe(1)
        ->and($process->getErrorOutput())
        ->toContain('docs/components/segmented.md')
        ->toContain('docs/screenshots/segmented/android-light.png')
        ->toContain('docs/screenshots/segmented/android-dark.png')
        ->toContain('spec/reviews/segmented-alpha.md');
});

it('runs the documentation gate in the matching review mode', function (array $arguments, string $expected) {
    $root = makeSegmentedValidationCopy();
    $log = installDocumentationGateProbe($root);

    $process = new Process(array_merge([$root.'/bin/check-component'], $arguments));
    $process->run();

    expect($log)->toBeFile()
        ->and(file_get_contents($log))->toBe($expected);
})->with([
    'development' => [['Segmented', '--development'], '--development'],
    'release' => [['Segmented'], ''],
]);

it('ships four concise skills with the required workflow entrypoints', function () {
    $root = dirname(__DIR__, 2);
    $contracts = [
        'firstlight-create-component' => [
            'Constitution.md',
            'spec/reference/icons.md',
            '-ios',
            '-android',
            'bin/scaffold-component',
            'firstlight-ios-component',
            'firstlight-android-component',
            'showcase',
            'firstlight-review-component',
            'Stop',
        ],
        'firstlight-ios-component' => [
            'genuine Apple',
            'spec/reference/icons.md',
            'IosSymbol',
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
            'spec/reference/icons.md',
            'AndroidSymbol',
            'filled',
            'outlined',
            'SuperNative',
            'server-authoritative',
            'Paparazzi',
            'TalkBack',
            'font scaling',
            'physical device',
            'real publication lookup',
            'Stop',
        ],
        'firstlight-review-component' => [
            'Constitution.md',
            'spec/reference/icons.md',
            'trailing-a11y-label',
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
            $searchableContents = $needle === 'physical device'
                ? str_replace('physical-device', 'physical device', $contents)
                : $contents;

            expect($searchableContents)->toContain($needle);
        }
    }
});

it('routes icon documentation through the maintained contract', function () {
    $root = dirname(__DIR__, 2);

    foreach ([
        '.agents/skills/firstlight-docs-write/SKILL.md',
        '.agents/skills/firstlight-docs-update/SKILL.md',
    ] as $path) {
        expect(file_get_contents($root.'/'.$path))
            ->toContain('spec/reference/icons.md')
            ->toContain('platform override');
    }
});
