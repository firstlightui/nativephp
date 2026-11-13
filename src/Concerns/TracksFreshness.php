<?php

namespace FirstlightUI\Concerns;

use DateTimeZone;
use FirstlightUI\Freshness\Freshness;
use FirstlightUI\Freshness\FreshnessNotice;
use InvalidArgumentException;
use LogicException;
use Native\Mobile\Device;
use Native\Mobile\Network;
use Throwable;

/**
 * Tracks how old each dataset on a screen is, and shows it in a Callout:
 * "Updated 12 minutes ago", "Showing data from 2:05 PM" while offline, or
 * "Couldn't refresh." with Try again. The screen still does all fetching.
 */
trait TracksFreshness
{
    /** @var array<string, Freshness> */
    private array $freshnessTrackers = [];

    /** The phone's timezone, read once per screen; false until read. */
    private string|false|null $freshnessTimezone = false;

    /**
     * The tracker for one dataset. Pass $freshFor, in seconds, the first time;
     * passing it again changes the window.
     */
    protected function freshness(string $key, ?int $freshFor = null): Freshness
    {
        if (preg_match('/^[A-Za-z0-9_.:-]+$/', $key) !== 1) {
            throw new InvalidArgumentException("Freshness key [{$key}] may use only letters, digits, and _ . : -.");
        }

        if (! isset($this->freshnessTrackers[$key])) {
            if ($freshFor === null) {
                throw new InvalidArgumentException("Pass freshFor the first time you track [{$key}].");
            }

            return $this->freshnessTrackers[$key] = new Freshness($key, $freshFor);
        }

        return $freshFor === null
            ? $this->freshnessTrackers[$key]
            : $this->freshnessTrackers[$key]->freshFor($freshFor);
    }

    /** The notice for the firstlight::native.freshness partial, or null to show nothing. */
    protected function freshnessNotice(string $key): ?FreshnessNotice
    {
        $tracker = $this->freshnessTrackers[$key] ?? null;

        if ($tracker === null) {
            return null;
        }

        // Only a notice with a clock time needs the phone's timezone.
        return $tracker->notice(
            $this->deviceIsOffline(),
            $tracker->fetchedAt() === null ? null : $this->deviceTimezone(),
        );
    }

    /** The notice's "Try again" action: calls the retry method the screen passed to failed(). */
    public function retryFreshness(string $key): void
    {
        $method = ($this->freshnessTrackers[$key] ?? null)?->retryMethod();

        if ($method === null) {
            return;
        }

        if (! method_exists($this, $method)) {
            throw new LogicException(static::class." has no [{$method}] method for the [{$key}] Try again action.");
        }

        $this->{$method}();
    }

    /**
     * The phone's timezone, which Laravel's app.timezone is not; null off the
     * device. Read once per screen, because each read is a Device.GetInfo call.
     */
    private function deviceTimezone(): ?string
    {
        if ($this->freshnessTimezone !== false) {
            return $this->freshnessTimezone;
        }

        try {
            $timezone = app(Device::class)->getTimezone();
        } catch (Throwable) {
            $timezone = null;
        }

        return $this->freshnessTimezone = $timezone !== null
            && in_array($timezone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)
            ? $timezone
            : null;
    }

    /**
     * Offline only when the OS says so. Without the Network plugin, or when
     * the read fails, the state is unknown and counts as online.
     */
    private function deviceIsOffline(): bool
    {
        try {
            $status = app(Network::class)->status();
        } catch (Throwable) {
            return false;
        }

        return $status !== null && ! isset($status->error) && ($status->connected ?? null) === false;
    }
}
