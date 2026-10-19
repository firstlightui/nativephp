<?php

namespace FirstlightUI\Components;

use FirstlightUI\Components\Concerns\DecodesBladeAttributes;
use Native\Mobile\Edge\Components\Native\NativeBladeComponent;

final class IconButton extends NativeBladeComponent
{
    use DecodesBladeAttributes;

    protected bool $isSelfClosing = true;

    protected function elementType(): string
    {
        return 'firstlight.icon-button';
    }
}
