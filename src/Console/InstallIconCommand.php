<?php

namespace FirstlightUI\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Native\Mobile\Icon\Icon;
use Native\Mobile\Icon\Svg\SvgParser;
use RuntimeException;

/**
 * Installs SVGs into `resources/icons/<name>.svg`, where NativePHP's build
 * turns them into native custom icons addressed as `custom:<name>`. Each SVG
 * is checked with NativePHP's own converter before it is written, so an icon
 * the build would reject never lands in the app.
 */
class InstallIconCommand extends Command
{
    protected $signature = 'firstlight:icon
                            {icons* : Icons such as lucide:rocket or heroicons:bell-solid (any Iconify set), fontawesome:light/rocket-launch (Font Awesome, needs FA_API_TOKEN), or a path or URL to an .svg file}
                            {--as= : Install a single icon under this name}
                            {--force : Replace icons that are already installed}';

    protected $description = 'Install SVG icons from Iconify, Font Awesome, a file or a URL as NativePHP custom icons';

    private const ICONIFY_API = 'https://api.iconify.design';

    private const FONT_AWESOME_API = 'https://api.fontawesome.com';

    private const SEGMENT = '[a-z0-9]+(?:-[a-z0-9]+)*';

    private ?string $fontAwesomeAccessToken = null;

