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

        $summary = [
            '# '.$configuration['name'],
            '',
            '> Current public documentation for '.$configuration['package'].'.',
            '',
            '- Website: '.$siteUrl,
            '- Repository: '.$configuration['repository_url'],
            '- Package: '.$configuration['package'],
            '',
            '## Documentation',
            '',
        ];

        foreach ($pages as $page) {
            $summary[] = '- ['.$page->title.']('.$this->publicUrl($siteUrl, $page->path).'): '.$page->description;
        }

        $full = ['# '.$configuration['name'].' Documentation', ''];
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

    /** @return array{name: string, site_url: string, repository_url: string, package: string, versioning: string} */
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
