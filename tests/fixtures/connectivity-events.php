<?php

// The events Connectivity listens for, from packages Firstlight does not
// require: nativephp/mobile-network#4. Core's
// AppForegrounded (mobile 4.8) uses Laravel's Dispatchable trait, which only
// laravel/framework provides, so a stand-in trait is declared here.

namespace Illuminate\Foundation\Events {
    if (! trait_exists(Dispatchable::class)) {
        trait Dispatchable {}
    }
}

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
