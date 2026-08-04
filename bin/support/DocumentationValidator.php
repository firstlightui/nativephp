<?php

declare(strict_types=1);

namespace FirstlightUI\Documentation;

use JsonException;
use RuntimeException;
use Throwable;

final class DocumentationValidator
{
    public function __construct(
        private readonly string $root,
        private readonly DocumentationRepository $repository,
        private readonly DocumentationArtifactBuilder $builder,
    ) {}

    /** @return list<string> */
    public function errors(bool $development, ?string $componentName = null): array
    {
        $errors = [];
        $pages = [];

        foreach ([
            ['docs/index.md', fn (): array => $this->repository->publicPaths(), true],
            ['spec/index.md', fn (): array => $this->repository->currentSpecificationPaths(), false],
        ] as [$indexPath, $paths, $public]) {
            try {
                $index = DocumentationPage::fromFile($this->root(), $indexPath);
                $this->validatePage($index, (bool) $public, $errors);
                $pages[] = $index;

                foreach ($paths() as $path) {
                    try {
                        $page = DocumentationPage::fromFile($this->root(), $path);
                        $this->validatePage($page, (bool) $public, $errors);
                        $pages[] = $page;
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            } catch (Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        foreach ($pages as $page) {
            $this->validateLinks($page, $development, $errors);
        }
        $this->validateStandaloneLinks('README.md', $development, $errors);

        $componentSlugs = $this->validateManifestComponents($errors, $componentName);
        $this->validateScreenshotManifest($componentSlugs, $development, $errors);
        $this->validateSkills($errors);
        $this->validateGeneratedOutputs($errors);

        return array_values(array_unique($errors));
    }

    private function root(): string
    {
        return rtrim($this->root, '/');
    }

    /** @param list<string> $errors */
    private function validatePage(DocumentationPage $page, bool $public, array &$errors): void
    {
        if ($public) {
            if (! in_array($page->type, ['tutorial', 'how-to', 'reference', 'explanation'], true)) {
                $errors[] = "Invalid or missing public page type: {$page->path}";
            }
            if ($page->audience !== 'consumer') {
                $errors[] = "Public page audience must be consumer: {$page->path}";
            }
        } elseif ($page->status !== 'current') {
            $errors[] = "Maintained specification status must be current: {$page->path}";
        }

        foreach ($page->sources as $source) {
            if (! file_exists($this->root().'/'.$source)) {
                $errors[] = "Missing declared source for {$page->path}: {$source}";
            }
        }
    }

    /** @param list<string> $errors */
    private function validateLinks(DocumentationPage $page, bool $development, array &$errors): void
    {
        $this->validateMarkdownLinks($page->path, $page->body, $development, $errors);
    }

    /** @param list<string> $errors */
    private function validateStandaloneLinks(string $path, bool $development, array &$errors): void
    {
        if (! is_file($this->root().'/'.$path)) {
            $errors[] = "Missing documentation landing page: {$path}";

            return;
        }

        $this->validateMarkdownLinks($path, (string) file_get_contents($this->root().'/'.$path), $development, $errors);
    }

    /** @param list<string> $errors */
    private function validateMarkdownLinks(string $sourcePath, string $contents, bool $development, array &$errors): void
    {
        preg_match_all('/!?\[[^\]]*\]\(([^)]+)\)/', $contents, $matches);

        foreach ($matches[1] as $target) {
            $target = trim((string) $target, " <>\t\n\r\0\x0B");
            if ($target === '' || str_starts_with($target, '#') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $target) === 1) {
                continue;
            }

            $target = explode('#', $target, 2)[0];
            $resolved = $this->repository->resolveRelativePath(dirname($sourcePath), $target);
            if ($development && str_starts_with($resolved, 'docs/screenshots/') && str_ends_with(strtolower($resolved), '.png')) {
                continue;
            }

            if (! file_exists($this->root().'/'.$resolved)) {
                $errors[] = "Broken relative link in {$sourcePath}: {$resolved}";
            }
        }
    }

    /** @param list<string> $errors
     *  @return list<string>
     */
    private function validateManifestComponents(array &$errors, ?string $componentName = null): array
    {
        try {
            $manifest = $this->json('nativephp.json');
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();

            return [];
        }

        try {
            $indexed = $this->repository->publicPaths();
        } catch (Throwable) {
            $indexed = [];
        }

        $slugs = [];
        $matchedRequestedComponent = $componentName === null;
        foreach ($manifest['components'] ?? [] as $component) {
            $element = is_array($component) ? ($component['element'] ?? null) : null;
            if (! is_string($element) || $element === '') {
                $errors[] = 'Invalid component element in nativephp.json';

                continue;
            }

            $name = substr($element, (int) strrpos('\\'.$element, '\\'));
            $name = ltrim($name, '\\');
            if ($componentName !== null && $name !== $componentName) {
                continue;
            }

            $matchedRequestedComponent = true;
            $type = $component['type'] ?? null;
            $slug = is_string($type) && preg_match('/^firstlight\.([a-z0-9-]+)$/', $type, $matches) === 1
                ? $matches[1]
                : strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $name));
            $path = "docs/components/{$slug}.md";
            $slugs[] = $slug;

            if (! is_file($this->root().'/'.$path) || ! in_array($path, $indexed, true)) {
                $errors[] = "Undocumented nativephp.json component {$name}: {$path}";
            }
        }

        if (! $matchedRequestedComponent) {
            $errors[] = "Requested documentation component [{$componentName}] is not registered in nativephp.json; use its manifest element name.";
        }

        return array_values(array_unique($slugs));
    }

    /** @param list<string> $componentSlugs
     *  @param list<string> $errors
     */
    private function validateScreenshotManifest(array $componentSlugs, bool $development, array &$errors): void
    {
        try {
            $manifest = $this->json('spec/screenshots.json');
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();

            return;
        }

        $components = $manifest['components'] ?? null;
        if (! is_array($components)) {
            $errors[] = 'Screenshot manifest must contain a components object: spec/screenshots.json';

            return;
        }

        $variants = ['ios-light', 'ios-dark', 'android-light', 'android-dark'];
        foreach ($componentSlugs as $slug) {
            $component = $components[$slug] ?? null;
            if (! is_array($component)) {
                $errors[] = "Missing screenshot manifest component: {$slug}";

                continue;
            }

            foreach (['route', 'test'] as $key) {
                if (! is_string($component[$key] ?? null) || trim($component[$key]) === '') {
                    $errors[] = "Missing screenshot manifest {$key} for {$slug}";
                }
            }

            $outputs = $component['outputs'] ?? null;
            foreach ($variants as $variant) {
                $path = is_array($outputs) ? ($outputs[$variant] ?? null) : null;
                if (! is_string($path) || trim($path) === '') {
                    $errors[] = "Missing screenshot manifest output {$variant} for {$slug}";

                    continue;
                }

                if (! $development && ! is_file($this->root().'/'.$path)) {
                    $errors[] = "Missing screenshot evidence: {$path}";
                }
            }

            if (! $development) {
                $review = "spec/reviews/{$slug}-alpha.md";
                if (! is_file($this->root().'/'.$review)) {
                    $errors[] = "Missing release review: {$review}";
                } elseif (str_contains((string) file_get_contents($this->root().'/'.$review), '- [ ]')) {
                    $errors[] = "Incomplete release review: {$review}";
                }
            }
        }
    }

    /** @param list<string> $errors */
    private function validateSkills(array &$errors): void
    {
        foreach (['write', 'update', 'audit', 'screenshots'] as $name) {
            $path = ".agents/skills/firstlight-docs-{$name}/SKILL.md";
            if (! is_file($this->root().'/'.$path)) {
                $errors[] = "Missing documentation skill: {$path}";
            }
        }
    }

    /** @param list<string> $errors */
    private function validateGeneratedOutputs(array &$errors): void
    {
        try {
            foreach ($this->builder->outputs() as $path => $expected) {
                $absolutePath = $this->root().'/'.$path;
                if (! is_file($absolutePath)) {
                    $errors[] = "Missing generated documentation artefact: {$path}";
                } elseif ((string) file_get_contents($absolutePath) !== $expected) {
                    $errors[] = "Generated documentation artefact {$path} is stale";
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'Unable to build documentation artefacts: '.$exception->getMessage();
        }
    }

    /** @return array<string, mixed> */
    private function json(string $path): array
    {
        $absolutePath = $this->root().'/'.$path;
        if (! is_file($absolutePath)) {
            throw new RuntimeException("Missing JSON documentation contract: {$path}");
        }

        try {
            $decoded = json_decode((string) file_get_contents($absolutePath), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Invalid JSON documentation contract {$path}: {$exception->getMessage()}", previous: $exception);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException("JSON documentation contract must be an object: {$path}");
        }

        return $decoded;
    }
}
