<?php

namespace FirstlightUI\Components;

use Native\Mobile\Edge\Components\Native\NativeBladeComponent;

class StatusLabel extends NativeBladeComponent
{
    protected bool $isSelfClosing = true;

    protected function elementType(): string
    {
        return 'firstlight.status-label';
    }
}
