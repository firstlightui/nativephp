<?php

namespace FirstlightUI\Components;

use Native\Mobile\Edge\Components\Native\NativeBladeComponent;

final class Badge extends NativeBladeComponent
{
    protected bool $isSelfClosing = true;

    protected function elementType(): string
    {
        return 'firstlight.badge';
    }
}
