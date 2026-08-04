<?php

declare(strict_types=1);

namespace FirstlightUI\Documentation;

use RuntimeException;

final class DocumentationRepository
{
    public function __construct(private readonly string $root) {}

    public function root(): string
    {
        return rtrim($this->root, '/');
    }

    /** @return list<DocumentationPage> */
    public function publicPages(): array
    {
        return array_map(
            fn (string $path): DocumentationPage => DocumentationPage::fromFile($this->root(), $path),
            $this->publicPaths(),
        );
    }

    /** @return list<DocumentationPage> */
    public function currentSpecifications(): array
    {
        return array_map(
            fn (string $path): DocumentationPage => DocumentationPage::fromFile($this->root(), $path),
            $this->currentSpecificationPaths(),
        );
    }

    /** @return list<string> */
    public function publicPaths(): array
    {
        return $this->pathsFromIndex('docs/index.md', 'docs');
    }

    /** @return list<string> */
    public function currentSpecificationPaths(): array
    {
        return $this->pathsFromIndex('spec/index.md', 'spec');
    }

    /** @return list<string> */
    private function pathsFromIndex(string $indexPath, string $boundary): array
    {
        $absolutePath = $this->root().'/'.$indexPath;
        if (! is_file($absolutePath)) {
            throw new RuntimeException("Missing documentation index: {$indexPath}");
        }

        $contents = str_replace(["\r\n", "\r"], "\n", (string) file_get_contents($absolutePath));
        preg_match_all('/(?<!!)\[[^\]]+\]\(([^)]+)\)/', $contents, $matches);
        $paths = [];

        foreach ($matches[1] as $target) {
            $target = trim((string) $target, " <>\t\n\r\0\x0B");
            if ($target === '' || str_starts_with($target, '#') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $target) === 1) {
                continue;
            }

            $target = explode('#', $target, 2)[0];
            if (! str_ends_with(strtolower($target), '.md')) {
                continue;
            }

            $resolved = $this->resolveRelativePath(dirname($indexPath), $target);
            if ($resolved !== $boundary && ! str_starts_with($resolved, $boundary.'/')) {
                throw new RuntimeException("Indexed path escapes {$boundary}/: {$resolved}");
            }

            $paths[] = $resolved;
        }

        if ($paths === []) {
            throw new RuntimeException("Documentation index contains no Markdown pages: {$indexPath}");
        }

        return array_values(array_unique($paths));
    }

    public function resolveRelativePath(string $directory, string $target): string
    {
        $parts = [];
        foreach (explode('/', str_replace('\\', '/', trim($directory.'/'.$target, '/'))) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                if ($parts === []) {
                    throw new RuntimeException("Documentation link escapes repository: {$target}");
                }
                array_pop($parts);
            } else {
                $parts[] = $part;
            }
        }

        return implode('/', $parts);
    }
}
