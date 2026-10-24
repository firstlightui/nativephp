<?php

use FirstlightUI\Authorization\GateEvaluator;
use FirstlightUI\Console\MakeScreenCommand;
use FirstlightUI\Facades\Feedback;
use FirstlightUI\Feedback\FeedbackTone;
use FirstlightUI\Pagination\ListPage;
use FirstlightUI\Testing\ScreenAssertions;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Pagination\LengthAwarePaginator;
use Native\Mobile\Testing\Native;
use Symfony\Component\Console\Tester\CommandTester;

require_once __DIR__.'/../Support/ScreenHarness.php';

final class GeneratedScreenApplication extends Container
{
    public function __construct(private readonly string $base) {}

    public function runningUnitTests(): bool
    {
        return true;
    }

    public function basePath(string $path = ''): string
    {
        return $this->base.($path === '' ? '' : '/'.$path);
    }

    public function path(string $path = ''): string
    {
        return $this->basePath('app'.($path === '' ? '' : '/'.$path));
    }

    public function resourcePath(string $path = ''): string
    {
        return $this->basePath('resources'.($path === '' ? '' : '/'.$path));
    }
}

final class GeneratedRow
{
    public bool $deleted = false;

    public function __construct(public int $id, public string $title) {}

    public function delete(): void
    {
        $this->deleted = true;
    }
}

beforeEach(function () {
    ScreenAssertions::register();
    $this->base = sys_get_temp_dir().'/firstlight-make-screen-'.bin2hex(random_bytes(8));
    $this->app = new GeneratedScreenApplication($this->base);
});

afterEach(function () {
    if (isset($this->harness)) {
        tearDownFirstlightScreenHarness($this->harness);
    }

    (new Filesystem)->deleteDirectory($this->base);
});

function makeFirstlightScreen(Container $app, array $input): CommandTester
{
    $command = new MakeScreenCommand(new Filesystem);
    $command->setLaravel($app);
    $tester = new CommandTester($command);
    $tester->execute($input);

    return $tester;
}

it('scaffolds a plain screen and view that render', function () {
    $tester = makeFirstlightScreen($this->app, ['name' => 'Settings/NotificationPreferences']);

    $class = $this->base.'/app/NativeComponents/Settings/NotificationPreferences.php';
    $view = $this->base.'/resources/views/native/settings/notification-preferences.blade.php';

    expect($tester->getStatusCode())->toBe(0)
        ->and(file_get_contents($class))
        ->toContain('namespace App\NativeComponents\Settings;')
        ->toContain('use FirstlightUI\NativeComponent;')
        ->toContain("return view('native.settings.notification-preferences');")
        ->not->toContain('submit(')
        ->and(file_exists($view))->toBeTrue()
        ->and(file_exists($this->base.'/tests/Feature/NativeComponents/Settings/NotificationPreferencesTest.php'))->toBeFalse()
        ->and($tester->getDisplay())->toContain("Route::native('/notification-preferences'");

    exec('php -l '.escapeshellarg($class), $output, $status);
    expect($status)->toBe(0);

    require $class;
    $this->harness = bootFirstlightScreenHarness($this->app, $this->base.'/resources/views');

    Native::test(App\NativeComponents\Settings\NotificationPreferences::class)
        ->assertSee('Notification Preferences');
});

it('wires form, list, and destroy concerns into a screen its generated test can drive', function () {
    $tester = makeFirstlightScreen($this->app, [
        'name' => 'GeneratedPosts',
        '--form' => true,
        '--destroy' => true,
        '--test' => true,
    ]);

    $class = $this->base.'/app/NativeComponents/GeneratedPosts.php';
    $test = $this->base.'/tests/Feature/NativeComponents/GeneratedPostsTest.php';

    expect($tester->getStatusCode())->toBe(0)
        ->and(file_get_contents($test))
        ->toContain('use App\NativeComponents\GeneratedPosts;')
        ->toContain('ScreenAssertions::register()')
        ->toContain("->assertFieldError('name')");

    foreach ([$class, $test] as $file) {
        exec('php -l '.escapeshellarg($file), $output, $status);
        expect($status)->toBe(0);
    }

    require $class;
    $this->harness = bootFirstlightScreenHarness($this->app, $this->base.'/resources/views');
    Feedback::fake();

    // The generated form path, exactly as the generated test drives it.
    Native::test(App\NativeComponents\GeneratedPosts::class)
        ->assertListItemCount(0)
        ->tap('Save')
        ->assertFieldError('name', 'The name field is required.')
        ->assertNoFeedbackSent()
        ->input('name', 'Ada')
        ->tap('Save')
        ->assertNoFieldError('name')
        ->assertFeedbackSent('Saved', FeedbackTone::Success);

    // Seed rows through the generated listQuery() seam and delete one.
    $screenClass = get_class(new class extends App\NativeComponents\GeneratedPosts
    {
        public static array $rows = [];

        protected function listQuery(ListPage $page): object
        {
            return new LengthAwarePaginator(self::$rows, count(self::$rows), 20, $page->page);
        }

        protected function actionGateEvaluator(): GateEvaluator
        {
            return new GateEvaluator(fn (): bool => true);
        }
    });
    $screenClass::$rows = [$first = new GeneratedRow(1, 'First post'), new GeneratedRow(2, 'Second post')];

    Native::test($screenClass)
        ->assertListItemCount(2)
        ->pressListItem('First post', 'Delete')
        ->assertConfirmationDialog('Delete item?')
        ->confirmDialog()
        ->assertMissingListItem('First post')
        ->assertListItem('Second post')
        ->assertFeedbackSent('Deleted', FeedbackTone::Success);

    expect($first->deleted)->toBeTrue();
});

it('refuses to overwrite existing files without --force', function () {
    makeFirstlightScreen($this->app, ['name' => 'Inbox']);
    $class = $this->base.'/app/NativeComponents/Inbox.php';
    file_put_contents($class, '<?php // edited');

    $tester = makeFirstlightScreen($this->app, ['name' => 'Inbox', '--list' => true]);

    expect($tester->getStatusCode())->toBe(1)
        ->and(file_get_contents($class))->toBe('<?php // edited');

    $tester = makeFirstlightScreen($this->app, ['name' => 'Inbox', '--list' => true, '--force' => true]);

    expect($tester->getStatusCode())->toBe(0)
        ->and(file_get_contents($class))->toContain('refreshList(');
});
