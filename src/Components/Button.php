<?php

namespace FirstlightUI\Components;

use FirstlightUI\Components\Concerns\DecodesBladeAttributes;

class Button extends \Native\Mobile\UI\Components\Button
{
    use DecodesBladeAttributes;

    protected function elementType(): string
    {
        return 'firstlight.button';
    }
}
