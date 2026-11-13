<?php

use FirstlightUI\AppGuard\Events\Foregrounded;
use FirstlightUI\Concerns\TracksFreshness;
use FirstlightUI\Connectivity\ConnectivityManager;
use FirstlightUI\Connectivity\ConnectivityState;
use FirstlightUI\Connectivity\PendingChanges;
use FirstlightUI\Facades\Connectivity;
use FirstlightUI\Facades\Feedback;
use FirstlightUI\Feedback\FeedbackRecord;
use FirstlightUI\Feedback\FeedbackTone;
use FirstlightUI\NativeComponent;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Native\Mobile\Edge\ElementRegistry;
use Native\Mobile\Events\Screen\ScreenMounted;
use Native\Mobile\Events\Screen\ScreenResumed;
use Native\Mobile\Testing\Native;
use Native\Mobile\Testing\TestableComponent;
use NativePHP\Network\Events\NetworkStatusChanged;

require_once __DIR__.'/../Support/ScreenHarness.php';
require_once __DIR__.'/../fixtures/connectivity-events.php';

if (! function_exists('event')) {
    function event(object $event): mixed
    {
        return app('events')->dispatch($event);
    }
}

/** A server list whose revalidate() counts calls; the tests choose its freshness windows. */
class ConnectivityServersScreen extends NativeComponent
{
    use TracksFreshness;

    /** @var list<int> */
    public static array $windows = [60];

    public int $revalidations = 0;

    public function mount(): void
    {
        foreach (self::$windows as $index => $seconds) {
            $this->freshness("data{$index}", freshFor: $seconds)->refreshed();
        }
    }

    protected function revalidate(): void
    {
        $this->revalidations++;
    }

    public function render(): \Illuminate\View\View
    {
        return view('native.status');
    }
}

class ConnectivityTestQueue implements PendingChanges
{
    public int $syncs = 0;

    public function __construct(public int $waiting = 0, public int $failed = 0, public bool $syncing = false) {}

    public function waitingCount(): int
    {
        return $this->waiting;
    }

    public function failedCount(): int
    {
        return $this->failed;
    }

    public function isSyncing(): bool
    {
        return $this->syncing;
    }

    public function sync(): void
    {
        $this->syncs++;
    }
}

beforeEach(function () {
    $this->views = sys_get_temp_dir().'/firstlight-connectivity-'.bin2hex(random_bytes(8));
    $files = new Filesystem;
    $files->makeDirectory($this->views.'/native', 0755, true);
    $files->put($this->views.'/native/status.blade.php', <<<'BLADE'
        <native:column>
            <native:text :text="\FirstlightUI\Facades\Connectivity::statusLine() ?? 'No status'" />
        </native:column>
        BLADE);

    $container = new Container;
    $this->harness = bootFirstlightScreenHarness($container, $this->views);
    $container->instance('events', $events = new Dispatcher($container));
    $container->singleton(ConnectivityManager::class);
    $container->make(ConnectivityManager::class)->listen($events);
    ElementRegistry::register('firstlight.callout', FirstlightUI\Elements\Callout::class);

    ConnectivityServersScreen::$windows = [60];
});

afterEach(function () {
    tearDownFirstlightScreenHarness($this->harness);
    (new Filesystem)->deleteDirectory($this->views);
});

function connectivityScreen(?array $network = ['connected' => true, 'type' => 'wifi']): TestableComponent
{
    $screen = Native::test(ConnectivityServersScreen::class);

    if ($network !== null) {
        $screen->bridge()->respondTo('Network.Status', $network);
    }

    return $screen;
}

function networkEvent(bool $connected, string $type = 'wifi'): array
{
    return ['connected' => $connected, 'type' => $connected ? $type : 'unknown', 'isExpensive' => false, 'isConstrained' => false];
}

it('reads the OS, and counts no plugin or a failed read as unknown and online', function (?array $status, ConnectivityState $state) {
    connectivityScreen($status);

    expect(Connectivity::state())->toBe($state)
        ->and(Connectivity::isOffline())->toBe($state === ConnectivityState::Offline);
})->with([
    'online' => [['connected' => true, 'type' => 'cellular'], ConnectivityState::Online],
    'offline' => [['connected' => false, 'type' => 'unknown'], ConnectivityState::Offline],
    'Wi-Fi without internet' => [['connected' => false, 'type' => 'wifi'], ConnectivityState::Offline],
    'no Network plugin' => [null, ConnectivityState::Unknown],
    'read failed' => [['connected' => false, 'type' => 'error', 'error' => 'denied'], ConnectivityState::Unknown],
]);

