<?php

// The events Connectivity listens for, from packages Firstlight does not
// require: nativephp/mobile-network#4 and firstlightui/app-guard. Core's
// AppForegrounded (mobile-air#672) is left undefined, as on mobile 4.7.0.

namespace NativePHP\Network\Events {
    use Native\Mobile\Events\Concerns\BroadcastsGlobally;

    if (! class_exists(NetworkStatusChanged::class)) {
        class NetworkStatusChanged implements BroadcastsGlobally
        {
            public function __construct(
                public bool $connected,
                public string $type,
                public bool $isExpensive = false,
                public bool $isConstrained = false,
            ) {}
        }
    }
}

namespace FirstlightUI\AppGuard\Events {
    use Native\Mobile\Events\Concerns\BroadcastsGlobally;

    if (! class_exists(Foregrounded::class)) {
        final class Foregrounded implements BroadcastsGlobally
        {
            public function __construct(public int $secondsAway, public bool $shielded) {}
        }
    }
}
