<?php

namespace FirstlightUI\Starter;

use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * A confirmed firstlight-starter.json: what firstlight:starter builds. Reading one validates all of it
 * and reports every problem at once, so a developer or agent can fix the plan in one pass.
 */
final class StarterPlan
{
    /**
     * Recipes the command can scaffold, in the order their routes are registered: a record's create
     * route must come before its {record} route.
     *
     * @var array<string, array{template: string, record: bool, route: string, class: string, name: string}>
     */
    public const array RECIPES = [
        'how-to/build-a-searchable-list-screen' => ['template' => 'searchable-list', 'record' => true, 'route' => '/__route__', 'class' => '__Models__Screen', 'name' => '__route__.index'],
        'how-to/build-a-form-screen' => ['template' => 'form', 'record' => true, 'route' => '/__route__/create', 'class' => 'Create__Model__Screen', 'name' => '__route__.create'],
        'how-to/build-a-list-detail-flow' => ['template' => 'list-detail', 'record' => true, 'route' => '/__route__/{__model__}', 'class' => '__Model__Screen', 'name' => '__route__.show'],
        'how-to/keep-drafts' => ['template' => 'keep-drafts', 'record' => true, 'route' => '/__route__/{__model__}/edit', 'class' => 'Edit__Model__Screen', 'name' => '__route__.edit'],
        'how-to/build-a-settings-screen' => ['template' => 'settings', 'record' => false, 'route' => '/settings', 'class' => 'SettingsScreen', 'name' => 'settings'],
    ];

    /** Tables a new Laravel app already creates, which a record must not reuse. */
    private const array RESERVED_TABLES = ['users', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'migrations'];

    /** Java keywords, which Android rejects as application ID segments. */
    private const array JAVA_KEYWORDS = ['abstract', 'assert', 'boolean', 'break', 'byte', 'case', 'catch', 'char', 'class', 'const', 'continue', 'default', 'do', 'double', 'else', 'enum', 'extends', 'false', 'final', 'finally', 'float', 'for', 'goto', 'if', 'implements', 'import', 'instanceof', 'int', 'interface', 'long', 'native', 'new', 'null', 'package', 'private', 'protected', 'public', 'return', 'short', 'static', 'strictfp', 'super', 'switch', 'synchronized', 'this', 'throw', 'throws', 'transient', 'true', 'try', 'void', 'volatile', 'while'];

    private const array RESERVED_SCHEMES = ['http', 'https', 'file', 'ftp', 'mailto', 'tel', 'sms', 'data', 'javascript', 'content', 'intent', 'market'];

    /** Firstlight's own Android and iOS floors. */
    public const int ANDROID_MIN_SDK = 29;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, array<string, mixed>>  $plugins  The plugin index entries, keyed by package.
     */
    private function __construct(public readonly array $data, public readonly array $plugins) {}

