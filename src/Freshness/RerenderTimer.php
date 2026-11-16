<?php

namespace FirstlightUI\Freshness;

use Native\Mobile\Edge\NativeElementCollector;

/**
 * Schedules a re-render of the screen being rendered, the same timer Blade's `native:poll`
 * sets. The freshness notice needs one even while it renders no element to put `native:poll` on.
 *
 * @internal
 */
final class RerenderTimer extends NativeElementCollector
{
    public static function after(int $milliseconds): void
    {
        static::$pollIntervals[] = $milliseconds;
    }
}
