<?php

namespace FirstlightUI\Starter;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

/**
 * Writes a confirmed StarterPlan into a Laravel app. New files are created; a file the developer has
 * changed is left alone unless $force is set, so running the command again is safe.
 */
final class StarterBuilder
{
    private const string ROUTES_BEGIN = '// firstlight:starter begin';

    private const string ROUTES_END = '// firstlight:starter end';

    /** @var array{created: list<string>, updated: list<string>, unchanged: list<string>, skipped: list<string>, todo: list<string>} */
    private array $result = ['created' => [], 'updated' => [], 'unchanged' => [], 'skipped' => [], 'todo' => []];

    /** @var list<string> */
    private array $leftOut = [];

    public function __construct(
        private readonly Filesystem $files,
        private readonly string $base,
        private readonly string $templates,
    ) {}

    /**
     * @param  list<string>  $notes  Lines for STARTER.md from the install step, such as paid plugins left out.
     * @param  list<string>  $leftOut  Accepted plugins the install step could not add.
     * @return array{created: list<string>, updated: list<string>, unchanged: list<string>, skipped: list<string>, todo: list<string>}
     */
    public function build(StarterPlan $plan, bool $force = false, array $notes = [], array $leftOut = []): array
    {
        $this->result['todo'] = $notes;
        $this->leftOut = $leftOut;

        $this->writeEnvironment($plan);
        $this->writeTimezoneConfig();
        $this->writeTheme($plan);
        $this->writePermissions($plan);
        $this->writeImages($plan);
        $this->writeLayout($plan, $force);

        foreach ($plan->records() as $record) {
            $this->writeRecord($plan, $record, $force);
        }

        foreach ($plan->screens() as $screen) {
            $this->writeScreen($plan, $screen, $force);
        }

        $this->writeRoutes($plan);
        $this->writePestSetup();
        $this->writeStarterNotes($plan, $force);

        return $this->result;
    }

    /**
     * Render a template: replace the record's names and keep @starter(flag) … @endstarter sections
     * whose flags are set. @starter(!flag) keeps a section when the flag is not set.
     *
     * @param  array<string, string>  $names
     * @param  array<string, bool>  $flags
     */
    public static function render(string $source, array $names, array $flags = []): string
    {
        $kept = [];
        $conditions = [];

        foreach (explode("\n", $source) as $number => $line) {
            if (preg_match('/^\s*@starter\((!?)(\w+)\)\s*$/', $line, $match) === 1) {
                if (! array_key_exists($match[2], $flags)) {
                    throw new RuntimeException("Template line {$number} uses unknown flag [{$match[2]}].");
                }

                $conditions[] = $match[1] === '!' ? ! $flags[$match[2]] : $flags[$match[2]];

                continue;
            }

            if (preg_match('/^\s*@endstarter\s*$/', $line) === 1) {
                array_pop($conditions);

                continue;
            }

            if (! in_array(false, $conditions, true)) {
                $kept[] = $line;
            }
        }

        $rendered = strtr(implode("\n", $kept), $names);

        if (preg_match('/__(?!(?:DIR|FILE|CLASS|LINE|FUNCTION|METHOD|NAMESPACE|TRAIT)__)[A-Za-z]+(?:_[a-z]+)*__/', $rendered, $leftover) === 1) {
            throw new RuntimeException("Template placeholder {$leftover[0]} has no value.");
        }

        return $rendered;
    }

