<?php

namespace FirstlightUI\Facades;

use FirstlightUI\Connectivity\ConnectivityFake;
use FirstlightUI\Connectivity\ConnectivityManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \FirstlightUI\Connectivity\ConnectivityState state()
 * @method static bool isOffline()
 * @method static ?string statusLine()
 * @method static void pendingUsing(\FirstlightUI\Connectivity\PendingChanges|\Closure|string|null $pending)
 * @method static ?\FirstlightUI\Connectivity\PendingChanges pending()
 * @method static void syncFinished(int $synced, int $failed = 0)
 */
final class Connectivity extends Facade
{
    /** Replace connectivity with a fake that starts online and keeps the app's pendingUsing(). */
    public static function fake(): ConnectivityFake
    {
        $fake = new ConnectivityFake;
        $current = static::getFacadeRoot();

        if ($current instanceof ConnectivityManager) {
            $fake->pendingUsing((fn () => $this->pending)->call($current));
        }

        static::swap($fake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return ConnectivityManager::class;
    }
}
