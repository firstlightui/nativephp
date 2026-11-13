<?php

namespace FirstlightUI\Connectivity;

enum ConnectivityState: string
{
    case Online = 'online';

    case Offline = 'offline';

    /** No Network plugin, or the read failed. Counts as online. */
    case Unknown = 'unknown';
}
