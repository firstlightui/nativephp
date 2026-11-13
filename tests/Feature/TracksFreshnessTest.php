<?php

use Carbon\CarbonImmutable;
use FirstlightUI\Concerns\TracksFreshness;
use FirstlightUI\Freshness\Freshness;
use FirstlightUI\Freshness\FreshnessNotice;
use FirstlightUI\NativeComponent;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Testing\Native;
use Native\Mobile\Testing\TestableComponent;

require_once __DIR__.'/../Support/ScreenHarness.php';

/** A server list that fetches nothing: tests report what it shows and how refreshes went. */
class FreshnessServersScreen extends NativeComponent
{
    use TracksFreshness;

    public int $reloads = 0;

    public function mount(): void
    {
        $this->freshness('servers', freshFor: 60);
    }

    public function tracker(string $key = 'servers', ?int $freshFor = null): Freshness
    {
        return $this->freshness($key, $freshFor);
    }

    public function notice(string $key = 'servers'): ?FreshnessNotice
    {
        return $this->freshnessNotice($key);
    }

    public function reload(): void
    {
        $this->reloads++;
        $this->freshness('servers')->refreshed();
    }

    /** Publishes a new frame after a test changes a tracker directly. */
    public function rerender(): void {}

    public function render(): \Illuminate\View\View
    {
        return view('native.servers');
    }
}

beforeEach(function () {
    $this->views = sys_get_temp_dir().'/firstlight-freshness-'.bin2hex(random_bytes(8));
    $files = new Filesystem;
    $files->makeDirectory($this->views.'/native', 0755, true);
    $files->put($this->views.'/native/servers.blade.php', <<<'BLADE'
        <native:column>
            @include('firstlight::native.freshness', ['notice' => $this->freshnessNotice('servers')])
            <native:text text="Servers" />
        </native:column>
        BLADE);

    $this->harness = bootFirstlightScreenHarness(new Container, $this->views);
    app('view')->addNamespace('firstlight', dirname(__DIR__, 2).'/resources/views');
    app('blade.compiler')->component('native-firstlight-callout', FirstlightUI\Components\Callout::class);
    ElementRegistry::register('firstlight.callout', FirstlightUI\Elements\Callout::class);

    // 2:05 PM UTC, the harness's timezone.
    $this->fetched = CarbonImmutable::parse('2026-11-13 14:05:00', 'UTC');
    CarbonImmutable::setTestNow($this->fetched);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
    tearDownFirstlightScreenHarness($this->harness);
    (new Filesystem)->deleteDirectory($this->views);
});

function freshnessScreen(?array $network = null): TestableComponent
{
    $screen = Native::test(FreshnessServersScreen::class);

    if ($network !== null) {
        $screen->bridge()->respondTo('Network.Status', $network);
    }

    return $screen;
}

function freshnessNoticeOf(TestableComponent $screen): ?array
{
    $notice = $screen->instance()->notice();

    return $notice === null ? null : [$notice->tone, $notice->message, $notice->actionLabel];
}

it('says nothing while the data is inside its freshness window, then shows its age', function () {
    $screen = freshnessScreen();
    $screen->instance()->tracker()->showing($this->fetched);

    CarbonImmutable::setTestNow($this->fetched->addSeconds(59));
    expect($screen->instance()->tracker()->isStale())->toBeFalse()
        ->and(freshnessNoticeOf($screen))->toBeNull();

    CarbonImmutable::setTestNow($this->fetched->addSeconds(60));
    expect($screen->instance()->tracker()->isStale())->toBeTrue()
        ->and(freshnessNoticeOf($screen))->toBe(['neutral', 'Updated 1 minute ago', null]);

    CarbonImmutable::setTestNow($this->fetched->addMinutes(12)->addSeconds(40));
    expect(freshnessNoticeOf($screen))->toBe(['neutral', 'Updated 12 minutes ago', null]);

    CarbonImmutable::setTestNow($this->fetched->addSeconds(3599));
    expect(freshnessNoticeOf($screen))->toBe(['neutral', 'Updated 59 minutes ago', null]);

    // After an hour the clock time, which doesn't go out of date while the screen is open.
    CarbonImmutable::setTestNow($this->fetched->addHour());
    expect(freshnessNoticeOf($screen))->toBe(['neutral', 'Updated at 2:05 PM', null]);
});