    /** @param array<string, mixed> $index The decoded resources/starter/plugins.json. */
    public static function fromFile(string $path, array $index): self
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException("No starter plan at {$path}. The firstlight-init skill writes one; see resources/starter/firstlight-starter.example.json in firstlightui/nativephp.");
        }

        try {
            $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidArgumentException("{$path} is not valid JSON: {$exception->getMessage()}");
        }

        return self::fromArray(is_array($data) ? $data : [], $index);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $index
     */
    public static function fromArray(array $data, array $index): self
    {
        $plugins = [];
        foreach ($index['plugins'] ?? [] as $plugin) {
            $plugins[$plugin['package']] = $plugin;
        }

        $plan = new self($data, $plugins);
        $errors = $plan->problems();

        if ($errors !== []) {
            throw new InvalidArgumentException("The starter plan has problems:\n- ".implode("\n- ", $errors));
        }

        return $plan;
    }

    /**
     * The names a record's templates use, for a model such as WorkOrder.
     *
     * @return array<string, string>
     */
    public static function names(string $model): array
    {
        $label = Str::lower(Str::headline($model));
        $labels = Str::plural($label);

        return [
            '__Model__' => $model,
            '__Models__' => Str::pluralStudly($model),
            '__model__' => Str::camel($model),
            '__models__' => Str::camel(Str::pluralStudly($model)),
            '__route__' => Str::kebab(Str::pluralStudly($model)),
            '__view__' => Str::kebab($model),
            '__table__' => Str::snake(Str::pluralStudly($model)),
            '__label__' => $label,
            '__labels__' => $labels,
            '__Label__' => Str::ucfirst($label),
            '__Labels__' => Str::ucfirst($labels),
        ];
    }

    /**
     * Every screen the plan builds, in route order, with its resolved class and route.
     *
     * @return list<array{recipe: string, record: ?string, template: string, class: string, route: string, name: string}>
     */
    public function screens(): array
    {
        $screens = [];

        foreach (self::RECIPES as $recipe => $definition) {
            foreach ($this->data['screens'] ?? [] as $screen) {
                if (($screen['recipe'] ?? null) !== $recipe) {
                    continue;
                }

                $record = $definition['record'] ? $screen['record'] : null;
                $names = $record === null ? [] : self::names($record);

                $screens[] = [
                    'recipe' => $recipe,
                    'record' => $record,
                    'template' => $definition['template'],
                    'class' => strtr($definition['class'], $names),
                    'route' => strtr($definition['route'], $names),
                    'name' => strtr($definition['name'], $names),
                ];
            }
        }

        return $screens;
    }

    /**
     * Screens whose recipe has a how-to page but no template yet, to build by hand after the command.
     *
     * @return list<array{recipe: string, record: ?string}>
     */
    public function manualScreens(): array
    {
        return array_values(array_map(
            fn (array $screen): array => ['recipe' => $screen['recipe'], 'record' => $screen['record'] ?? null],
            array_filter($this->data['screens'] ?? [], fn (array $screen): bool => ! isset(self::RECIPES[$screen['recipe'] ?? ''])),
        ));
    }

    /** @return list<string> Recipes the plan uses for one record. */
    public function recipesFor(string $model): array
    {
        return array_values(array_map(
            fn (array $screen): string => $screen['recipe'],
            array_filter($this->screens(), fn (array $screen): bool => $screen['record'] === $model),
        ));
    }

    /** @return list<array<string, mixed>> */
    public function records(): array
    {
        return array_values($this->data['records'] ?? []);
    }

    /**
     * Plugins the developer said yes to, with their index entries.
     *
     * @return list<array<string, mixed>>
     */
    public function acceptedPlugins(): array
    {
        return array_values(array_map(
            fn (array $choice): array => $this->plugins[$choice['package']],
            array_filter($this->data['plugins'] ?? [], fn (array $choice): bool => ($choice['accepted'] ?? false) === true),
        ));
    }

    /** @return list<string> */
    private function problems(): array
    {
        $errors = [];
        $data = $this->data;

        $name = $data['app']['name'] ?? null;
        if (! is_string($name) || trim($name) === '') {
            $errors[] = 'app.name is required.';
        }

        $id = $data['app']['id'] ?? null;
        if (! is_string($id) || preg_match('/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/', $id) !== 1) {
            $errors[] = 'app.id must be a reverse-DNS ID such as com.example.app: lowercase letters, digits and underscores, at least two segments, each starting with a letter. NativePHP uses it as both the iOS bundle ID and the Android application ID.';
        } elseif (array_intersect(explode('.', $id), self::JAVA_KEYWORDS) !== []) {
            $errors[] = "app.id {$id} has a Java keyword as a segment (".implode(', ', array_intersect(explode('.', $id), self::JAVA_KEYWORDS)).'), which Android rejects.';
        }

        $scheme = $data['app']['deeplink_scheme'] ?? null;
        if ($scheme !== null && (! is_string($scheme) || preg_match('/^[a-z][a-z0-9+.-]*$/', $scheme) !== 1 || in_array($scheme, self::RESERVED_SCHEMES, true))) {
            $errors[] = 'app.deeplink_scheme must be lowercase letters and digits, start with a letter, and not be a system scheme such as https.';
        }

        $sharing = in_array('nativephp/mobile-share', array_column(array_filter($data['plugins'] ?? [], fn ($choice) => ($choice['accepted'] ?? false) === true), 'package'), true);
        if ($sharing && ! is_string($scheme)) {
            $errors[] = 'app.deeplink_scheme is required with nativephp/mobile-share, because shared links open the record in the app.';
        }

        if (! in_array($data['app']['appearance'] ?? 'system', ['system', 'light', 'dark'], true)) {
            $errors[] = 'app.appearance must be system, light or dark.';
        }

        foreach (['light', 'dark'] as $mode) {
            $pair = $data['theme'][$mode] ?? null;

            if ($pair === null && $mode === 'dark') {
                continue;
            }

            $primary = $pair['primary'] ?? null;
            $onPrimary = $pair['on-primary'] ?? null;

            if (! self::isHex($primary) || ! self::isHex($onPrimary)) {
                $errors[] = "theme.{$mode} needs primary and on-primary as #RRGGBB colours.";

                continue;
            }

            $ratio = self::contrast($primary, $onPrimary);
            if ($ratio < 4.5) {
                $errors[] = sprintf('theme.%s on-primary %s on primary %s has %.2f:1 contrast; it needs at least 4.5:1.', $mode, $onPrimary, $primary, $ratio);
            }
        }

        $icon = $data['icon'] ?? null;
        if ($icon !== null && ! is_string($icon)) {
            $errors[] = 'icon must be a path to a 1024 × 1024 PNG, or null for a placeholder.';
        }

        $timezone = $data['locale']['timezone'] ?? 'UTC';
        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            $errors[] = "locale.timezone {$timezone} is not an IANA timezone such as Australia/Sydney.";
        }

        foreach (['locale', 'fallback'] as $key) {
            if (preg_match('/^[a-z]{2,3}(_[A-Z]{2})?$/', (string) ($data['locale'][$key] ?? 'en')) !== 1) {
                $errors[] = "locale.{$key} must be a Laravel locale such as en or pt_BR.";
            }
        }

        $models = [];
        foreach ($data['records'] ?? [] as $record) {
            $model = $record['model'] ?? null;

            if (! is_string($model) || preg_match('/^[A-Z][A-Za-z0-9]*$/', $model) !== 1 || Str::singular($model) !== $model) {
                $errors[] = 'records[].model must be a singular StudlyCase model name such as Task or WorkOrder; got '.json_encode($model).'.';

                continue;
            }

            if (in_array($model, $models, true)) {
                $errors[] = "records has {$model} twice.";
            }

            if (in_array(self::names($model)['__table__'], self::RESERVED_TABLES, true)) {
                $errors[] = "records[].model {$model} would use the ".self::names($model)['__table__'].' table, which Laravel already creates. Pick another name, such as Task or WorkOrder.';
            }

            $models[] = $model;
        }

        $seen = [];
        foreach ($data['screens'] ?? [] as $screen) {
            $recipe = $screen['recipe'] ?? null;
            $definition = self::RECIPES[$recipe] ?? null;

            if ($definition === null) {
                // A recipe without a template is built by hand afterwards, so it only has to be a real page.
                if (! is_string($recipe) || preg_match('#^how-to/[a-z-]+$#', $recipe) !== 1 || ! is_file(dirname(__DIR__, 2)."/docs/{$recipe}.md")) {
                    $errors[] = 'screens[].recipe '.json_encode($recipe).' is not a Firstlight how-to page, such as how-to/build-a-form-screen.';
                }

                continue;
            }

            $record = $screen['record'] ?? null;
            if ($definition['record'] && ! in_array($record, $models, true)) {
                $errors[] = "screens[] {$recipe} needs a record from records[]; got ".json_encode($record).'.';

                continue;
            }

            $key = $recipe.'|'.($definition['record'] ? $record : '');
            if (isset($seen[$key])) {
                $errors[] = "screens has {$recipe}".($definition['record'] ? " for {$record}" : '').' twice.';
            }
            $seen[$key] = true;
        }

        foreach ($models as $model) {
            $recipes = array_map(fn (array $screen) => $screen['recipe'] ?? null, array_filter($data['screens'] ?? [], fn ($screen) => ($screen['record'] ?? null) === $model));
            $list = in_array('how-to/build-a-searchable-list-screen', $recipes, true);
            $detail = in_array('how-to/build-a-list-detail-flow', $recipes, true);

            if ($list !== $detail) {
                $errors[] = "{$model} has ".($list ? 'a list but no detail screen' : 'a detail screen but no list').': add both how-to/build-a-searchable-list-screen and how-to/build-a-list-detail-flow.';
            }

            if (in_array('how-to/keep-drafts', $recipes, true) && ! $detail) {
                $errors[] = "{$model}'s edit screen (how-to/keep-drafts) opens from its detail screen; add how-to/build-a-list-detail-flow.";
            }
        }

        $layout = $data['layout']['type'] ?? null;
        $routes = $errors === [] ? array_column($this->screens(), 'route') : [];
        if ($layout === 'tabs') {
            $tabs = $data['layout']['tabs'] ?? [];

            if (count($tabs) < 2 || count($tabs) > 5) {
                $errors[] = 'layout.tabs needs two to five tabs; use "type": "stack" for one.';
            }

            foreach ($tabs as $tab) {
                if (! is_string($tab['label'] ?? null) || trim($tab['label']) === '') {
                    $errors[] = 'Every tab needs a label.';
                }

                if ($routes !== [] && ! in_array($tab['url'] ?? null, $routes, true)) {
                    $errors[] = 'Tab '.json_encode($tab['label'] ?? null).' opens '.json_encode($tab['url'] ?? null).', which is not a screen in the plan. Screens: '.implode(', ', array_filter($routes, fn ($route) => ! str_contains($route, '{'))).'.';
                }
            }
        } elseif ($layout !== 'stack') {
            $errors[] = 'layout.type must be tabs or stack.';
        }

        $floor = self::ANDROID_MIN_SDK;
        foreach ($data['plugins'] ?? [] as $choice) {
            $package = $choice['package'] ?? null;
            $plugin = $this->plugins[$package] ?? null;

            if ($plugin === null) {
                $errors[] = 'plugins[] '.json_encode($package).' is not in the plugin index. Add marketplace plugins outside the index by hand after the starter is built.';

                continue;
            }

            if (($choice['accepted'] ?? false) === true) {
                if ($plugin['status'] !== 'available') {
                    $errors[] = "{$package} is {$plugin['status']} and can't be installed yet.";
                }

                $floor = max($floor, (int) $plugin['android']['min']);
            }
        }

        $minSdk = $data['android_min_sdk'] ?? self::ANDROID_MIN_SDK;
        if (! is_int($minSdk) || $minSdk < $floor) {
            $errors[] = "android_min_sdk must be at least {$floor}, Firstlight's floor raised by the accepted plugins.";
        }

        foreach ($data['permissions'] ?? [] as $key => $text) {
            if (preg_match('/^NS\w+UsageDescription$/', (string) $key) !== 1 || ! is_string($text) || trim($text) === '') {
                $errors[] = 'permissions must map iOS usage description keys such as NSCameraUsageDescription to a sentence; check '.json_encode($key).'.';
            }
        }

        return $errors;
    }

    private static function isHex(mixed $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1;
    }

    /** WCAG 2 contrast ratio between two #RRGGBB colours. */
    public static function contrast(string $first, string $second): float
    {
        $luminance = function (string $hex): float {
            $channels = array_map(function (string $pair): float {
                $value = hexdec($pair) / 255;

                return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
            }, str_split(substr($hex, 1), 2));

            return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
        };

        [$lighter, $darker] = [max($luminance($first), $luminance($second)), min($luminance($first), $luminance($second))];

        return ($lighter + 0.05) / ($darker + 0.05);
    }
}
