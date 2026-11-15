<?php

use FirstlightUI\Starter\StarterBuilder;
use FirstlightUI\Starter\StarterPlan;
use Illuminate\Filesystem\Filesystem;

function starterRoot(string $path = ''): string
{
    return dirname(__DIR__, 2).'/resources/starter'.($path === '' ? '' : '/'.$path);
}

function starterIndex(): array
{
    return json_decode(file_get_contents(starterRoot('plugins.json')), true, flags: JSON_THROW_ON_ERROR);
}

/** The example plan with $changes merged in; a null value removes the key. */
function starterPlanData(array $changes = []): array
{
    $plan = json_decode(file_get_contents(starterRoot('firstlight-starter.example.json')), true, flags: JSON_THROW_ON_ERROR);

    foreach ($changes as $key => $value) {
        $value === null ? data_forget($plan, $key) : data_set($plan, $key, $value);
    }

    return $plan;
}

/** A new Laravel app's files that the builder edits, using NativePHP's real published config. */
function starterFixtureApp(): string
{
    $base = sys_get_temp_dir().'/firstlight-starter-'.bin2hex(random_bytes(8));
    $files = new Filesystem;
    $vendor = dirname(__DIR__, 2).'/vendor/nativephp';

    $files->ensureDirectoryExists($base.'/config');
    $files->ensureDirectoryExists($base.'/database/seeders');
    $files->ensureDirectoryExists($base.'/tests');
    $files->copy($vendor.'/mobile-ui/config/native-ui.php', $base.'/config/native-ui.php');
    $files->copy($vendor.'/mobile/config/nativephp.php', $base.'/config/nativephp.php');
    $files->put($base.'/config/app.php', "<?php\n\nreturn [\n    'name' => env('APP_NAME', 'Laravel'),\n    'timezone' => 'UTC',\n];\n");
    $files->put($base.'/.env', "APP_NAME=Laravel\nAPP_ENV=local\nAPP_LOCALE=en\n");
    $files->put($base.'/.env.example', "APP_NAME=Laravel\nAPP_LOCALE=en\n");
    $files->put($base.'/database/seeders/DatabaseSeeder.php', "<?php\n\nnamespace Database\\Seeders;\n\nuse Illuminate\\Database\\Seeder;\n\nclass DatabaseSeeder extends Seeder\n{\n    public function run(): void\n    {\n        // User::factory(10)->create();\n    }\n}\n");
    $files->put($base.'/tests/Pest.php', "<?php\n\npest()->extend(Tests\\TestCase::class)->in('Feature');\n");

    return $base;
}

function buildStarter(string $base, array $plan, bool $force = false, array $notes = [], array $leftOut = []): array
{
    return (new StarterBuilder(new Filesystem, $base, starterRoot()))
        ->build(StarterPlan::fromArray($plan, starterIndex()), $force, $notes, $leftOut);
}

afterEach(function () {
    if (isset($this->base)) {
        (new Filesystem)->deleteDirectory($this->base);
    }
});