it('treats nothing cached as stale but leaves the empty state to the screen while online', function () {
    $screen = freshnessScreen();
    $screen->instance()->tracker()->showing(null);

    expect($screen->instance()->tracker()->isStale())->toBeTrue()
        ->and(freshnessNoticeOf($screen))->toBeNull();
});

it('says what time the data is from while offline, ahead of age and failures', function () {
    $screen = freshnessScreen(['connected' => false, 'type' => 'unknown']);
    $tracker = $screen->instance()->tracker();

    $tracker->showing($this->fetched);
    expect(freshnessNoticeOf($screen))->toBe(['warning', 'Showing data from 2:05 PM', null]);

    $tracker->failed(retry: 'reload');
    expect(freshnessNoticeOf($screen))->toBe(['warning', 'Showing data from 2:05 PM', null]);

    $tracker->showing($this->fetched->subDays(10));
    expect(freshnessNoticeOf($screen))->toBe(['warning', 'Showing data from Nov 3, 2026 2:05 PM', null]);

    $tracker->showing(null);
    expect(freshnessNoticeOf($screen))->toBe(['neutral', "You're offline. This will load when you're back online.", null]);
});

it('counts the device as online unless the OS says it is offline', function (?array $status, bool $offline) {
    $screen = freshnessScreen($status);
    $screen->instance()->tracker()->showing($this->fetched);

    expect(freshnessNoticeOf($screen)[0] ?? null)->toBe($offline ? 'warning' : null);
})->with([
    'no Network plugin' => [null, false],
    'connected' => [['connected' => true, 'type' => 'wifi'], false],
    'disconnected' => [['connected' => false, 'type' => 'unknown'], true],
    'Wi-Fi without validated internet' => [['connected' => false, 'type' => 'wifi'], true],
    'native read failed' => [['connected' => false, 'type' => 'error', 'error' => 'No permission'], false],
    'no connected field' => [['type' => 'wifi'], false],
]);

it('keeps a failure up through the retry and clears it when the refresh lands', function () {
    $screen = freshnessScreen();
    $tracker = $screen->instance()->tracker();
    $tracker->showing($this->fetched)->failed(retry: 'reload');

    expect(freshnessNoticeOf($screen))->toBe(['warning', "Couldn't refresh. Showing data from 2:05 PM.", 'Try again']);

    $tracker->refreshing();
    expect($tracker->isRefreshing())->toBeTrue()
        ->and(freshnessNoticeOf($screen))->toBe(['warning', "Couldn't refresh. Showing data from 2:05 PM.", 'Try again']);

    $tracker->failed('Too many requests. Wait a minute.');
    expect(freshnessNoticeOf($screen))->toBe(['warning', 'Too many requests. Wait a minute. Showing data from 2:05 PM.', null]);

    CarbonImmutable::setTestNow($this->fetched->addMinutes(5));
    $tracker->refreshed();
    expect($tracker->hasFailed())->toBeFalse()
        ->and($tracker->fetchedAt()->equalTo($this->fetched->addMinutes(5)))->toBeTrue()
        ->and(freshnessNoticeOf($screen))->toBeNull();
});

it('shows a failed first load as danger, with the app reason when given', function () {
    $screen = freshnessScreen();
    $tracker = $screen->instance()->tracker();

    $tracker->showing(null)->failed(retry: 'reload');
    expect(freshnessNoticeOf($screen))->toBe(['danger', "Couldn't load this.", 'Try again']);

    $tracker->failed('The server is down for maintenance.');
    expect(freshnessNoticeOf($screen))->toBe(['danger', 'The server is down for maintenance.', null]);
});

it('renders the notice as a Callout whose Try again calls the retry method', function () {
    $screen = freshnessScreen()
        ->assertSee('Servers')
        ->assertMissingElement('firstlight.callout');

    $screen->instance()->tracker()->showing($this->fetched)->failed(retry: 'reload');

    $screen->call('rerender')
        ->assertElement('firstlight.callout', fn ($node) => $node['props']['tone'] === 'warning'
            && $node['props']['message'] === "Couldn't refresh. Showing data from 2:05 PM."
            && $node['props']['action_label'] === 'Try again'
            && is_int($node['on_press'] ?? $node['props']['on_press'] ?? null))
        ->press("retryFreshness('servers')")
        ->assertSet('reloads', 1)
        ->assertMissingElement('firstlight.callout');
});

