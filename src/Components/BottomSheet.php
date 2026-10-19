<?php

namespace FirstlightUI\Components;

use FirstlightUI\Components\Concerns\DecodesBladeAttributes;

class BottomSheet extends \Native\Mobile\UI\Components\BottomSheet
{
    use DecodesBladeAttributes;

    protected function elementType(): string
    {
        return 'firstlight.bottom-sheet';
    }
}