    public function __construct(
        private readonly Filesystem $files,
        private readonly HttpFactory $http,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        /** @var list<string> $references */
        $references = $this->argument('icons');
        $as = $this->option('as');

        if ($as !== null && count($references) > 1) {
            $this->components->error('--as names one icon. Install the others in a separate command.');

            return self::FAILURE;
        }

        $failed = false;
        $installed = [];

        foreach ($references as $reference) {
            try {
                $name = $as ?? $this->defaultName($reference);

                if (! Icon::isValidName($name)) {
                    throw new RuntimeException("[{$name}] is not a valid icon name. Use lowercase kebab-case, for example --as=git-commit.");
                }

                $relativePath = "resources/icons/{$name}.svg";
                $path = $this->laravel->resourcePath("icons/{$name}.svg");

                if ($this->files->exists($path) && ! $this->option('force')) {
                    $this->components->twoColumnDetail("custom:{$name}", 'Already installed. Use --force to replace it.');

                    continue;
                }

                [$svg, $source] = $this->fetch($reference);
                $contents = '<!-- '.str_replace('--', '-', $source)." -->\n".$this->withoutXmlDeclaration($svg)."\n";

                // Throws NativePHP's own build error, naming the element and attribute.
                SvgParser::parse($contents, $relativePath);

                $this->files->ensureDirectoryExists(dirname($path));
                $this->files->put($path, $contents);
                $this->components->twoColumnDetail("custom:{$name}", $relativePath);
                $installed[] = $name;
            } catch (RuntimeException|ConnectionException $exception) {
                $this->components->error("{$reference}: {$exception->getMessage()}");
                $failed = true;
            }
        }

        if ($installed !== []) {
            $this->newLine();
            $this->line('  Use them anywhere an icon goes, for example <comment>icon="custom:'.$installed[0].'"</comment>.');
            $this->line('  Custom icons are built into the native app: run <comment>php artisan native:run</comment> to see them.');
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function defaultName(string $reference): string
    {
        if ($this->isUrl($reference) || $this->isSvgFile($reference)) {
            return pathinfo((string) parse_url($reference, PHP_URL_PATH), PATHINFO_FILENAME);
        }

        $name = str_contains($reference, ':') ? substr($reference, strpos($reference, ':') + 1) : $reference;

        return basename($name);
    }

    /**
     * @return array{0: string, 1: string} the SVG and a provenance line for its comment
     */
    private function fetch(string $reference): array
    {
        if ($this->isUrl($reference)) {
            $response = $this->http->timeout(20)->get($reference);

            if (! $response->successful()) {
                throw new RuntimeException("The download failed with HTTP {$response->status()}.");
            }

            return [$response->body(), "Source: {$reference}"];
        }

        if ($this->isSvgFile($reference)) {
            return [$this->files->get($reference), 'Source: '.basename($reference)];
        }

        if (str_ends_with(strtolower($reference), '.svg')) {
            throw new RuntimeException('No SVG file exists at that path.');
        }

        if (str_starts_with($reference, 'fontawesome:')) {
            return $this->fetchFontAwesome(substr($reference, strlen('fontawesome:')));
        }

        if (preg_match('/^('.self::SEGMENT.'):('.self::SEGMENT.')$/', $reference, $matches) === 1) {
            return $this->fetchIconify($matches[1], $matches[2]);
        }

        throw new RuntimeException('Unrecognised icon. Use set:name (an Iconify set such as lucide:rocket), fontawesome:style/name, or a path or URL to an .svg file.');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function fetchIconify(string $set, string $name): array
    {
        $response = $this->http->timeout(20)->get(self::ICONIFY_API."/{$set}/{$name}.svg");

        if ($response->status() === 404) {
            throw new RuntimeException("Iconify has no [{$name}] icon in the [{$set}] set. Browse the set at https://icon-sets.iconify.design/{$set}/");
        }

        if (! $response->successful()) {
            throw new RuntimeException("Iconify returned HTTP {$response->status()}.");
        }

        return [$response->body(), "{$set}:{$name} from Iconify. Set and licence: https://icon-sets.iconify.design/{$set}/"];
    }

    /**
     * `[family/]style/name`, such as `light/rocket-launch` or `sharp-duotone/thin/rocket`.
     *
     * @return array{0: string, 1: string}
     */
    private function fetchFontAwesome(string $path): array
    {
        $segments = explode('/', $path);

        if (count($segments) === 2) {
            array_unshift($segments, 'classic');
        }

        if (count($segments) !== 3 || preg_grep('/^'.self::SEGMENT.'$/', $segments) !== $segments) {
            throw new RuntimeException('Use fontawesome:style/name or fontawesome:family/style/name, for example fontawesome:light/rocket-launch or fontawesome:sharp/solid/rocket-launch.');
        }

        [$family, $style, $name] = $segments;
        $enum = fn (string $value): string => strtoupper(str_replace('-', '_', $value));

        // Every segment matched SEGMENT above, so nothing here can escape the query.
        $query = sprintf(
            'query { release(version: "7.x") { version icon(name: "%s") { svgs(filter: { familyStyles: [{ family: %s, style: %s }] }) { html } } } }',
            $name, $enum($family), $enum($style),
        );

        $response = $this->http->timeout(20)
            ->withToken($this->fontAwesomeAccessToken())
            ->post(self::FONT_AWESOME_API, ['query' => $query]);

        if (! $response->successful()) {
            throw new RuntimeException("Font Awesome returned HTTP {$response->status()}.");
        }

        if (is_string($error = $response->json('errors.0.message'))) {
            throw new RuntimeException(str_contains($error, 'familyStyles')
                ? "Font Awesome has no [{$family}/{$style}] style. See https://docs.fontawesome.com/web/dig-deeper/styles"
                : "Font Awesome returned an error: {$error}");
        }

        if ($response->json('data.release.icon') === null) {
            throw new RuntimeException("Font Awesome has no [{$name}] icon. Search https://fontawesome.com/search");
        }

        $svg = $response->json('data.release.icon.svgs.0.html');

        if (! is_string($svg)) {
            throw new RuntimeException("[{$name}] has no {$family}/{$style} SVG, or your Font Awesome plan doesn't include that style.");
        }

        $version = $response->json('data.release.version');

        return [$svg, "Font Awesome {$version} {$family}/{$style}/{$name}. License: https://fontawesome.com/license"];
    }

    private function fontAwesomeAccessToken(): string
    {
        if ($this->fontAwesomeAccessToken !== null) {
            return $this->fontAwesomeAccessToken;
        }

        // Laravel loads .env into these; the shell environment works too.
        $apiToken = $_ENV['FA_API_TOKEN'] ?? $_SERVER['FA_API_TOKEN'] ?? getenv('FA_API_TOKEN');

        if (! is_string($apiToken) || $apiToken === '') {
            throw new RuntimeException('Set FA_API_TOKEN to an API token from https://fontawesome.com/account/general to install Font Awesome icons. Free icons are also on Iconify, as fa7-solid:rocket, fa7-regular:bell and fa7-brands:github.');
        }

        $response = $this->http->timeout(20)->withToken($apiToken)->post(self::FONT_AWESOME_API.'/token');
        $accessToken = $response->json('access_token');

        if (! $response->successful() || ! is_string($accessToken)) {
            throw new RuntimeException("Font Awesome rejected FA_API_TOKEN (HTTP {$response->status()}).");
        }

        return $this->fontAwesomeAccessToken = $accessToken;
    }

    private function withoutXmlDeclaration(string $svg): string
    {
        return trim((string) preg_replace('/^\s*<\?xml[^>]*\?>/', '', $svg));
    }

    private function isUrl(string $reference): bool
    {
        return preg_match('#^https?://#i', $reference) === 1;
    }

    private function isSvgFile(string $reference): bool
    {
        return str_ends_with(strtolower($reference), '.svg') && $this->files->isFile($reference);
    }
}
