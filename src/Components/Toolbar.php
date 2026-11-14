<?php

namespace FirstlightUI\Components;

use FirstlightUI\Components\Concerns\DecodesBladeAttributes;
use Native\Mobile\Edge\Components\Native\NativeBladeComponent;

final class Toolbar extends NativeBladeComponent
{
    use DecodesBladeAttributes;

    protected function elementType(): string
    {
        return 'firstlight.toolbar';
    }
}
