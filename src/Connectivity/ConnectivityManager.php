<?php

namespace FirstlightUI\Connectivity;

use Closure;
use FirstlightUI\Facades\Feedback;
use FirstlightUI\Support\Chrome;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Events\Screen\ScreenMounted;
use Native\Mobile\Events\Screen\ScreenResumed;
use Native\Mobile\Network;
use Throwable;

/**
 * Whether the device is online, the app-wide line for the nav bar subtitle,
 * and when screens revalidate. Firstlight never fetches and owns no write
 * queue: it reads the OS, shows the app's PendingChanges, and calls the
 * revalidate() hook of a screen that uses TracksFreshness.
 */
class ConnectivityManager
{
    /** nativephp/mobile-network's change event (mobile-network#4). */
    public const NETWORK_CHANGED = 'NativePHP\\Network\\Events\\NetworkStatusChanged';

    /** Core's foreground event (mobile-air#672). */
    public const APP_FOREGROUNDED = 'Native\\Mobile\\Events\\App\\AppForegrounded';

    /** App Guard's foreground event, used while core has none. */
    public const APP_GUARD_FOREGROUNDED = 'FirstlightUI\\AppGuard\\Events\\Foregrounded';

    /** The action key of the "Review" button on the failed-sync Feedback. */
    public const REVIEW_ACTION = 'firstlight-sync-review';

    /** What the last NetworkStatusChanged said. Once one has arrived, reads need no bridge call. */
    private ?ConnectivityState $reported = null;

    /** The known state when Firstlight last checked for a reconnect. */
    private ?ConnectivityState $observed = null;

    private bool $warnedUnknown = false;

    protected PendingChanges|Closure|string|null $pending = null;

    public function state(): ConnectivityState
    {
        $state = $this->reported ?? $this->read();

        // Any read that finds the device offline counts, such as a render on a
        // screen the user never leaves, so the next check that reads online
        // sees the reconnect.
        if ($state === ConnectivityState::Offline) {
            $this->observed = $state;
        }

        return $state;
    }

    /** True only when the OS says the device is offline; unknown counts as online. */
    public function isOffline(): bool
    {
        return $this->state() === ConnectivityState::Offline;
    }

    /**
     * The nav bar subtitle, such as "Offline · 3 waiting to sync", or null
     * when there is nothing to say.
     */
    public function statusLine(): ?string
    {
        $pending = $this->pending();
        $waiting = $pending?->waitingCount() ?? 0;

        if ($this->isOffline()) {
            return $waiting > 0
                ? Chrome::choice('connectivity_offline_waiting', $waiting)
                : Chrome::string('connectivity_offline');
        }

        if ($waiting > 0 && $pending->isSyncing()) {
            return Chrome::choice('connectivity_syncing', $waiting);
        }

        $failed = $pending?->failedCount() ?? 0;

        return $failed > 0 ? Chrome::choice('connectivity_failed', $failed) : null;
    }

    /**
     * Show the app's write queue: an instance, a container class name, or a
     * closure that returns one, resolved each time it is read.
     */
    public function pendingUsing(PendingChanges|Closure|string|null $pending): void
    {
        $this->pending = $pending;
    }

    public function pending(): ?PendingChanges
    {
        $pending = match (true) {
            $this->pending instanceof Closure => ($this->pending)(),
            is_string($this->pending) => Container::getInstance()->make($this->pending),
            default => $this->pending,
        };

        if ($pending !== null && ! $pending instanceof PendingChanges) {
            throw new InvalidArgumentException('Connectivity::pendingUsing() must resolve to '.PendingChanges::class.'.');
        }

        return $pending;
    }

    /**
     * Report a finished drain: success Feedback "Synced 3 changes." when
     * anything synced, and warning Feedback "1 change failed to sync." with
     * a "Review" action (REVIEW_ACTION) when anything failed.
     */
    public function syncFinished(int $synced, int $failed = 0): void
    {
        if ($synced < 0 || $failed < 0) {
            throw new InvalidArgumentException('Sync counts must not be negative.');
        }

        if ($synced > 0) {
            Feedback::success(Chrome::choice('sync_succeeded', $synced))->send();
        }

        if ($failed > 0) {
            Feedback::warning(Chrome::choice('sync_failed', $failed))
                ->action(Chrome::string('sync_review'), self::REVIEW_ACTION)
                ->send();
        }
    }