    private function writeEnvironment(StarterPlan $plan): void
    {
        $data = $plan->data;
        $light = $data['theme']['light'];
        $dark = $data['theme']['dark'] ?? null;

        $values = array_filter([
            'APP_NAME' => $data['app']['name'],
            'APP_LOCALE' => $data['locale']['locale'] ?? 'en',
            'APP_FALLBACK_LOCALE' => $data['locale']['fallback'] ?? 'en',
            'APP_TIMEZONE' => $data['locale']['timezone'] ?? 'UTC',
            'NATIVEPHP_APP_ID' => $data['app']['id'],
            'NATIVEPHP_DEEPLINK_SCHEME' => $data['app']['deeplink_scheme'] ?? null,
            'NATIVEPHP_APPEARANCE' => $data['app']['appearance'] ?? 'system',
            'NATIVEPHP_START_URL' => $this->startUrl($plan),
            'NATIVEPHP_ANDROID_MIN_SDK' => (string) ($data['android_min_sdk'] ?? StarterPlan::ANDROID_MIN_SDK),
            'NATIVEPHP_ANDROID_COLOR_PRIMARY' => $light['primary'],
            'NATIVEPHP_ANDROID_COLOR_ON_PRIMARY' => $light['on-primary'],
            'NATIVEPHP_ANDROID_COLOR_PRIMARY_NIGHT' => $dark['primary'] ?? $light['primary'],
        ], fn ($value) => $value !== null);

        foreach (['.env', '.env.example'] as $file) {
            $path = $this->path($file);

            if (! $this->files->exists($path)) {
                if ($file === '.env') {
                    $this->result['todo'][] = 'Create `.env` (copy `.env.example`), then run `php artisan firstlight:starter` again to set the app name, ID and colours.';
                }

                continue;
            }

            $contents = $this->files->get($path);
            $original = $contents;

            foreach ($values as $key => $value) {
                $line = $key.'='.(preg_match('/[\s#"\']/', $value) === 1 ? '"'.addcslashes($value, '"\\').'"' : $value);
                $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

                $contents = preg_match($pattern, $contents) === 1
                    ? preg_replace($pattern, str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $contents, 1)
                    : rtrim($contents, "\n")."\n".$line."\n";
            }

            $this->record($file, $original, $contents);
        }
    }

    private function startUrl(StarterPlan $plan): ?string
    {
        if (($plan->data['layout']['type'] ?? null) === 'tabs') {
            return $plan->data['layout']['tabs'][0]['url'];
        }

        foreach ($plan->screens() as $screen) {
            if (! str_contains($screen['route'], '{')) {
                return $screen['route'];
            }
        }

        return null;
    }

    /** Laravel's default config/app.php hard-codes UTC, so APP_TIMEZONE would never reach the pickers. */
    private function writeTimezoneConfig(): void
    {
        $path = $this->path('config/app.php');

        if (! $this->files->exists($path)) {
            return;
        }

        $original = $this->files->get($path);
        $contents = preg_replace("/'timezone' => '[^']*',/", "'timezone' => env('APP_TIMEZONE', 'UTC'),", $original, 1);

        $this->record('config/app.php', $original, $contents);
    }

    private function writeTheme(StarterPlan $plan): void
    {
        $path = $this->path('config/native-ui.php');

        if (! $this->files->exists($path)) {
            $this->result['todo'][] = 'Publish the theme with `php artisan vendor:publish --tag=native-ui-config`, then run `php artisan firstlight:starter` again to set the brand colour.';

            return;
        }

        $original = $this->files->get($path);
        $lightAt = strpos($original, "'light' => [");
        $darkAt = strpos($original, "'dark' => [");

        if ($lightAt === false || $darkAt === false || $darkAt < $lightAt) {
            $this->result['todo'][] = "Set `primary` and `on-primary` in `config/native-ui.php` by hand; its 'light' and 'dark' blocks weren't found.";

            return;
        }

        $light = $plan->data['theme']['light'];
        $dark = $plan->data['theme']['dark'] ?? null;

        $head = substr($original, 0, $darkAt);
        $tail = substr($original, $darkAt);

        foreach (['primary', 'on-primary'] as $token) {
            $head = preg_replace("/'{$token}' => '[^']*',/", "'{$token}' => '{$light[$token]}',", $head, 1);

            // Without dark tokens NativePHP derives them from light; the published defaults would win otherwise.
            $tail = $dark === null
                ? preg_replace("/\n[ \t]*'{$token}' => '[^']*',/", '', $tail, 1)
                : preg_replace("/'{$token}' => '[^']*',/", "'{$token}' => '{$dark[$token]}',", $tail, 1);
        }

        $this->record('config/native-ui.php', $original, $head.$tail);
    }

