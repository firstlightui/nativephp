<?php

namespace FirstlightUI\Facades;

use FirstlightUI\Haptics\Haptic;
use FirstlightUI\Haptics\HapticsFake;
use FirstlightUI\Haptics\HapticsManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void play(Haptic|string $haptic)
 * @method static void success()
 * @method static void warning()
 * @method static void error()
 * @method static void selection()
 * @method static void impact(string $weight = 'medium', float $intensity = 1.0)
 * @method static void setEnabled(bool $enabled)
 * @method static list<array<string, mixed>> played()
 * @method static void assertPlayed(Haptic|string $haptic, ?int $times = null)
 * @method static void assertNotPlayed(Haptic|string $haptic)
 * @method static void assertNothingPlayed()
 */
final class Haptics extends Facade
{
    /** Records haptics instead of playing them; Feedback and validation haptics are recorded too. */
    public static function fake(): HapticsFake
    {
        self::swap($fake = new HapticsFake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return HapticsManager::class;
    }
}