it('rejects a plan that would build a broken app, naming the problem', function (array $changes, string $problem) {
    expect(fn () => StarterPlan::fromArray(starterPlanData($changes), starterIndex()))
        ->toThrow(InvalidArgumentException::class, $problem);
})->with([
    'hyphen in the app ID' => [['app.id' => 'com.acme.field-work'], 'app.id must be a reverse-DNS ID'],
    'app ID segment starting with a digit' => [['app.id' => 'com.2acme.fieldwork'], 'app.id must be a reverse-DNS ID'],
    'single-segment app ID' => [['app.id' => 'fieldwork'], 'app.id must be a reverse-DNS ID'],
    'Java keyword in the app ID' => [['app.id' => 'com.new.fieldwork'], 'Java keyword'],
    'unreadable brand pair' => [['theme.light' => ['primary' => '#14B8A6', 'on-primary' => '#FFFFFF']], 'it needs at least 4.5:1'],
    'model using a Laravel table' => [['records' => [['model' => 'Job']], 'screens' => [['recipe' => 'how-to/build-a-settings-screen']], 'layout' => ['type' => 'stack']], 'jobs table, which Laravel already creates'],
    'plural model name' => [['records.0.model' => 'WorkOrders'], 'singular StudlyCase'],
    'tab to a screen the plan lacks' => [['layout.tabs.1.url' => '/profile'], 'which is not a screen in the plan'],
    'one tab' => [['layout.tabs' => [['label' => 'Work orders', 'url' => '/work-orders']]], 'two to five tabs'],
    'list without a detail screen' => [['screens' => [['recipe' => 'how-to/build-a-searchable-list-screen', 'record' => 'WorkOrder'], ['recipe' => 'how-to/build-a-settings-screen']]], 'a list but no detail screen'],
    'recipe that is not a how-to page' => [['screens.5' => ['recipe' => 'how-to/build-a-map-screen', 'record' => 'WorkOrder']], 'is not a Firstlight how-to page'],
    'accepted unreleased plugin' => [['plugins' => [['package' => 'firstlightui/app-shortcuts', 'accepted' => true]]], 'is unreleased'],
    'plugin outside the index' => [['plugins' => [['package' => 'acme/unknown', 'accepted' => true]]], 'is not in the plugin index'],
    'minimum SDK under an accepted plugin' => [['android_min_sdk' => 26], 'android_min_sdk must be at least 29'],
    'sharing without a deep link scheme' => [['app.deeplink_scheme' => null], 'deeplink_scheme is required with nativephp/mobile-share'],
    'unknown timezone' => [['locale.timezone' => 'Sydney'], 'not an IANA timezone'],
]);

it('reports every problem in a plan at once', function () {
    $message = '';

    try {
        StarterPlan::fromArray(starterPlanData(['app.id' => 'fieldwork', 'locale.timezone' => 'Sydney', 'android_min_sdk' => 21]), starterIndex());
    } catch (InvalidArgumentException $exception) {
        $message = $exception->getMessage();
    }

    expect($message)->toContain('app.id', 'locale.timezone', 'android_min_sdk');
});

it('registers create routes before record routes whatever order the plan lists them in', function () {
    $plan = StarterPlan::fromArray(starterPlanData(['screens' => array_reverse(starterPlanData()['screens'])]), starterIndex());

    expect(array_column($plan->screens(), 'route'))
        ->toBe(['/work-orders', '/work-orders/create', '/work-orders/{workOrder}', '/work-orders/{workOrder}/edit', '/settings'])
        ->and(array_column($plan->screens(), 'class'))
        ->toBe(['WorkOrdersScreen', 'CreateWorkOrderScreen', 'WorkOrderScreen', 'EditWorkOrderScreen', 'SettingsScreen']);
});

it('names a multi-word record the way Laravel would', function () {
    expect(StarterPlan::names('WorkOrder'))->toMatchArray([
        '__Models__' => 'WorkOrders',
        '__model__' => 'workOrder',
        '__route__' => 'work-orders',
        '__view__' => 'work-order',
        '__table__' => 'work_orders',
        '__labels__' => 'work orders',
        '__Label__' => 'Work order',
    ]);
});

it('keeps only the template sections whose options are on', function () {
    $template = "a\n@starter(edit)\nb\n@starter(!share)\nc\n@endstarter\n@endstarter\nd __label__";

    expect(StarterBuilder::render($template, ['__label__' => 'job'], ['edit' => true, 'share' => false]))->toBe("a\nb\nc\nd job")
        ->and(StarterBuilder::render($template, ['__label__' => 'job'], ['edit' => true, 'share' => true]))->toBe("a\nb\nd job")
        ->and(StarterBuilder::render($template, ['__label__' => 'job'], ['edit' => false, 'share' => false]))->toBe("a\nd job")
        ->and(fn () => StarterBuilder::render('__label__', [], []))->toThrow(RuntimeException::class, '__label__ has no value')
        ->and(fn () => StarterBuilder::render("@starter(nope)\n@endstarter", [], []))->toThrow(RuntimeException::class, 'unknown flag');
});