    private function writePermissions(StarterPlan $plan): void
    {
        $permissions = $plan->data['permissions'] ?? [];
        $path = $this->path('config/nativephp.php');

        if ($permissions === []) {
            return;
        }

        if (! $this->files->exists($path)) {
            $this->result['todo'][] = 'Publish NativePHP\'s config with `php artisan vendor:publish --tag=nativephp-mobile-config`, then run `php artisan firstlight:starter` again to add the permission text.';

            return;
        }

        $original = $this->files->get($path);

        if (preg_match("/( *)'permissions' => \[\n(.*?)\n\\1\],/s", $original, $match, PREG_OFFSET_CAPTURE) !== 1) {
            $this->result['todo'][] = 'Add the permission text to `permissions` in `config/nativephp.php` by hand.';

            return;
        }

        $indent = $match[1][0].'    ';
        $lines = array_filter(
            explode("\n", $match[2][0]),
            fn (string $line): bool => preg_match("/^\s*(?:\/\/\s*)?'(\w+)' =>/", $line, $key) !== 1 || ! array_key_exists($key[1], $permissions),
        );

        foreach (array_reverse($permissions, true) as $key => $text) {
            array_unshift($lines, $indent.var_export($key, true).' => '.var_export($text, true).',');
        }

        $block = $match[1][0]."'permissions' => [\n".implode("\n", $lines)."\n".$match[1][0].'],';
        $contents = substr_replace($original, $block, $match[0][1], strlen($match[0][0]));

        $this->record('config/nativephp.php', $original, $contents);
    }

    private function writeImages(StarterPlan $plan): void
    {
        $light = $plan->data['theme']['light']['primary'];
        $icon = $plan->data['icon'] ?? null;
        $iconPath = $this->path('public/icon.png');

        if ($icon !== null) {
            $source = str_starts_with($icon, '/') ? $icon : $this->path($icon);
            $size = is_file($source) ? @getimagesize($source) : false;

            if ($size === false || $size[2] !== IMAGETYPE_PNG || $size[0] !== $size[1] || $size[0] < 1024) {
                $this->result['todo'][] = "The icon at `{$icon}` must be a square PNG of at least 1024 × 1024; it wasn't copied.";
            } else {
                $this->record('public/icon.png', $this->files->exists($iconPath) ? $this->files->get($iconPath) : null, (string) file_get_contents($source));
            }
        } elseif (! $this->files->exists($iconPath)) {
            if ($this->writePlaceholder($iconPath, 1024, 1024, $light, $light)) {
                $this->result['created'][] = 'public/icon.png';
                $this->result['todo'][] = 'Replace the placeholder `public/icon.png` with the real 1024 × 1024 app icon, with no transparency.';
            } else {
                $this->result['todo'][] = 'Add a 1024 × 1024 PNG app icon at `public/icon.png`. PHP has no GD extension here, so no placeholder was drawn.';
            }
        }

        foreach (['public/splash.png' => '#FFFFFF', 'public/splash-dark.png' => '#000000'] as $file => $background) {
            if (! $this->files->exists($this->path($file)) && $this->writePlaceholder($this->path($file), 1170, 2532, $background, $light)) {
                $this->result['created'][] = $file;
            }
        }
    }

