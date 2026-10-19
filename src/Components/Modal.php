<?php

namespace FirstlightUI\Components;

use FirstlightUI\Components\Concerns\DecodesBladeAttributes;

class Modal extends \Native\Mobile\UI\Components\Modal
{
    use DecodesBladeAttributes;

    protected function elementType(): string
    {
        return 'firstlight.modal';
    }
}
