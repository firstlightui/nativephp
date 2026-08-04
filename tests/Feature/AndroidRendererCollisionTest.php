<?php

it('exports Android renderer class names that remain unambiguous beside installed UI plugins', function () {
    $root = dirname(__DIR__, 2);
    $manifestPaths = [
        $root.'/nativephp.json',
        $root.'/vendor/nativephp/mobile-ui/nativephp.json',
    ];

    $renderersByClassName = [];

    foreach ($manifestPaths as $manifestPath) {
        $manifest = json_decode(file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);

        foreach ($manifest['components'] ?? [] as $component) {
            $renderer = $component['android_renderer'] ?? null;

            if (! is_string($renderer) || $renderer === '') {
                continue;
            }

            $segments = explode('.', $renderer);
            $className = end($segments);
            $renderersByClassName[$className][$renderer] = true;
        }
    }

    $collisions = array_filter(
        $renderersByClassName,
        static fn (array $renderers): bool => count($renderers) > 1,
    );

    expect(array_keys($collisions))->toBe([]);
});