    /** Draw a plain placeholder: a background with a brand-coloured rounded square in the middle. */
    private function writePlaceholder(string $path, int $width, int $height, string $background, string $mark): bool
    {
        if (! function_exists('imagecreatetruecolor')) {
            return false;
        }

        $image = imagecreatetruecolor($width, $height);
        $colour = fn (string $hex) => imagecolorallocate($image, ...array_map('hexdec', str_split(substr($hex, 1), 2)));

        imagefill($image, 0, 0, $colour($background));

        $side = (int) (min($width, $height) * ($background === $mark ? 0.5 : 0.3));
        [$x, $y] = [intdiv($width - $side, 2), intdiv($height - $side, 2)];
        $fill = $colour($background === $mark ? '#FFFFFF' : $mark);
        $radius = intdiv($side, 5);

        imagefilledrectangle($image, $x + $radius, $y, $x + $side - $radius, $y + $side, $fill);
        imagefilledrectangle($image, $x, $y + $radius, $x + $side, $y + $side - $radius, $fill);
        foreach ([[$x + $radius, $y + $radius], [$x + $side - $radius, $y + $radius], [$x + $radius, $y + $side - $radius], [$x + $side - $radius, $y + $side - $radius]] as [$cx, $cy]) {
            imagefilledellipse($image, $cx, $cy, $radius * 2, $radius * 2, $fill);
        }

        $this->files->ensureDirectoryExists(dirname($path));
        return imagepng($image, $path);
    }

    private function writeLayout(StarterPlan $plan, bool $force): void
    {
        $tabs = ($plan->data['layout']['type'] ?? null) === 'tabs';
        $links = implode("\n", array_map(
            fn (array $tab): string => '            ->add(Tab::link('.var_export($tab['label'], true).', '.var_export($tab['url'], true).', icon: '.var_export($tab['icon'] ?? 'home', true).'))',
            $tabs ? $plan->data['layout']['tabs'] : [],
        ));

        $source = $this->files->get($this->templates.'/app/NativeComponents/Layouts/AppLayout.php.stub');

        $this->put('app/NativeComponents/Layouts/AppLayout.php', self::render($source, ['__tab_links__' => $links], ['tabs' => $tabs]), $force);
    }

    /** @param array<string, mixed> $record */
    private function writeRecord(StarterPlan $plan, array $record, bool $force): void
    {
        $names = StarterPlan::names($record['model']);
        $names['__delete_rule__'] = str_replace('*/', '* /', (string) ($record['delete_rule'] ?? 'anyone.'));
        $flags = [];

        foreach ($this->files->allFiles($this->templates.'/record') as $template) {
            $relative = $template->getRelativePathname();

            if (str_contains($relative, 'Policy') && ! ($record['deletable'] ?? false)) {
                continue;
            }

            $target = substr(strtr($relative, $names), 0, -strlen('.stub'));

            if (str_contains($target, '__migration__')) {
                $existing = glob($this->path("database/migrations/*_create_{$names['__table__']}_table.php")) ?: [];
                $target = $existing !== []
                    ? 'database/migrations/'.basename($existing[0])
                    : str_replace('__migration__', date('Y_m_d_His')."_create_{$names['__table__']}_table", $target);
            }

            $this->put($target, self::render($template->getContents(), $names, $flags), $force);
        }

        if ($record['deletable'] ?? false) {
            $this->result['todo'][] = "Write who may delete {$names['__a_label__']} in `app/Policies/{$names['__Model__']}Policy.php`".(isset($record['delete_rule']) ? ': '.rtrim($record['delete_rule'], '.') : '').'. It allows everyone until you do.';
        }

        $this->writeSeederCall($names['__Model__'].'Seeder');
    }

    private function writeSeederCall(string $seeder): void
    {
        $path = $this->path('database/seeders/DatabaseSeeder.php');

        if (! $this->files->exists($path)) {
            return;
        }

        $original = $this->files->get($path);

        if (str_contains($original, "{$seeder}::class")) {
            return;
        }

        $contents = preg_replace('/(public function run\(\): void\s*\{\n)/', "$1        \$this->call({$seeder}::class);\n", $original, 1);

        if ($contents === $original) {
            $this->result['todo'][] = "Call `{$seeder}` from `database/seeders/DatabaseSeeder.php`.";

            return;
        }

        $this->record('database/seeders/DatabaseSeeder.php', $original, $contents);
    }

