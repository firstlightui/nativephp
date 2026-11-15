<?php

function starterFile(string $path): array
{
    return json_decode(file_get_contents(dirname(__DIR__, 2).'/resources/starter/'.$path), true, flags: JSON_THROW_ON_ERROR);
}

function starterStrings(array $value): array
{
    $strings = [];

    array_walk_recursive($value, function (mixed $item) use (&$strings): void {
        if (is_string($item)) {
            $strings[] = $item;
        }
    });

    return $strings;
}

function starterKeys(array $value): array
{
    $keys = [];

    foreach ($value as $key => $item) {
        $keys[] = (string) $key;

        if (is_array($item)) {
            array_push($keys, ...starterKeys($item));
        }
    }

    return $keys;
}

it('describes every plugin the init skill may propose', function () {
    $index = starterFile('plugins.json');
    $packages = array_column($index['plugins'], 'package');

    expect($packages)->toBe(array_unique($packages));

    foreach ($index['plugins'] as $plugin) {
        $name = $plugin['package'];

        expect($name)->toMatch('#^[a-z0-9-]+/[a-z0-9-]+$#')
            ->and($plugin['source'])->toBeIn(['firstlight', 'nativephp', 'community'], $name)
            ->and($plugin['pricing'])->toBeIn(['free', 'paid'], $name)
            ->and($plugin['status'])->toBeIn(['available', 'preview', 'unreleased'], $name)
            ->and($plugin['purpose'])->toBeString()->not->toBeEmpty()
            ->and($plugin['use_cases'])->toBeArray()->not->toBeEmpty()
            ->and($plugin['ios']['min'])->toMatch('/^\d+\.\d+$/', $name)
            ->and($plugin['android']['min'])->toBeInt()
            ->and($plugin['ios']['does'])->toBeString()->not->toBeEmpty()
            ->and($plugin['android']['does'])->toBeString()->not->toBeEmpty();

        // The skill shows what the app does without a paid plugin, so it is never a hard dependency.
        if ($plugin['pricing'] === 'paid') {
            expect($plugin['without'] ?? null)->toBeString("{$name} is paid but does not say what the app does without it")->not->toBeEmpty();
        }

        // Marketplace plugins link their page; Firstlight's own are not on the marketplace.
        expect($plugin['marketplace'])->toBe($plugin['source'] === 'firstlight' ? null : "https://nativephp.com/plugins/{$name}");

        foreach ($plugin['recipes'] as $recipe) {
            expect(dirname(__DIR__, 2)."/docs/{$recipe}.md")->toBeFile();
        }

        foreach (array_keys($plugin['pairs_with']) as $partner) {
            expect($packages)->toContain($partner);
        }
    }
});

it('never proposes a plugin Firstlight already covers', function () {
    $index = starterFile('plugins.json');
    $proposed = array_column($index['plugins'], 'package');

    foreach ($index['covered_by_firstlight'] as $covered) {
        expect($proposed)->not->toContain($covered['package'])
            ->and($covered['covered_by'])->toBeString()->not->toBeEmpty();
    }
});

it('keeps versions and prices out of the index', function () {
    $index = starterFile('plugins.json');

    expect(array_map('strtolower', starterKeys($index)))
        ->not->toContain('version', 'versions', 'constraint', 'price', 'latest');

    foreach (starterStrings($index) as $text) {
        // OS floors such as "18.2" are allowed; package versions, constraints and prices are not.
        expect($text)->not->toMatch('/\bv?\d+\.\d+\.\d+\b|[\^~]\d|\$\s?\d/', $text);
    }
});

it('gives an example plan the init skill can follow', function () {
    $index = collect(starterFile('plugins.json')['plugins'])->keyBy('package');
    $plan = starterFile('firstlight-starter.example.json');

    expect($plan['app']['id'])->toMatch('/^[a-z][a-z0-9]*(\.[a-z][a-z0-9]*)+$/')
        ->and($plan)->not->toHaveKey('version');

    $accepted = [];

    foreach ($plan['plugins'] as $choice) {
        expect($index)->toHaveKey($choice['package']);

        if ($choice['accepted']) {
            expect($index[$choice['package']]['status'])->toBe('available');
            $accepted[] = $index[$choice['package']]['android']['min'];
        }
    }

    expect($plan['android_min_sdk'])->toBeGreaterThanOrEqual(max(29, ...$accepted));

    foreach ($plan['screens'] as $screen) {
        expect(dirname(__DIR__, 2)."/docs/{$screen['recipe']}.md")->toBeFile();
    }

    $routes = array_column($plan['screens'], 'route');

    foreach ($plan['layout']['tabs'] as $tab) {
        expect($routes)->toContain($tab['url']);
    }
});

it('points the init skill at the shipped index and example plan', function () {
    $skill = file_get_contents(dirname(__DIR__, 2).'/resources/boost/skills/firstlight-init/SKILL.md');

    preg_match_all('#vendor/firstlightui/nativephp/(resources/starter/[\w.-]+)#', $skill, $matches);

    expect($matches[1])->toContain('resources/starter/plugins.json', 'resources/starter/firstlight-starter.example.json');

    foreach (array_unique($matches[1]) as $path) {
        expect(dirname(__DIR__, 2).'/'.$path)->toBeFile();
    }

    preg_match_all('#`(?:how-to/)?((?:build|keep|show|guard|destroy|authorize)-[a-z-]+)`#', $skill, $recipes);

    expect($recipes[1])->not->toBeEmpty();

    foreach (array_unique($recipes[1]) as $recipe) {
        expect(dirname(__DIR__, 2)."/docs/how-to/{$recipe}.md")->toBeFile();
    }
});
