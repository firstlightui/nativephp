<?php

namespace FirstlightUI\Components;

use FirstlightUI\Components\Concerns\DecodesBladeAttributes;
use Native\Mobile\Edge\Components\Native\NativeBladeComponent;

final class TimePicker extends NativeBladeComponent
{
    use DecodesBladeAttributes;

    protected bool $isSelfClosing = true;

    protected function elementType(): string
    {
        return 'firstlight.time-picker';
    }
}