    /**
     * Listen app-wide. A Laravel listener for a native event also makes the
     * live screen republish, so subtitles and ages update. Events from
     * plugins that are not installed never fire.
     */
    public function listen(Dispatcher $events): void
    {
        $self = static fn (): self => Container::getInstance()->make(self::class);

        $events->listen(self::NETWORK_CHANGED, static fn (object $event) => $self()->networkChanged($event));
        $events->listen(self::APP_FOREGROUNDED, static fn (object $event) => $self()->foregrounded($event));
        $events->listen(self::APP_GUARD_FOREGROUNDED, static function (object $event) use ($self): void {
            // Core sends its own event as well once it has one; react once.
            if (! class_exists(self::APP_FOREGROUNDED)) {
                $self()->foregrounded($event);
            }
        });
        $events->listen(ScreenMounted::class, static fn () => $self()->screenShown(resumed: false));
        $events->listen(ScreenResumed::class, static fn () => $self()->screenShown(resumed: true));
    }

    /** @internal Listener for NetworkStatusChanged. */
    public function networkChanged(object $event): void
    {
        $this->reported = ($event->connected ?? null) === false ? ConnectivityState::Offline : ConnectivityState::Online;
        $this->checkReconnect(revalidate: true);
    }

    /**
     * @internal Listener for the foreground events. Changes while the app was
     * away are not replayed, so read the OS again. A screen whose shortest
     * freshness window is shorter than the time away revalidates.
     */
    public function foregrounded(object $event): void
    {
        $this->reread();

        if (! $this->checkReconnect(revalidate: true)) {
            $this->revalidateActiveScreen(max(0, (int) ($event->secondsAway ?? 0)));
        }
    }

    /**
     * @internal Listener for ScreenMounted and ScreenResumed: catches a
     * reconnect that no event reported. A newly mounted screen loads anyway,
     * so only a resumed one revalidates.
     */
    public function screenShown(bool $resumed): void
    {
        $this->reread();
        $this->checkReconnect(revalidate: $resumed);
    }

    /** Read the OS. Tests replace this through Connectivity::fake(). */
    protected function read(): ConnectivityState
    {
        try {
            $status = Container::getInstance()->make(Network::class)->status();
        } catch (Throwable $exception) {
            return $this->unknown($exception->getMessage());
        }

        if ($status === null) {
            return $this->unknown('Network::status() returned nothing. Install nativephp/mobile-network.');
        }

        if (isset($status->error)) {
            return $this->unknown('Network::status() reported: '.(is_string($status->error) ? $status->error : 'an error'));
        }

        return ($status->connected ?? null) === false ? ConnectivityState::Offline : ConnectivityState::Online;
    }

    /**
     * After a reconnect, ask the app's queue for one drain and revalidate the
     * screen on display. Answers whether the device just came back online.
     */
    protected function checkReconnect(bool $revalidate): bool
    {
        $state = $this->state();

        if ($state === ConnectivityState::Unknown) {
            return false;
        }

        $previous = $this->observed;
        $this->observed = $state;

        if ($previous !== ConnectivityState::Offline || $state !== ConnectivityState::Online) {
            return false;
        }

        $this->pending()?->sync();

        if ($revalidate) {
            $this->revalidateActiveScreen(null);
        }

        return true;
    }

    /** Null on a reconnect, which always revalidates; otherwise the seconds the app was away. */
    protected function revalidateActiveScreen(?int $secondsAway): void
    {
        $screen = NativeComponent::active();

        if ($screen !== null && method_exists($screen, 'revalidateFreshness')) {
            (fn () => $this->revalidateFreshness($secondsAway))->call($screen);
        }
    }

    /** Once events report the state, catch up on what changed while none could arrive. */
    private function reread(): void
    {
        if ($this->reported !== null && ($state = $this->read()) !== ConnectivityState::Unknown) {
            $this->reported = $state;
        }
    }

    private function unknown(string $reason): ConnectivityState
    {
        $container = Container::getInstance();

        // App tests run without the Network plugin on purpose; don't fill their log.
        if ($this->warnedUnknown || ! $container->bound('log') || ! $container->bound('config')
            || (method_exists($container, 'runningUnitTests') && $container->runningUnitTests())) {
            return ConnectivityState::Unknown;
        }

        $this->warnedUnknown = true;

        try {
            if ($container->make('config')->get('app.debug') === true) {
                $container->make('log')->warning(
                    "Firstlight can't tell whether the device is offline, so it counts it as online. {$reason}"
                );
            }
        } catch (Throwable) {
        }

        return ConnectivityState::Unknown;
    }
}
