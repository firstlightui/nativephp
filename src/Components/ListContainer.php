<?php

namespace FirstlightUI\Components;

use FirstlightUI\Components\Concerns\DecodesBladeAttributes;

class ListContainer extends \Native\Mobile\UI\Components\NativeList
{
    use DecodesBladeAttributes;

    protected function elementType(): string
    {
        return 'firstlight.list';
    }
}
