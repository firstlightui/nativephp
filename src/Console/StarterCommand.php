<?php

namespace FirstlightUI\Console;

use FirstlightUI\Starter\StarterBuilder;
use FirstlightUI\Starter\StarterPlan;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use Symfony\Component\Process\Process;

class StarterCommand extends Command
{
    protected $signature = 'firstlight:starter
                            {plan=firstlight-starter.json : The confirmed plan the firstlight-init skill wrote}
                            {--skip-plugins : Do not run composer require or native:plugin:register for the accepted plugins}
                            {--force : Overwrite files that already exist with other contents}';

    protected $description = 'Build a Firstlight starter app from a confirmed firstlight-starter.json plan';

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $templates = dirname(__DIR__, 2).'/resources/starter';
        $index = json_decode($this->files->get($templates.'/plugins.json'), true, flags: JSON_THROW_ON_ERROR);
        $path = $this->argument('plan');
        $path = str_starts_with($path, '/') ? $path : $this->laravel->basePath($path);

        try {
            $plan = StarterPlan::fromFile($path, $index);
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        [$notes, $leftOut] = $this->option('skip-plugins') ? [[], []] : $this->installPlugins($plan);

        foreach (['native-ui-config' => 'config/native-ui.php', 'nativephp-mobile-config' => 'config/nativephp.php'] as $tag => $file) {
            if (! $this->files->exists($this->laravel->basePath($file))) {
                $this->callSilently('vendor:publish', ['--tag' => $tag]);
            }
        }

        $result = (new StarterBuilder($this->files, $this->laravel->basePath(), $templates))
            ->build($plan, (bool) $this->option('force'), $notes, $leftOut);

        foreach (['created' => 'Created', 'updated' => 'Updated', 'skipped' => 'Kept (yours differs)'] as $key => $label) {
            foreach ($result[$key] as $file) {
                $this->components->twoColumnDetail($label, $file);
            }
        }

        $this->newLine();
        $this->line('  Next: <comment>php artisan migrate --seed</comment>, run the tests, and read <comment>STARTER.md</comment> for what is left.');

        return self::SUCCESS;
    }

    /**
     * Require and register each accepted plugin. A paid plugin that Composer refuses is left out,
     * with the steps to buy and add it.
     *
     * @return array{0: list<string>, 1: list<string>} Notes for STARTER.md, and the packages left out.
     */
    private function installPlugins(StarterPlan $plan): array
    {
        [$notes, $leftOut] = [[], []];

        foreach ($plan->acceptedPlugins() as $plugin) {
            $package = $plugin['package'];
            $note = $this->installPlugin($plugin);

            $this->components->twoColumnDetail("Installing {$package}", $note === null ? '<fg=green;options=bold>DONE</>' : '<fg=yellow;options=bold>LEFT OUT</>');

            if ($note !== null) {
                $notes[] = $note;
                $leftOut[] = $package;
            }
        }

        return [$notes, $leftOut];
    }

    /**
     * @param  array<string, mixed>  $plugin
     * @return ?string Why the plugin is missing and how to add it, or null once it is installed and registered.
     */
    private function installPlugin(array $plugin): ?string
    {
        $package = $plugin['package'];

        // The repository stays even if the plugin can't be installed yet: buying it is the only step left.
        if ($plugin['pricing'] === 'paid') {
            $this->ensurePluginRepository();
        }

        $manifests = array_filter(['composer.json', 'composer.lock'], fn (string $file): bool => $this->files->exists($this->laravel->basePath($file)));
        $saved = array_map(fn (string $file): string => $this->files->get($this->laravel->basePath($file)), array_combine($manifests, $manifests));

        $require = $this->process(['composer', 'require', $package, '--no-interaction']);

        if (! $require->isSuccessful()) {
            // Composer writes the package into composer.json and the lock file before the download a
            // missing licence refuses, so put both back as they were.
            foreach ($saved as $file => $contents) {
                $this->files->put($this->laravel->basePath($file), $contents);
            }

            $output = $require->getOutput().$require->getErrorOutput();

            return $plugin['pricing'] === 'paid' && preg_match('/\b(401|403)\b|authenticat|credentials/i', $output) === 1
                ? "Add the paid plugin `{$package}`: buy it on {$plugin['marketplace']}, run `composer config http-basic.plugins.nativephp.com <email> <licence-key>` yourself (the key is on https://nativephp.com/dashboard/purchased-plugins), then `composer require {$package}` and `php artisan native:plugin:register {$package}`. Until then: {$plugin['without']}"
                : "Install `{$package}`: `composer require {$package}` failed. Run it yourself to see why, then `php artisan native:plugin:register {$package}`.";
        }

        if (! $this->process(['php', 'artisan', 'native:plugin:register', $package, '--no-interaction'])->isSuccessful()) {
            return "Register `{$package}` with `php artisan native:plugin:register {$package}`.";
        }

        return null;
    }

    private function ensurePluginRepository(): void
    {
        $composer = json_decode($this->files->get($this->laravel->basePath('composer.json')), true);

        foreach ($composer['repositories'] ?? [] as $repository) {
            if (str_starts_with((string) ($repository['url'] ?? ''), 'https://plugins.nativephp.com')) {
                return;
            }
        }

        $this->process(['composer', 'config', 'repositories.nativephp-plugins', 'composer', 'https://plugins.nativephp.com']);

        // The lock file's content hash covers the repositories, so refresh it without changing any package.
        if ($this->files->exists($this->laravel->basePath('composer.lock'))) {
            $this->process(['composer', 'update', '--lock', '--no-install', '--no-interaction']);
        }
    }

    /** @param list<string> $command */
    private function process(array $command): Process
    {
        $process = new Process($command, $this->laravel->basePath(), timeout: 600);
        $process->run();

        return $process;
    }
}