it('warns once in debug builds when it cannot tell, naming the missing plugin', function (bool $debug, int $warnings) {
    $logged = new ArrayObject;
    Container::getInstance()->instance('log', new class($logged)
    {
        public function __construct(private ArrayObject $logged) {}

        public function warning(string $message): void
        {
            $this->logged[] = $message;
        }
    });
    Container::getInstance()->instance('config', new class($debug)
    {
        public function __construct(private bool $debug) {}

        public function get(string $key): mixed
        {
            return $key === 'app.debug' ? $this->debug : null;
        }
    });
    connectivityScreen(null);

    Connectivity::state();
    Connectivity::isOffline();

    expect($logged->getArrayCopy())->toHaveCount($warnings);
    if ($warnings > 0) {
        expect($logged[0])->toContain('nativephp/mobile-network');
    }
})->with(['debug' => [true, 1], 'production' => [false, 0]]);

it('writes the status line from connectivity and the app queue', function (bool $offline, ?ConnectivityTestQueue $queue, ?string $line) {
    connectivityScreen(['connected' => ! $offline, 'type' => $offline ? 'unknown' : 'wifi']);
    Connectivity::pendingUsing($queue);

    expect(Connectivity::statusLine())->toBe($line);
})->with([
    'online, no queue' => [false, null, null],
    'online, queue empty' => [false, new ConnectivityTestQueue, null],
    'offline' => [true, null, 'Offline'],
    'offline, queue empty' => [true, new ConnectivityTestQueue, 'Offline'],
    'offline with writes waiting' => [true, new ConnectivityTestQueue(waiting: 3, failed: 1), 'Offline · 3 waiting to sync'],
    'draining one' => [false, new ConnectivityTestQueue(waiting: 1, syncing: true), 'Syncing 1 change…'],
    'draining three' => [false, new ConnectivityTestQueue(waiting: 3, failed: 2, syncing: true), 'Syncing 3 changes…'],
    'waiting but not draining' => [false, new ConnectivityTestQueue(waiting: 2), null],
    'failed writes' => [false, new ConnectivityTestQueue(failed: 1), '1 failed to sync'],
]);

it('drains and revalidates once when NetworkStatusChanged brings the device back online', function () {
    $queue = new ConnectivityTestQueue(waiting: 2);
    Connectivity::pendingUsing($queue);
    $screen = connectivityScreen()->assertSee('No status');
    $bridgeReads = count($screen->bridge()->callsTo('Network.Status'));

    // The first state heard is a baseline; a change between networks is no reconnect.
    $screen->emitNative(NetworkStatusChanged::class, networkEvent(true, 'cellular'))
        ->assertRerendered();
    expect($screen->get('revalidations'))->toBe(0);

    $screen->emitNative(NetworkStatusChanged::class, networkEvent(false))
        ->assertRerendered()
        ->assertSee('Offline · 2 waiting to sync');
    expect([$screen->get('revalidations'), $queue->syncs])->toBe([0, 0]);

    $screen->emitNative(NetworkStatusChanged::class, networkEvent(true))
        ->assertDontSee('Offline · 2 waiting to sync');
    expect([$screen->get('revalidations'), $queue->syncs])->toBe([1, 1]);

    $screen->emitNative(NetworkStatusChanged::class, networkEvent(true, 'cellular'));
    expect([$screen->get('revalidations'), $queue->syncs])->toBe([1, 1])
        // Once events report the state, renders read it without a bridge call.
        ->and($screen->bridge()->callsTo('Network.Status'))->toHaveCount($bridgeReads);
});

it('reads the OS again on foreground, because changes in the background are not replayed', function () {
    $screen = connectivityScreen();
    $screen->emitNative(NetworkStatusChanged::class, networkEvent(false))->assertSee('Offline');

    // Online again while the app was away; the event for it never arrived.
    $screen->emitNative(Foregrounded::class, ['secondsAway' => 5, 'shielded' => false])
        ->assertSee('No status');

    expect($screen->get('revalidations'))->toBe(1);
});

it('revalidates on foreground only after longer than the shortest freshness window', function (array $windows, int $secondsAway, int $revalidations) {
    ConnectivityServersScreen::$windows = $windows;
    $screen = connectivityScreen();

    $screen->emitNative(Foregrounded::class, ['secondsAway' => $secondsAway, 'shielded' => false])
        ->assertRerendered();

    expect($screen->get('revalidations'))->toBe($revalidations);
})->with([
    'within the window' => [[60], 60, 0],
    'past the window' => [[60], 61, 1],
    'past the shorter of two' => [[300, 60], 120, 1],
    'inside both' => [[300, 120], 90, 0],
    'nothing tracked' => [[], 86_400, 0],
]);

