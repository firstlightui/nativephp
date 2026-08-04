<?php

use FirstlightUI\Documentation\DocumentationArtifactBuilder;
use FirstlightUI\Documentation\DocumentationRepository;
use FirstlightUI\Documentation\DocumentationValidator;

foreach ([
    'DocumentationPage.php',
    'DocumentationRepository.php',
    'DocumentationArtifactBuilder.php',
    'DocumentationValidator.php',
] as $supportFile) {
    $path = dirname(__DIR__, 2).'/bin/support/'.$supportFile;
    if (is_file($path)) {
        require_once $path;
    }
}

function documentationFixture(): string
{
    $root = sys_get_temp_dir().'/firstlight-docs-'.bin2hex(random_bytes(6));

    foreach ([
        'docs/getting-started',
        'docs/components',
        'spec/architecture',
        'spec/reviews',
        '.agents/skills/firstlight-docs-write',
        '.agents/skills/firstlight-docs-update',
        '.agents/skills/firstlight-docs-audit',
        '.agents/skills/firstlight-docs-screenshots',
    ] as $directory) {
        mkdir($root.'/'.$directory, 0777, true);
    }

    file_put_contents($root.'/docs/index.md', <<<'MARKDOWN'
        ---
        title: Documentation
        description: Public pages.
        type: reference
        audience: consumer
        sources:
          - composer.json
        ---

        # Documentation

        - [Install](getting-started/install.md) — Install the package.
        - [Segmented](components/segmented.md) — Use Segmented.
        MARKDOWN);

    file_put_contents($root.'/docs/getting-started/install.md', <<<'MARKDOWN'
        ---
        title: Install
        description: Install Firstlight.
        type: how-to
        audience: consumer
        sources:
          - composer.json
        ---

        # Install

        See the [component](../components/segmented.md).
        MARKDOWN);

    file_put_contents($root.'/docs/components/segmented.md', <<<'MARKDOWN'
        ---
        title: Segmented
        description: Segmented reference.
        type: reference
        audience: consumer
        sources:
          - src/Elements/Segmented.php
        ---

        # Segmented

        Public component contract.
        MARKDOWN);

    file_put_contents($root.'/spec/index.md', <<<'MARKDOWN'
        ---
        title: Specifications
        description: Current specifications.
        status: current
        audience: maintainer
        sources:
          - Constitution.md
        ---

        # Specifications

        - [Architecture](architecture/package.md) — Internal architecture.
        MARKDOWN);

    file_put_contents($root.'/spec/architecture/package.md', <<<'MARKDOWN'
        ---
        title: Architecture
        description: Internal package architecture.
        status: current
        audience: maintainer
        sources:
          - src/Elements/Segmented.php
        ---

        # Internal architecture

        This must not appear in public artefacts.
        MARKDOWN);

    file_put_contents($root.'/spec/documentation.json', json_encode([
        'name' => 'Firstlight UI',
        'site_url' => 'https://firstlightui.dev',
        'repository_url' => 'https://github.com/firstlightui/nativephp',
        'package' => 'firstlightui/nativephp',
        'versioning' => 'current',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

    file_put_contents($root.'/spec/screenshots.json', json_encode([
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

    file_put_contents($root.'/nativephp.json', json_encode([
        'components' => [[
            'element' => 'FirstlightUI\\Elements\\Segmented',
        ]],
    ], JSON_PRETTY_PRINT)."\n");

    file_put_contents($root.'/composer.json', "{}\n");
    file_put_contents($root.'/Constitution.md', "# Constitution\n");
    mkdir($root.'/src/Elements', 0777, true);
    file_put_contents($root.'/src/Elements/Segmented.php', "<?php\n");
    file_put_contents($root.'/README.md', "# Firstlight UI\n\n[Documentation](docs/index.md)\n");

    foreach (['write', 'update', 'audit', 'screenshots'] as $skill) {
        file_put_contents(
            $root.'/.agents/skills/firstlight-docs-'.$skill.'/SKILL.md',
            "---\nname: firstlight-docs-{$skill}\ndescription: Test skill.\n---\n",
        );
    }

    return $root;
}

function removeDocumentationFixture(string $root): void
{
    if (! is_dir($root)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($root);
}

it('builds deterministic public artefacts in index order', function () {
    $root = documentationFixture();

    try {
        $repository = new DocumentationRepository($root);
        $builder = new DocumentationArtifactBuilder($repository);

        $first = $builder->outputs();
        $second = $builder->outputs();

        expect($second)->toBe($first)
            ->and(strpos($first['llms-full.txt'], '# Install'))
            ->toBeLessThan(strpos($first['llms-full.txt'], '# Segmented'))
            ->and($first['llms-full.txt'])->not->toContain('Internal architecture')
            ->and($first['llms.txt'])->toContain('https://firstlightui.dev/getting-started/install')
            ->and($first['llms.txt'])->toEndWith("\n")
            ->and($first['llms-full.txt'])->toEndWith("\n");
    } finally {
        removeDocumentationFixture($root);
    }
});

it('reports documentation contract failures', function (string $case, string $expected) {
    $root = documentationFixture();

    try {
        $repository = new DocumentationRepository($root);
        $builder = new DocumentationArtifactBuilder($repository);
        foreach ($builder->outputs() as $path => $contents) {
            file_put_contents($root.'/'.$path, $contents);
        }

        match ($case) {
            'missing indexed file' => unlink($root.'/docs/components/segmented.md'),
            'missing source' => unlink($root.'/src/Elements/Segmented.php'),
            'broken link' => file_put_contents(
                $root.'/docs/getting-started/install.md',
                str_replace('../components/segmented.md', '../components/missing.md', file_get_contents($root.'/docs/getting-started/install.md')),
            ),
            'undocumented component' => file_put_contents(
                $root.'/nativephp.json',
                json_encode(['components' => [['element' => 'FirstlightUI\\Elements\\Picker']]], JSON_PRETTY_PRINT)."\n",
            ),
            'incomplete screenshots' => file_put_contents(
                $root.'/spec/screenshots.json',
                json_encode(['components' => ['segmented' => ['route' => '/captures/segmented', 'test' => 'test', 'outputs' => ['ios-light' => 'one.png']]]], JSON_PRETTY_PRINT)."\n",
            ),
            'stale output' => file_put_contents($root.'/llms.txt', "stale\n"),
        };

        $validator = new DocumentationValidator($root, $repository, $builder);

        expect(implode("\n", $validator->errors(true)))->toContain($expected);
    } finally {
        removeDocumentationFixture($root);
    }
})->with([
    'missing indexed file' => ['missing indexed file', 'docs/components/segmented.md'],
    'missing source' => ['missing source', 'src/Elements/Segmented.php'],
    'broken relative link' => ['broken link', 'docs/components/missing.md'],
    'undocumented manifest component' => ['undocumented component', 'Picker'],
    'incomplete screenshot manifest' => ['incomplete screenshots', 'android-dark'],
    'stale generated output' => ['stale output', 'llms.txt is stale'],
]);

it('defers absent screenshot and review evidence only in development mode', function () {
    $root = documentationFixture();

    try {
        $repository = new DocumentationRepository($root);
        $builder = new DocumentationArtifactBuilder($repository);
        foreach ($builder->outputs() as $path => $contents) {
            file_put_contents($root.'/'.$path, $contents);
        }

        $validator = new DocumentationValidator($root, $repository, $builder);

        expect(implode("\n", $validator->errors(true)))->not->toContain('Missing screenshot evidence')
            ->and(implode("\n", $validator->errors(false)))
            ->toContain('Missing screenshot evidence: docs/screenshots/segmented/ios-light.png')
            ->toContain('Missing release review: spec/reviews/segmented-alpha.md');
    } finally {
        removeDocumentationFixture($root);
    }
});

it('scopes a component gate to its manifest entry and public type slug', function () {
    $root = documentationFixture();

    try {
        $repository = new DocumentationRepository($root);
        $builder = new DocumentationArtifactBuilder($repository);
        foreach ($builder->outputs() as $path => $contents) {
            file_put_contents($root.'/'.$path, $contents);
        }

        file_put_contents($root.'/nativephp.json', json_encode([
            'components' => [
                [
                    'type' => 'firstlight.segmented',
                    'element' => 'FirstlightUI\\Elements\\Segmented',
                ],
                [
                    'type' => 'firstlight.switch',
                    'element' => 'FirstlightUI\\Elements\\SwitchControl',
                ],
            ],
        ], JSON_PRETTY_PRINT)."\n");

        $validator = new DocumentationValidator($root, $repository, $builder);

        expect($validator->errors(true, 'Segmented'))->toBe([])
            ->and(implode("\n", $validator->errors(true)))
            ->toContain('docs/components/switch.md');
    } finally {
        removeDocumentationFixture($root);
    }
});

it('rejects a scoped component gate without a matching manifest element', function (string $component) {
    $root = documentationFixture();

    try {
        $repository = new DocumentationRepository($root);
        $builder = new DocumentationArtifactBuilder($repository);
        foreach ($builder->outputs() as $path => $contents) {
            file_put_contents($root.'/'.$path, $contents);
        }

        $validator = new DocumentationValidator($root, $repository, $builder);

        expect($validator->errors(true, $component))
            ->toContain("Requested documentation component [{$component}] is not registered in nativephp.json; use its manifest element name.");
    } finally {
        removeDocumentationFixture($root);
    }
})->with(['unknown component' => 'NotAComponent', 'public name instead of element name' => 'Switch']);
