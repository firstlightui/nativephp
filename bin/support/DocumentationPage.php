<?php

declare(strict_types=1);

namespace FirstlightUI\Documentation;

use RuntimeException;

final readonly class DocumentationPage
{
    /** @param list<string> $sources */
    public function __construct(
        public string $path,
        public string $title,
        public string $description,
        public ?string $type,
        public string $audience,
        public ?string $status,
        public array $sources,
        public string $body,
    ) {}

    public static function fromFile(string $root, string $relativePath): self
    {
        $relativePath = self::normaliseRelativePath($relativePath);
        $absolutePath = rtrim($root, '/').'/'.$relativePath;

        if (! is_file($absolutePath)) {
            throw new RuntimeException("Missing indexed documentation file: {$relativePath}");
        }

        $contents = str_replace(["\r\n", "\r"], "\n", (string) file_get_contents($absolutePath));
        if (! str_starts_with($contents, "---\n")) {
            throw new RuntimeException("Missing frontmatter opening delimiter: {$relativePath}");
        }

        $closing = strpos($contents, "\n---\n", 4);
        if ($closing === false) {
            throw new RuntimeException("Missing frontmatter closing delimiter: {$relativePath}");
        }

        $header = substr($contents, 4, $closing - 4);
        $metadata = self::parseMetadata($relativePath, $header);
        $body = ltrim(substr($contents, $closing + 5), "\n");
        $body = rtrim($body)."\n";

        foreach (['title', 'description', 'audience'] as $required) {
            if (! isset($metadata[$required]) || trim((string) $metadata[$required]) === '') {
                throw new RuntimeException("Missing required frontmatter key [{$required}]: {$relativePath}");
            }
        }

        if (! isset($metadata['sources']) || $metadata['sources'] === []) {
            throw new RuntimeException("Missing required frontmatter list [sources]: {$relativePath}");
        }

        return new self(
            path: $relativePath,
            title: (string) $metadata['title'],
            description: (string) $metadata['description'],
            type: isset($metadata['type']) ? (string) $metadata['type'] : null,
            audience: (string) $metadata['audience'],
            status: isset($metadata['status']) ? (string) $metadata['status'] : null,
            sources: $metadata['sources'],
            body: $body,
        );
    }

    /** @return array<string, string|list<string>> */
    private static function parseMetadata(string $path, string $header): array
    {
        $allowed = ['title', 'description', 'type', 'audience', 'status', 'sources'];
        $metadata = [];
        $activeList = null;

        foreach (explode("\n", $header) as $lineNumber => $line) {
            if ($line === '') {
                continue;
            }

            if (preg_match('/^  - (.+)$/', $line, $matches) === 1) {
                if ($activeList !== 'sources') {
                    throw new RuntimeException("Unsupported frontmatter list at line ".($lineNumber + 2).": {$path}");
                }

                $value = trim($matches[1]);
                if ($value === '') {
                    throw new RuntimeException("Empty frontmatter source at line ".($lineNumber + 2).": {$path}");
                }

                $metadata['sources'][] = self::unquote($value);

                continue;
            }

            if (preg_match('/^([a-z_]+):(.*)$/', $line, $matches) !== 1) {
                throw new RuntimeException("Unsupported frontmatter syntax at line ".($lineNumber + 2).": {$path}");
            }

            $key = $matches[1];
            $value = trim($matches[2]);
            if (! in_array($key, $allowed, true)) {
                throw new RuntimeException("Unsupported frontmatter key [{$key}]: {$path}");
            }
            if (array_key_exists($key, $metadata)) {
                throw new RuntimeException("Duplicate frontmatter key [{$key}]: {$path}");
            }

            $activeList = null;
            if ($key === 'sources') {
                if ($value !== '') {
                    throw new RuntimeException("Frontmatter [sources] must be a list: {$path}");
                }
                $metadata[$key] = [];
                $activeList = $key;

                continue;
            }

            if ($value === '') {
                throw new RuntimeException("Empty frontmatter value [{$key}]: {$path}");
            }

            $metadata[$key] = self::unquote($value);
        }

        return $metadata;
    }

    private static function unquote(string $value): string
    {
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    private static function normaliseRelativePath(string $path): string
    {
        $parts = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                if ($parts === []) {
                    throw new RuntimeException("Documentation path escapes repository: {$path}");
                }
                array_pop($parts);
            } else {
                $parts[] = $part;
            }
        }

        return implode('/', $parts);
    }
}