    /** @param array{recipe: string, record: ?string, template: string, class: string, route: string, name: string} $screen */
    private function writeScreen(StarterPlan $plan, array $screen, bool $force): void
    {
        $names = $screen['record'] === null ? [] : StarterPlan::names($screen['record']);
        $recipes = $screen['record'] === null ? [] : $plan->recipesFor($screen['record']);
        $record = collect($plan->records())->firstWhere('model', $screen['record']);

        $flags = [
            'create' => in_array('how-to/build-a-form-screen', $recipes, true),
            'detail' => in_array('how-to/build-a-list-detail-flow', $recipes, true),
            'edit' => in_array('how-to/keep-drafts', $recipes, true),
            'deletable' => (bool) ($record['deletable'] ?? false),
            'share' => in_array('nativephp/mobile-share', array_diff(array_column($plan->acceptedPlugins(), 'package'), $this->leftOut), true),
        ];
        $flags['navactions'] = $flags['edit'] || $flags['share'];

        foreach ($this->files->allFiles($this->templates.'/recipes/'.$screen['template']) as $template) {
            $target = substr(strtr($template->getRelativePathname(), $names), 0, -strlen('.stub'));

            $this->put($target, self::render($template->getContents(), $names, $flags), $force);
        }
    }

    private function writeRoutes(StarterPlan $plan): void
    {
        $lines = [self::ROUTES_BEGIN];

        foreach ($plan->screens() as $screen) {
            $lines[] = 'Route::native('.var_export($screen['route'], true).', \\App\\NativeComponents\\'.$screen['class'].'::class)'
                .'->name('.var_export($screen['name'], true).')'
                .'->layout(\\App\\NativeComponents\\Layouts\\AppLayout::class);';
        }

        $lines[] = self::ROUTES_END;
        $block = implode("\n", $lines);
        $path = $this->path('routes/mobile.php');

        if (! $this->files->exists($path)) {
            $this->put('routes/mobile.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n{$block}\n", false);

            return;
        }

        $original = $this->files->get($path);
        $pattern = '/'.preg_quote(self::ROUTES_BEGIN, '/').'.*?'.preg_quote(self::ROUTES_END, '/').'/s';

        $contents = preg_match($pattern, $original) === 1
            ? preg_replace($pattern, str_replace(['\\', '$'], ['\\\\', '\\$'], $block), $original, 1)
            : rtrim($original, "\n")."\n\n".$block."\n";

        if (! str_contains($contents, 'use Illuminate\\Support\\Facades\\Route;')) {
            $contents = preg_replace('/^<\?php\n/', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n", $contents, 1);
        }

        $this->record('routes/mobile.php', $original, $contents);
    }

    private function writePestSetup(): void
    {
        $block = <<<'PHP'
            // firstlight:starter — screen tests get a fresh database and Firstlight's screen assertions.
            pest()->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
                ->beforeEach(fn () => FirstlightUI\Testing\ScreenAssertions::register())
                ->in('Feature/NativeComponents');
            PHP;

        $path = $this->path('tests/Pest.php');

        if (! $this->files->exists($path)) {
            $this->put('tests/Pest.php', "<?php\n\npest()->extend(Tests\\TestCase::class)->in('Feature');\n\n{$block}\n", false);

            return;
        }

        $original = $this->files->get($path);

        if (str_contains($original, 'ScreenAssertions::register()')) {
            return;
        }

        $this->record('tests/Pest.php', $original, rtrim($original, "\n")."\n\n".$block."\n");
    }

    private function writeStarterNotes(StarterPlan $plan, bool $force): void
    {
        $lines = ['# '.$plan->data['app']['name'], ''];

        if (isset($plan->data['description'])) {
            $lines[] = $plan->data['description'];
            $lines[] = '';
        }

        $lines[] = 'Built by `php artisan firstlight:starter` from `firstlight-starter.json`. Run it again after changing the plan; it leaves files you have edited alone unless you pass `--force`.';
        $lines[] = '';
        $lines[] = '## Screens';
        $lines[] = '';

        foreach ($plan->screens() as $screen) {
            $lines[] = "- `{$screen['route']}`: `{$screen['class']}`, from [{$screen['recipe']}](https://firstlightui.dev/docs/{$screen['recipe']})";
        }

        $manual = $plan->manualScreens();
        if ($manual !== []) {
            $lines[] = '';
            $lines[] = '## Build next';
            $lines[] = '';
            $lines[] = 'These screens have no template yet. Build each from its recipe, renamed to its record, as the `building-firstlight-screens` skill describes:';
            $lines[] = '';

            foreach ($manual as $screen) {
                $lines[] = "- [ ] [{$screen['recipe']}](https://firstlightui.dev/docs/{$screen['recipe']})".($screen['record'] === null ? '' : " for {$screen['record']}");
            }
        }

        $lines[] = '';
        $lines[] = 'Each record has a title, an excerpt and a body, as in the recipes. Change the migration, model, factory and screens to your own fields. The settings screen holds example settings; replace them with yours.';

        $plugins = array_filter($plan->acceptedPlugins(), fn (array $plugin): bool => ! in_array($plugin['package'], $this->leftOut, true));
        if ($plugins !== []) {
            $lines[] = '';
            $lines[] = '## Plugins';
            $lines[] = '';

            foreach ($plugins as $plugin) {
                $line = "- `{$plugin['package']}` ({$plugin['pricing']}): {$plugin['purpose']}";

                if ($plugin['source'] === 'community') {
                    $line .= ' Community plugin. Neither Firstlight nor NativePHP maintains or warrants it. Check its licence and repository before you ship it.';
                }

                $lines[] = $line;

                foreach ($plugin['setup'] as $step) {
                    $lines[] = "  - Setup: {$step}";
                }
            }
        }

        $lines[] = '';
        $lines[] = '## Left for you';
        $lines[] = '';

        foreach ([
            ...$this->result['todo'],
            'Check each screen on an iOS simulator and an Android emulator with `php artisan native:run`.',
            'Set up signing and builds: Bifrost (https://bifrost.nativephp.com), or `php artisan native:credentials`.',
            'Create the App Store Connect app record and the Play Console listing, with screenshots and privacy answers.',
            'Set `NATIVEPHP_APP_VERSION` and `NATIVEPHP_APP_VERSION_CODE` when you release.',
        ] as $item) {
            $lines[] = "- [ ] {$item}";
        }

        foreach ($this->result['skipped'] as $file) {
            $lines[] = "- [ ] `{$file}` already existed with other contents and was left alone. Compare it with the recipe, or run `php artisan firstlight:starter --force`.";
        }

        $this->put('STARTER.md', implode("\n", $lines)."\n", true);
    }

    private function put(string $relative, string $contents, bool $force): void
    {
        $path = $this->path($relative);

        if (! $this->files->exists($path)) {
            $this->files->ensureDirectoryExists(dirname($path));
            $this->files->put($path, $contents);
            $this->result['created'][] = $relative;

            return;
        }

        if ($this->files->get($path) === $contents) {
            $this->result['unchanged'][] = $relative;

            return;
        }

        if (! $force) {
            $this->result['skipped'][] = $relative;

            return;
        }

        $this->files->put($path, $contents);
        $this->result['updated'][] = $relative;
    }

    /** Record an in-place edit of a file the app already has. */
    private function record(string $relative, ?string $original, string $contents): void
    {
        if ($original === $contents) {
            $this->result['unchanged'][] = $relative;

            return;
        }

        $path = $this->path($relative);
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $contents);
        $this->result[$original === null ? 'created' : 'updated'][] = $relative;
    }

    private function path(string $relative): string
    {
        return $this->base.'/'.$relative;
    }
}