it('renders a Callout without an action when there is nothing to retry', function () {
    $screen = freshnessScreen(['connected' => false, 'type' => 'unknown']);
    $screen->instance()->tracker()->showing($this->fetched);

    $screen->call('rerender')->assertElement('firstlight.callout', fn ($node) => $node['props']['tone'] === 'warning'
        && $node['props']['action_label'] === ''
        && ($node['on_press'] ?? $node['props']['on_press'] ?? null) === null);
});

it('uses the app locale and timezone', function () {
    $path = dirname(__DIR__, 2).'/lang';
    $translator = new Translator(new FileLoader(new Filesystem, $path), 'de');
    $translator->addNamespace('firstlight', $path);
    app()->instance('translator', $translator);
    app()->instance('config', new class
    {
        public function get(string $key, mixed $default = null): mixed
        {
            return $key === 'app.timezone' ? 'Australia/Sydney' : $default;
        }
    });

    // 14:05 in Sydney (AEDT, UTC+11).
    $fetched = CarbonImmutable::parse('2026-11-13 03:05:00', 'UTC');
    $screen = freshnessScreen();
    $screen->instance()->tracker()->showing($fetched);

    CarbonImmutable::setTestNow($fetched->addMinutes(12));
    expect(freshnessNoticeOf($screen))->toBe(['neutral', 'Aktualisiert vor 12 Minuten', null]);

    CarbonImmutable::setTestNow($fetched->addMinutes(75));
    expect(freshnessNoticeOf($screen))->toBe(['neutral', 'Aktualisiert: 14:05', null]);

});

it('shows clock times in the phone timezone, asking the phone once per screen', function (string $phoneZone, string $expected) {
    app()->instance('config', new class
    {
        public function get(string $key, mixed $default = null): mixed
        {
            return $key === 'app.timezone' ? 'Australia/Sydney' : $default;
        }
    });

    $screen = freshnessScreen();
    $screen->bridge()->respondTo('Device.GetInfo', ['info' => json_encode(['timezone' => $phoneZone])]);
    $screen->instance()->tracker()->showing($this->fetched);
    CarbonImmutable::setTestNow($this->fetched->addHours(2));

    expect(freshnessNoticeOf($screen))->toBe(['neutral', $expected, null])
        ->and(freshnessNoticeOf($screen))->toBe(['neutral', $expected, null])
        ->and($screen->bridge()->callsTo('Device.GetInfo'))->toHaveCount(1);
})->with([
    // 14:05 UTC is 2:05 PM in Lisbon in November and 1:05 AM in Sydney.
    'phone in Lisbon' => ['Europe/Lisbon', 'Updated at 2:05 PM'],
    'unknown zone falls back to app.timezone' => ['Mars/Olympus', 'Updated at 1:05 AM'],
]);

it('rejects mistakes when the screen sets up tracking', function (Closure $mistake, string $message) {
    expect(fn () => $mistake(freshnessScreen()->instance()))->toThrow(Exception::class, $message);
})->with([
    'first use without freshFor' => [fn ($screen) => $screen->tracker('posts'), 'Pass freshFor the first time you track [posts].'],
    'key the callback cannot carry' => [fn ($screen) => $screen->tracker("it's", 60), 'may use only letters'],
    'zero window' => [fn ($screen) => $screen->tracker('servers', 0), 'freshFor must be at least one second.'],
    'blank reason' => [fn ($screen) => $screen->tracker()->failed(' '), 'must not be blank'],
    'retry that is not a method name' => [fn ($screen) => $screen->tracker()->failed(retry: 'reload()'), 'must be a method name'],
    'retry method that does not exist' => [function ($screen) {
        $screen->tracker()->failed(retry: 'reloadServers');
        $screen->retryFreshness('servers');
    }, 'has no [reloadServers] method'],
]);
