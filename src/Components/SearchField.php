<?php

namespace FirstlightUI\Components;

use FirstlightUI\Components\Concerns\DecodesBladeAttributes;
use Native\Mobile\Edge\Components\Native\NativeBladeComponent;

class SearchField extends NativeBladeComponent
{
    use DecodesBladeAttributes;

    protected bool $isSelfClosing = true;

    protected function elementType(): string
    {
        return 'firstlight.search-field';
    }
}