it('renders every recipe template to valid PHP for every combination of options', function () {
    $files = new Filesystem;
    $names = StarterPlan::names('WorkOrder') + ['__delete_rule__' => 'anyone.', '__tab_links__' => "            ->add(Tab::link('A', '/a'))"];
    $flagNames = ['create', 'detail', 'edit', 'deletable', 'share', 'navactions', 'tabs'];
    $checked = 0;

    foreach (range(0, 2 ** count($flagNames) - 1) as $bits) {
        $flags = [];
        foreach ($flagNames as $i => $flag) {
            $flags[$flag] = (bool) ($bits & (1 << $i));
        }

        foreach ($files->allFiles(starterRoot()) as $template) {
            if (! str_ends_with($template->getFilename(), '.php.stub') || str_ends_with($template->getFilename(), '.blade.php.stub')) {
                continue;
            }

            $source = StarterBuilder::render($template->getContents(), $names, $flags);

            try {
                token_get_all($source, TOKEN_PARSE);
            } catch (ParseError $error) {
                throw new RuntimeException($template->getRelativePathname().' with '.json_encode(array_keys(array_filter($flags))).": {$error->getMessage()} on line {$error->getLine()}");
            }

            $checked++;
        }
    }

    expect($checked)->toBeGreaterThan(100);
});

it('makes the same Firstlight calls as the recipe page each template comes from', function (string $recipe, array $notCopied) {
    $template = StarterPlan::RECIPES[$recipe]['template'];
    $names = StarterPlan::names('Post');
    $flags = ['create' => true, 'detail' => true, 'edit' => true, 'deletable' => true, 'share' => true, 'navactions' => true];
    $rendered = '';

    foreach ((new Filesystem)->allFiles(starterRoot('recipes/'.$template)) as $file) {
        $rendered .= StarterBuilder::render($file->getContents(), $names, $flags);
    }

    preg_match_all('/^```php\n(.*?)^```/ms', file_get_contents(dirname(__DIR__, 2)."/docs/{$recipe}.md"), $blocks);
    preg_match_all('/\$this->(\w+)\(/', implode("\n", $blocks[1]), $calls);
    preg_match_all('/\$this->(\w+)\(/', $rendered, $made);

    $missing = array_values(array_diff(array_unique($calls[1]), $notCopied, $made[1]));

    expect($missing)->toBe([], "{$template} no longer makes these calls from docs/{$recipe}.md: ".implode(', ', $missing));
})->with([
    ['how-to/build-a-searchable-list-screen', []],
    ['how-to/build-a-form-screen', []],
    ['how-to/build-a-list-detail-flow', []],
    ['how-to/keep-drafts', []],
    // The template drops the recipe's Privacy row, which opens a screen the starter doesn't build.
    ['how-to/build-a-settings-screen', ['navigate']],
]);

