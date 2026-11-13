<?php

namespace FirstlightUI\Freshness;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use FirstlightUI\Support\Chrome;
use InvalidArgumentException;

/**
 * How old one dataset on a screen is, and whether its last refresh failed.
 * Firstlight never fetches: the screen reports what it shows and how its
 * refreshes went, and reads back a notice and isStale().
 */
final class Freshness
{
    private ?CarbonImmutable $fetchedAt = null;

    private bool $refreshing = false;

    private bool $failed = false;

    private ?string $reason = null;

    private ?string $retry = null;

    public function __construct(public readonly string $key, private int $freshFor)
    {
        self::ensureFreshFor($freshFor);
    }

    public function freshFor(int $seconds): self
    {
        self::ensureFreshFor($seconds);
        $this->freshFor = $seconds;

        return $this;
    }

    /** The screen now shows data fetched at $fetchedAt, or nothing cached when null. Starts a new load. */
    public function showing(?DateTimeInterface $fetchedAt): self
    {
        $this->fetchedAt = $fetchedAt === null ? null : CarbonImmutable::instance($fetchedAt);
        $this->refreshing = false;
        $this->failed = false;
        $this->reason = null;
        $this->retry = null;

        return $this;
    }

    /** A refresh started. A failure notice stays up until the refresh lands. */
    public function refreshing(): self
    {
        $this->refreshing = true;

        return $this;
    }

    /** A refresh landed with data fetched at $fetchedAt, or now. */
    public function refreshed(?DateTimeInterface $fetchedAt = null): self
    {
        return $this->showing($fetchedAt ?? CarbonImmutable::now());
    }

    /**
     * The refresh or load failed. $reason replaces the default text, such as
     * a rate-limit message; $retry names a public screen method that the
     * notice's "Try again" action calls.
     */
    public function failed(?string $reason = null, ?string $retry = null): self
    {
        if ($reason !== null && trim($reason) === '') {
            throw new InvalidArgumentException('A freshness failure reason must not be blank.');
        }

        if ($retry !== null && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $retry) !== 1) {
            throw new InvalidArgumentException("Freshness retry [{$retry}] must be a method name.");
        }

        $this->refreshing = false;
        $this->failed = true;
        $this->reason = $reason;
        $this->retry = $retry;

        return $this;
    }

    public function fetchedAt(): ?CarbonImmutable
    {
        return $this->fetchedAt;
    }

    public function isRefreshing(): bool
    {
        return $this->refreshing;
    }

    public function hasFailed(): bool
    {
        return $this->failed;
    }

    public function retryMethod(): ?string
    {
        return $this->failed ? $this->retry : null;
    }

    /** True when nothing is cached or the data is older than its freshness window. */
    public function isStale(): bool
    {
        return $this->fetchedAt === null
            || $this->fetchedAt->addSeconds($this->freshFor)->lessThanOrEqualTo(CarbonImmutable::now());
    }

    /**
     * The Callout to show above the data, or null when nothing needs saying.
     * Clock times use $timezone, such as the phone's, else the app's.
     */
    public function notice(bool $offline, ?string $timezone = null): ?FreshnessNotice
    {
        $cached = $this->fetchedAt !== null;
        $timezone ??= Chrome::applicationTimezone() ?? date_default_timezone_get();

        if ($offline) {
            return $cached
                ? $this->make(Chrome::string('freshness_showing', ['time' => $this->clockTime($timezone)]), 'warning')
                : $this->make(Chrome::string('freshness_offline_empty'), 'neutral');
        }

        if ($this->failed) {
            $tryAgain = $this->retry === null ? null : Chrome::string('try_again');

            return $cached
                ? $this->make(Chrome::string('freshness_failed_showing', [
                    'reason' => $this->reason ?? Chrome::string('freshness_refresh_failed'),
                    'time' => $this->clockTime($timezone),
                ]), 'warning', $tryAgain)
                : $this->make($this->reason ?? Chrome::string('freshness_load_failed'), 'danger', $tryAgain);
        }

        if (! $cached || ! $this->isStale()) {
            return null;
        }

        // "12 minutes ago" within the hour; the clock time after that, so it doesn't go out of date.
        $now = CarbonImmutable::now();

        if ($this->fetchedAt->diffInSeconds($now) >= 3600) {
            return $this->make(Chrome::string('freshness_updated_at', ['time' => $this->clockTime($timezone)]), 'neutral');
        }

        return $this->make(Chrome::string('freshness_updated', [
            'when' => $this->local($this->fetchedAt, $timezone)->diffForHumans($this->local($now, $timezone), [
                'syntax' => CarbonInterface::DIFF_RELATIVE_TO_NOW,
                'minimumUnit' => 'minute',
                'options' => CarbonInterface::JUST_NOW,
            ]),
        ]), 'neutral');
    }

    private function make(string $message, string $tone, ?string $actionLabel = null): FreshnessNotice
    {
        return new FreshnessNotice($this->key, $message, $tone, $actionLabel);
    }

    /** "2:05 PM" today, "Nov 3, 2026 2:05 PM" on an earlier day, in the app's locale. */
    private function clockTime(string $timezone): string
    {
        $time = $this->local($this->fetchedAt, $timezone);

        return $time->isSameDay($this->local(CarbonImmutable::now(), $timezone))
            ? $time->isoFormat('LT')
            : $time->isoFormat('lll');
    }

    private function local(CarbonImmutable $time, string $timezone): CarbonImmutable
    {
        $time = $time->setTimezone($timezone);
        $locale = Chrome::applicationLocale();

        return $locale === null ? $time : $time->locale(str_replace('-', '_', $locale));
    }

    private static function ensureFreshFor(int $seconds): void
    {
        if ($seconds < 1) {
            throw new InvalidArgumentException('freshFor must be at least one second.');
        }
    }
}
