<?php

namespace FirstlightUI\Haptics;

use PHPUnit\Framework\Assert;

/**
 * Records what would have played, after the same interaction rules as the
 * real manager: outcomes inside an interaction collapse to the strongest.
 */
final class HapticsFake extends HapticsManager
{
    /** @var list<array<string, mixed>> */
    private array $played = [];

    private ?bool $enabled = null;

    /**
     * Each played haptic in order: `['haptic' => 'success']` or
     * `['impact' => 'medium', 'intensity' => 1.0]`.
     *
     * @return list<array<string, mixed>>
     */
    public function played(): array
    {
        return $this->played;
    }

    /** The last setEnabled() value, or null when it was never called. */
    public function enabledSetting(): ?bool
    {
        return $this->enabled;
    }

    public function assertPlayed(Haptic|string $haptic, ?int $times = null): void
    {
        $count = $this->count(Haptic::parse($haptic));

        if ($times === null) {
            Assert::assertGreaterThan(0, $count, "The [{$this->name($haptic)}] haptic was not played.");

            return;
        }

        Assert::assertSame($times, $count, "The [{$this->name($haptic)}] haptic played {$count} times, not {$times}.");
    }

    public function assertNotPlayed(Haptic|string $haptic): void
    {
        Assert::assertSame(0, $this->count(Haptic::parse($haptic)), "The [{$this->name($haptic)}] haptic was played unexpectedly.");
    }

    public function assertNothingPlayed(): void
    {
        Assert::assertSame([], $this->played, 'Haptics were played unexpectedly.');
    }

    protected function call(string $function, array $payload): void
    {
        if ($function === self::SET_ENABLED) {
            $this->enabled = (bool) $payload['enabled'];

            return;
        }

        $this->played[] = $payload;
    }

    private function count(Haptic $haptic): int
    {
        return count(array_filter($this->played, fn (array $payload): bool => ($payload['haptic'] ?? null) === $haptic->value));
    }

    private function name(Haptic|string $haptic): string
    {
        return $haptic instanceof Haptic ? $haptic->value : $haptic;
    }
}