it('catches a reconnect that no event reported when the app returns or a screen shows', function () {
    $queue = new ConnectivityTestQueue;
    Connectivity::pendingUsing($queue);
    $screen = connectivityScreen(['connected' => false, 'type' => 'unknown']);

    // Back in the foreground, still offline, then online by the next resume.
    // (Illuminate Foundation is not installed here, so the router's lifecycle
    // events are dispatched by name; the listeners ignore their payload.)
    $screen->emitNative(Foregrounded::class, ['secondsAway' => 5, 'shielded' => false]);
    $screen->bridge()->respondTo('Network.Status', ['connected' => true, 'type' => 'wifi']);
    app('events')->dispatch(ScreenResumed::class);
    expect([$screen->get('revalidations'), $queue->syncs])->toBe([1, 1]);

    // Offline again, and online by the time a new screen mounts: that screen
    // loads anyway, so the queue drains and nothing revalidates.
    $screen->bridge()->respondTo('Network.Status', ['connected' => false, 'type' => 'unknown']);
    app('events')->dispatch(ScreenMounted::class);
    $screen->bridge()->respondTo('Network.Status', ['connected' => true, 'type' => 'wifi']);
    app('events')->dispatch(ScreenMounted::class);
    expect([$screen->get('revalidations'), $queue->syncs])->toBe([1, 2]);

    // Coming back online on foreground revalidates once, not again for the time away.
    $screen->bridge()->respondTo('Network.Status', ['connected' => false, 'type' => 'unknown']);
    app('events')->dispatch(ScreenResumed::class);
    $screen->bridge()->respondTo('Network.Status', ['connected' => true, 'type' => 'wifi']);
    $screen->emitNative(Foregrounded::class, ['secondsAway' => 600, 'shielded' => false]);
    expect([$screen->get('revalidations'), $queue->syncs])->toBe([2, 3]);
});

it('counts an offline render as seen, so the next screen that reads online drains the queue', function () {
    $queue = new ConnectivityTestQueue(waiting: 1);
    Connectivity::pendingUsing($queue);

    // Offline for the whole visit to one screen; no lifecycle event fires.
    Native::fakeBridge()->respondTo('Network.Status', ['connected' => false, 'type' => 'unknown']);
    $screen = Native::test(ConnectivityServersScreen::class)->assertSee('Offline · 1 waiting to sync');

    $screen->bridge()->respondTo('Network.Status', ['connected' => true, 'type' => 'wifi']);
    app('events')->dispatch(ScreenMounted::class);

    expect($queue->syncs)->toBe(1)
        ->and($screen->get('revalidations'))->toBe(0);
});

it('drives transitions in tests with Connectivity::fake(), keeping the app queue', function () {
    $queue = new ConnectivityTestQueue(waiting: 1);
    Connectivity::pendingUsing($queue);
    $fake = Connectivity::fake();
    $screen = connectivityScreen(null);

    $fake->goOffline();
    expect(Connectivity::statusLine())->toBe('Offline · 1 waiting to sync')
        ->and([$screen->get('revalidations'), $queue->syncs])->toBe([0, 0]);

    $fake->goOnline();
    expect(Connectivity::isOffline())->toBeFalse()
        ->and([$screen->get('revalidations'), $queue->syncs])->toBe([1, 1]);

    $fake->foreground(30)->foreground(61);
    expect($screen->get('revalidations'))->toBe(2);

    // A failed read in between is no observation, so this is still a reconnect.
    $fake->goOffline()->goUnknown()->goOnline();
    expect([$screen->get('revalidations'), $queue->syncs])->toBe([3, 2])
        ->and($screen->bridge()->callsTo('Network.Status'))->toBe([]);
});

it('reports a finished drain as Feedback, with Review on failures', function () {
    $feedback = Feedback::fake();

    Connectivity::syncFinished(3, failed: 1);
    Connectivity::syncFinished(0);

    expect($feedback->sent()->map(fn (FeedbackRecord $record) => [
        $record->tone, $record->message, $record->actionLabel, $record->actionKey,
    ])->all())->toBe([
        [FeedbackTone::Success, 'Synced 3 changes.', null, null],
        [FeedbackTone::Warning, '1 change failed to sync.', 'Review', ConnectivityManager::REVIEW_ACTION],
    ]);
});

it('resolves the app queue from the container and rejects anything else', function () {
    Container::getInstance()->instance(ConnectivityTestQueue::class, $queue = new ConnectivityTestQueue);

    Connectivity::pendingUsing(ConnectivityTestQueue::class);
    expect(Connectivity::pending())->toBe($queue);

    Connectivity::pendingUsing(fn () => new stdClass);
    Connectivity::pending();
})->throws(InvalidArgumentException::class, 'must resolve to');