it('writes the app identity, theme, permissions, screens and routes from the plan', function () {
    $this->base = starterFixtureApp();
    $result = buildStarter($this->base, starterPlanData());
    $read = fn (string $file): string => file_get_contents($this->base.'/'.$file);

    expect($result['created'])->toContain(
        'app/NativeComponents/Layouts/AppLayout.php',
        'app/Models/WorkOrder.php',
        'app/Policies/WorkOrderPolicy.php',
        'app/NativeComponents/WorkOrdersScreen.php',
        'app/NativeComponents/EditWorkOrderScreen.php',
        'tests/Feature/NativeComponents/CreateWorkOrderScreenTest.php',
        'routes/mobile.php',
        'STARTER.md',
    );

    expect($read('.env'))
        ->toContain("APP_NAME=Fieldwork\n", "APP_ENV=local\n", "NATIVEPHP_APP_ID=com.acme.fieldwork\n", "APP_TIMEZONE=Australia/Sydney\n", "NATIVEPHP_START_URL=/work-orders\n", "NATIVEPHP_ANDROID_COLOR_PRIMARY=\"#0F766E\"\n")
        ->not->toContain('APP_NAME=Laravel')
        ->and($read('config/app.php'))->toContain("'timezone' => env('APP_TIMEZONE', 'UTC'),")
        ->and($read('config/nativephp.php'))->toContain("'NSCameraUsageDescription' => 'Fieldwork uses the camera to add photos to a work order.',");

    $theme = (require $this->base.'/config/native-ui.php')['theme'];
    expect($theme['light']['primary'])->toBe('#0F766E')
        ->and($theme['light']['on-primary'])->toBe('#FFFFFF')
        // No dark pair in the plan: NativePHP derives it, so the published default must not win.
        ->and($theme['dark'])->not->toHaveKeys(['primary', 'on-primary']);

    $routes = $read('routes/mobile.php');
    expect(strpos($routes, "'/work-orders/create'"))->toBeLessThan(strpos($routes, "'/work-orders/{workOrder}'"))
        ->and($read('app/NativeComponents/Layouts/AppLayout.php'))->toContain("Tab::link('Work orders', '/work-orders', icon: 'list')", "Tab::link('Settings', '/settings', icon: 'settings')")
        ->and($read('app/NativeComponents/WorkOrderScreen.php'))->toContain('Share::url(', "NavAction::make('edit')")
        ->and($read('database/seeders/DatabaseSeeder.php'))->toContain('$this->call(WorkOrderSeeder::class);')
        ->and($read('tests/Pest.php'))->toContain('ScreenAssertions::register()')
        ->and($read('STARTER.md'))->toContain('`nativephp/mobile-network` (free)', 'Only the technician assigned to the work order. It allows everyone');
});

it('runs again without touching files the developer changed, and says which it kept', function () {
    $this->base = starterFixtureApp();
    buildStarter($this->base, starterPlanData());

    $screen = $this->base.'/app/NativeComponents/WorkOrdersScreen.php';
    file_put_contents($screen, file_get_contents($screen)."\n// my change\n");

    $again = buildStarter($this->base, starterPlanData());

    expect($again['created'])->toBe([])
        ->and($again['skipped'])->toBe(['app/NativeComponents/WorkOrdersScreen.php'])
        ->and(file_get_contents($screen))->toContain('// my change')
        ->and(substr_count(file_get_contents($this->base.'/routes/mobile.php'), '// firstlight:starter begin'))->toBe(1)
        ->and(substr_count(file_get_contents($this->base.'/database/seeders/DatabaseSeeder.php'), 'WorkOrderSeeder::class'))->toBe(1)
        ->and(file_get_contents($this->base.'/STARTER.md'))->toContain('`app/NativeComponents/WorkOrdersScreen.php` already existed');

    $forced = buildStarter($this->base, starterPlanData(), force: true);

    expect($forced['updated'])->toContain('app/NativeComponents/WorkOrdersScreen.php')
        ->and(file_get_contents($screen))->not->toContain('// my change');
});

it('builds a single stack, lists recipes without a template, and leaves out a paid plugin that could not be installed', function () {
    $this->base = starterFixtureApp();
    $plan = starterPlanData([
        'screens.5' => ['recipe' => 'how-to/build-a-photo-upload-screen', 'record' => 'WorkOrder'],
        'layout' => ['type' => 'stack'],
        'plugins' => [['package' => 'nativephp/mobile-biometrics', 'accepted' => true], ['package' => 'nativephp/mobile-share', 'accepted' => true]],
    ]);

    buildStarter($this->base, $plan, notes: ['Add the paid plugin `nativephp/mobile-biometrics`: buy it first.'], leftOut: ['nativephp/mobile-biometrics']);
    $starter = file_get_contents($this->base.'/STARTER.md');

    expect(file_get_contents($this->base.'/app/NativeComponents/Layouts/AppLayout.php'))->not->toContain('tabBar', 'Tab::')
        ->and($starter)->toContain('- [ ] Add the paid plugin `nativephp/mobile-biometrics`: buy it first.', '`nativephp/mobile-share` (free)')
        ->and($starter)->not->toContain('`nativephp/mobile-biometrics` (paid)')
        ->and($starter)->toContain('- [ ] [how-to/build-a-photo-upload-screen](https://firstlightui.dev/docs/how-to/build-a-photo-upload-screen) for WorkOrder');
});
