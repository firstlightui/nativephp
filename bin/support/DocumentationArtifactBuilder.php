<?php

declare(strict_types=1);

namespace FirstlightUI\Documentation;

use JsonException;
use RuntimeException;

final class DocumentationArtifactBuilder
{
    public function __construct(private readonly DocumentationRepository $repository) {}

    /** @return array{'llms.txt': string, 'llms-full.txt': string} */
    public function outputs(): array
    {
        $configuration = $this->configuration();
        $pages = $this->repository->publicPages();
        $siteUrl = rtrim($configuration['site_url'], '/');
        $docsUrl = rtrim($configuration['docs_url'] ?? $siteUrl, '/');
        $index = DocumentationPage::fromFile($this->repository->root(), 'docs/index.md');
        [$introduction, $headings] = $this->indexStructure($index);

        $summary = [
            '# '.$configuration['name'],
            '',
            '> '.$index->description,
            '',
        ];

        if ($introduction !== '') {
            $summary[] = $introduction;
            $summary[] = '';
        }

        array_push(
            $summary,
            '- Website: '.$siteUrl,
            '- Documentation: '.$docsUrl,
            '- Repository: '.$configuration['repository_url'],
            '- Package: '.$configuration['package'],
        );

        $currentHeading = null;
        foreach ($pages as $page) {
            $heading = $headings[$page->path] ?? 'Documentation';
            if ($heading !== $currentHeading) {
                array_push($summary, '', '## '.$heading, '');
                $currentHeading = $heading;
            }

            $summary[] = '- ['.$page->title.']('.$this->publicUrl($docsUrl, $page->path).'): '.$page->description;
        }

        $full = ['# '.$configuration['name'].' Documentation', '', '> '.$index->description, ''];
        foreach ($pages as $page) {
            $full[] = '--- Source: '.$page->path.' ---';
            $full[] = '';
            $full[] = rtrim(str_replace(["\r\n", "\r"], "\n", $page->body));
            $full[] = '';
        }

        return [
            'llms.txt' => rtrim(implode("\n", $summary))."\n",
            'llms-full.txt' => rtrim(implode("\n", $full))."\n",
        ];
    }

    /**
     * The index's lead text, and the second-level heading each indexed page is listed under.
     *
     * @return array{0: string, 1: array<string, string>}
     */
    private function indexStructure(DocumentationPage $index): array
    {
        $introduction = [];
        $headings = [];
        $heading = null;

        foreach (explode("\n", $index->body) as $line) {
            if (preg_match('/^##\s+(.+)$/', $line, $match) === 1) {
                $heading = trim($match[1]);

                continue;
            }

            if ($heading === null) {
                if (! str_starts_with($line, '# ')) {
                    $introduction[] = $line;
                }

                continue;
            }

            preg_match_all('/(?<!!)\[[^\]]+\]\(([^)#\s]+\.md)(?:#[^)]*)?\)/', $line, $matches);
            foreach ($matches[1] as $target) {
                $path = $this->repository->resolveRelativePath('docs', $target);
                $headings[$path] ??= $heading;
            }
        }

        return [trim(implode("\n", $introduction)), $headings];
    }

    /** @return array{name: string, site_url: string, docs_url?: string, repository_url: string, package: string, versioning: string} */
    private function configuration(): array
    {
        $path = $this->repository->root().'/spec/documentation.json';
        if (! is_file($path)) {
            throw new RuntimeException('Missing documentation configuration: spec/documentation.json');
        }

        try {
            $configuration = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Invalid documentation configuration: '.$exception->getMessage(), previous: $exception);
        }

        foreach (['name', 'site_url', 'repository_url', 'package', 'versioning'] as $key) {
            if (! is_string($configuration[$key] ?? null) || trim($configuration[$key]) === '') {
                throw new RuntimeException("Missing documentation configuration key: {$key}");
            }
        }

        return $configuration;
    }

    private function publicUrl(string $siteUrl, string $path): string
    {
        if ($path === 'docs/index.md') {
            return $siteUrl;
        }

        $relative = preg_replace('#^docs/#', '', $path);
        $relative = preg_replace('/\.md$/i', '', (string) $relative);

        return $siteUrl.'/'.ltrim((string) $relative, '/');
    }
}
